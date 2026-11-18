<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Models\MposCashMovement;
use App\Models\MposOutlet;
use App\Models\MposSalesH;
use App\Traits\ResolvesOutletScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OfficeDashboardController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        Auth::setUser($user);

        // 1. Date Range Handling (Default Hari Ini)
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $thisMonthStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $thisMonthEnd = Carbon::now()->endOfMonth()->format('Y-m-d');
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
        $sevenDaysAgo = Carbon::now()->subDays(6)->format('Y-m-d');

        $startDate = (string) $request->input('start_date', $today);
        $endDate = (string) $request->input('end_date', $today);

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $endDateParsed = Carbon::parse($endDate)->endOfDay();

        // 2. Outlet Scope Resolution
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $isSuperAdmin = $this->isSuperAdminUser();
        $isOutletLocked = $this->isOutletLocked();
        $outlets = $this->getAvailableOutlets();
        $allowedOutletIds = $this->getUserAllowedOutletIds();

        $activeOutletName = 'Semua Outlet';
        if (!empty($selectedOutletId)) {
            $matched = collect($outlets)->firstWhere('nid', $selectedOutletId)
                ?? MposOutlet::find($selectedOutletId);
            $activeOutletName = $matched ? $matched->cname : ('Outlet #' . $selectedOutletId);
        }

        // 3. Date Label Formatting
        $dateLabel = match (true) {
            $startDate === $today && $endDate === $today => 'Hari Ini (' . Carbon::parse($today)->format('d M Y') . ')',
            $startDate === $yesterday && $endDate === $yesterday => 'Kemarin (' . Carbon::parse($yesterday)->format('d M Y') . ')',
            $startDate === $sevenDaysAgo && $endDate === $today => '7 Hari Lalu (' . Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y') . ')',
            $startDate === $thisMonthStart && $endDate === $thisMonthEnd => 'Bulan Ini (' . Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y') . ')',
            $startDate === $lastMonthStart && $endDate === $lastMonthEnd => 'Bulan Lalu (' . Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y') . ')',
            default => Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y'),
        };

        // 4. Metric 1: Pelanggan Baru
        $newCustomersCount = 0;
        if (Schema::hasColumn('mpos_cust', 'dcreated')) {
            $custQuery = DB::table('mpos_cust')
                ->where('dcreated', '>=', $startDate)
                ->where('dcreated', '<=', $endDateParsed);
            if (!empty($selectedOutletId)) {
                $custQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $custQuery->whereIn('nid_outlet', $allowedOutletIds);
            }
            $newCustomersCount = $custQuery->count();
        } elseif (Schema::hasColumn('mpos_cust', 'created_at')) {
            $custQuery = DB::table('mpos_cust')
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDateParsed);
            if (!empty($selectedOutletId)) {
                $custQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $custQuery->whereIn('nid_outlet', $allowedOutletIds);
            }
            $newCustomersCount = $custQuery->count();
        } else {
            $firstSales = DB::table('mpos_sales_h')
                ->select('nid_customer', DB::raw('MIN(dtransaction) as first_trx'))
                ->whereNotNull('nid_customer');
            if (!empty($selectedOutletId)) {
                $firstSales->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $firstSales->whereIn('nid_outlet', $allowedOutletIds);
            }
            $firstSales->groupBy('nid_customer')
                ->havingRaw('MIN(dtransaction) >= ? AND MIN(dtransaction) <= ?', [$startDate, $endDateParsed]);

            $newCustomersCount = DB::table(DB::raw("({$firstSales->toSql()}) as sub"))
                ->mergeBindings($firstSales)
                ->count();
        }

        // 5. Metric 2 & 3: Pesanan & Pendapatan
        $salesBaseQuery = DB::table('mpos_sales_h')
            ->where('dtransaction', '>=', $startDate)
            ->where('dtransaction', '<=', $endDateParsed)
            ->whereNull('mpos_sales_h.nid_parent_transaction')
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
        } elseif (!$isSuperAdmin) {
            $salesBaseQuery->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
        }

        $totalPesanan = (clone $salesBaseQuery)->count();

        $itemDeductions = DB::table('mpos_sales_d')
            ->select(
                'nid_transaction',
                DB::raw('SUM((nqty_void + nqty_refund) * nprice) as total_void_refund')
            )
            ->groupBy('nid_transaction');

        $reversals = DB::table('mpos_sales_h')
            ->select(
                'nid_parent_transaction',
                DB::raw('SUM(ABS(ngrandtotal)) as reversal_amount')
            )
            ->whereNotNull('nid_parent_transaction')
            ->groupBy('nid_parent_transaction');

        $salesSummary = (clone $salesBaseQuery)
            ->leftJoinSub($itemDeductions, 'items', function ($join) {
                $join->on('mpos_sales_h.nid', '=', 'items.nid_transaction');
            })
            ->leftJoinSub($reversals, 'rev', function ($join) {
                $join->on('mpos_sales_h.nid', '=', 'rev.nid_parent_transaction');
            })
            ->selectRaw("
                SUM(CASE
                    WHEN mpos_sales_h.csource = 'OLSERA' THEN mpos_sales_h.ngrandtotal
                    ELSE (mpos_sales_h.ngrandtotal - GREATEST(COALESCE(items.total_void_refund, 0), COALESCE(rev.reversal_amount, 0)))
                END) as net_sales,
                SUM(CASE
                    WHEN mpos_sales_h.csource = 'OLSERA' THEN 0
                    ELSE GREATEST(COALESCE(items.total_void_refund, 0), COALESCE(rev.reversal_amount, 0))
                END) as partial_void_refund
            ")
            ->first();

        $totalPendapatan = max(0, (float) ($salesSummary->net_sales ?? 0));

        // 6. Metric 4: Pengeluaran
        $totalPengeluaran = 0.0;
        if (Schema::hasTable('mpos_cash_movement')) {
            $cashOutQuery = DB::table('mpos_cash_movement')
                ->join('mpos_shift', 'mpos_cash_movement.nid_shift', '=', 'mpos_shift.nid')
                ->where('mpos_cash_movement.ctype', MposCashMovement::TYPE_CASH_OUT)
                ->where('mpos_cash_movement.dcreated_at', '>=', $startDate)
                ->where('mpos_cash_movement.dcreated_at', '<=', $endDateParsed);

            if (!empty($selectedOutletId)) {
                $cashOutQuery->where('mpos_shift.nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $cashOutQuery->whereIn('mpos_shift.nid_outlet', $allowedOutletIds);
            }

            $totalPengeluaran = (float) $cashOutQuery->sum('mpos_cash_movement.namount');
        }

        // 7. Metric 5: Pengembalian / Pembatalan
        $refundVoidQuery = DB::table('mpos_sales_h')
            ->where('dtransaction', '>=', $startDate)
            ->where('dtransaction', '<=', $endDateParsed)
            ->whereNull('mpos_sales_h.nid_parent_transaction')
            ->whereIn('cstatus', [MposSalesH::STATUS_REFUND, MposSalesH::STATUS_VOID, MposSalesH::STATUS_CANCELLED]);

        if (!empty($selectedOutletId)) {
            $refundVoidQuery->where('mpos_sales_h.nid_outlet', $selectedOutletId);
        } elseif (!$isSuperAdmin) {
            $refundVoidQuery->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
        }

        $fullRefundVoidAmount = (float) $refundVoidQuery->sum(DB::raw('ABS(ngrandtotal)'));
        $partialVoidRefund = (float) ($salesSummary->partial_void_refund ?? 0);
        $totalPengembalian = $fullRefundVoidAmount + $partialVoidRefund;

        // 8. Metric 6: Rata-rata penjualan per transaksi
        $averageSales = $totalPesanan > 0 ? ($totalPendapatan / $totalPesanan) : 0.0;

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
        } elseif (!$isSuperAdmin) {
            $salesDetailQuery->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
        }

        $itemSalesBase = (clone $salesDetailQuery)
            ->select(
                DB::raw("UPPER(TRIM(COALESCE(NULLIF(TRIM(mpos_grp_product.cname), ''), 'Tanpa Kategori'))) as category_name"),
                DB::raw("COALESCE(NULLIF(TRIM(mpos_sales_d.cname), ''), 'Produk') as product_name"),
                DB::raw('(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) as net_qty')
            );

        $topGroupsRaw = DB::query()
            ->fromSub($itemSalesBase, 'sub')
            ->select('category_name', DB::raw('SUM(net_qty) as total_qty'))
            ->groupBy('category_name')
            ->havingRaw('SUM(net_qty) > 0')
            ->orderBy('total_qty', 'desc')
            ->get();

        $maxGroupQty = (float) ($topGroupsRaw->max('total_qty') ?: 1);

        $productsByGroup = DB::query()
            ->fromSub($itemSalesBase, 'sub')
            ->select('category_name', 'product_name', DB::raw('SUM(net_qty) as total_qty'))
            ->groupBy('category_name', 'product_name')
            ->havingRaw('SUM(net_qty) > 0')
            ->orderBy('total_qty', 'asc')
            ->get()
            ->groupBy('category_name');

        $topGroups = $topGroupsRaw->map(function ($group) use ($maxGroupQty, $productsByGroup) {
            $qty = (float) $group->total_qty;
            $products = ($productsByGroup->get($group->category_name) ?? collect())->map(function ($prod) {
                return [
                    'product_name' => $prod->product_name,
                    'total_qty' => (float) $prod->total_qty,
                ];
            })->values();

            return [
                'category_name' => $group->category_name,
                'total_qty' => $qty,
                'percentage' => round(($qty / $maxGroupQty) * 100, 2),
                'products' => $products,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'filter' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'date_label' => $dateLabel,
                    'selected_outlet_id' => $selectedOutletId ? (int) $selectedOutletId : null,
                    'active_outlet_name' => $activeOutletName,
                    'is_outlet_locked' => $isOutletLocked,
                ],
                'summary' => [
                    'pelanggan_baru' => (int) $newCustomersCount,
                    'pesanan' => (int) $totalPesanan,
                    'pendapatan' => $totalPendapatan,
                    'pengeluaran' => $totalPengeluaran,
                    'pengembalian' => $totalPengembalian,
                    'rata_rata_penjualan' => round($averageSales, 2),
                ],
                'top_groups' => $topGroups,
                'max_group_qty' => $maxGroupQty,
            ],
        ], 200);
    }
}
