<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MposSalesH;
use App\Models\MposSalesD;
use App\Models\MposCust;
use App\Models\MposProduct;
use App\Models\MposGrpProduct;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomerProductExport;
use App\Models\MposOutlet;
use App\Traits\ResolvesOutletScope;
use Illuminate\Support\Facades\Auth;

class CustomerProductReportController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request)
    {
        // Default to this month if no dates provided
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        $customerId = $request->input('customer_id');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');

        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');
        if (empty($outletId) && $request->has('nid_outlet')) {
            $outletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        }
        $isCashierOnly = $this->isCashierOnlyUser();
        $outlets = $this->getAvailableOutlets();

        $sortCol = $request->input('sort_col', 'customer_name');
        $sortDir = $request->input('sort_dir', 'asc');

        // Valid columns to prevent SQL injection
        $validSortCols = ['customer_name', 'category_name', 'product_name', 'qty', 'total_penjualan', 'diskon', 'modal', 'laba', 'jml_transaksi'];
        if (!in_array($sortCol, $validSortCols)) {
            $sortCol = 'customer_name';
        }
        $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        // Get filter data for dropdowns, grouped by name to avoid duplicates across outlets
        $customersQuery = MposCust::select('cname')->whereNotNull('cname');
        if (!empty($outletId)) {
            $customersQuery->where('nid_outlet', $outletId);
        }
        $customers = $customersQuery->distinct()->orderBy('cname')->get();

        $categories = MposGrpProduct::select('cname')->whereNotNull('cname')->distinct()->orderBy('cname')->get();
        $productsQuery = MposProduct::select('cname')->whereNotNull('cname');
        if (!empty($outletId)) {
            $productsQuery->where('nid_outlet', $outletId);
        }
        if ($categoryId) {
            $productsQuery->whereHas('category', function($q) use ($categoryId) {
                if (is_array($categoryId)) {
                    $q->whereIn('cname', $categoryId);
                } else {
                    $q->where('cname', $categoryId);
                }
            });
        }
        $products = $productsQuery->distinct()->orderBy('cname')->get();

        $query = $this->buildReportQuery($startDate, $endDate, $customerId, $categoryId, $productId, $outletId);

        // Calculate Grand Totals (ignoring pagination)
        $summaryQuery = clone $query;
        $grandTotals = $summaryQuery->select(
            DB::raw('COUNT(DISTINCT mpos_sales_h.nid_customer) as total_customers'),
            DB::raw('SUM(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) as total_qty'),
            DB::raw('SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) as total_penjualan'),
            // Approximate discount portion per item. Since we are doing a grand total over the filtered set, this is a bit tricky.
            // A simplified way is to calculate total discount for the matching transactions, but that would overcount if only one product is filtered.
            // Using proportional discount: (item_subtotal / transaction_subtotal) * transaction_discount
            DB::raw('SUM(
                CASE 
                    WHEN mpos_sales_h.nsubtotal > 0 
                    THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount 
                    ELSE 0 
                END
            ) as total_diskon'),
            DB::raw('COUNT(DISTINCT mpos_sales_h.nid) as total_transaksi')
        )->first();

        // Calculate Modal & Laba
        $grandTotals->total_modal = 0; // HPP/Modal is not defined in current models
        $grandTotals->total_laba = $grandTotals->total_penjualan - $grandTotals->total_diskon - $grandTotals->total_modal;

        $isSummary = empty($categoryId) && empty($productId);

        if ($isSummary) {
            // Adjust sort col if it's not available in summary
            if (in_array($sortCol, ['category_name', 'product_name', 'qty'])) {
                $sortCol = 'customer_name';
            }
            $queryBuilder = $query->select(
                'mpos_cust.cname as customer_name',
                DB::raw('SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) as total_penjualan'),
                DB::raw('SUM(
                    CASE 
                        WHEN mpos_sales_h.nsubtotal > 0 
                        THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount 
                        ELSE 0 
                    END
                ) as diskon'),
                DB::raw('0 as modal'),
                DB::raw('(SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) - SUM(
                    CASE 
                        WHEN mpos_sales_h.nsubtotal > 0 
                        THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount 
                        ELSE 0 
                    END
                )) as laba'),
                DB::raw('COUNT(DISTINCT mpos_sales_h.nid) as jml_transaksi')
            )
            ->groupBy('mpos_cust.cname')
            ->orderBy($sortCol, $sortDir);
        } else {
            $queryBuilder = $query->select(
                'mpos_cust.cname as customer_name',
                'mpos_grp_product.cname as category_name',
                'mpos_product.cname as product_name',
                DB::raw('SUM(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) as qty'),
                DB::raw('SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) as total_penjualan'),
                DB::raw('SUM(
                    CASE 
                        WHEN mpos_sales_h.nsubtotal > 0 
                        THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount 
                        ELSE 0 
                    END
                ) as diskon'),
                DB::raw('0 as modal'),
                DB::raw('(SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) - SUM(
                    CASE 
                        WHEN mpos_sales_h.nsubtotal > 0 
                        THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount 
                        ELSE 0 
                    END
                )) as laba'),
                DB::raw('COUNT(DISTINCT mpos_sales_h.nid) as jml_transaksi')
            )
            ->groupBy('mpos_cust.cname', 'mpos_grp_product.cname', 'mpos_product.cname')
            ->orderBy($sortCol, $sortDir);
        }

        if ($request->has('print')) {
            $reports = $queryBuilder->get();
        } else {
            $reports = $queryBuilder->paginate(10)->withQueryString();
        }

        return view('reports.customer_product.index', compact(
            'reports', 
            'grandTotals', 
            'customers', 
            'categories', 
            'products',
            'startDate',
            'endDate',
            'customerId',
            'categoryId',
            'productId',
            'sortCol',
            'sortDir',
            'isSummary',
            'outletId',
            'outlets',
            'isCashierOnly'
        ));
    }

    public function getProductsByCategory(Request $request)
    {
        $categoryId = $request->input('category_id');
        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');
        if (empty($outletId) && $request->has('nid_outlet')) {
            $outletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        }

        $productsQuery = MposProduct::select('cname')->whereNotNull('cname');
        if (!empty($outletId)) {
            $productsQuery->where('nid_outlet', $outletId);
        }

        if (!empty($categoryId)) {
            $productsQuery->whereHas('category', function($q) use ($categoryId) {
                if (is_array($categoryId)) {
                    $q->whereIn('cname', $categoryId);
                } else {
                    $q->where('cname', $categoryId);
                }
            });
        }
        
        $products = $productsQuery->distinct()->orderBy('cname')->get();
        return response()->json($products);
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $customerId = $request->input('customer_id');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');

        $sortCol = $request->input('sort_col', 'customer_name');
        $sortDir = $request->input('sort_dir', 'asc');
        $validSortCols = ['customer_name', 'category_name', 'product_name', 'qty', 'total_penjualan', 'diskon', 'modal', 'laba', 'jml_transaksi'];
        if (!in_array($sortCol, $validSortCols)) {
            $sortCol = 'customer_name';
        }
        $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');
        if (empty($outletId) && $request->has('nid_outlet')) {
            $outletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        }

        $query = $this->buildReportQuery($startDate, $endDate, $customerId, $categoryId, $productId, $outletId);
        
        // Calculate Grand Totals
        $summaryQuery = clone $query;
        $grandTotalsObj = $summaryQuery->select(
            DB::raw('SUM(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) as total_qty'),
            DB::raw('SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) as total_penjualan'),
            DB::raw('SUM(
                CASE 
                    WHEN mpos_sales_h.nsubtotal > 0 
                    THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount 
                    ELSE 0 
                END
            ) as total_diskon'),
            DB::raw('COUNT(DISTINCT mpos_sales_h.nid) as total_transaksi')
        )->first();

        $grandTotals = [
            'qty' => $grandTotalsObj->total_qty ?? 0,
            'total_penjualan' => $grandTotalsObj->total_penjualan ?? 0,
            'diskon' => $grandTotalsObj->total_diskon ?? 0,
            'modal' => 0,
            'laba' => ($grandTotalsObj->total_penjualan ?? 0) - ($grandTotalsObj->total_diskon ?? 0) - 0,
            'jml_transaksi' => $grandTotalsObj->total_transaksi ?? 0
        ];

        // 1. Data Rangkuman per Pelanggan
        $summaryDataQuery = clone $query;
        $summaryData = $summaryDataQuery->select(
            'mpos_cust.cname as customer_name',
            DB::raw('SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) as total_penjualan'),
            DB::raw('SUM(
                CASE 
                    WHEN mpos_sales_h.nsubtotal > 0 
                    THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount 
                    ELSE 0 
                END
            ) as diskon'),
            DB::raw('COUNT(DISTINCT mpos_sales_h.nid) as jml_transaksi')
        )
        ->groupBy('mpos_cust.cname');

        $summarySortCol = $sortCol;
        if (in_array($sortCol, ['category_name', 'product_name', 'qty'])) {
            $summarySortCol = 'customer_name';
        }
        
        if ($summarySortCol === 'laba') {
             $summaryData->orderByRaw('(SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) - SUM(CASE WHEN mpos_sales_h.nsubtotal > 0 THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount ELSE 0 END)) ' . $sortDir);
        } else {
             $summaryData->orderBy($summarySortCol, $sortDir);
        }
        $summaryData = $summaryData->get();

        // 2. Data Detail Produk
        $detailDataQuery = clone $query;
        $detailData = $detailDataQuery->select(
            'mpos_cust.cname as customer_name',
            'mpos_grp_product.cname as category_name',
            'mpos_product.cname as product_name',
            DB::raw('SUM(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) as qty'),
            DB::raw('SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) as total_penjualan'),
            DB::raw('SUM(
                CASE 
                    WHEN mpos_sales_h.nsubtotal > 0 
                    THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount 
                    ELSE 0 
                END
            ) as diskon'),
            DB::raw('COUNT(DISTINCT mpos_sales_h.nid) as jml_transaksi')
        )
        ->groupBy('mpos_cust.cname', 'mpos_grp_product.cname', 'mpos_product.cname');

        if ($sortCol === 'laba') {
             $detailData->orderByRaw('(SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) - SUM(CASE WHEN mpos_sales_h.nsubtotal > 0 THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount ELSE 0 END)) ' . $sortDir);
        } else {
             $detailData->orderBy($sortCol, $sortDir);
        }
        $detailData = $detailData->get();

        return Excel::download(new CustomerProductExport($summaryData, $detailData, $grandTotals, $startDate, $endDate), 'laporan_pelanggan_produk.xlsx');
    }

    private function buildReportQuery($startDate, $endDate, $customerId, $categoryId, $productId, $outletId = null)
    {
        $query = DB::table('mpos_sales_d')
            ->join('mpos_sales_h', 'mpos_sales_d.nid_transaction', '=', 'mpos_sales_h.nid')
            ->join('mpos_cust', 'mpos_sales_h.nid_customer', '=', 'mpos_cust.nid')
            ->join('mpos_product', 'mpos_sales_d.nid_product', '=', 'mpos_product.nid')
            ->leftJoin('mpos_grp_product', 'mpos_product.nid_category', '=', 'mpos_grp_product.nid')
            ->where('mpos_sales_h.cstatus', MposSalesH::STATUS_PAID)
            // ensure valid items
            ->whereRaw('(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) > 0')
            ->whereDate('mpos_sales_h.dtransaction', '>=', $startDate)
            ->whereDate('mpos_sales_h.dtransaction', '<=', $endDate);

        if (!empty($outletId)) {
            $query->where('mpos_sales_h.nid_outlet', $outletId);
        }

        if ($customerId) {
            $query->where('mpos_cust.cname', $customerId);
        }

        if (!empty($categoryId)) {
            if (is_array($categoryId)) {
                $query->whereIn('mpos_grp_product.cname', $categoryId);
            } else {
                $query->where('mpos_grp_product.cname', $categoryId);
            }
        }

        if ($productId) {
            $query->where('mpos_product.cname', $productId);
        }

        return $query;
    }
}
