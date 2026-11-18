<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\MposSalesH;
use App\Traits\ResolvesOutletScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request): View
    {
        $activeTab = (string) $request->input('tab', 'selesai');
        $validTabs = ['butuh_proses', 'selesai', 'pengembalian', 'dibatalkan'];
        if (!in_array($activeTab, $validTabs, true)) {
            $activeTab = 'selesai';
        }

        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $isSuperAdmin = $this->isSuperAdminUser();

        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        if ($startDate > $endDate) {
            $endDate = $startDate;
        }

        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 15;
        }

        $sortCol = (string) $request->input('sort_col', 'dtransaction');
        $validSortCols = ['cnotransaction', 'dtransaction', 'customer_name', 'ngrandtotal'];
        if (!in_array($sortCol, $validSortCols, true)) {
            $sortCol = 'dtransaction';
        }
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $sales = null;
        if ($activeTab === 'selesai') {
            $query = MposSalesH::query()
                ->leftJoin('mpos_cust', 'mpos_sales_h.nid_customer', '=', 'mpos_cust.nid')
                ->with(['customer', 'outlet'])
                ->select([
                    'mpos_sales_h.*',
                    DB::raw("COALESCE(NULLIF(NULLIF(TRIM(mpos_sales_h.cname_customer), ''), 'Umum'), NULLIF(NULLIF(TRIM(mpos_cust.cname), ''), 'Umum'), '-') as customer_name"),
                ])
                ->where(function ($q) {
                    $q->whereIn('mpos_sales_h.cstatus', [
                        MposSalesH::STATUS_PAID,
                        MposSalesH::STATUS_COMPLETED,
                    ])->orWhere(function ($sub) {
                        $sub->whereNotIn('mpos_sales_h.cstatus', [
                            MposSalesH::STATUS_CANCELLED,
                            MposSalesH::STATUS_DRAFT,
                        ])->whereNotNull('mpos_sales_h.nid_payment');
                    });
                })
                ->whereBetween('mpos_sales_h.dtransaction', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59',
                ]);

            if (!empty($selectedOutletId)) {
                $query->where('mpos_sales_h.nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $query->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('mpos_sales_h.cnotransaction', 'like', "%{$search}%")
                      ->orWhere('mpos_sales_h.cname_customer', 'like', "%{$search}%")
                      ->orWhere('mpos_cust.cname', 'like', "%{$search}%")
                      ->orWhere('mpos_cust.cphone', 'like', "%{$search}%")
                      ->orWhere('mpos_cust.cemail', 'like', "%{$search}%");
                });
            }

            $sortExpression = match ($sortCol) {
                'cnotransaction' => 'mpos_sales_h.cnotransaction',
                'customer_name' => 'customer_name',
                'ngrandtotal' => 'mpos_sales_h.ngrandtotal',
                default => 'mpos_sales_h.dtransaction',
            };

            $query->orderBy($sortExpression, $sortDir);
            if ($sortCol !== 'dtransaction') {
                $query->orderBy('mpos_sales_h.dtransaction', 'desc');
            }

            $sales = $query->paginate($perPage)->withQueryString();
        }

        return view('sales.index', compact(
            'activeTab',
            'sales',
            'selectedOutletId',
            'startDate',
            'endDate',
            'search',
            'perPage',
            'sortCol',
            'sortDir'
        ));
    }

    public function show(string $id): View
    {
        $sale = MposSalesH::with([
            'details.product.category',
            'customer',
            'outlet',
            'payment',
            'paymentDual',
            'posUser.user',
        ])
            ->where('nid', $id)
            ->orWhere('cnotransaction', $id)
            ->firstOrFail();

        $allowedOutletIds = $this->getUserAllowedOutletIds();
        if (!$this->isSuperAdminUser() && !in_array((int) $sale->nid_outlet, $allowedOutletIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke transaksi outlet ini.');
        }

        return view('sales.show', compact('sale'));
    }
}
