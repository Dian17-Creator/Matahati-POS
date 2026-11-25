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
        if (is_array($categoryId)) {
            $categoryId = array_values(array_filter($categoryId, fn($v) => !is_null($v) && $v !== ''));
            if (empty($categoryId)) {
                $categoryId = null;
            }
        }
        $productId = $request->input('product_id');

        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');
        $isCashierOnly = $this->isCashierOnlyUser();
        $isSuperAdmin = $this->isSuperAdminUser();
        $isOutletLocked = $this->isOutletLocked();
        $outlets = $this->getAvailableOutlets();
        $allowedOutletIds = $this->getUserAllowedOutletIds();

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
        } elseif (!$this->isSuperAdminUser()) {
            $customersQuery->whereIn('nid_outlet', $allowedOutletIds);
        }
        $customers = $customersQuery->distinct()->orderBy('cname')->get();

        // Resolve department scope for categories
        $user = Auth::user();
        $deptId = null;
        if (!empty($outletId)) {
            $currentOutlet = MposOutlet::find($outletId);
            $deptId = $currentOutlet?->nid_dept;
        }
        if (empty($deptId) && $user && !empty($user->niddept)) {
            $deptId = $user->niddept;
        }

        $targetOutletIds = [];
        if (!empty($deptId)) {
            $targetOutletIds = MposOutlet::where('nid_dept', $deptId)->pluck('nid')->map(fn($id) => (int)$id)->toArray();
        }

        if (empty($targetOutletIds)) {
            if (!empty($outletId)) {
                $targetOutletIds = [(int) $outletId];
            } elseif (!$this->isSuperAdminUser()) {
                $targetOutletIds = array_map('intval', $allowedOutletIds);
            }
        }

        $categoriesQuery = MposGrpProduct::select('cname')->whereNotNull('cname');
        if (!empty($targetOutletIds)) {
            $categoriesQuery->where(function($q) use ($targetOutletIds) {
                $q->whereIn('nid_outlet', $targetOutletIds)
                  ->orWhereHas('products', function($pq) use ($targetOutletIds) {
                      $pq->whereIn('nid_outlet', $targetOutletIds);
                  });
            });
        } elseif (!$this->isSuperAdminUser()) {
            $categoriesQuery->whereIn('nid_outlet', $allowedOutletIds);
        }
        $categories = $categoriesQuery->distinct()->orderBy('cname')->get();
        $productsQuery = MposProduct::select('cname')->whereNotNull('cname');
        if (!empty($outletId)) {
            $productsQuery->where('nid_outlet', $outletId);
        } elseif (!$this->isSuperAdminUser()) {
            $productsQuery->whereIn('nid_outlet', $allowedOutletIds);
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
            DB::raw('COUNT(DISTINCT nid_customer) as total_customers'),
            DB::raw('COALESCE(SUM(net_qty), 0) as total_qty'),
            DB::raw('COALESCE(SUM(item_penjualan), 0) as total_penjualan'),
            DB::raw('ROUND(COALESCE(SUM(item_diskon), 0), 2) as total_diskon'),
            DB::raw('COUNT(DISTINCT unique_trx_id) as total_transaksi')
        )->first();

        // Calculate Modal & Laba
        $grandTotals->total_modal = 0; // HPP/Modal is not defined in current models
        $grandTotals->total_penjualan = round((float) ($grandTotals->total_penjualan ?? 0), 2);
        $grandTotals->total_diskon = round((float) ($grandTotals->total_diskon ?? 0), 2);
        $grandTotals->total_laba = round($grandTotals->total_penjualan - $grandTotals->total_diskon - $grandTotals->total_modal, 2);

        $isSummary = empty($categoryId) && empty($productId);

        if ($isSummary) {
            // Adjust sort col if it's not available in summary
            if (in_array($sortCol, ['category_name', 'product_name', 'qty'])) {
                $sortCol = 'customer_name';
            }
            $queryBuilder = $query->select(
                'customer_name',
                DB::raw('ROUND(SUM(item_penjualan), 2) as total_penjualan'),
                DB::raw('ROUND(SUM(item_diskon), 2) as diskon'),
                DB::raw('0 as modal'),
                DB::raw('ROUND((SUM(item_penjualan) - SUM(item_diskon)), 2) as laba'),
                DB::raw('COUNT(DISTINCT unique_trx_id) as jml_transaksi')
            )
            ->groupBy('customer_name')
            ->orderBy($sortCol, $sortDir);
        } else {
            $queryBuilder = $query->select(
                'customer_name',
                'category_name',
                'product_name',
                DB::raw('SUM(net_qty) as qty'),
                DB::raw('ROUND(SUM(item_penjualan), 2) as total_penjualan'),
                DB::raw('ROUND(SUM(item_diskon), 2) as diskon'),
                DB::raw('0 as modal'),
                DB::raw('ROUND((SUM(item_penjualan) - SUM(item_diskon)), 2) as laba'),
                DB::raw('COUNT(DISTINCT unique_trx_id) as jml_transaksi')
            )
            ->groupBy('customer_name', 'category_name', 'product_name')
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
            'isCashierOnly',
            'isSuperAdmin',
            'isOutletLocked',
            'allowedOutletIds'
        ));
    }

    public function getProductsByCategory(Request $request)
    {
        $categoryId = $request->input('category_id');
        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');

        $user = Auth::user();
        $deptId = null;
        if (!empty($outletId)) {
            $currentOutlet = MposOutlet::find($outletId);
            $deptId = $currentOutlet?->nid_dept;
        }
        if (empty($deptId) && $user && !empty($user->niddept)) {
            $deptId = $user->niddept;
        }

        $targetOutletIds = [];
        if (!empty($deptId)) {
            $targetOutletIds = MposOutlet::where('nid_dept', $deptId)->pluck('nid')->map(fn($id) => (int)$id)->toArray();
        }

        if (empty($targetOutletIds)) {
            if (!empty($outletId)) {
                $targetOutletIds = [(int) $outletId];
            } elseif (!$this->isSuperAdminUser()) {
                $targetOutletIds = array_map('intval', $this->getUserAllowedOutletIds());
            }
        }

        $productsQuery = MposProduct::select('cname')->whereNotNull('cname');
        if (!empty($targetOutletIds)) {
            $productsQuery->whereIn('nid_outlet', $targetOutletIds);
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
        if (is_array($categoryId)) {
            $categoryId = array_values(array_filter($categoryId, fn($v) => !is_null($v) && $v !== ''));
            if (empty($categoryId)) {
                $categoryId = null;
            }
        }
        $productId = $request->input('product_id');

        $sortCol = $request->input('sort_col', 'customer_name');
        $sortDir = $request->input('sort_dir', 'asc');
        $validSortCols = ['customer_name', 'category_name', 'product_name', 'qty', 'total_penjualan', 'diskon', 'modal', 'laba', 'jml_transaksi'];
        if (!in_array($sortCol, $validSortCols)) {
            $sortCol = 'customer_name';
        }
        $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');

        $query = $this->buildReportQuery($startDate, $endDate, $customerId, $categoryId, $productId, $outletId);
        
        // Calculate Grand Totals
        $summaryQuery = clone $query;
        $grandTotalsObj = $summaryQuery->select(
            DB::raw('COALESCE(SUM(net_qty), 0) as total_qty'),
            DB::raw('COALESCE(SUM(item_penjualan), 0) as total_penjualan'),
            DB::raw('ROUND(COALESCE(SUM(item_diskon), 0), 2) as total_diskon'),
            DB::raw('COUNT(DISTINCT unique_trx_id) as total_transaksi')
        )->first();

        $totalPenjualan = round((float) ($grandTotalsObj->total_penjualan ?? 0), 2);
        $totalDiskon = round((float) ($grandTotalsObj->total_diskon ?? 0), 2);
        $totalModal = 0;
        $totalLaba = round($totalPenjualan - $totalDiskon - $totalModal, 2);

        $grandTotals = [
            'qty' => $grandTotalsObj->total_qty ?? 0,
            'total_penjualan' => $totalPenjualan,
            'diskon' => $totalDiskon,
            'modal' => $totalModal,
            'laba' => $totalLaba,
            'jml_transaksi' => $grandTotalsObj->total_transaksi ?? 0
        ];

        // 1. Data Rangkuman per Pelanggan
        $summaryDataQuery = clone $query;
        $summaryData = $summaryDataQuery->select(
            'customer_name',
            DB::raw('ROUND(SUM(item_penjualan), 2) as total_penjualan'),
            DB::raw('ROUND(SUM(item_diskon), 2) as diskon'),
            DB::raw('COUNT(DISTINCT unique_trx_id) as jml_transaksi')
        )
        ->groupBy('customer_name');

        $summarySortCol = $sortCol;
        if (in_array($sortCol, ['category_name', 'product_name', 'qty'])) {
            $summarySortCol = 'customer_name';
        }
        
        if ($summarySortCol === 'laba') {
             $summaryData->orderByRaw('(SUM(item_penjualan) - SUM(item_diskon)) ' . $sortDir);
        } else {
             $summaryData->orderBy($summarySortCol, $sortDir);
        }
        $summaryData = $summaryData->get();

        // 2. Data Detail Produk
        $detailDataQuery = clone $query;
        $detailData = $detailDataQuery->select(
            'customer_name',
            'category_name',
            'product_name',
            DB::raw('SUM(net_qty) as qty'),
            DB::raw('ROUND(SUM(item_penjualan), 2) as total_penjualan'),
            DB::raw('ROUND(SUM(item_diskon), 2) as diskon'),
            DB::raw('COUNT(DISTINCT unique_trx_id) as jml_transaksi')
        )
        ->groupBy('customer_name', 'category_name', 'product_name');

        if ($sortCol === 'laba') {
             $detailData->orderByRaw('(SUM(item_penjualan) - SUM(item_diskon)) ' . $sortDir);
        } else {
             $detailData->orderBy($sortCol, $sortDir);
        }
        $detailData = $detailData->get();

        return Excel::download(new CustomerProductExport($summaryData, $detailData, $grandTotals, $startDate, $endDate), 'laporan_pelanggan_produk.xlsx');
    }

    private function buildReportQuery($startDate, $endDate, $customerId, $categoryId, $productId, $outletId = null)
    {
        if (!empty($outletId) && (int) $outletId > 0) {
            $type = \App\Models\MposOutlet::resolveTransactionTypeForOutlet((int) $outletId);
            $sources = $type === \App\Models\MposOutlet::TRANSACTION_TYPE_OFFLINE
                ? [['mpos_sls_h', 'mpos_sls_d', 'OFF']]
                : [['mpos_sales_h', 'mpos_sales_d', 'ON']];
        } else {
            $sources = [
                ['mpos_sales_h', 'mpos_sales_d', 'ON'],
                ['mpos_sls_h', 'mpos_sls_d', 'OFF'],
            ];
        }

        $subQueries = [];
        foreach ($sources as [$headerTable, $detailTable, $idPrefix]) {
            $subQueries[] = $this->buildSingleSourceCustomerProductQuery(
                $headerTable,
                $detailTable,
                $idPrefix,
                $startDate,
                $endDate,
                $customerId,
                $categoryId,
                $productId,
                $outletId
            );
        }

        $combined = array_shift($subQueries);
        foreach ($subQueries as $sub) {
            $combined->unionAll($sub);
        }

        return DB::query()->fromSub($combined, 'combined_customer_products');
    }

    private function buildSingleSourceCustomerProductQuery(
        string $headerTable,
        string $detailTable,
        string $idPrefix,
        $startDate,
        $endDate,
        $customerId,
        $categoryId,
        $productId,
        $outletId = null
    ) {
        $customerNameExpr = "COALESCE(NULLIF(mpos_cust.cname, ''), NULLIF({$headerTable}.cname_customer, ''), 'Pelanggan Umum')";
        $productNameExpr = "COALESCE(mpos_product.cname, {$detailTable}.cname)";

        $query = DB::table($detailTable)
            ->join($headerTable, "{$detailTable}.nid_transaction", '=', "{$headerTable}.nid")
            ->leftJoin('mpos_cust', "{$headerTable}.nid_customer", '=', 'mpos_cust.nid')
            ->leftJoin('mpos_product', "{$detailTable}.nid_product", '=', 'mpos_product.nid')
            ->leftJoin('mpos_grp_product', 'mpos_product.nid_category', '=', 'mpos_grp_product.nid')
            ->where(function ($q) use ($headerTable) {
                $q->whereIn("{$headerTable}.cstatus", [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                ])->orWhere(function ($sub) use ($headerTable) {
                    $sub->whereNotIn("{$headerTable}.cstatus", [
                        MposSalesH::STATUS_CANCELLED,
                        MposSalesH::STATUS_DRAFT,
                        MposSalesH::STATUS_VOID,
                        MposSalesH::STATUS_REFUND,
                    ])->whereNotNull("{$headerTable}.nid_payment");
                });
            })
            ->whereRaw("({$detailTable}.nqty - {$detailTable}.nqty_void - {$detailTable}.nqty_refund) > 0")
            ->whereDate("{$headerTable}.dtransaction", '>=', $startDate)
            ->whereDate("{$headerTable}.dtransaction", '<=', $endDate);

        if (!empty($outletId)) {
            $query->where("{$headerTable}.nid_outlet", $outletId);
        } elseif (!$this->isSuperAdminUser()) {
            $query->whereIn("{$headerTable}.nid_outlet", $this->getUserAllowedOutletIds());
        }

        if ($customerId) {
            $query->whereRaw("{$customerNameExpr} = ?", [$customerId]);
        }

        if (!empty($categoryId)) {
            if (is_array($categoryId)) {
                $query->whereIn('mpos_grp_product.cname', $categoryId);
            } else {
                $query->where('mpos_grp_product.cname', $categoryId);
            }
        }

        if ($productId) {
            $query->whereRaw("{$productNameExpr} = ?", [$productId]);
        }

        $netQtyExpr = "({$detailTable}.nqty - {$detailTable}.nqty_void - {$detailTable}.nqty_refund)";

        return $query->select(
            DB::raw("CONCAT('{$idPrefix}-', {$headerTable}.nid) as unique_trx_id"),
            "{$headerTable}.nid_customer",
            DB::raw("{$customerNameExpr} as customer_name"),
            'mpos_grp_product.cname as category_name',
            DB::raw("{$productNameExpr} as product_name"),
            DB::raw("{$netQtyExpr} as net_qty"),
            DB::raw("({$netQtyExpr} * {$detailTable}.nprice) as item_penjualan"),
            DB::raw("CASE WHEN {$headerTable}.nsubtotal > 0 THEN (({$netQtyExpr} * {$detailTable}.nprice) / {$headerTable}.nsubtotal) * {$headerTable}.ndiscount ELSE 0 END as item_diskon")
        );
    }
}
