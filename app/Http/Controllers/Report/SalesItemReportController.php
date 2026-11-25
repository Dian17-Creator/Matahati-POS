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
        $sortCol = $request->input('sort_col', 'dtransaction');
        $sortDir = $request->input('sort_dir', 'desc');
        $validSortCols = [
            'cnotransaction', 'dtransaction', 'cashier_name', 'item_name',
            'customer_name', 'net_qty', 'total_penjualan', 'total_modal', 'laba',
        ];
        if (!in_array($sortCol, $validSortCols, true)) {
            $sortCol = 'dtransaction';
        }
        $sortDir = strtolower((string) $sortDir) === 'asc' ? 'asc' : 'desc';

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
        $items = $this->buildItemsQuery($baseQuery, $sortCol, $sortDir)->paginate(15)->withQueryString();

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
            'summary',
            'sortCol',
            'sortDir'
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
        $sortCol = $request->input('sort_col', 'dtransaction');
        $sortDir = $request->input('sort_dir', 'desc');
        $validSortCols = [
            'cnotransaction', 'dtransaction', 'cashier_name', 'item_name',
            'customer_name', 'net_qty', 'total_penjualan', 'total_modal', 'laba',
        ];
        if (!in_array($sortCol, $validSortCols, true)) {
            $sortCol = 'dtransaction';
        }
        $sortDir = strtolower((string) $sortDir) === 'asc' ? 'asc' : 'desc';

        $outletName = 'Semua Outlet';
        if (!empty($outletId)) {
            $outlet = MposOutlet::find($outletId);
            if ($outlet) {
                $outletName = $outlet->cname;
            }
        }

        $baseQuery = $this->buildBaseQuery($startDate, $endDateParsed, $outletId, $productId, $search);
        $summary = $this->buildSummaryQuery($baseQuery)->first();
        $items = $this->buildItemsQuery($baseQuery, $sortCol, $sortDir)->get();
        $dateSuffix = $startDate === $endDate
            ? Carbon::parse($startDate)->format('d-m-Y')
            : Carbon::parse($startDate)->format('d-m-Y') . '_sd_' . Carbon::parse($endDate)->format('d-m-Y');

        return Excel::download(new SalesItemExport($items, $summary, $startDate, $endDate, $outletName), "laporan_item_penjualan_{$dateSuffix}.xlsx");
    }

    private function buildBaseQuery($startDate, $endDateParsed, $outletId, $productId, $search)
    {
        $subQueries = [];
        foreach ([
            ['mpos_sales_h', 'mpos_sales_d', 'ONLINE'],
            ['mpos_sls_h', 'mpos_sls_d', 'OFFLINE'],
        ] as [$headerTable, $detailTable, $catLabel]) {
            $subQueries[] = $this->buildSingleSourceItemQuery(
                $headerTable,
                $detailTable,
                $catLabel,
                $startDate,
                $endDateParsed,
                $outletId,
                $productId,
                $search
            );
        }

        $combined = $subQueries[0]->unionAll($subQueries[1]);

        return DB::query()->fromSub($combined, 'combined_items');
    }

    private function buildSingleSourceItemQuery(
        string $headerTable,
        string $detailTable,
        string $categoryLabel,
        $startDate,
        $endDateParsed,
        $outletId,
        $productId,
        $search
    ) {
        $baseQuery = DB::table($detailTable)
            ->join($headerTable, "{$detailTable}.nid_transaction", '=', "{$headerTable}.nid")
            ->leftJoin('mpos_cust', "{$headerTable}.nid_customer", '=', 'mpos_cust.nid')
            ->leftJoin('mpos_product', "{$detailTable}.nid_product", '=', 'mpos_product.nid')
            ->leftJoin('mpos_user', "{$headerTable}.nid_user", '=', 'mpos_user.nid')
            ->leftJoin('muser', 'mpos_user.nid_user', '=', 'muser.nid')
            ->leftJoin('mpos_outlet', "{$headerTable}.nid_outlet", '=', 'mpos_outlet.nid')
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
            ->where("{$headerTable}.dtransaction", '>=', $startDate)
            ->where("{$headerTable}.dtransaction", '<=', $endDateParsed)
            ->whereRaw("({$detailTable}.nqty - {$detailTable}.nqty_void - {$detailTable}.nqty_refund) > 0");

        if (!empty($outletId)) {
            $baseQuery->where("{$headerTable}.nid_outlet", $outletId);
        } elseif (!$this->isSuperAdminUser()) {
            $baseQuery->whereIn("{$headerTable}.nid_outlet", $this->getUserAllowedOutletIds());
        }

        if (!empty($productId)) {
            $baseQuery->where("{$detailTable}.nid_product", $productId);
        }

        if (!empty($search)) {
            $baseQuery->where(function ($q) use ($headerTable, $detailTable, $search) {
                $q->where("{$headerTable}.cnotransaction", 'like', "%{$search}%")
                  ->orWhere("{$detailTable}.cname", 'like', "%{$search}%")
                  ->orWhere('mpos_cust.cname', 'like', "%{$search}%")
                  ->orWhere("{$headerTable}.cname_customer", 'like', "%{$search}%");
            });
        }

        $netQtyExpr = "({$detailTable}.nqty - {$detailTable}.nqty_void - {$detailTable}.nqty_refund)";
        $totalPenjualanExpr = "GREATEST(0, ({$netQtyExpr} * {$detailTable}.nprice) - (CASE WHEN {$headerTable}.nsubtotal > 0 AND {$headerTable}.ndiscount > 0 THEN (({$netQtyExpr} * {$detailTable}.nprice) / {$headerTable}.nsubtotal) * {$headerTable}.ndiscount ELSE 0 END))";
        $totalModalExpr = "({$netQtyExpr} * {$detailTable}.ncost)";

        return $baseQuery->select([
            "{$detailTable}.nid as item_id",
            "{$detailTable}.nid_product",
            "{$detailTable}.cname as item_name",
            "{$detailTable}.nprice",
            "{$detailTable}.ncost",
            "{$detailTable}.nqty",
            "{$detailTable}.nqty_void",
            "{$detailTable}.nqty_refund",
            "{$headerTable}.nid as transaction_id",
            "{$headerTable}.cnotransaction",
            "{$headerTable}.dtransaction",
            "{$headerTable}.csource",
            "{$headerTable}.nsubtotal as transaction_subtotal",
            "{$headerTable}.ndiscount as transaction_discount",
            "{$headerTable}.cstatus",
            DB::raw("'{$categoryLabel}' as ctransaction_category"),
            'muser.cname as cashier_name',
            DB::raw("COALESCE(NULLIF(NULLIF(TRIM({$headerTable}.cname_customer), ''), 'Umum'), NULLIF(NULLIF(TRIM(mpos_cust.cname), ''), 'Umum'), '-') as customer_name"),
            'mpos_outlet.cname as outlet_name',
            DB::raw("{$netQtyExpr} as net_qty"),
            DB::raw("{$totalPenjualanExpr} as total_penjualan"),
            DB::raw("{$totalModalExpr} as total_modal"),
            DB::raw("({$totalPenjualanExpr} - {$totalModalExpr}) as laba"),
        ]);
    }

    private function buildSummaryQuery($baseQuery)
    {
        $summaryQuery = clone $baseQuery;

        return $summaryQuery->select([
            DB::raw('COALESCE(SUM(net_qty), 0) as total_qty'),
            DB::raw('0 as total_discount'),
            DB::raw('COALESCE(SUM(total_penjualan), 0) as total_penjualan'),
            DB::raw('COALESCE(SUM(total_modal), 0) as total_modal'),
            DB::raw('COALESCE(SUM(laba), 0) as total_laba'),
        ]);
    }

    private function buildItemsQuery($baseQuery, string $sortCol = 'dtransaction', string $sortDir = 'desc')
    {
        $itemsQuery = clone $baseQuery;
        $itemsQuery->select('*');

        $validSortCols = [
            'cnotransaction', 'dtransaction', 'cashier_name', 'item_name',
            'customer_name', 'net_qty', 'total_penjualan', 'total_modal', 'laba',
        ];
        $orderCol = in_array($sortCol, $validSortCols, true) ? $sortCol : 'dtransaction';

        $itemsQuery->orderBy($orderCol, $sortDir);
        if ($orderCol !== 'dtransaction') {
            $itemsQuery->orderBy('dtransaction', 'desc');
        }
        $itemsQuery->orderBy('item_id', 'desc');

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
