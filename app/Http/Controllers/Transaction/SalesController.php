<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\MposOutlet;
use App\Models\MposSalesH;
use App\Models\MposSalesoffH;
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
            $sources = [];
            if (!empty($selectedOutletId)) {
                $outletType = MposOutlet::resolveTransactionTypeForOutlet((int) $selectedOutletId);
                $sources = $outletType === MposOutlet::TRANSACTION_TYPE_OFFLINE
                    ? [['mpos_sls_h', 'OFFLINE']]
                    : [['mpos_sales_h', 'ONLINE']];
            } else {
                $sources = [
                    ['mpos_sales_h', 'ONLINE'],
                    ['mpos_sls_h', 'OFFLINE'],
                ];
            }

            $subQueries = [];
            foreach ($sources as [$table, $catLabel]) {
                $q = DB::table($table)
                    ->leftJoin('mpos_cust', "{$table}.nid_customer", '=', 'mpos_cust.nid')
                    ->leftJoin('mpos_outlet', "{$table}.nid_outlet", '=', 'mpos_outlet.nid')
                    ->select([
                        "{$table}.nid",
                        "{$table}.nid_outlet",
                        "{$table}.cnotransaction",
                        "{$table}.dtransaction",
                        "{$table}.ngrandtotal",
                        DB::raw("COALESCE(NULLIF(NULLIF(TRIM({$table}.cname_customer), ''), 'Umum'), NULLIF(NULLIF(TRIM(mpos_cust.cname), ''), 'Umum'), '-') as customer_name"),
                        'mpos_outlet.cname as outlet_name',
                        DB::raw("'{$catLabel}' as ctransaction_category"),
                    ])
                    ->where(function ($statusQ) use ($table) {
                        $statusQ->whereIn("{$table}.cstatus", [
                            MposSalesH::STATUS_PAID,
                            MposSalesH::STATUS_COMPLETED,
                        ])->orWhere(function ($sub) use ($table) {
                            $sub->whereNotIn("{$table}.cstatus", [
                                MposSalesH::STATUS_CANCELLED,
                                MposSalesH::STATUS_DRAFT,
                            ])->whereNotNull("{$table}.nid_payment");
                        });
                    })
                    ->whereBetween("{$table}.dtransaction", [
                        $startDate . ' 00:00:00',
                        $endDate . ' 23:59:59',
                    ]);

                if (!empty($selectedOutletId)) {
                    $q->where("{$table}.nid_outlet", $selectedOutletId);
                } elseif (!$isSuperAdmin) {
                    $q->whereIn("{$table}.nid_outlet", $allowedOutletIds);
                }

                if ($search !== '') {
                    $q->where(function ($searchQ) use ($table, $search) {
                        $searchQ->where("{$table}.cnotransaction", 'like', "%{$search}%")
                            ->orWhere("{$table}.cname_customer", 'like', "%{$search}%")
                            ->orWhere('mpos_cust.cname', 'like', "%{$search}%")
                            ->orWhere('mpos_cust.cphone', 'like', "%{$search}%")
                            ->orWhere('mpos_cust.cemail', 'like', "%{$search}%");
                    });
                }

                $subQueries[] = $q;
            }

            $combined = array_shift($subQueries);
            foreach ($subQueries as $sub) {
                $combined->unionAll($sub);
            }

            $query = DB::query()->fromSub($combined, 'combined_sales');

            $sortExpression = match ($sortCol) {
                'cnotransaction' => 'cnotransaction',
                'customer_name' => 'customer_name',
                'ngrandtotal' => 'ngrandtotal',
                default => 'dtransaction',
            };

            $query->orderBy($sortExpression, $sortDir);
            if ($sortCol !== 'dtransaction') {
                $query->orderBy('dtransaction', 'desc');
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

    public function show(Request $request, string $id): View
    {
        $category = strtoupper(trim((string) $request->input('category', '')));
        $with = [
            'details.product.category',
            'customer',
            'outlet',
            'payment',
            'paymentDual',
            'posUser.user',
        ];

        if ($category === 'OFFLINE') {
            $candidates = [MposSalesoffH::class, MposSalesH::class];
        } elseif ($category === 'ONLINE') {
            $candidates = [MposSalesH::class, MposSalesoffH::class];
        } elseif (!is_numeric($id) && str_contains(strtoupper($id), 'OFF')) {
            $candidates = [MposSalesoffH::class, MposSalesH::class];
        } else {
            $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
            $outletType = $selectedOutletId > 0 ? MposOutlet::resolveTransactionTypeForOutlet($selectedOutletId) : null;
            $candidates = $outletType === MposOutlet::TRANSACTION_TYPE_OFFLINE
                ? [MposSalesoffH::class, MposSalesH::class]
                : [MposSalesH::class, MposSalesoffH::class];
        }

        $sale = null;
        foreach ($candidates as $modelClass) {
            $sale = $modelClass::with($with)
                ->where(function ($q) use ($id) {
                    if (is_numeric($id)) {
                        $q->where('nid', (int) $id)->orWhere('cnotransaction', $id);
                    } else {
                        $q->where('cnotransaction', $id);
                    }
                })
                ->first();

            if ($sale) {
                break;
            }
        }

        if (! $sale) {
            abort(404);
        }

        $allowedOutletIds = $this->getUserAllowedOutletIds();
        if (!$this->isSuperAdminUser() && !in_array((int) $sale->nid_outlet, $allowedOutletIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke transaksi outlet ini.');
        }

        return view('sales.show', compact('sale'));
    }
}
