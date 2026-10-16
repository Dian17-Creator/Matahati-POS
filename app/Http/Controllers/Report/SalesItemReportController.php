<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Exports\SalesItemExport;
use App\Imports\OlseraItemSalesImport;
use App\Models\MposOutlet;
use App\Models\MposProduct;
use App\Models\MposSalesH;
use App\Models\MposUser;
use App\Traits\ResolvesOutletScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SalesItemReportController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // If start date is after end date, synchronize them safely
        if ($startDate > $endDate) {
            $endDate = $startDate;
        }

        $endDateParsed = Carbon::parse($endDate)->endOfDay();

        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');
        $productId = $request->input('product_id', '');
        $search = trim($request->input('search', ''));

        $isCashierOnly = $this->isCashierOnlyUser();
        $isSuperAdmin = $this->isSuperAdminUser();
        $isOutletLocked = $this->isOutletLocked();
        $outlets = $this->getAvailableOutlets();
        $allowedOutletIds = $this->getUserAllowedOutletIds();

        $productsQuery = MposProduct::select('nid', 'cname')->whereNotNull('cname');
        if (!empty($outletId)) {
            $productsQuery->where('nid_outlet', $outletId);
        } elseif (!$this->isSuperAdminUser()) {
            $productsQuery->whereIn('nid_outlet', $allowedOutletIds);
        }
        $products = $productsQuery->distinct()->orderBy('cname')->get();

        $baseQuery = $this->buildBaseQuery($startDate, $endDateParsed, $outletId, $productId, $search);
        $summary = $this->buildSummaryQuery($baseQuery)->first();
        $items = $this->buildItemsQuery($baseQuery)->paginate(15)->withQueryString();

        return view('reports.sales_item.index', compact(
            'items',
            'startDate',
            'endDate',
            'outletId',
            'productId',
            'search',
            'outlets',
            'products',
            'isCashierOnly',
            'isSuperAdmin',
            'isOutletLocked',
            'allowedOutletIds',
            'summary'
        ));
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        if ($startDate > $endDate) {
            $endDate = $startDate;
        }

        $endDateParsed = Carbon::parse($endDate)->endOfDay();
        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');
        $productId = $request->input('product_id', '');
        $search = trim($request->input('search', ''));

        $outletName = 'Semua Outlet';
        if (!empty($outletId)) {
            $outlet = MposOutlet::find($outletId);
            if ($outlet) {
                $outletName = $outlet->cname;
            }
        }

        $baseQuery = $this->buildBaseQuery($startDate, $endDateParsed, $outletId, $productId, $search);
        $summary = $this->buildSummaryQuery($baseQuery)->first();
        $items = $this->buildItemsQuery($baseQuery)->get();
        $dateSuffix = $startDate === $endDate
            ? Carbon::parse($startDate)->format('d-m-Y')
            : Carbon::parse($startDate)->format('d-m-Y') . '_sd_' . Carbon::parse($endDate)->format('d-m-Y');

        return Excel::download(new SalesItemExport($items, $summary, $startDate, $endDate, $outletName), "laporan_item_penjualan_{$dateSuffix}.xlsx");
    }

    private function buildBaseQuery($startDate, $endDateParsed, $outletId, $productId, $search)
    {
        $baseQuery = DB::table('mpos_sales_d')
            ->join('mpos_sales_h', 'mpos_sales_d.nid_transaction', '=', 'mpos_sales_h.nid')
            ->leftJoin('mpos_cust', 'mpos_sales_h.nid_customer', '=', 'mpos_cust.nid')
            ->leftJoin('mpos_product', 'mpos_sales_d.nid_product', '=', 'mpos_product.nid')
            ->leftJoin('mpos_user', 'mpos_sales_h.nid_user', '=', 'mpos_user.nid')
            ->leftJoin('muser', 'mpos_user.nid_user', '=', 'muser.nid')
            ->leftJoin('mpos_outlet', 'mpos_sales_h.nid_outlet', '=', 'mpos_outlet.nid')
            ->where(function ($q) {
                $q->whereIn('mpos_sales_h.cstatus', [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                ])->orWhere(function ($sub) {
                    $sub->whereNotIn('mpos_sales_h.cstatus', [
                        MposSalesH::STATUS_CANCELLED,
                        MposSalesH::STATUS_DRAFT,
                        MposSalesH::STATUS_VOID,
                        MposSalesH::STATUS_REFUND,
                    ])->whereNotNull('mpos_sales_h.nid_payment');
                });
            })
            ->where('mpos_sales_h.dtransaction', '>=', $startDate)
            ->where('mpos_sales_h.dtransaction', '<=', $endDateParsed)
            ->whereRaw('(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) > 0');

        if (!empty($outletId)) {
            $baseQuery->where('mpos_sales_h.nid_outlet', $outletId);
        } elseif (!$this->isSuperAdminUser()) {
            $baseQuery->whereIn('mpos_sales_h.nid_outlet', $this->getUserAllowedOutletIds());
        }

        if (!empty($productId)) {
            $baseQuery->where('mpos_sales_d.nid_product', $productId);
        }

        if (!empty($search)) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('mpos_sales_h.cnotransaction', 'like', "%{$search}%")
                  ->orWhere('mpos_sales_d.cname', 'like', "%{$search}%")
                  ->orWhere('mpos_cust.cname', 'like', "%{$search}%")
                  ->orWhere('mpos_sales_h.cname_customer', 'like', "%{$search}%");
            });
        }

        return $baseQuery;
    }

    private function buildSummaryQuery($baseQuery)
    {
        $summaryQuery = clone $baseQuery;
        return $summaryQuery->select([
            DB::raw('COALESCE(SUM(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund), 0) as total_qty'),
            DB::raw('0 as total_discount'),
            DB::raw('COALESCE(SUM(
                ((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) - 
                CASE 
                    WHEN mpos_sales_h.nsubtotal > 0 
                    THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount 
                    ELSE 0 
                END
            ), 0) as total_penjualan'),
            DB::raw('COALESCE(SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.ncost), 0) as total_modal'),
            DB::raw('COALESCE(SUM(
                (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) - 
                CASE 
                    WHEN mpos_sales_h.nsubtotal > 0 
                    THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount 
                    ELSE 0 
                END) - 
                ((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.ncost)
            ), 0) as total_laba'),
        ]);
    }

    private function buildItemsQuery($baseQuery)
    {
        $itemsQuery = clone $baseQuery;
        $itemsQuery->select([
            'mpos_sales_d.nid as item_id',
            'mpos_sales_d.nid_product',
            'mpos_sales_d.cname as item_name',
            'mpos_sales_d.nprice',
            'mpos_sales_d.ncost',
            'mpos_sales_d.nqty',
            'mpos_sales_d.nqty_void',
            'mpos_sales_d.nqty_refund',
            'mpos_sales_h.nid as transaction_id',
            'mpos_sales_h.cnotransaction',
            'mpos_sales_h.dtransaction',
            'mpos_sales_h.csource',
            'mpos_sales_h.nsubtotal as transaction_subtotal',
            'mpos_sales_h.ndiscount as transaction_discount',
            'mpos_sales_h.cstatus',
            'muser.cname as cashier_name',
            DB::raw('COALESCE(mpos_sales_h.cname_customer, mpos_cust.cname, \'Umum\') as customer_name'),
            'mpos_outlet.cname as outlet_name',
        ]);

        $itemsQuery->orderBy('mpos_sales_h.dtransaction', 'desc')
                   ->orderBy('mpos_sales_d.nid', 'desc');

        return $itemsQuery;
    }

    /**
     * Import Detail Item Transaksi Olsera (1-Step Direct Import)
     */
    public function import(Request $request)
    {
        $request->validate([
            'outlet_id' => 'required|exists:mpos_outlet,nid',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $user = Auth::user();
        $mposUser = null;
        if ($user) {
            $mposUser = MposUser::where('nid_user', $user->id ?? $user->nid)->first();
            if ($mposUser && !$mposUser->fowner && !$mposUser->fspv && ($mposUser->fcashier || $mposUser->fcaptain)) {
                if ((int)$request->outlet_id !== (int)$mposUser->nid_outlet) {
                    return redirect()->back()->with('error', 'Anda hanya memiliki hak akses untuk mengimpor transaksi ke outlet Anda sendiri.');
                }
            }
        }

        $mposUserId = $mposUser?->nid 
            ?? MposUser::where('nid_outlet', $request->outlet_id)->first()?->nid 
            ?? MposUser::first()?->nid;

        DB::beginTransaction();
        try {
            $outletName = MposOutlet::find($request->outlet_id)?->cname ?? 'Outlet #' . $request->outlet_id;
            $importer = new OlseraItemSalesImport((int)$request->outlet_id, $mposUserId, false);
            Excel::import($importer, $request->file('file'));

            DB::commit();

            $savedOrders = $importer->results['saved_orders_count'];
            $savedItems = $importer->results['saved_items_count'];
            $headerNew = $importer->results['header_new_count'];
            $headerAttach = $importer->results['header_attach_count'];
            $dupOrders = $importer->results['duplicate_orders_count'];
            $unmatchedProducts = $importer->results['unmatched_products_count'];
            $confOrders = $importer->results['conflict_orders_count'];
            $invRows = $importer->results['invalid_rows_count'];
            $totalAmountFormatted = number_format($importer->results['valid_total_amount'], 0, ',', '.');

            $msg = "Import Detail Item Olsera Berhasil! Tersimpan {$savedItems} item ({$savedOrders} transaksi, Header baru: {$headerNew}, Header ditautkan: {$headerAttach}, Total: Rp {$totalAmountFormatted}) untuk outlet {$outletName}.";
            if ($dupOrders > 0 || $unmatchedProducts > 0 || $confOrders > 0 || $invRows > 0) {
                $msg .= " Dilewati: {$dupOrders} duplikat, {$unmatchedProducts} produk tidak cocok, {$confOrders} konflik, {$invRows} baris tidak valid.";
            }

            return redirect()->route('reports.sales-item')->with('success', $msg);

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('reports.sales-item')->with('error', 'Gagal mengimpor detail item transaksi: ' . $e->getMessage());
        }
    }
}
