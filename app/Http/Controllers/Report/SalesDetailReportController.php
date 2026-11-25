<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Exports\SalesDetailExport;
use App\Imports\OlseraSalesImport;
use App\Models\MposOutlet;
use App\Models\MposPayment;
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

class SalesDetailReportController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $endDateParsed = Carbon::parse($endDate)->endOfDay();
        
        $status = $request->input('status', '');
        $paymentId = $request->input('payment_id', '');
        $category = strtoupper(trim((string) $request->input('category', '')));
        if (!in_array($category, ['ONLINE', 'OFFLINE'], true)) {
            $category = '';
        }
        $sortCol = $request->input('sort_col', 'dtransaction');
        $sortDir = $request->input('sort_dir', 'desc');
        $validSortCols = [
            'dtransaction', 'cnotransaction', 'payment_name', 'display_status',
            'cashier_name', 'final_customer_name', 'gross_sales', 'total_void_refund',
            'ndiscount', 'ntax', 'nservice_charge', 'nrounding', 'net_sales',
            'total_cost', 'laba',
        ];
        if (!in_array($sortCol, $validSortCols, true)) {
            $sortCol = 'dtransaction';
        }
        $sortDir = strtolower((string) $sortDir) === 'asc' ? 'asc' : 'desc';

        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');
        $isCashierOnly = $this->isCashierOnlyUser();
        $isSuperAdmin = $this->isSuperAdminUser();
        $isOutletLocked = $this->isOutletLocked();
        $outlets = $this->getAvailableOutlets();
        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $payments = MposPayment::orderBy('cname')->get();

        $query = $this->buildTransactionsQuery($startDate, $endDateParsed, $status, $outletId, $paymentId, $sortCol, $sortDir, $category);
        $transactions = $query->paginate(10)->withQueryString();

        $summary = $this->buildSummaryQuery($startDate, $endDateParsed, $outletId, $status, $paymentId, $category)->first();
        $netSales = $summary->net_sales ?? 0;

        return view('reports.sales_detail.index', compact(
            'transactions',
            'startDate',
            'endDate',
            'status',
            'paymentId',
            'category',
            'payments',
            'outletId',
            'outlets',
            'isCashierOnly',
            'isSuperAdmin',
            'isOutletLocked',
            'allowedOutletIds',
            'summary',
            'netSales',
            'sortCol',
            'sortDir'
        ));
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $endDateParsed = Carbon::parse($endDate)->endOfDay();
        
        $status = $request->input('status', '');
        $paymentId = $request->input('payment_id', '');
        $category = strtoupper(trim((string) $request->input('category', '')));
        if (!in_array($category, ['ONLINE', 'OFFLINE'], true)) {
            $category = '';
        }
        $sortCol = $request->input('sort_col', 'dtransaction');
        $sortDir = $request->input('sort_dir', 'desc');
        $validSortCols = [
            'dtransaction', 'cnotransaction', 'payment_name', 'display_status',
            'cashier_name', 'final_customer_name', 'gross_sales', 'total_void_refund',
            'ndiscount', 'ntax', 'nservice_charge', 'nrounding', 'net_sales',
            'total_cost', 'laba',
        ];
        if (!in_array($sortCol, $validSortCols, true)) {
            $sortCol = 'dtransaction';
        }
        $sortDir = strtolower((string) $sortDir) === 'asc' ? 'asc' : 'desc';

        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');

        $outletName = 'Semua Outlet';
        if (!empty($outletId)) {
            $outlet = MposOutlet::find($outletId);
            if ($outlet) {
                $outletName = $outlet->cname;
            }
        }

        $transactions = $this->buildTransactionsQuery($startDate, $endDateParsed, $status, $outletId, $paymentId, $sortCol, $sortDir, $category)->get();
        $summary = $this->buildSummaryQuery($startDate, $endDateParsed, $outletId, $status, $paymentId, $category)->first();
        $dateSuffix = $startDate === $endDate
            ? Carbon::parse($startDate)->format('d-m-Y')
            : Carbon::parse($startDate)->format('d-m-Y') . '_sd_' . Carbon::parse($endDate)->format('d-m-Y');

        return Excel::download(new SalesDetailExport($transactions, $summary, $startDate, $endDate, $outletName), "laporan_penjualan_{$dateSuffix}.xlsx");
    }

    private function buildTransactionsQuery($startDate, $endDateParsed, $status, $outletId, $paymentId = '', string $sortCol = 'dtransaction', string $sortDir = 'desc', string $category = '')
    {
        $sources = match ($category) {
            'ONLINE' => [['mpos_sales_h', 'mpos_sales_d', 'ONLINE']],
            'OFFLINE' => [['mpos_sls_h', 'mpos_sls_d', 'OFFLINE']],
            default => [
                ['mpos_sales_h', 'mpos_sales_d', 'ONLINE'],
                ['mpos_sls_h', 'mpos_sls_d', 'OFFLINE'],
            ],
        };

        $subQueries = [];
        foreach ($sources as [$headerTable, $detailTable, $catLabel]) {
            $subQueries[] = $this->buildSingleSourceTransactionsQuery(
                $headerTable,
                $detailTable,
                $catLabel,
                $startDate,
                $endDateParsed,
                $status,
                $outletId,
                $paymentId
            );
        }

        $combined = array_shift($subQueries);
        foreach ($subQueries as $sub) {
            $combined->unionAll($sub);
        }

        $query = DB::query()->fromSub($combined, 'combined_sales');

        $validSortCols = [
            'dtransaction', 'cnotransaction', 'payment_name', 'display_status',
            'cashier_name', 'final_customer_name', 'gross_sales', 'total_void_refund',
            'ndiscount', 'ntax', 'nservice_charge', 'nrounding', 'net_sales',
            'total_cost', 'laba',
        ];
        $orderCol = in_array($sortCol, $validSortCols, true) ? $sortCol : 'dtransaction';
        $query->orderBy($orderCol, $sortDir);
        if ($orderCol !== 'dtransaction') {
            $query->orderBy('dtransaction', 'desc');
        }

        return $query;
    }

    private function buildSingleSourceTransactionsQuery(
        string $headerTable,
        string $detailTable,
        string $categoryLabel,
        $startDate,
        $endDateParsed,
        $status,
        $outletId,
        $paymentId = ''
    ) {
        $query = DB::table($headerTable)
            ->leftJoin('mpos_payment', "{$headerTable}.nid_payment", '=', 'mpos_payment.nid')
            ->leftJoin('mpos_payment as p_dual', "{$headerTable}.nid_payment_dual", '=', 'p_dual.nid')
            ->leftJoin('mpos_user', "{$headerTable}.nid_user", '=', 'mpos_user.nid')
            ->leftJoin('muser', 'mpos_user.nid_user', '=', 'muser.nid')
            ->leftJoin('mpos_cust', "{$headerTable}.nid_customer", '=', 'mpos_cust.nid')
            ->where("{$headerTable}.dtransaction", '>=', $startDate)
            ->where("{$headerTable}.dtransaction", '<=', $endDateParsed)
            ->whereNull("{$headerTable}.nid_parent_transaction");

        if (!empty($outletId)) {
            $query->where("{$headerTable}.nid_outlet", $outletId);
        } elseif (!$this->isSuperAdminUser()) {
            $query->whereIn("{$headerTable}.nid_outlet", $this->getUserAllowedOutletIds());
        }

        $itemDeductions = DB::table($detailTable)
            ->select(
                'nid_transaction',
                DB::raw('SUM((nqty_void + nqty_refund) * nprice) as item_void_refund'),
                DB::raw('SUM(ncost * (nqty - nqty_void - nqty_refund)) as total_cost'),
                DB::raw('MIN(ncost) as min_cost')
            )
            ->groupBy('nid_transaction');

        $reversals = DB::table($headerTable)
            ->select(
                'nid_parent_transaction',
                DB::raw('SUM(ABS(ngrandtotal)) as reversal_amount'),
                DB::raw("SUM(CASE WHEN cstatus = 'VOID' THEN ABS(ngrandtotal) ELSE 0 END) as reversal_void"),
                DB::raw("SUM(CASE WHEN cstatus = 'REFUND' THEN ABS(ngrandtotal) ELSE 0 END) as reversal_refund")
            )
            ->whereNotNull('nid_parent_transaction')
            ->groupBy('nid_parent_transaction');

        $query->leftJoinSub($itemDeductions, 'items', function ($join) use ($headerTable) {
            $join->on("{$headerTable}.nid", '=', 'items.nid_transaction');
        });

        $query->leftJoinSub($reversals, 'rev', function ($join) use ($headerTable) {
            $join->on("{$headerTable}.nid", '=', 'rev.nid_parent_transaction');
        });

        if (!empty($status)) {
            if ($status === 'VOID') {
                $query->where(function ($q) use ($headerTable) {
                    $q->where("{$headerTable}.cstatus", 'VOID')
                      ->orWhereRaw("COALESCE(rev.reversal_amount, 0) >= {$headerTable}.ngrandtotal AND {$headerTable}.ngrandtotal > 0");
                });
            } elseif ($status === 'REFUND') {
                $query->where(function ($q) use ($headerTable) {
                    $q->where("{$headerTable}.cstatus", 'REFUND')
                      ->orWhere('rev.reversal_refund', '>', 0);
                });
            } else {
                $query->where("{$headerTable}.cstatus", $status)
                      ->whereRaw("COALESCE(rev.reversal_amount, 0) < {$headerTable}.ngrandtotal");
            }
        }

        if (!empty($paymentId)) {
            $query->where(function ($q) use ($headerTable, $paymentId) {
                $q->where("{$headerTable}.nid_payment", $paymentId)
                  ->orWhere("{$headerTable}.nid_payment_dual", $paymentId);
            });
        }

        $grossSalesSql = "CASE WHEN {$headerTable}.csource = 'OLSERA' THEN {$headerTable}.nsubtotal ELSE {$headerTable}.ngrandtotal END";
        $netSalesSql = "CASE WHEN {$headerTable}.csource = 'OLSERA' THEN {$headerTable}.ngrandtotal WHEN {$headerTable}.cstatus IN ('VOID', 'CANCELLED', 'REFUND') OR (COALESCE(rev.reversal_amount, 0) >= {$headerTable}.ngrandtotal AND {$headerTable}.ngrandtotal > 0) THEN 0 ELSE GREATEST(0, {$headerTable}.ngrandtotal - GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0))) END";
        $totalCostSql = "CASE WHEN {$headerTable}.csource = 'OLSERA' THEN CASE WHEN {$headerTable}.ntotal_cost IS NOT NULL THEN {$headerTable}.ntotal_cost ELSE 0 END WHEN {$headerTable}.cstatus IN ('VOID', 'CANCELLED', 'REFUND') THEN 0 WHEN GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0)) >= {$headerTable}.ngrandtotal THEN 0 WHEN items.min_cost >= 0 THEN items.total_cost ELSE 0 END";
        $labaSql = "CASE WHEN {$headerTable}.csource = 'OLSERA' THEN COALESCE({$headerTable}.ntotal_profit, 0) WHEN {$headerTable}.cstatus IN ('VOID', 'CANCELLED', 'REFUND') OR (COALESCE(rev.reversal_amount, 0) >= {$headerTable}.ngrandtotal AND {$headerTable}.ngrandtotal > 0) THEN 0 ELSE GREATEST(0, {$headerTable}.ngrandtotal - GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0))) - (CASE WHEN items.min_cost >= 0 THEN items.total_cost ELSE 0 END) END";

        return $query->select(
            "{$headerTable}.nid",
            "{$headerTable}.cnotransaction",
            "{$headerTable}.dtransaction",
            "{$headerTable}.nsubtotal",
            "{$headerTable}.ndiscount",
            "{$headerTable}.ntax",
            "{$headerTable}.ngrandtotal",
            "{$headerTable}.nservice_charge",
            "{$headerTable}.nrounding",
            "{$headerTable}.nvisitor",
            "{$headerTable}.cstatus",
            "{$headerTable}.csource",
            "{$headerTable}.ntotal_cost",
            "{$headerTable}.ntotal_profit",
            DB::raw("'{$categoryLabel}' as ctransaction_category"),
            'mpos_payment.cname as payment_name',
            'p_dual.cname as payment_dual_name',
            'muser.cname as cashier_name',
            DB::raw("COALESCE(NULLIF(NULLIF(TRIM({$headerTable}.cname_customer), ''), 'Umum'), NULLIF(NULLIF(TRIM(mpos_cust.cname), ''), 'Umum'), '-') as final_customer_name"),
            DB::raw("CASE 
                WHEN {$headerTable}.csource = 'OLSERA' THEN 0 
                WHEN {$headerTable}.cstatus IN ('VOID', 'CANCELLED', 'REFUND') THEN ABS({$headerTable}.ngrandtotal)
                ELSE GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0)) 
            END as total_void_refund"),
            DB::raw("{$totalCostSql} as total_cost"),
            'items.min_cost',
            DB::raw("CASE 
                WHEN {$headerTable}.cstatus IN ('VOID', 'CANCELLED', 'REFUND') THEN {$headerTable}.cstatus
                WHEN COALESCE(rev.reversal_amount, 0) >= {$headerTable}.ngrandtotal AND {$headerTable}.ngrandtotal > 0 THEN 'VOID'
                ELSE {$headerTable}.cstatus 
            END as display_status"),
            DB::raw("{$grossSalesSql} as gross_sales"),
            DB::raw("{$netSalesSql} as net_sales"),
            DB::raw("{$labaSql} as laba")
        );
    }

    private function buildSummaryQuery($startDate, $endDateParsed, $outletId, $status = '', $paymentId = '', string $category = '')
    {
        $sources = match ($category) {
            'ONLINE' => [['mpos_sales_h', 'mpos_sales_d']],
            'OFFLINE' => [['mpos_sls_h', 'mpos_sls_d']],
            default => [
                ['mpos_sales_h', 'mpos_sales_d'],
                ['mpos_sls_h', 'mpos_sls_d'],
            ],
        };

        $subQueries = [];
        foreach ($sources as [$headerTable, $detailTable]) {
            $subQueries[] = $this->buildSingleSourceSummaryRowsQuery(
                $headerTable,
                $detailTable,
                $startDate,
                $endDateParsed,
                $outletId,
                $status,
                $paymentId
            );
        }

        $combined = array_shift($subQueries);
        foreach ($subQueries as $sub) {
            $combined->unionAll($sub);
        }

        return DB::query()
            ->fromSub($combined, 'summary_rows')
            ->select(
                DB::raw('COUNT(*) as total_transaksi'),
                DB::raw('COALESCE(SUM(gross_sales), 0) as gross_sales'),
                DB::raw('COALESCE(SUM(net_sales), 0) as net_sales'),
                DB::raw('COALESCE(SUM(total_void_refund), 0) as total_void_refund'),
                DB::raw('COALESCE(SUM(total_discount), 0) as total_discount'),
                DB::raw('COALESCE(SUM(total_tax), 0) as total_tax'),
                DB::raw('COALESCE(SUM(total_service), 0) as total_service'),
                DB::raw('COALESCE(SUM(total_rounding), 0) as total_rounding'),
                DB::raw('COALESCE(SUM(total_visitor), 0) as total_visitor'),
                DB::raw('COALESCE(SUM(valid_total_cost), 0) as valid_total_cost'),
                DB::raw('COALESCE(SUM(invalid_cost_count), 0) as invalid_cost_count'),
                DB::raw('COALESCE(SUM(valid_total_laba), 0) as valid_total_laba'),
                DB::raw('COALESCE(SUM(invalid_profit_count), 0) as invalid_profit_count')
            );
    }

    private function buildSingleSourceSummaryRowsQuery(
        string $headerTable,
        string $detailTable,
        $startDate,
        $endDateParsed,
        $outletId,
        $status = '',
        $paymentId = ''
    ) {
        $summaryQuery = DB::table($headerTable)
            ->where("{$headerTable}.dtransaction", '>=', $startDate)
            ->where("{$headerTable}.dtransaction", '<=', $endDateParsed)
            ->whereNull("{$headerTable}.nid_parent_transaction");

        if (!empty($outletId)) {
            $summaryQuery->where("{$headerTable}.nid_outlet", $outletId);
        } elseif (!$this->isSuperAdminUser()) {
            $summaryQuery->whereIn("{$headerTable}.nid_outlet", $this->getUserAllowedOutletIds());
        }

        if (!empty($paymentId)) {
            $summaryQuery->where(function ($q) use ($headerTable, $paymentId) {
                $q->where("{$headerTable}.nid_payment", $paymentId)
                  ->orWhere("{$headerTable}.nid_payment_dual", $paymentId);
            });
        }

        $itemDeductions = DB::table($detailTable)
            ->select(
                'nid_transaction',
                DB::raw('SUM((nqty_void + nqty_refund) * nprice) as item_void_refund'),
                DB::raw('SUM(ncost * (nqty - nqty_void - nqty_refund)) as total_cost'),
                DB::raw('MIN(ncost) as min_cost')
            )
            ->groupBy('nid_transaction');

        $reversals = DB::table($headerTable)
            ->select(
                'nid_parent_transaction',
                DB::raw('SUM(ABS(ngrandtotal)) as reversal_amount'),
                DB::raw("SUM(CASE WHEN cstatus = 'VOID' THEN ABS(ngrandtotal) ELSE 0 END) as reversal_void"),
                DB::raw("SUM(CASE WHEN cstatus = 'REFUND' THEN ABS(ngrandtotal) ELSE 0 END) as reversal_refund")
            )
            ->whereNotNull('nid_parent_transaction')
            ->groupBy('nid_parent_transaction');

        $summaryQuery->leftJoinSub($itemDeductions, 'items', function ($join) use ($headerTable) {
            $join->on("{$headerTable}.nid", '=', 'items.nid_transaction');
        });

        $summaryQuery->leftJoinSub($reversals, 'rev', function ($join) use ($headerTable) {
            $join->on("{$headerTable}.nid", '=', 'rev.nid_parent_transaction');
        });

        if (!empty($status)) {
            if ($status === 'VOID') {
                $summaryQuery->where(function ($q) use ($headerTable) {
                    $q->where("{$headerTable}.cstatus", 'VOID')
                      ->orWhereRaw("COALESCE(rev.reversal_amount, 0) >= {$headerTable}.ngrandtotal AND {$headerTable}.ngrandtotal > 0");
                });
            } elseif ($status === 'REFUND') {
                $summaryQuery->where(function ($q) use ($headerTable) {
                    $q->where("{$headerTable}.cstatus", 'REFUND')
                      ->orWhere('rev.reversal_refund', '>', 0);
                });
            } else {
                $summaryQuery->where("{$headerTable}.cstatus", $status)
                             ->whereRaw("COALESCE(rev.reversal_amount, 0) < {$headerTable}.ngrandtotal");
            }
        } else {
            $summaryQuery->where(function ($q) use ($headerTable) {
                $q->whereIn("{$headerTable}.cstatus", [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                    MposSalesH::STATUS_VOID,
                    MposSalesH::STATUS_REFUND,
                ])->orWhere(function ($sub) use ($headerTable) {
                    $sub->whereNotIn("{$headerTable}.cstatus", [
                        MposSalesH::STATUS_CANCELLED,
                        MposSalesH::STATUS_DRAFT,
                    ])->whereNotNull("{$headerTable}.nid_payment");
                });
            });
        }

        return $summaryQuery->select(
            DB::raw("CASE WHEN {$headerTable}.csource = 'OLSERA' THEN {$headerTable}.nsubtotal ELSE {$headerTable}.ngrandtotal END as gross_sales"),
            DB::raw("CASE 
                WHEN {$headerTable}.csource = 'OLSERA' THEN {$headerTable}.ngrandtotal 
                WHEN {$headerTable}.cstatus IN ('VOID', 'CANCELLED', 'REFUND') THEN 0
                ELSE ({$headerTable}.ngrandtotal - GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0))) 
            END as net_sales"),
            DB::raw("CASE 
                WHEN {$headerTable}.csource = 'OLSERA' THEN 0 
                WHEN {$headerTable}.cstatus IN ('VOID', 'CANCELLED', 'REFUND') THEN ABS({$headerTable}.ngrandtotal)
                ELSE GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0)) 
            END as total_void_refund"),
            DB::raw("{$headerTable}.ndiscount as total_discount"),
            DB::raw("{$headerTable}.ntax as total_tax"),
            DB::raw("{$headerTable}.nservice_charge as total_service"),
            DB::raw("{$headerTable}.nrounding as total_rounding"),
            DB::raw("{$headerTable}.nvisitor as total_visitor"),
            DB::raw("CASE 
                WHEN {$headerTable}.csource = 'OLSERA' THEN 
                    CASE WHEN {$headerTable}.ntotal_cost IS NOT NULL THEN {$headerTable}.ntotal_cost ELSE 0 END
                WHEN {$headerTable}.cstatus IN ('VOID', 'CANCELLED', 'REFUND') THEN 0
                WHEN GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0)) >= {$headerTable}.ngrandtotal THEN 0
                WHEN items.min_cost >= 0 THEN 
                    items.total_cost 
                ELSE 0 
            END as valid_total_cost"),
            DB::raw("CASE 
                WHEN {$headerTable}.csource = 'OLSERA' AND {$headerTable}.ntotal_cost IS NULL THEN 1
                WHEN ({$headerTable}.csource IS NULL OR {$headerTable}.csource != 'OLSERA') AND items.min_cost < 0 THEN 1
                ELSE 0 
            END as invalid_cost_count"),
            DB::raw("CASE 
                WHEN {$headerTable}.csource = 'OLSERA' THEN 
                    CASE WHEN {$headerTable}.ntotal_profit IS NOT NULL THEN {$headerTable}.ntotal_profit ELSE 0 END
                WHEN {$headerTable}.cstatus IN ('VOID', 'CANCELLED', 'REFUND') THEN 0
                WHEN GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0)) >= {$headerTable}.ngrandtotal THEN 0
                WHEN items.min_cost >= 0 THEN 
                    ({$headerTable}.ngrandtotal - GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0)) - COALESCE(items.total_cost, 0))
                ELSE 0 
            END as valid_total_laba"),
            DB::raw("CASE 
                WHEN {$headerTable}.csource = 'OLSERA' AND {$headerTable}.ntotal_profit IS NULL THEN 1
                WHEN ({$headerTable}.csource IS NULL OR {$headerTable}.csource != 'OLSERA') AND items.min_cost < 0 THEN 1
                ELSE 0 
            END as invalid_profit_count")
        );
    }

    /**
     * Import Transaksi Olsera (1-Step Direct Import)
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
            $importer = new OlseraSalesImport((int)$request->outlet_id, $mposUserId, false);
            Excel::import($importer, $request->file('file'));

            DB::commit();

            $saved = $importer->results['saved_count'];
            $dup = $importer->results['duplicate_skipped'];
            $conf = $importer->results['conflict_skipped'];
            $netFormatted = number_format($importer->results['valid_net_amount'], 0, ',', '.');

            $msg = "Import Transaksi Olsera Berhasil! Tersimpan {$saved} transaksi (Nett: Rp {$netFormatted}) untuk outlet {$outletName}.";
            if ($dup > 0 || $conf > 0) {
                $msg .= " Dilewati: {$dup} duplikat, {$conf} konflik data.";
            }

            return redirect()->route('reports.sales-detail')->with('success', $msg);

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('reports.sales-detail')->with('error', 'Gagal mengimpor transaksi: ' . $e->getMessage());
        }
    }
}
