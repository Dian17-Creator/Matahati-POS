<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MposSalesH;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;

class PosDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'required|date_format:Y-m-d',
            'date_to' => 'required|date_format:Y-m-d|after_or_equal:date_from',
            'nid_outlet' => 'nullable|integer'
        ]);

        $dateFrom = $validated['date_from'];
        $dateTo = $validated['date_to'];
        $nidOutlet = $validated['nid_outlet'] ?? null;

        // 1. Base Query untuk transaksi yang PAID
        $paidQuery = MposSalesH::where('cstatus', MposSalesH::STATUS_PAID)
            ->whereDate('dtransaction', '>=', $dateFrom)
            ->whereDate('dtransaction', '<=', $dateTo);

        if ($nidOutlet) {
            $paidQuery->where('nid_outlet', $nidOutlet);
        }

        // 2. Base Query untuk transaksi yang Full REFUND
        $refundQuery = MposSalesH::where('cstatus', MposSalesH::STATUS_REFUND)
            ->whereDate('dtransaction', '>=', $dateFrom)
            ->whereDate('dtransaction', '<=', $dateTo);

        if ($nidOutlet) {
            $refundQuery->where('nid_outlet', $nidOutlet);
        }

        $grossSalesPaid = (float) $paidQuery->sum('ngrandtotal');
        $totalFullRefund = (float) $refundQuery->sum('ngrandtotal');

        // 3. Menghitung partial Void & Refund dari item pada transaksi PAID
        $itemDeductionsQuery = DB::table('mpos_sales_d')
            ->join('mpos_sales_h', 'mpos_sales_d.nid_transaction', '=', 'mpos_sales_h.nid')
            ->where('mpos_sales_h.cstatus', MposSalesH::STATUS_PAID)
            ->whereDate('mpos_sales_h.dtransaction', '>=', $dateFrom)
            ->whereDate('mpos_sales_h.dtransaction', '<=', $dateTo);

        if ($nidOutlet) {
            $itemDeductionsQuery->where('mpos_sales_h.nid_outlet', $nidOutlet);
        }

        $itemDeductions = $itemDeductionsQuery->selectRaw('
            SUM(mpos_sales_d.nqty_void * mpos_sales_d.nprice) as total_void,
            SUM(mpos_sales_d.nqty_refund * mpos_sales_d.nprice) as total_refund
        ')->first();

        $partialVoid = (float) ($itemDeductions->total_void ?? 0);
        $partialRefund = (float) ($itemDeductions->total_refund ?? 0);

        // 4. Kalkulasi Total
        $totalSales = max(0, $grossSalesPaid - $partialVoid - $partialRefund);
        $totalRefund = $totalFullRefund + $partialRefund;

        // 5. Sales By Date
        $dailySalesQuery = DB::table('mpos_sales_h')
            ->where('cstatus', MposSalesH::STATUS_PAID)
            ->whereDate('dtransaction', '>=', $dateFrom)
            ->whereDate('dtransaction', '<=', $dateTo);
            
        if ($nidOutlet) {
            $dailySalesQuery->where('nid_outlet', $nidOutlet);
        }

        $dailyGross = $dailySalesQuery
            ->selectRaw('DATE(dtransaction) as date, SUM(ngrandtotal) as gross_total')
            ->groupByRaw('DATE(dtransaction)')
            ->pluck('gross_total', 'date');

        $dailyDeductionsQuery = DB::table('mpos_sales_d')
            ->join('mpos_sales_h', 'mpos_sales_d.nid_transaction', '=', 'mpos_sales_h.nid')
            ->where('mpos_sales_h.cstatus', MposSalesH::STATUS_PAID)
            ->whereDate('mpos_sales_h.dtransaction', '>=', $dateFrom)
            ->whereDate('mpos_sales_h.dtransaction', '<=', $dateTo);

        if ($nidOutlet) {
            $dailyDeductionsQuery->where('mpos_sales_h.nid_outlet', $nidOutlet);
        }

        $dailyDeductions = $dailyDeductionsQuery
            ->selectRaw('DATE(mpos_sales_h.dtransaction) as date')
            ->selectRaw('SUM( (mpos_sales_d.nqty_void + mpos_sales_d.nqty_refund) * mpos_sales_d.nprice ) as deduction_total')
            ->groupByRaw('DATE(mpos_sales_h.dtransaction)')
            ->pluck('deduction_total', 'date');

        $salesByDate = [];
        $period = CarbonPeriod::create($dateFrom, $dateTo);
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $gross = (float) ($dailyGross[$dateStr] ?? 0);
            $deduction = (float) ($dailyDeductions[$dateStr] ?? 0);
            $salesByDate[] = [
                'date' => $dateStr,
                'total' => max(0, $gross - $deduction)
            ];
        }

        // 6. Top Sales by Product Group
        $topProductGroupsQuery = DB::table('mpos_sales_d')
            ->join('mpos_sales_h', 'mpos_sales_d.nid_transaction', '=', 'mpos_sales_h.nid')
            ->join('mpos_product', 'mpos_sales_d.nid_product', '=', 'mpos_product.nid')
            ->join('mpos_grp_product', 'mpos_product.nid_category', '=', 'mpos_grp_product.nid')
            ->where('mpos_sales_h.cstatus', MposSalesH::STATUS_PAID)
            ->whereDate('mpos_sales_h.dtransaction', '>=', $dateFrom)
            ->whereDate('mpos_sales_h.dtransaction', '<=', $dateTo);

        if ($nidOutlet) {
            $topProductGroupsQuery->where('mpos_sales_h.nid_outlet', $nidOutlet);
        }

        $topProductGroups = $topProductGroupsQuery
            ->selectRaw('mpos_grp_product.nid')
            ->selectRaw('mpos_grp_product.cname as name')
            ->selectRaw('SUM(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) as qty')
            ->selectRaw('SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) as total')
            ->groupBy('mpos_grp_product.nid', 'mpos_grp_product.cname')
            ->havingRaw('qty > 0')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'nid' => (int) $item->nid,
                    'name' => $item->name,
                    'qty' => (int) $item->qty,
                    'total' => (float) $item->total,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Dashboard berhasil diambil',
            'data' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'total_sales' => $totalSales,
                'total_refund' => $totalRefund,
                'sales_by_date' => $salesByDate,
                'top_product_groups' => $topProductGroups
            ]
        ]);
    }
}
