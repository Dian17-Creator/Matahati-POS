<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Exports\SalesDetailExport;
use App\Imports\OlseraSalesImport;
use App\Models\MposOutlet;
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
        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');
        $isCashierOnly = $this->isCashierOnlyUser();
        $isSuperAdmin = $this->isSuperAdminUser();
        $isOutletLocked = $this->isOutletLocked();
        $outlets = $this->getAvailableOutlets();
        $allowedOutletIds = $this->getUserAllowedOutletIds();

        $query = $this->buildTransactionsQuery($startDate, $endDateParsed, $status, $outletId);
        $transactions = $query->paginate(10)->withQueryString();

        $summary = $this->buildSummaryQuery($startDate, $endDateParsed, $outletId, $status)->first();
        $netSales = $summary->net_sales ?? 0;

        return view('reports.sales_detail.index', compact(
            'transactions',
            'startDate',
            'endDate',
            'status',
            'outletId',
            'outlets',
            'isCashierOnly',
            'isSuperAdmin',
            'isOutletLocked',
            'allowedOutletIds',
            'summary',
            'netSales'
        ));
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $endDateParsed = Carbon::parse($endDate)->endOfDay();
        
        $status = $request->input('status', '');
        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');

        $outletName = 'Semua Outlet';
        if (!empty($outletId)) {
            $outlet = MposOutlet::find($outletId);
            if ($outlet) {
                $outletName = $outlet->cname;
            }
        }

        $transactions = $this->buildTransactionsQuery($startDate, $endDateParsed, $status, $outletId)->get();
        $summary = $this->buildSummaryQuery($startDate, $endDateParsed, $outletId, $status)->first();
        $dateSuffix = $startDate === $endDate
            ? Carbon::parse($startDate)->format('d-m-Y')
            : Carbon::parse($startDate)->format('d-m-Y') . '_sd_' . Carbon::parse($endDate)->format('d-m-Y');

        return Excel::download(new SalesDetailExport($transactions, $summary, $startDate, $endDate, $outletName), "laporan_penjualan_{$dateSuffix}.xlsx");
    }

    private function buildTransactionsQuery($startDate, $endDateParsed, $status, $outletId)
    {
        $query = DB::table('mpos_sales_h')
            ->leftJoin('mpos_payment', 'mpos_sales_h.nid_payment', '=', 'mpos_payment.nid')
            ->leftJoin('mpos_user', 'mpos_sales_h.nid_user', '=', 'mpos_user.nid')
            ->leftJoin('muser', 'mpos_user.nid_user', '=', 'muser.nid')
            ->leftJoin('mpos_cust', 'mpos_sales_h.nid_customer', '=', 'mpos_cust.nid')
            ->where('mpos_sales_h.dtransaction', '>=', $startDate)
            ->where('mpos_sales_h.dtransaction', '<=', $endDateParsed);

        if (!empty($status)) {
            $query->where('mpos_sales_h.cstatus', $status);
        }

        if (!empty($outletId)) {
            $query->where('mpos_sales_h.nid_outlet', $outletId);
        } elseif (!$this->isSuperAdminUser()) {
            $query->whereIn('mpos_sales_h.nid_outlet', $this->getUserAllowedOutletIds());
        }

        $itemDeductions = DB::table('mpos_sales_d')
            ->select('nid_transaction', 
                DB::raw('SUM((nqty_void + nqty_refund) * nprice) as total_void_refund'),
                DB::raw('SUM(ncost * (nqty - nqty_void - nqty_refund)) as total_cost'),
                DB::raw('MIN(ncost) as min_cost')
            )
            ->groupBy('nid_transaction');

        $query->leftJoinSub($itemDeductions, 'items', function ($join) {
            $join->on('mpos_sales_h.nid', '=', 'items.nid_transaction');
        });

        $query->select(
            'mpos_sales_h.*',
            'mpos_payment.cname as payment_name',
            'muser.cname as cashier_name',
            DB::raw('COALESCE(mpos_sales_h.cname_customer, mpos_cust.cname, \'Umum\') as final_customer_name'),
            DB::raw('COALESCE(items.total_void_refund, 0) as total_void_refund'),
            DB::raw('COALESCE(items.total_cost, 0) as total_cost'),
            'items.min_cost'
        );

        $query->orderBy('mpos_sales_h.dtransaction', 'desc');

        return $query;
    }

    private function buildSummaryQuery($startDate, $endDateParsed, $outletId, $status = '')
    {
        $summaryQuery = DB::table('mpos_sales_h')
            ->where('mpos_sales_h.dtransaction', '>=', $startDate)
            ->where('mpos_sales_h.dtransaction', '<=', $endDateParsed);

        if (!empty($status)) {
            $summaryQuery->where('mpos_sales_h.cstatus', $status);
        } else {
            $summaryQuery->where(function ($q) {
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
            });
        }

        if (!empty($outletId)) {
            $summaryQuery->where('mpos_sales_h.nid_outlet', $outletId);
        } elseif (!$this->isSuperAdminUser()) {
            $summaryQuery->whereIn('mpos_sales_h.nid_outlet', $this->getUserAllowedOutletIds());
        }

        $itemDeductions = DB::table('mpos_sales_d')
            ->select('nid_transaction', 
                DB::raw('SUM((nqty_void + nqty_refund) * nprice) as total_void_refund'),
                DB::raw('SUM(ncost * (nqty - nqty_void - nqty_refund)) as total_cost'),
                DB::raw('MIN(ncost) as min_cost')
            )
            ->groupBy('nid_transaction');

        $summaryQuery->leftJoinSub($itemDeductions, 'items', function ($join) {
            $join->on('mpos_sales_h.nid', '=', 'items.nid_transaction');
        });

        return $summaryQuery->select(
            DB::raw('COUNT(mpos_sales_h.nid) as total_transaksi'),
            DB::raw("SUM(CASE WHEN mpos_sales_h.csource = 'OLSERA' THEN mpos_sales_h.nsubtotal ELSE mpos_sales_h.ngrandtotal END) as gross_sales"),
            DB::raw("SUM(CASE WHEN mpos_sales_h.csource = 'OLSERA' THEN mpos_sales_h.ngrandtotal ELSE (mpos_sales_h.ngrandtotal - COALESCE(items.total_void_refund, 0)) END) as net_sales"),
            DB::raw("SUM(CASE WHEN mpos_sales_h.csource = 'OLSERA' THEN 0 ELSE COALESCE(items.total_void_refund, 0) END) as total_void_refund"),
            DB::raw('SUM(mpos_sales_h.ndiscount) as total_discount'),
            DB::raw('SUM(mpos_sales_h.ntax) as total_tax'),
            DB::raw('SUM(mpos_sales_h.nservice_charge) as total_service'),
            DB::raw('SUM(mpos_sales_h.nrounding) as total_rounding'),
            DB::raw('SUM(mpos_sales_h.nvisitor) as total_visitor'),
            DB::raw("SUM(CASE 
                WHEN mpos_sales_h.csource = 'OLSERA' THEN 
                    CASE WHEN mpos_sales_h.ntotal_cost IS NOT NULL THEN mpos_sales_h.ntotal_cost ELSE 0 END
                WHEN items.min_cost >= 0 THEN 
                    items.total_cost 
                ELSE 0 
            END) as valid_total_cost"),
            DB::raw("SUM(CASE 
                WHEN mpos_sales_h.csource = 'OLSERA' AND mpos_sales_h.ntotal_cost IS NULL THEN 1
                WHEN (mpos_sales_h.csource IS NULL OR mpos_sales_h.csource != 'OLSERA') AND items.min_cost < 0 THEN 1
                ELSE 0 
            END) as invalid_cost_count"),
            DB::raw("SUM(CASE 
                WHEN mpos_sales_h.csource = 'OLSERA' THEN 
                    CASE WHEN mpos_sales_h.ntotal_profit IS NOT NULL THEN mpos_sales_h.ntotal_profit ELSE 0 END
                WHEN items.min_cost >= 0 THEN 
                    (mpos_sales_h.ngrandtotal - COALESCE(items.total_void_refund, 0) - COALESCE(items.total_cost, 0))
                ELSE 0 
            END) as valid_total_laba"),
            DB::raw("SUM(CASE 
                WHEN mpos_sales_h.csource = 'OLSERA' AND mpos_sales_h.ntotal_profit IS NULL THEN 1
                WHEN (mpos_sales_h.csource IS NULL OR mpos_sales_h.csource != 'OLSERA') AND items.min_cost < 0 THEN 1
                ELSE 0 
            END) as invalid_profit_count")
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
            if ($mposUser && !$mposUser->fowner && !$mposUser->fcaptain && $mposUser->fcashier) {
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
