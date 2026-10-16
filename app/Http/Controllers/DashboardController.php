<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MposSalesH;
use App\Models\MposCashMovement;
use App\Models\MposCust;
use App\Models\MposUser;
use App\Traits\ResolvesOutletScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request)
    {
        // 1. Date Range Handling (Default Hari Ini)
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $thisMonthStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $thisMonthEnd = Carbon::now()->endOfMonth()->format('Y-m-d');
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
        $sevenDaysAgo = Carbon::now()->subDays(6)->format('Y-m-d');

        $defaultStartDate = $today;
        $defaultEndDate = $today;

        $startDate = $request->input('start_date', $defaultStartDate);
        $endDate = $request->input('end_date', $defaultEndDate);

        // If start date is after end date, swap them safely
        if ($startDate > $endDate) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        $endDateParsed = Carbon::parse($endDate)->endOfDay();

        // 2. Outlet Scope Resolution
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $isCashierOnly = $this->isCashierOnlyUser();
        $isSuperAdmin = $this->isSuperAdminUser();
        $isOutletLocked = $this->isOutletLocked();
        $outlets = $this->getAvailableOutlets();
        $allowedOutletIds = $this->getUserAllowedOutletIds();

        $activeOutletName = 'Semua Outlet';
        if (!empty($selectedOutletId)) {
            $matched = collect($outlets)->firstWhere('nid', $selectedOutletId);
            if (!$matched) {
                $matched = \App\Models\MposOutlet::find($selectedOutletId);
            }
            $activeOutletName = $matched ? $matched->cname : ('Outlet #' . $selectedOutletId);
        } else {
            $activeOutletName = 'Semua Outlet';
        }

        // 3. Date Label formatting (matching Olsera pill format)
        if ($startDate === $today && $endDate === $today) {
            $dateLabel = 'Hari Ini (' . Carbon::parse($today)->format('d M Y') . ')';
        } elseif ($startDate === $yesterday && $endDate === $yesterday) {
            $dateLabel = 'Kemarin (' . Carbon::parse($yesterday)->format('d M Y') . ')';
        } elseif ($startDate === $sevenDaysAgo && $endDate === $today) {
            $dateLabel = '7 Hari yang lalu ' . Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y');
        } elseif ($startDate === $thisMonthStart && $endDate === $thisMonthEnd) {
            $dateLabel = 'Bulan Ini (' . Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y') . ')';
        } elseif ($startDate === $lastMonthStart && $endDate === $lastMonthEnd) {
            $dateLabel = 'Bulan Lalu (' . Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y') . ')';
        } else {
            $dateLabel = Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y');
        }

        // 4. Metric 1: Pelanggan Baru
        $newCustomersCount = 0;
        if (Schema::hasColumn('mpos_cust', 'dcreated')) {
            $custQuery = DB::table('mpos_cust')
                ->where('dcreated', '>=', $startDate)
                ->where('dcreated', '<=', $endDateParsed);
            if (!empty($selectedOutletId)) {
                $custQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$this->isSuperAdminUser()) {
                $custQuery->whereIn('nid_outlet', $allowedOutletIds);
            }
            $newCustomersCount = $custQuery->count();
        } elseif (Schema::hasColumn('mpos_cust', 'created_at')) {
            $custQuery = DB::table('mpos_cust')
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDateParsed);
            if (!empty($selectedOutletId)) {
                $custQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$this->isSuperAdminUser()) {
                $custQuery->whereIn('nid_outlet', $allowedOutletIds);
            }
            $newCustomersCount = $custQuery->count();
        } else {
            $firstSales = DB::table('mpos_sales_h')
                ->select('nid_customer', DB::raw('MIN(dtransaction) as first_trx'))
                ->whereNotNull('nid_customer');
            if (!empty($selectedOutletId)) {
                $firstSales->where('nid_outlet', $selectedOutletId);
            } elseif (!$this->isSuperAdminUser()) {
                $firstSales->whereIn('nid_outlet', $allowedOutletIds);
            }
            $firstSales->groupBy('nid_customer')
                ->havingRaw('MIN(dtransaction) >= ? AND MIN(dtransaction) <= ?', [$startDate, $endDateParsed]);

            $newCustomersCount = DB::table(DB::raw("({$firstSales->toSql()}) as sub"))
                ->mergeBindings($firstSales)
                ->count();
        }

        // 5. Metric 2: Pesanan & Metric 3: Pendapatan
        $salesBaseQuery = DB::table('mpos_sales_h')
            ->where('dtransaction', '>=', $startDate)
            ->where('dtransaction', '<=', $endDateParsed)
            ->where(function ($q) {
                $q->whereIn('cstatus', [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                ])->orWhere(function ($sub) {
                    $sub->whereNotIn('cstatus', [
                        MposSalesH::STATUS_CANCELLED,
                        MposSalesH::STATUS_DRAFT,
                        MposSalesH::STATUS_VOID,
                        MposSalesH::STATUS_REFUND,
                    ])->whereNotNull('nid_payment');
                });
            });

        if (!empty($selectedOutletId)) {
            $salesBaseQuery->where('mpos_sales_h.nid_outlet', $selectedOutletId);
        } elseif (!$this->isSuperAdminUser()) {
            $salesBaseQuery->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
        }

        $totalPesanan = (clone $salesBaseQuery)->count();

        // Item deductions for partial void & refund
        $itemDeductions = DB::table('mpos_sales_d')
            ->select(
                'nid_transaction',
                DB::raw('SUM((nqty_void + nqty_refund) * nprice) as total_void_refund')
            )
            ->groupBy('nid_transaction');

        $salesSummary = (clone $salesBaseQuery)
            ->leftJoinSub($itemDeductions, 'items', function ($join) {
                $join->on('mpos_sales_h.nid', '=', 'items.nid_transaction');
            })
            ->selectRaw("
                SUM(CASE WHEN mpos_sales_h.csource = 'OLSERA' THEN mpos_sales_h.ngrandtotal ELSE (mpos_sales_h.ngrandtotal - COALESCE(items.total_void_refund, 0)) END) as net_sales,
                SUM(CASE WHEN mpos_sales_h.csource = 'OLSERA' THEN 0 ELSE COALESCE(items.total_void_refund, 0) END) as partial_void_refund
            ")
            ->first();

        $totalPendapatan = max(0, (float) ($salesSummary->net_sales ?? 0));

        // 6. Metric 4: Pengeluaran (Cash out movements)
        $totalPengeluaran = 0;
        if (Schema::hasTable('mpos_cash_movement')) {
            $cashOutQuery = DB::table('mpos_cash_movement')
                ->join('mpos_shift', 'mpos_cash_movement.nid_shift', '=', 'mpos_shift.nid')
                ->where('mpos_cash_movement.ctype', MposCashMovement::TYPE_CASH_OUT)
                ->where('mpos_cash_movement.dcreated_at', '>=', $startDate)
                ->where('mpos_cash_movement.dcreated_at', '<=', $endDateParsed);

            if (!empty($selectedOutletId)) {
                $cashOutQuery->where('mpos_shift.nid_outlet', $selectedOutletId);
            } elseif (!$this->isSuperAdminUser()) {
                $cashOutQuery->whereIn('mpos_shift.nid_outlet', $allowedOutletIds);
            }

            $totalPengeluaran = (float) $cashOutQuery->sum('mpos_cash_movement.namount');
        }

        // 7. Metric 5: Pengembalian (Full void & refund + partial void & refund)
        $refundVoidQuery = DB::table('mpos_sales_h')
            ->where('dtransaction', '>=', $startDate)
            ->where('dtransaction', '<=', $endDateParsed)
            ->whereIn('cstatus', [MposSalesH::STATUS_REFUND, MposSalesH::STATUS_VOID]);

        if (!empty($selectedOutletId)) {
            $refundVoidQuery->where('mpos_sales_h.nid_outlet', $selectedOutletId);
        } elseif (!$this->isSuperAdminUser()) {
            $refundVoidQuery->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
        }

        $fullRefundVoidAmount = (float) $refundVoidQuery->sum('ngrandtotal');
        $partialVoidRefund = (float) ($salesSummary->partial_void_refund ?? 0);
        $totalPengembalian = $fullRefundVoidAmount + $partialVoidRefund;

        // 8. Metric 6: Rata-rata penjualan per transaksi
        $averageSales = $totalPesanan > 0 ? ($totalPendapatan / $totalPesanan) : 0;

        // 9. Grup Produk Terjual Terbanyak & Produk Terjual per Grup
        $salesDetailQuery = DB::table('mpos_sales_d')
            ->join('mpos_sales_h', 'mpos_sales_d.nid_transaction', '=', 'mpos_sales_h.nid')
            ->leftJoin('mpos_product', 'mpos_sales_d.nid_product', '=', 'mpos_product.nid')
            ->leftJoin('mpos_grp_product', 'mpos_product.nid_category', '=', 'mpos_grp_product.nid')
            ->where('mpos_sales_h.dtransaction', '>=', $startDate)
            ->where('mpos_sales_h.dtransaction', '<=', $endDateParsed)
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
            });

        if (!empty($selectedOutletId)) {
            $salesDetailQuery->where('mpos_sales_h.nid_outlet', $selectedOutletId);
        } elseif (!$this->isSuperAdminUser()) {
            $salesDetailQuery->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
        }

        // Top Product Groups
        $topGroups = (clone $salesDetailQuery)
            ->select(
                'mpos_product.nid_category as category_id',
                DB::raw("COALESCE(NULLIF(TRIM(MAX(mpos_grp_product.cname)), ''), 'Tanpa Kategori') as category_name"),
                DB::raw("SUM(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) as total_qty")
            )
            ->groupBy('mpos_product.nid_category')
            ->havingRaw("SUM(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) > 0")
            ->orderBy('total_qty', 'desc')
            ->get()
            ->map(function ($item) {
                $item->category_id = (int) ($item->category_id ?? 0);
                return $item;
            });

        $maxGroupQty = $topGroups->max('total_qty') ?: 1;

        // Breakdown Products per Group (sorted ascending to match Olsera view)
        $productsByGroupRaw = (clone $salesDetailQuery)
            ->select(
                'mpos_product.nid_category as category_id',
                DB::raw("COALESCE(NULLIF(TRIM(mpos_sales_d.cname), ''), 'Produk') as product_name"),
                DB::raw("SUM(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) as total_qty")
            )
            ->groupBy('mpos_product.nid_category', 'mpos_sales_d.cname')
            ->havingRaw("SUM(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) > 0")
            ->orderBy('total_qty', 'asc')
            ->get()
            ->map(function ($item) {
                $item->category_id = (int) ($item->category_id ?? 0);
                return $item;
            });

        $groupedProducts = $productsByGroupRaw->groupBy('category_id');

        return view('dashboard.index', compact(
            'startDate',
            'endDate',
            'selectedOutletId',
            'activeOutletName',
            'outlets',
            'isCashierOnly',
            'isSuperAdmin',
            'isOutletLocked',
            'allowedOutletIds',
            'dateLabel',
            'newCustomersCount',
            'totalPesanan',
            'totalPendapatan',
            'totalPengeluaran',
            'totalPengembalian',
            'averageSales',
            'topGroups',
            'maxGroupQty',
            'groupedProducts',
            'today',
            'yesterday',
            'thisMonthStart',
            'thisMonthEnd',
            'lastMonthStart',
            'lastMonthEnd',
            'sevenDaysAgo'
        ));
    }
}
