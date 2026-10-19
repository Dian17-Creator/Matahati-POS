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

        // 1. Total Penjualan Bersih (Net Sales) = SUM(PAID + COMPLETED) + SUM(Reversals Baru < 0)
        $salesQuery = MposSalesH::where(function ($q) {
                $q->whereIn('cstatus', [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                ])
                ->orWhereNotNull('nid_parent_transaction');
            })
            ->whereDate('dtransaction', '>=', $dateFrom)
            ->whereDate('dtransaction', '<=', $dateTo);

        if ($nidOutlet) {
            $salesQuery->where('nid_outlet', $nidOutlet);
        }

        $totalSales = max(0, (float) $salesQuery->sum('ngrandtotal'));

        // 2. Total Pembatalan / Refund (Besaran Reversal Baru + Transaksi Void Lama)
        $refundQuery = MposSalesH::whereIn('cstatus', [
                MposSalesH::STATUS_VOID,
                MposSalesH::STATUS_REFUND,
            ])
            ->whereDate('dtransaction', '>=', $dateFrom)
            ->whereDate('dtransaction', '<=', $dateTo);

        if ($nidOutlet) {
            $refundQuery->where('nid_outlet', $nidOutlet);
        }

        $totalRefund = abs((float) $refundQuery->sum('ngrandtotal'));

        // 3. Sales By Date (Per Hari)
        $dailySalesQuery = DB::table('mpos_sales_h')
            ->where(function ($q) {
                $q->whereIn('cstatus', [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                ])
                ->orWhereNotNull('nid_parent_transaction');
            })
            ->whereDate('dtransaction', '>=', $dateFrom)
            ->whereDate('dtransaction', '<=', $dateTo);

        if ($nidOutlet) {
            $dailySalesQuery->where('nid_outlet', $nidOutlet);
        }

        $dailyNet = $dailySalesQuery
            ->selectRaw('DATE(dtransaction) as date, SUM(ngrandtotal) as net_total')
            ->groupByRaw('DATE(dtransaction)')
            ->pluck('net_total', 'date');

        $salesByDate = [];
        $period = CarbonPeriod::create($dateFrom, $dateTo);
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $total = (float) ($dailyNet[$dateStr] ?? 0);
            $salesByDate[] = [
                'date' => $dateStr,
                'total' => max(0, $total)
            ];
        }

        // 4. Top Sales by Product Group
        $topProductGroupsQuery = DB::table('mpos_sales_d')
            ->join('mpos_sales_h', 'mpos_sales_d.nid_transaction', '=', 'mpos_sales_h.nid')
            ->join('mpos_product', 'mpos_sales_d.nid_product', '=', 'mpos_product.nid')
            ->join('mpos_grp_product', 'mpos_product.nid_category', '=', 'mpos_grp_product.nid')
            ->where(function ($q) {
                $q->whereIn('mpos_sales_h.cstatus', [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                ])
                ->orWhereNotNull('mpos_sales_h.nid_parent_transaction');
            })
            ->whereDate('mpos_sales_h.dtransaction', '>=', $dateFrom)
            ->whereDate('mpos_sales_h.dtransaction', '<=', $dateTo);

        if ($nidOutlet) {
            $topProductGroupsQuery->where('mpos_sales_h.nid_outlet', $nidOutlet);
        }

        $topProductGroups = $topProductGroupsQuery
            ->selectRaw('MIN(mpos_grp_product.nid) as nid')
            ->selectRaw('UPPER(TRIM(mpos_grp_product.cname)) as name')
            ->selectRaw('SUM(mpos_sales_d.nqty) as qty')
            ->selectRaw('SUM(mpos_sales_d.nsubtotal) as total')
            ->groupBy(DB::raw('UPPER(TRIM(mpos_grp_product.cname))'))
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
