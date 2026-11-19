<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Models\MposOutlet;
use App\Models\MposSalesH;
use App\Traits\ResolvesOutletScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfficeReportController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            $bearer = $request->bearerToken();
            if ($bearer && str_starts_with($bearer, 'logged_in_office_')) {
                $userId = (int) substr($bearer, strlen('logged_in_office_'));
                $user = \App\Models\Muser::find($userId);
            }
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        Auth::setUser($user);

        $type = strtolower((string) $request->input('type', 'sales_detail'));
        if (!in_array($type, ['sales_detail', 'sales_item', 'customer'], true)) {
            $type = 'sales_detail';
        }

        $startDate = (string) $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = (string) $request->input('end_date', Carbon::now()->format('Y-m-d'));

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $endDateParsed = Carbon::parse($endDate)->endOfDay();
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $isSuperAdmin = $this->isSuperAdminUser();

        return match ($type) {
            'sales_item' => $this->getSalesItemReport($request, $startDate, $endDateParsed, $selectedOutletId, $allowedOutletIds, $isSuperAdmin),
            'customer' => $this->getCustomerReport($request, $startDate, $endDateParsed, $selectedOutletId, $allowedOutletIds, $isSuperAdmin),
            default => $this->getSalesDetailReport($request, $startDate, $endDateParsed, $selectedOutletId, $allowedOutletIds, $isSuperAdmin),
        };
    }

    private function getSalesDetailReport(Request $request, string $startDate, Carbon $endDateParsed, ?int $outletId, array $allowedOutletIds, bool $isSuperAdmin): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        // 1. Items Deductions & Reversals Subqueries
        $itemDeductions = DB::table('mpos_sales_d')
            ->select(
                'nid_transaction',
                DB::raw('SUM((nqty_void + nqty_refund) * nprice) as item_void_refund'),
                DB::raw('SUM(ncost * (nqty - nqty_void - nqty_refund)) as total_cost'),
                DB::raw('MIN(ncost) as min_cost')
            )
            ->groupBy('nid_transaction');

        $reversals = DB::table('mpos_sales_h')
            ->select(
                'nid_parent_transaction',
                DB::raw('SUM(ABS(ngrandtotal)) as reversal_amount')
            )
            ->whereNotNull('nid_parent_transaction')
            ->groupBy('nid_parent_transaction');

        // 2. Summary Query
        $summaryQuery = DB::table('mpos_sales_h')
            ->where('mpos_sales_h.dtransaction', '>=', $startDate)
            ->where('mpos_sales_h.dtransaction', '<=', $endDateParsed)
            ->whereNull('mpos_sales_h.nid_parent_transaction')
            ->leftJoinSub($itemDeductions, 'items', 'mpos_sales_h.nid', '=', 'items.nid_transaction')
            ->leftJoinSub($reversals, 'rev', 'mpos_sales_h.nid', '=', 'rev.nid_parent_transaction')
            ->where(function ($q) {
                $q->whereIn('mpos_sales_h.cstatus', [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                    MposSalesH::STATUS_VOID,
                    MposSalesH::STATUS_REFUND,
                ])->orWhere(function ($sub) {
                    $sub->whereNotIn('mpos_sales_h.cstatus', [
                        MposSalesH::STATUS_CANCELLED,
                        MposSalesH::STATUS_DRAFT,
                    ])->whereNotNull('mpos_sales_h.nid_payment');
                });
            });

        if (!empty($outletId)) {
            $summaryQuery->where('mpos_sales_h.nid_outlet', $outletId);
        } elseif (!$isSuperAdmin) {
            $summaryQuery->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
        }

        $summary = $summaryQuery->select(
            DB::raw('COUNT(mpos_sales_h.nid) as total_transaksi'),
            DB::raw("SUM(CASE WHEN mpos_sales_h.csource = 'OLSERA' THEN mpos_sales_h.nsubtotal ELSE mpos_sales_h.ngrandtotal END) as gross_sales"),
            DB::raw("SUM(CASE
                WHEN mpos_sales_h.csource = 'OLSERA' THEN mpos_sales_h.ngrandtotal
                WHEN mpos_sales_h.cstatus IN ('VOID', 'CANCELLED', 'REFUND') THEN 0
                ELSE (mpos_sales_h.ngrandtotal - GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0)))
            END) as net_sales"),
            DB::raw("SUM(CASE
                WHEN mpos_sales_h.csource = 'OLSERA' THEN 0
                WHEN mpos_sales_h.cstatus IN ('VOID', 'CANCELLED', 'REFUND') THEN ABS(mpos_sales_h.ngrandtotal)
                ELSE GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0))
            END) as total_void_refund"),
            DB::raw('SUM(mpos_sales_h.ndiscount) as total_discount'),
            DB::raw("SUM(CASE
                WHEN mpos_sales_h.csource = 'OLSERA' THEN
                    CASE WHEN mpos_sales_h.ntotal_cost IS NOT NULL THEN mpos_sales_h.ntotal_cost ELSE 0 END
                WHEN mpos_sales_h.cstatus IN ('VOID', 'CANCELLED', 'REFUND') THEN 0
                WHEN GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0)) >= mpos_sales_h.ngrandtotal THEN 0
                WHEN items.min_cost >= 0 THEN items.total_cost
                ELSE 0
            END) as valid_total_cost"),
            DB::raw("SUM(CASE
                WHEN mpos_sales_h.csource = 'OLSERA' THEN
                    CASE WHEN mpos_sales_h.ntotal_profit IS NOT NULL THEN mpos_sales_h.ntotal_profit ELSE 0 END
                WHEN mpos_sales_h.cstatus IN ('VOID', 'CANCELLED', 'REFUND') THEN 0
                WHEN GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0)) >= mpos_sales_h.ngrandtotal THEN 0
                WHEN items.min_cost >= 0 THEN
                    (mpos_sales_h.ngrandtotal - GREATEST(COALESCE(items.item_void_refund, 0), COALESCE(rev.reversal_amount, 0)) - COALESCE(items.total_cost, 0))
                ELSE 0
            END) as valid_total_laba")
        )->first();

        $grossSales = (float) ($summary->gross_sales ?? 0);
        $voidRefund = (float) ($summary->total_void_refund ?? 0);
        $totalDiscount = (float) ($summary->total_discount ?? 0);
        $netSales = (float) ($summary->net_sales ?? 0);
        $totalCost = (float) ($summary->valid_total_cost ?? 0);
        $totalLaba = (float) ($summary->valid_total_laba ?? 0);
        $totalTransactions = (int) ($summary->total_transaksi ?? 0);

        // 3. Transactions Query
        $transQuery = DB::table('mpos_sales_h')
            ->leftJoin('mpos_payment', 'mpos_sales_h.nid_payment', '=', 'mpos_payment.nid')
            ->leftJoin('mpos_user', 'mpos_sales_h.nid_user', '=', 'mpos_user.nid')
            ->leftJoin('muser', 'mpos_user.nid_user', '=', 'muser.nid')
            ->leftJoin('mpos_cust', 'mpos_sales_h.nid_customer', '=', 'mpos_cust.nid')
            ->where('mpos_sales_h.dtransaction', '>=', $startDate)
            ->where('mpos_sales_h.dtransaction', '<=', $endDateParsed)
            ->whereNull('mpos_sales_h.nid_parent_transaction');

        if (!empty($outletId)) {
            $transQuery->where('mpos_sales_h.nid_outlet', $outletId);
        } elseif (!$isSuperAdmin) {
            $transQuery->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
        }

        $transQuery->select(
            'mpos_sales_h.nid',
            'mpos_sales_h.cnotransaction',
            'mpos_sales_h.dtransaction',
            'mpos_sales_h.cstatus',
            'mpos_sales_h.ngrandtotal',
            'mpos_sales_h.csource',
            'mpos_payment.cname as payment_name',
            'muser.cname as cashier_name',
            DB::raw("COALESCE(NULLIF(NULLIF(TRIM(mpos_sales_h.cname_customer), ''), 'Umum'), NULLIF(NULLIF(TRIM(mpos_cust.cname), ''), 'Umum'), '-') as customer_name")
        )->orderBy('mpos_sales_h.dtransaction', 'desc')->orderBy('mpos_sales_h.nid', 'desc');

        $paginator = $transQuery->paginate($perPage);

        $items = collect($paginator->items())->map(function ($row) {
            $dtrx = $row->dtransaction ? Carbon::parse($row->dtransaction) : null;
            $amt = (float) $row->ngrandtotal;

            return [
                'tanggal_transaksi' => $dtrx ? $dtrx->format('d/m/Y') : '-',
                'waktu_transaksi' => $dtrx ? $dtrx->format('d/m/Y H:i') : '-',
                'cnotransaction' => $row->cnotransaction ? ('#' . $row->cnotransaction) : ('#' . $row->nid),
                'payment_method' => $row->payment_name ?: 'CASH',
                'status' => $row->cstatus ?: 'PAID',
                'kasir' => $row->cashier_name ?: 'Admin',
                'pelanggan' => $row->customer_name ?: '-',
                'total_penjualan' => $amt,
                'total_penjualan_formatted' => 'Rp ' . number_format($amt, 0, ',', '.'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'type' => 'sales_detail',
                'summary' => [
                    'total_penjualan' => $grossSales,
                    'total_penjualan_formatted' => 'Rp ' . number_format($grossSales, 0, ',', '.'),
                    'void_refund' => $voidRefund,
                    'void_refund_formatted' => 'Rp ' . number_format($voidRefund, 0, ',', '.'),
                    'total_diskon' => $totalDiscount,
                    'total_diskon_formatted' => 'Rp ' . number_format($totalDiscount, 0, ',', '.'),
                    'penjualan_nett' => $netSales,
                    'penjualan_nett_formatted' => 'Rp ' . number_format($netSales, 0, ',', '.'),
                    'modal_produk' => $totalCost,
                    'modal_produk_formatted' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
                    'total_laba' => $totalLaba,
                    'total_laba_formatted' => 'Rp ' . number_format($totalLaba, 0, ',', '.'),
                    'jml_transaksi' => $totalTransactions,
                ],
                'reports' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'data' => $items,
                ],
            ],
        ]);
    }

    private function getSalesItemReport(Request $request, string $startDate, Carbon $endDateParsed, ?int $outletId, array $allowedOutletIds, bool $isSuperAdmin): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $baseQuery = DB::table('mpos_sales_d')
            ->join('mpos_sales_h', 'mpos_sales_d.nid_transaction', '=', 'mpos_sales_h.nid')
            ->leftJoin('mpos_cust', 'mpos_sales_h.nid_customer', '=', 'mpos_cust.nid')
            ->leftJoin('mpos_product', 'mpos_sales_d.nid_product', '=', 'mpos_product.nid')
            ->leftJoin('mpos_user', 'mpos_sales_h.nid_user', '=', 'mpos_user.nid')
            ->leftJoin('muser', 'mpos_user.nid_user', '=', 'muser.nid')
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
        } elseif (!$isSuperAdmin) {
            $baseQuery->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
        }

        // Summary
        $summaryQuery = clone $baseQuery;
        $summary = $summaryQuery->select([
            DB::raw('COALESCE(SUM(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund), 0) as total_qty'),
            DB::raw('0 as total_discount'),
            DB::raw('COALESCE(SUM(((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) - CASE WHEN mpos_sales_h.nsubtotal > 0 THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount ELSE 0 END), 0) as total_penjualan'),
            DB::raw('COALESCE(SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.ncost), 0) as total_modal'),
            DB::raw('COALESCE(SUM((((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) - CASE WHEN mpos_sales_h.nsubtotal > 0 THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount ELSE 0 END) - ((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.ncost)), 0) as total_laba'),
        ])->first();

        $totalQty = (int) ($summary->total_qty ?? 0);
        $totalDiscount = (float) ($summary->total_discount ?? 0);
        $totalPenjualan = (float) ($summary->total_penjualan ?? 0);
        $totalModal = (float) ($summary->total_modal ?? 0);
        $totalLaba = (float) ($summary->total_laba ?? 0);

        // Paginated Items
        $itemsQuery = clone $baseQuery;
        $itemsQuery->select([
            'mpos_sales_d.nid as item_id',
            'mpos_sales_d.cname as item_name',
            'mpos_sales_d.nprice',
            'mpos_sales_d.ncost',
            'mpos_sales_d.nqty',
            'mpos_sales_d.nqty_void',
            'mpos_sales_d.nqty_refund',
            'mpos_sales_h.nid as transaction_id',
            'mpos_sales_h.cnotransaction',
            'mpos_sales_h.dtransaction',
            'mpos_sales_h.nsubtotal as transaction_subtotal',
            'mpos_sales_h.ndiscount as transaction_discount',
            'muser.cname as cashier_name',
            DB::raw("COALESCE(NULLIF(NULLIF(TRIM(mpos_sales_h.cname_customer), ''), 'Umum'), NULLIF(NULLIF(TRIM(mpos_cust.cname), ''), 'Umum'), '-') as customer_name"),
        ])->orderBy('mpos_sales_h.dtransaction', 'desc')->orderBy('mpos_sales_d.nid', 'desc');

        $paginator = $itemsQuery->paginate($perPage);

        $items = collect($paginator->items())->map(function ($row) {
            $dtrx = $row->dtransaction ? Carbon::parse($row->dtransaction) : null;
            $netQty = max(0, (int) ($row->nqty - $row->nqty_void - $row->nqty_refund));
            $itemSub = $netQty * (float) $row->nprice;
            $itemDisc = ($row->transaction_subtotal > 0 && $row->transaction_discount > 0)
                ? ($itemSub / (float) $row->transaction_subtotal) * (float) $row->transaction_discount
                : 0.0;
            $netTotal = max(0, $itemSub - $itemDisc);

            return [
                'cnotransaction' => $row->cnotransaction ? ('#' . $row->cnotransaction) : ('#' . $row->transaction_id),
                'waktu_transaksi' => $dtrx ? $dtrx->format('d/m/Y H:i') : '-',
                'penjualan_oleh' => $row->cashier_name ?: 'Admin',
                'item_name' => $row->item_name ?: 'Produk',
                'customer_name' => $row->customer_name ?: '-',
                'qty' => $netQty,
                'diskon' => $itemDisc,
                'diskon_formatted' => 'Rp ' . number_format($itemDisc, 0, ',', '.'),
                'total_penjualan' => $netTotal,
                'total_penjualan_formatted' => 'Rp ' . number_format($netTotal, 0, ',', '.'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'type' => 'sales_item',
                'summary' => [
                    'total_qty' => $totalQty,
                    'total_diskon' => $totalDiscount,
                    'total_diskon_formatted' => 'Rp ' . number_format($totalDiscount, 0, ',', '.'),
                    'total_penjualan' => $totalPenjualan,
                    'total_penjualan_formatted' => 'Rp ' . number_format($totalPenjualan, 0, ',', '.'),
                    'modal_produk' => $totalModal,
                    'modal_produk_formatted' => 'Rp ' . number_format($totalModal, 0, ',', '.'),
                    'total_laba' => $totalLaba,
                    'total_laba_formatted' => 'Rp ' . number_format($totalLaba, 0, ',', '.'),
                ],
                'reports' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'data' => $items,
                ],
            ],
        ]);
    }

    private function getCustomerReport(Request $request, string $startDate, Carbon $endDateParsed, ?int $outletId, array $allowedOutletIds, bool $isSuperAdmin): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $customerNameExpr = "COALESCE(NULLIF(TRIM(mpos_cust.cname), ''), NULLIF(TRIM(mpos_sales_h.cname_customer), ''), 'Pelanggan Umum')";

        $baseQuery = DB::table('mpos_sales_d')
            ->join('mpos_sales_h', 'mpos_sales_d.nid_transaction', '=', 'mpos_sales_h.nid')
            ->leftJoin('mpos_cust', 'mpos_sales_h.nid_customer', '=', 'mpos_cust.nid')
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
            ->whereRaw('(mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) > 0')
            ->where('mpos_sales_h.dtransaction', '>=', $startDate)
            ->where('mpos_sales_h.dtransaction', '<=', $endDateParsed);

        if (!empty($outletId)) {
            $baseQuery->where('mpos_sales_h.nid_outlet', $outletId);
        } elseif (!$isSuperAdmin) {
            $baseQuery->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
        }

        // Summary
        $summaryQuery = clone $baseQuery;
        $summaryObj = $summaryQuery->select(
            DB::raw('SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) as total_penjualan'),
            DB::raw('ROUND(SUM(CASE WHEN mpos_sales_h.nsubtotal > 0 THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount ELSE 0 END), 2) as total_diskon'),
            DB::raw('COUNT(DISTINCT mpos_sales_h.nid) as total_transaksi')
        )->first();

        $totalPenjualan = round((float) ($summaryObj->total_penjualan ?? 0), 2);
        $totalDiskon = round((float) ($summaryObj->total_diskon ?? 0), 2);
        $totalModal = 0.0;
        $totalLaba = round($totalPenjualan - $totalDiskon - $totalModal, 2);
        $totalTransactions = (int) ($summaryObj->total_transaksi ?? 0);

        // Items Query grouped by Customer
        $customerQuery = clone $baseQuery;
        $customerQuery->select(
            DB::raw("{$customerNameExpr} as customer_name"),
            DB::raw('ROUND(SUM((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice), 2) as total_penjualan'),
            DB::raw('ROUND(SUM(CASE WHEN mpos_sales_h.nsubtotal > 0 THEN (((mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund) * mpos_sales_d.nprice) / mpos_sales_h.nsubtotal) * mpos_sales_h.ndiscount ELSE 0 END), 2) as diskon'),
            DB::raw('COUNT(DISTINCT mpos_sales_h.nid) as jml_transaksi')
        )->groupBy('customer_name')->orderBy('customer_name', 'asc');

        $paginator = $customerQuery->paginate($perPage);

        $items = collect($paginator->items())->map(function ($row) {
            $penjualan = (float) $row->total_penjualan;
            $diskon = (float) $row->diskon;
            $modal = 0.0;
            $laba = max(0, $penjualan - $diskon - $modal);

            return [
                'pelanggan' => $row->customer_name ?: 'Pelanggan Umum',
                'total_penjualan' => $penjualan,
                'total_penjualan_formatted' => 'IDR ' . number_format($penjualan, 2, ',', '.'),
                'diskon' => $diskon,
                'diskon_formatted' => 'IDR ' . number_format($diskon, 2, ',', '.'),
                'modal_produk' => $modal,
                'modal_produk_formatted' => 'IDR ' . number_format($modal, 2, ',', '.'),
                'laba' => $laba,
                'laba_formatted' => 'IDR ' . number_format($laba, 2, ',', '.'),
                'jml_transaksi' => (int) $row->jml_transaksi,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'type' => 'customer',
                'summary' => [
                    'total_penjualan' => $totalPenjualan,
                    'total_penjualan_formatted' => 'IDR ' . number_format($totalPenjualan, 2, ',', '.'),
                    'diskon' => $totalDiskon,
                    'diskon_formatted' => 'IDR ' . number_format($totalDiskon, 2, ',', '.'),
                    'modal_produk' => $totalModal,
                    'modal_produk_formatted' => 'IDR ' . number_format($totalModal, 2, ',', '.'),
                    'laba' => $totalLaba,
                    'laba_formatted' => 'IDR ' . number_format($totalLaba, 2, ',', '.'),
                    'jml_transaksi' => $totalTransactions,
                ],
                'reports' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'data' => $items,
                ],
            ],
        ]);
    }
}
