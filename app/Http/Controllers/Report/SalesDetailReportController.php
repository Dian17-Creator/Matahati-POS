<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\MposSalesH;
use App\Models\MposOutlet;
use App\Models\MposUser;
use Carbon\Carbon;

class SalesDetailReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $endDateParsed = Carbon::parse($endDate)->endOfDay();
        
        $status = $request->input('status', '');
        $outletId = $request->input('outlet_id', '');

        // Handle authorization for outlet
        $user = Auth::user();
        $mposUser = null;
        if ($user) {
            $mposUser = MposUser::where('nid_user', $user->id ?? $user->nid)->first();
        }
        
        // If cashier and not owner/captain, force their outlet
        $isCashierOnly = $mposUser && !$mposUser->fowner && !$mposUser->fcaptain && $mposUser->fcashier;
        
        if ($isCashierOnly) {
            $outletId = $mposUser->nid_outlet;
        }

        $outlets = MposOutlet::orderBy('cname')->get();

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
        }

        // Subquery for Modal & Valid Void/Refund per transaction
        // To avoid N+1 and complex looping in blade.
        $itemDeductions = DB::table('mpos_sales_d')
            ->select('nid_transaction', 
                DB::raw('SUM((nqty_void + nqty_refund) * nprice) as total_void_refund'),
                DB::raw('SUM(ncost * (nqty - nqty_void - nqty_refund)) as total_cost'),
                DB::raw('MIN(ncost) as min_cost') // if < 0, cost is invalid
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

        $transactions = $query->paginate(15)->withQueryString();

        // Calculate Summary for PAID transactions only
        $summaryQuery = DB::table('mpos_sales_h')
            ->where('mpos_sales_h.dtransaction', '>=', $startDate)
            ->where('mpos_sales_h.dtransaction', '<=', $endDateParsed)
            ->where('mpos_sales_h.cstatus', MposSalesH::STATUS_PAID);

        if (!empty($outletId)) {
            $summaryQuery->where('mpos_sales_h.nid_outlet', $outletId);
        }

        $summaryQuery->leftJoinSub($itemDeductions, 'items', function ($join) {
            $join->on('mpos_sales_h.nid', '=', 'items.nid_transaction');
        });

        // We only sum costs and profits if min_cost >= 0
        $summary = $summaryQuery->select(
            DB::raw('SUM(mpos_sales_h.ngrandtotal) as gross_sales'),
            DB::raw('SUM(COALESCE(items.total_void_refund, 0)) as total_void_refund'),
            DB::raw('SUM(mpos_sales_h.ndiscount) as total_discount'),
            DB::raw('SUM(mpos_sales_h.ntax) as total_tax'),
            DB::raw('SUM(mpos_sales_h.nservice_charge) as total_service'),
            DB::raw('SUM(mpos_sales_h.nrounding) as total_rounding'),
            DB::raw('SUM(mpos_sales_h.nvisitor) as total_visitor'),
            DB::raw('SUM(CASE WHEN items.min_cost >= 0 THEN items.total_cost ELSE 0 END) as valid_total_cost'),
            DB::raw('SUM(CASE WHEN items.min_cost >= 0 THEN (mpos_sales_h.ngrandtotal - COALESCE(items.total_void_refund, 0) - COALESCE(items.total_cost, 0)) ELSE 0 END) as valid_total_laba'),
            DB::raw('SUM(CASE WHEN items.min_cost < 0 THEN 1 ELSE 0 END) as invalid_cost_count')
        )->first();

        $netSales = max(0, ($summary->gross_sales ?? 0) - ($summary->total_void_refund ?? 0));

        return view('reports.sales_detail.index', compact(
            'transactions',
            'startDate',
            'endDate',
            'status',
            'outletId',
            'outlets',
            'isCashierOnly',
            'summary',
            'netSales'
        ));
    }
}
