<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MposSalesH;
use App\Models\MposSalesoffH;
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
            'nid_outlet' => 'nullable|integer',
            'outlet_id' => 'nullable|integer',
            'ctransaction_category' => 'nullable|string',
            'category' => 'nullable|string',
        ]);

        $dateFrom = $validated['date_from'];
        $dateTo = $validated['date_to'];
        $nidOutlet = $validated['nid_outlet'] ?? $validated['outlet_id'] ?? $request->input('nid_outlet') ?? $request->input('outlet_id');

        $categoryFilter = strtoupper(trim((string) ($request->input('ctransaction_category') ?? $request->input('category') ?? 'OFFLINE')));
        $isOffline = ($categoryFilter === 'OFFLINE');

        $headerModelClass = $isOffline ? MposSalesoffH::class : MposSalesH::class;
        $headerTable = $isOffline ? 'mpos_sls_h' : 'mpos_sales_h';
        $detailTable = $isOffline ? 'mpos_sls_d' : 'mpos_sales_d';

        // 1. Total Penjualan Bersih (Net Sales) = SUM(PAID + COMPLETED) + SUM(Reversals Baru < 0)
        $salesQuery = $headerModelClass::where(function ($q) {
                $q->whereIn('cstatus', [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                ])
                ->orWhereNotNull('nid_parent_transaction');
            })
            ->whereDate(DB::raw('COALESCE(dpayment_date, dtransaction)'), '>=', $dateFrom)
            ->whereDate(DB::raw('COALESCE(dpayment_date, dtransaction)'), '<=', $dateTo);

        if ($nidOutlet) {
            $salesQuery->where('nid_outlet', $nidOutlet);
        }

        $totalSales = max(0, (float) $salesQuery->sum('ngrandtotal'));

        // 2. Total Pembatalan / Refund (Besaran Reversal Baru + Transaksi Void Lama)
        $refundQuery = $headerModelClass::whereIn('cstatus', [
                MposSalesH::STATUS_VOID,
                MposSalesH::STATUS_REFUND,
            ])
            ->whereDate(DB::raw('COALESCE(dpayment_date, dtransaction)'), '>=', $dateFrom)
            ->whereDate(DB::raw('COALESCE(dpayment_date, dtransaction)'), '<=', $dateTo);

        if ($nidOutlet) {
            $refundQuery->where('nid_outlet', $nidOutlet);
        }

        $totalRefund = abs((float) $refundQuery->sum('ngrandtotal'));

        // 3. Sales By Date (Per Hari)
        $dailySalesQuery = DB::table($headerTable)
            ->where(function ($q) {
                $q->whereIn('cstatus', [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                ])
                ->orWhereNotNull('nid_parent_transaction');
            })
            ->whereDate(DB::raw('COALESCE(dpayment_date, dtransaction)'), '>=', $dateFrom)
            ->whereDate(DB::raw('COALESCE(dpayment_date, dtransaction)'), '<=', $dateTo);

        if ($nidOutlet) {
            $dailySalesQuery->where('nid_outlet', $nidOutlet);
        }

        $dailyNet = $dailySalesQuery
            ->selectRaw('DATE(COALESCE(dpayment_date, dtransaction)) as date, SUM(ngrandtotal) as net_total')
            ->groupByRaw('DATE(COALESCE(dpayment_date, dtransaction))')
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
        $topProductGroupsQuery = DB::table($detailTable)
            ->join($headerTable, "{$detailTable}.nid_transaction", '=', "{$headerTable}.nid")
            ->join('mpos_product', "{$detailTable}.nid_product", '=', 'mpos_product.nid')
            ->join('mpos_grp_product', 'mpos_product.nid_category', '=', 'mpos_grp_product.nid')
            ->where(function ($q) use ($headerTable) {
                $q->whereIn("{$headerTable}.cstatus", [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                ])
                ->orWhereNotNull("{$headerTable}.nid_parent_transaction");
            })
            ->whereDate(DB::raw("COALESCE({$headerTable}.dpayment_date, {$headerTable}.dtransaction)"), '>=', $dateFrom)
            ->whereDate(DB::raw("COALESCE({$headerTable}.dpayment_date, {$headerTable}.dtransaction)"), '<=', $dateTo);

        if ($nidOutlet) {
            $topProductGroupsQuery->where("{$headerTable}.nid_outlet", $nidOutlet);
        }

        $topProductGroups = $topProductGroupsQuery
            ->selectRaw('MIN(mpos_grp_product.nid) as nid')
            ->selectRaw('UPPER(TRIM(mpos_grp_product.cname)) as name')
            ->selectRaw("SUM({$detailTable}.nqty) as qty")
            ->selectRaw("SUM({$detailTable}.nsubtotal) as total")
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
