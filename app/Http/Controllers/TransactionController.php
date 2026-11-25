<?php

namespace App\Http\Controllers;

use App\Models\MposProduct;
use App\Models\MposSalesD;
use App\Models\MposSalesH;
use App\Models\MposSalesoffD;
use App\Models\MposSalesoffH;
use App\Models\MposShift;
use App\Models\MposTaxSetting;
use App\Models\MposUser;
use App\Models\Muser;
use App\Services\RecipeStockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TransactionController extends Controller
{
    protected static ?bool $salesDHasCostCol = null;
    protected static ?bool $salesHHasCostCols = null;

    protected static function hasSalesDCostColumn(): bool
    {
        if (static::$salesDHasCostCol === null) {
            static::$salesDHasCostCol = Schema::hasColumn('mpos_sales_d', 'ncost');
        }

        return static::$salesDHasCostCol;
    }

    protected static function hasSalesHCostColumns(): bool
    {
        if (static::$salesHHasCostCols === null) {
            static::$salesHHasCostCols = Schema::hasColumn('mpos_sales_h', 'ntotal_cost')
                && Schema::hasColumn('mpos_sales_h', 'ntotal_profit');
        }

        return static::$salesHHasCostCols;
    }

    protected function resolveCategoryFromRequest(Request $request, ?string $orderType = null): string
    {
        $outletId = (int) ($request->input('nid_outlet') ?? $request->input('outlet_id') ?? 0);
        if ($outletId > 0) {
            return \App\Models\MposOutlet::resolveTransactionTypeForOutlet($outletId) ?? MposTaxSetting::CATEGORY_ONLINE;
        }

        $explicit = strtoupper(trim((string) ($request->input('ctransaction_category') ?? $request->input('category') ?? '')));
        if (in_array($explicit, MposTaxSetting::CATEGORIES, true)) {
            return $explicit;
        }

        if (MposSalesH::isOnlineOrderType($orderType)) {
            return MposTaxSetting::CATEGORY_ONLINE;
        }

        return MposTaxSetting::CATEGORY_OFFLINE;
    }

    /**
     * @return array{0: class-string<MposSalesH|MposSalesoffH>, 1: class-string<MposSalesD|MposSalesoffD>}
     */
    protected function modelClassesForCategory(string $category): array
    {
        if (strtoupper($category) === MposTaxSetting::CATEGORY_OFFLINE) {
            return [MposSalesoffH::class, MposSalesoffD::class];
        }

        return [MposSalesH::class, MposSalesD::class];
    }

    /**
     * @return class-string<MposSalesH|MposSalesoffH>
     */
    protected function headerClassFor(MposSalesH|MposSalesoffH $transaction): string
    {
        return $transaction instanceof MposSalesoffH ? MposSalesoffH::class : MposSalesH::class;
    }

    /**
     * @return class-string<MposSalesD|MposSalesoffD>
     */
    protected function detailClassFor(MposSalesH|MposSalesoffH $transaction): string
    {
        return $transaction instanceof MposSalesoffH ? MposSalesoffD::class : MposSalesD::class;
    }

    protected function categoryForTransaction(MposSalesH|MposSalesoffH $transaction): string
    {
        return $transaction instanceof MposSalesoffH
            ? MposTaxSetting::CATEGORY_OFFLINE
            : MposTaxSetting::CATEGORY_ONLINE;
    }

    /**
     * @param  array<int, string>  $with
     */
    protected function findTransactionByIdOrNo(string $id, ?string $category = null, array $with = [], bool $lockForUpdate = false): MposSalesH|MposSalesoffH|null
    {
        $normalizedCat = strtoupper(trim((string) $category));
        if (in_array($normalizedCat, MposTaxSetting::CATEGORIES, true)) {
            [$headerClass] = $this->modelClassesForCategory($normalizedCat);
            $candidates = [$headerClass];
        } elseif (! is_numeric($id) && str_contains(strtoupper($id), 'OFF')) {
            $candidates = [MposSalesoffH::class, MposSalesH::class];
        } else {
            $candidates = [MposSalesH::class, MposSalesoffH::class];
        }

        foreach ($candidates as $modelClass) {
            $query = $modelClass::query();
            if (! empty($with)) {
                $query->with($with);
            }
            $query->where(function ($q) use ($id): void {
                if (is_numeric($id)) {
                    $q->where('nid', (int) $id)->orWhere('cnotransaction', $id);
                } else {
                    $q->where('cnotransaction', $id);
                }
            });
            if ($lockForUpdate) {
                $query->lockForUpdate();
            }

            $found = $query->first();
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param  class-string<MposSalesH|MposSalesoffH>  $modelClass
     */
    protected function buildFilteredHistoryQuery(string $modelClass, Request $request): Builder
    {
        $query = $modelClass::with([
            'details.product',
            'payment',
            'paymentDual',
            'customer',
            'voucher',
            'posUser.user',
            'outlet',
            'reversals',
            'parentTransaction',
        ]);

        if ($request->filled('nid_outlet')) {
            $query->where('nid_outlet', $request->input('nid_outlet'));
        } elseif ($request->filled('outlet_id')) {
            $query->where('nid_outlet', $request->input('outlet_id'));
        }

        if ($request->filled('cstatus')) {
            $query->where('cstatus', strtoupper($request->input('cstatus')));
        } elseif ($request->filled('status')) {
            $query->where('cstatus', strtoupper($request->input('status')));
        }

        if ($request->filled('cordertype')) {
            $query->where('cordertype', strtoupper($request->input('cordertype')));
        } elseif ($request->filled('order_type')) {
            $query->where('cordertype', strtoupper($request->input('order_type')));
        }

        if ($request->filled('nid_user')) {
            $query->where('nid_user', $request->input('nid_user'));
        }
        if ($request->filled('nid_payment')) {
            $query->where('nid_payment', $request->input('nid_payment'));
        } elseif ($request->filled('payment_id')) {
            $query->where('nid_payment', $request->input('payment_id'));
        }
        if ($request->filled('date')) {
            $query->whereDate(DB::raw('COALESCE(dpayment_date, dtransaction)'), $request->input('date'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate(DB::raw('COALESCE(dpayment_date, dtransaction)'), '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate(DB::raw('COALESCE(dpayment_date, dtransaction)'), '<=', $request->input('end_date'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('cnotransaction', 'like', "%{$search}%")
                    ->orWhere('cname_customer', 'like', "%{$search}%")
                    ->orWhere('ctable', 'like', "%{$search}%");
            });
        }

        return $query->orderBy(DB::raw('COALESCE(dpayment_date, dtransaction)'), 'desc')->orderBy('nid', 'desc');
    }

    public function index(Request $request)
    {
        try {
            // Perbaiki otomatis status transaksi induk di DB jika sebelumnya terubah menjadi VOID/REFUND
            foreach ([
                [MposSalesH::class, 'mpos_sales_h'],
                [MposSalesoffH::class, 'mpos_sls_h'],
            ] as [$headerClass, $tableName]) {
                $headerClass::whereIn('nid', function ($sub) use ($tableName): void {
                    $sub->select('nid_parent_transaction')
                        ->from($tableName)
                        ->whereNotNull('nid_parent_transaction');
                })
                ->whereIn('cstatus', [MposSalesH::STATUS_VOID, MposSalesH::STATUS_REFUND])
                ->update(['cstatus' => MposSalesH::STATUS_COMPLETED]);
            }

            $outletIdFilter = (int) ($request->input('nid_outlet') ?? $request->input('outlet_id') ?? 0);
            if ($outletIdFilter > 0) {
                $outletType = \App\Models\MposOutlet::resolveTransactionTypeForOutlet($outletIdFilter);
                $includeOnline = ($outletType === \App\Models\MposOutlet::TRANSACTION_TYPE_ONLINE);
                $includeOffline = ($outletType === \App\Models\MposOutlet::TRANSACTION_TYPE_OFFLINE);
            } else {
                $categoryFilter = strtoupper(trim((string) ($request->input('ctransaction_category') ?? $request->input('category') ?? 'ALL')));
                $includeOnline = $categoryFilter === 'ALL' || $categoryFilter === MposTaxSetting::CATEGORY_ONLINE;
                $includeOffline = $categoryFilter === 'ALL' || $categoryFilter === MposTaxSetting::CATEGORY_OFFLINE;
            }

            $combined = collect();
            if ($includeOnline) {
                $onlineRows = $this->buildFilteredHistoryQuery(MposSalesH::class, $request)->get()->map(function ($row) {
                    $row->setAttribute('ctransaction_category', MposTaxSetting::CATEGORY_ONLINE);
                    return $row;
                });
                $combined = $combined->concat($onlineRows);
            }
            if ($includeOffline) {
                $offlineRows = $this->buildFilteredHistoryQuery(MposSalesoffH::class, $request)->get()->map(function ($row) {
                    $row->setAttribute('ctransaction_category', MposTaxSetting::CATEGORY_OFFLINE);
                    return $row;
                });
                $combined = $combined->concat($offlineRows);
            }

            $sorted = $combined->sort(function ($a, $b): int {
                $dateA = $a->dpayment_date ?? $a->dtransaction;
                $dateB = $b->dpayment_date ?? $b->dtransaction;
                $tsA = $dateA ? strtotime((string) $dateA) : 0;
                $tsB = $dateB ? strtotime((string) $dateB) : 0;
                if ($tsA !== $tsB) {
                    return $tsB <=> $tsA;
                }

                return ((int) $b->nid) <=> ((int) $a->nid);
            })->values();

            if ($request->boolean('paginate')) {
                $perPage = max(1, (int) $request->input('per_page', 15));
                $page = max(1, (int) $request->input('page', 1));
                $total = $sorted->count();
                $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values();
                $paginator = new LengthAwarePaginator($items, $total, $perPage, $page);

                return response()->json([
                    'success' => true,
                    'message' => 'Riwayat transaksi berhasil diambil',
                    'data' => $paginator->items(),
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                    ],
                ], 200);
            }

            $limit = $request->filled('limit') ? (int) $request->input('limit') : null;
            $transactions = ($limit && $limit > 0) ? $sorted->take($limit)->values() : $sorted;

            return response()->json([
                'success' => true,
                'message' => 'Riwayat transaksi berhasil diambil',
                'data' => $transactions,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil history transaksi: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil riwayat transaksi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, string $id)
    {
        try {
            $category = $request->input('ctransaction_category') ?? $request->input('category');
            $transaction = $this->findTransactionByIdOrNo($id, $category, [
                'details.product',
                'payment',
                'paymentDual',
                'customer',
                'voucher',
                'posUser.user',
                'outlet',
                'reversals',
                'parentTransaction',
            ]);

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan',
                ], 404);
            }

            $transaction->setAttribute('ctransaction_category', $this->categoryForTransaction($transaction));

            return response()->json([
                'success' => true,
                'message' => 'Detail transaksi berhasil diambil',
                'data' => $transaction,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil detail transaksi: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil detail transaksi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nid' => 'nullable|integer',
            'nid_customer' => 'nullable|integer',
            'nid_outlet' => 'required|integer',
            'nid_user' => 'nullable|integer',
            'nid_payment' => 'nullable|integer',
            'nid_payment_dual' => 'nullable|integer',
            'ndp_amount' => 'nullable|numeric|min:0',
            'nid_voucher' => 'nullable|integer',
            'cname_customer' => 'nullable|string|max:255',

            'cordertype' => [
                'required',
                'string',
                'in:' . implode(',', MposSalesH::ORDER_TYPES),
            ],

            'nvisitor' => 'nullable|integer|min:1',
            'ctable' => 'nullable|string|max:100',
            'nqueue' => 'nullable|integer|min:1',

            'ndiscount' => 'nullable|numeric|min:0',
            'ntax' => 'nullable|numeric|min:0',
            'npaid' => 'nullable|numeric|min:0',
            'ctransaction_category' => 'nullable|string|in:ONLINE,OFFLINE,online,offline',
            'btax_exempt' => 'nullable|boolean',

            'cnote' => 'nullable|string|max:1000',

            'details' => 'nullable|array',
            'details.*.nid_product' => 'required_with:details|integer',
            'details.*.nqty' => 'required_with:details|integer|min:1',
            'details.*.cnote' => 'nullable|string|max:500',

            'ccancel_note' => 'nullable|string|max:1000',
            'cstatus' => 'nullable|string|max:50',
            'cpdf_url' => 'nullable|string|max:500',
        ]);

        $authUser = $request->user() ?? Auth::user();

        if (! $authUser) {
            $bearer = $request->bearerToken();
            if ($bearer && str_starts_with($bearer, 'logged_in_')) {
                $userId = (int) substr($bearer, strlen('logged_in_'));
                $authUser = Muser::find($userId);
            }
        }

        $loggedInMposUser = null;
        if ($authUser) {
            $loggedInMposUser = MposUser::where('nid_user', $authUser->nid ?? $authUser->id)->first();
        }

        if (! $loggedInMposUser) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi atau data user POS (mpos_user) tidak valid.',
            ], 401);
        }

        if (! $loggedInMposUser->fowner && ! $loggedInMposUser->fspv && ! $loggedInMposUser->fcashier && ! $loggedInMposUser->fcaptain) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak memiliki hak akses kasir/POS.',
            ], 403);
        }

        $outletId = (int) $validated['nid_outlet'];

        // 2. Resolve "Dilayani Oleh" (Served By) - nid_user from request if provided
        $nidUser = $loggedInMposUser->nid; // default to logged-in user
        if ($request->filled('nid_user')) {
            $requestedNidUser = (int) $request->input('nid_user');
            $servedByMposUser = MposUser::where('nid', $requestedNidUser)->first();

            if ($servedByMposUser) {
                if (! $servedByMposUser->fowner && ! $servedByMposUser->fspv && ! $servedByMposUser->fcashier && ! $servedByMposUser->fcaptain) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User "Dilayani Oleh" tidak memiliki hak akses kasir/POS.',
                    ], 403);
                }
                $nidUser = $servedByMposUser->nid;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User "Dilayani Oleh" tidak valid.',
                ], 422);
            }
        }

        // Cek Shift Aktif (harus berdasarkan user kasir yang sedang login)
        $activeShift = MposShift::where('nid_user', $loggedInMposUser->nid)
            ->where('nid_outlet', $outletId)
            ->where('cstatus', 'OPEN')
            ->first();

        if (! $activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Kasir belum memulai shift.',
            ], 422);
        }

        // Cek apakah Customer ber-tipe Reseller
        $customerId = $validated['nid_customer'] ?? null;
        $isReseller = false;
        $resellerTypeIds = [];

        if (!empty($customerId)) {
            $customer = \App\Models\MposCust::with('type')->find($customerId);
            if ($customer && $customer->type) {
                $typeName = strtoupper(trim((string) $customer->type->cname));
                if ($typeName === 'RESELLER') {
                    $isReseller = true;
                    $resellerTypeIds = \App\Models\MposCustType::whereRaw('UPPER(TRIM(cname)) = ?', ['RESELLER'])
                        ->pluck('nid')
                        ->toArray();
                    if ($customer->type->nid && !in_array($customer->type->nid, $resellerTypeIds)) {
                        $resellerTypeIds[] = $customer->type->nid;
                    }
                }
            }
        }

        $category = $this->resolveCategoryFromRequest($request, $validated['cordertype']);
        [$headerModelClass, $detailModelClass] = $this->modelClassesForCategory($category);

        // 4. Cek apakah ini update DRAFT atau transaksi baru (dan idempotensi)
        $existingDraft = null;
        if (!empty($validated['nid'])) {
            $existingTransaction = $headerModelClass::where('nid', $validated['nid'])->first();
            if ($existingTransaction) {
                if ($existingTransaction->cstatus !== MposSalesH::STATUS_DRAFT) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Transaksi sudah diproses (status: ' . $existingTransaction->cstatus . ') dan tidak dapat diubah.',
                    ], 422);
                }
                $existingDraft = $existingTransaction;
            }
        }

        // 5. Atomic database transaction
        DB::beginTransaction();
        try {
            // Generate Cnotransaction if new (inside DB transaction for atomic locking)
            if (!$existingDraft) {
                $trxData = $this->generateTransactionNumber(
                    (int) $validated['nid_outlet'],
                    $validated['nqueue'] ?? null,
                    $category
                );
                $cnotransaction = $trxData['cnotransaction'];
                $nqueue = $trxData['nqueue'];
            } else {
                $cnotransaction = $existingDraft->cnotransaction;
                $nqueue = $existingDraft->nqueue;
            }
            // 2. Fetch products and validate existence, ACTIVE status & STOCK
            $detailsToInsert = [];
            $nsubtotal = 0;
            $nitem = 0;
            $ntotalCost = 0.0;
            $requestedQtys = [];
            $recipeStockService = app(RecipeStockService::class);
            $hasDetailCostCol = static::hasSalesDCostColumn();

            if ($request->has('details') && is_array($request->details) && count($request->details) > 0) {
                $productIds = collect($validated['details'])->pluck('nid_product')->unique()->all();

                // Use lockForUpdate with category & recipes to ensure stock and tracking integrity
                $products = MposProduct::with(['category', 'recipes.ingredient'])
                    ->whereIn('nid', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('nid');

                $unitCosts = $recipeStockService->calculateUnitCosts($products, (int) $validated['nid_outlet']);

                foreach ($validated['details'] as $item) {
                    $productId = $item['nid_product'];
                    $qty = (int) $item['nqty'];
                    $requestedQtys[$productId] = ($requestedQtys[$productId] ?? 0) + $qty;
                }

                foreach ($validated['details'] as $item) {
                    $productId = $item['nid_product'];
                    $product = $products->get($productId);

                    if (! $product) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Produk dengan ID {$productId} tidak ditemukan.",
                        ], 422);
                    }

                    if (strtoupper((string) $product->cstatus) !== 'ACTIVE') {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Produk '{$product->cname}' sedang tidak aktif dan tidak dapat ditransaksikan.",
                        ], 422);
                    }

                    $orderType = strtoupper((string) ($validated['cordertype'] ?? ''));
                    $isOnlineOrder = MposSalesH::isOnlineOrderType($orderType);
                    $price = (float) $product->nprice;

                    if ($isReseller && !empty($resellerTypeIds)) {
                        $resellerPriceRecord = \App\Models\MposProductPrice::where('nid_product', $product->nid)
                            ->whereIn('nid_cust_type', $resellerTypeIds)
                            ->where(function ($sq) {
                                $sq->where('nqty_start', '<=', 1)
                                   ->orWhereNull('nqty_start');
                            })
                            ->orderBy('nqty_start', 'asc')
                            ->first();

                        if ($resellerPriceRecord && $resellerPriceRecord->nprice !== null) {
                            $price = (float) $resellerPriceRecord->nprice;
                        }
                    } elseif ($isOnlineOrder) {
                        $deliveryCustomPrice = null;
                        if (MposSalesH::isFoodDeliveryOrderType($orderType)) {
                            $deliverySetting = \App\Models\MposDeliveryS::where('nid_outlet', (int) $validated['nid_outlet'])
                                ->where('cplatform', $orderType)
                                ->first();
                            if ($deliverySetting) {
                                $deliveryDetail = \App\Models\MposDeliveryD::where('nid_delivery_s', $deliverySetting->nid)
                                    ->where('nid_product', $product->nid)
                                    ->where('bis_active', true)
                                    ->first();
                                if ($deliveryDetail && (float) $deliveryDetail->nprice > 0) {
                                    $deliveryCustomPrice = (float) $deliveryDetail->nprice;
                                }
                            }
                        }

                        if ($deliveryCustomPrice !== null) {
                            $price = $deliveryCustomPrice;
                        } elseif (!empty($product->nprice_online) && (float) $product->nprice_online > 0) {
                            $price = (float) $product->nprice_online;
                        }
                    }

                    $qty = (int) $item['nqty'];
                    $itemSubtotal = $price * $qty;
                    $unitCost = (float) ($unitCosts[(int) $product->nid] ?? 0.0);

                    $detailRow = [
                        'nid_product' => $product->nid,
                        'cname' => $product->cname,
                        'nqty' => $qty,
                        'nprice' => $price,
                        'nsubtotal' => $itemSubtotal,
                        'cnote' => $item['cnote'] ?? null,
                    ];

                    if ($hasDetailCostCol) {
                        $detailRow['ncost'] = $unitCost;
                    }

                    $detailsToInsert[] = $detailRow;

                    $nsubtotal += $itemSubtotal;
                    $nitem += $qty;
                    $ntotalCost += ($unitCost * $qty);
                }
            }

            // 3. Calculate header totals & tax
            $ndiscount = (float) ($validated['ndiscount'] ?? 0);
            $orderType = strtoupper((string) ($validated['cordertype'] ?? ''));
            $taxSetting = MposTaxSetting::getForOutlet($outletId, $category);

            if ($taxSetting) {
                $taxCalc = $taxSetting->calculateTax(
                    $nsubtotal,
                    $ndiscount,
                    $orderType,
                    (bool) ($validated['btax_exempt'] ?? false)
                );
                $ntax = $taxCalc['ntax'];
                $ctaxMode = $taxCalc['ctax_mode'];
                $ntaxPercent = $taxCalc['ntax_percent'];
                $ndpp = $taxCalc['ndpp'];
                $netSalesForProfit = $taxCalc['net_sales'];
                $ngrandtotal = $taxCalc['ngrandtotal'];
            } elseif ($category === MposTaxSetting::CATEGORY_ONLINE) {
                $ntax = 0.0;
                $ctaxMode = MposTaxSetting::TAX_MODE_NONE;
                $ntaxPercent = 0.0;
                $ndpp = max(0.0, $nsubtotal - $ndiscount);
                $netSalesForProfit = $ndpp;
                $ngrandtotal = $ndpp;
            } else {
                $ntax = (float) ($validated['ntax'] ?? 0);
                $ctaxMode = $ntax > 0 ? MposTaxSetting::TAX_MODE_EXCLUDE : MposTaxSetting::TAX_MODE_NONE;
                $ntaxPercent = 0.0;
                $ndpp = max(0.0, $nsubtotal - $ndiscount);
                $netSalesForProfit = $ndpp;
                $ngrandtotal = max(0.0, $ndpp + $ntax);
            }

            $npaid = (float) ($validated['npaid'] ?? 0);
            $reqStatus = $validated['cstatus'] ?? null;

            $isCashName = function ($cname) {
                $upper = strtoupper(trim((string) $cname));
                return str_contains($upper, 'CASH') || str_contains($upper, 'TUNAI');
            };

            // Cek apakah ada metode pembayaran tunai (CASH) pada pembayaran 1 atau 2
            $hasCashPayment = false;
            if (!empty($validated['nid_payment'])) {
                $payment1 = \App\Models\MposPayment::find($validated['nid_payment']);
                if ($payment1 && $isCashName($payment1->cname)) {
                    $hasCashPayment = true;
                }
            }
            if (!empty($validated['nid_payment_dual'])) {
                $payment2 = \App\Models\MposPayment::find($validated['nid_payment_dual']);
                if ($payment2 && $isCashName($payment2->cname)) {
                    $hasCashPayment = true;
                }
            }
            if (empty($validated['nid_payment']) && empty($validated['nid_payment_dual'])) {
                $hasCashPayment = true;
            }

            $isNonCash = !$hasCashPayment;

            if ($reqStatus === MposSalesH::STATUS_CANCELLED) {
                $nchange = 0;
                $status = MposSalesH::STATUS_CANCELLED;
            } elseif ($reqStatus === MposSalesH::STATUS_DRAFT) {
                $nchange = 0;
                $status = MposSalesH::STATUS_DRAFT;
            } elseif (($validated['cordertype'] ?? '') === MposSalesH::ORDER_TYPE_RESERVATION && $ngrandtotal == 0) {
                $nchange = 0;
                $status = MposSalesH::STATUS_PENDING;
            } elseif ($reqStatus === MposSalesH::STATUS_PAID || $reqStatus === MposSalesH::STATUS_COMPLETED) {
                $status = $reqStatus;
                if ($npaid < $ngrandtotal) {
                    $npaid = $ngrandtotal;
                }
                $nchange = max(0, $npaid - $ngrandtotal);
            } elseif ($isNonCash) {
                // Untuk transaksi non-tunai (QRIS dll.), pembayaran adalah pas/lunas langsung
                if ($npaid < $ngrandtotal) {
                    $npaid = $ngrandtotal;
                }
                $nchange = 0;
                $status = MposSalesH::STATUS_PAID;
            } else {
                if ($npaid >= $ngrandtotal) {
                    $nchange = $npaid - $ngrandtotal;
                    $status = MposSalesH::STATUS_PAID;
                } else {
                    $nchange = 0;
                    $status = MposSalesH::STATUS_PENDING;
                }
            }

            // Stock reduction (produk & bahan baku resep/kombo) saat status transaksi lunas/selesai
            if (($status === MposSalesH::STATUS_PAID || $status === MposSalesH::STATUS_COMPLETED) && !empty($requestedQtys)) {
                $deductResult = $recipeStockService->deductForSale($requestedQtys, (int) $validated['nid_outlet']);
                if (! $deductResult['success']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => $deductResult['message'],
                    ], 422);
                }
            }

            $netSalesAmount = $netSalesForProfit;
            $ntotalCostRounded = round($ntotalCost, 2);
            $ntotalProfitRounded = round($netSalesAmount - $ntotalCostRounded, 2);
            $hasHeaderCostCols = static::hasSalesHCostColumns();

            $ndpAmount = (float) ($validated['ndp_amount'] ?? 0);
            if (($validated['cordertype'] ?? '') === MposSalesH::ORDER_TYPE_RESERVATION && $ndpAmount == 0 && $npaid > 0) {
                $ndpAmount = $npaid;
            }
            $nidPaymentDual = $validated['nid_payment_dual'] ?? null;
            if (($status === MposSalesH::STATUS_PAID || $status === MposSalesH::STATUS_COMPLETED) && ($validated['cordertype'] ?? '') !== MposSalesH::ORDER_TYPE_RESERVATION) {
                $nremainingAmount = 0;
            } else {
                $nremainingAmount = max(0, $ngrandtotal - ($ndpAmount > 0 ? $ndpAmount : $npaid));
            }

            if ($existingDraft) {
                $pdfUrl = $validated['cpdf_url'] ?? $existingDraft->cpdf_url;
                if (!$pdfUrl) {
                    $cleanTrxNo = str_replace('#', '', $existingDraft->cnotransaction);
                    $datePart = $existingDraft->dtransaction ? \Carbon\Carbon::parse($existingDraft->dtransaction)->format('Ymd') : now()->format('Ymd');
                    $pdfUrl = "https://pos.matahaticafe.com/pos/invoice/invoice_{$cleanTrxNo}_{$datePart}.pdf";
                }

                $isPaidOrCompleted = ($status === MposSalesH::STATUS_PAID || $status === MposSalesH::STATUS_COMPLETED);
                $paymentDate = $existingDraft->dpayment_date;
                if ($isPaidOrCompleted && !$paymentDate) {
                    $paymentDate = now();
                }

                $draftUpdateData = [
                    'nid_customer' => $validated['nid_customer'] ?? null,
                    'nid_user' => $nidUser,
                    'nid_shift' => $activeShift->nid,
                    'nid_outlet' => (int) $validated['nid_outlet'],
                    'nid_voucher' => $validated['nid_voucher'] ?? null,
                    'nid_payment' => $validated['nid_payment'] ?? null,
                    'nid_payment_dual' => $nidPaymentDual,
                    'cname_customer' => $validated['cname_customer'] ?? null,
                    'cordertype' => $validated['cordertype'],
                    'nvisitor' => (int) ($validated['nvisitor'] ?? 1),
                    'ctable' => $validated['ctable'] ?? null,
                    'nsubtotal' => $nsubtotal,
                    'ndiscount' => $ndiscount,
                    'ntax' => $ntax,
                    'ngrandtotal' => $ngrandtotal,
                    'npaid' => $npaid,
                    'ndp_amount' => $ndpAmount,
                    'nremaining_amount' => $nremainingAmount,
                    'nchange' => $nchange,
                    'nitem' => $nitem,
                    'cnote' => $validated['cnote'] ?? null,
                    'cstatus' => $status,
                    'ccancel_note' => $validated['ccancel_note'] ?? null,
                    'cpdf_url' => $pdfUrl,
                    'dpayment_date' => $paymentDate,
                ];
                if ($hasHeaderCostCols) {
                    $draftUpdateData['ntotal_cost'] = $ntotalCostRounded;
                    $draftUpdateData['ntotal_profit'] = $ntotalProfitRounded;
                }
                if ($headerModelClass === MposSalesoffH::class) {
                    $draftUpdateData['ctax_mode'] = $ctaxMode;
                    $draftUpdateData['ntax_percent'] = $ntaxPercent;
                    $draftUpdateData['ndpp'] = $ndpp;
                }

                $existingDraft->update($draftUpdateData);
                $salesH = $existingDraft;

                // Hapus detail item lama di detail table
                $detailModelClass::where('nid_transaction', $salesH->nid)->delete();
            } else {
                $isPaidOrCompleted = ($status === MposSalesH::STATUS_PAID || $status === MposSalesH::STATUS_COMPLETED);
                $paymentDate = $isPaidOrCompleted ? now() : null;

                $headerCreateData = [
                    'cnotransaction' => $cnotransaction,
                    'dtransaction' => now(),
                    'dpayment_date' => $paymentDate,
                    'nid_customer' => $validated['nid_customer'] ?? null,
                    'nid_user' => $nidUser,
                    'nid_shift' => $activeShift->nid,
                    'nid_outlet' => (int) $validated['nid_outlet'],
                    'nid_voucher' => $validated['nid_voucher'] ?? null,
                    'nid_payment' => $validated['nid_payment'] ?? null,
                    'nid_payment_dual' => $nidPaymentDual,
                    'cname_customer' => $validated['cname_customer'] ?? null,
                    'nqueue' => $nqueue,
                    'cordertype' => $validated['cordertype'],
                    'nvisitor' => (int) ($validated['nvisitor'] ?? 1),
                    'ctable' => $validated['ctable'] ?? null,
                    'nsubtotal' => $nsubtotal,
                    'ndiscount' => $ndiscount,
                    'ntax' => $ntax,
                    'ngrandtotal' => $ngrandtotal,
                    'npaid' => $npaid,
                    'ndp_amount' => $ndpAmount,
                    'nremaining_amount' => $nremainingAmount,
                    'nchange' => $nchange,
                    'nitem' => $nitem,
                    'cnote' => $validated['cnote'] ?? null,
                    'cstatus' => $status,
                    'ccancel_note' => $validated['ccancel_note'] ?? null,
                    'cpdf_url' => $validated['cpdf_url'] ?? null,
                ];
                if ($hasHeaderCostCols) {
                    $headerCreateData['ntotal_cost'] = $ntotalCostRounded;
                    $headerCreateData['ntotal_profit'] = $ntotalProfitRounded;
                }
                if ($headerModelClass === MposSalesoffH::class) {
                    $headerCreateData['ctax_mode'] = $ctaxMode;
                    $headerCreateData['ntax_percent'] = $ntaxPercent;
                    $headerCreateData['ndpp'] = $ndpp;
                }

                $salesH = $headerModelClass::create($headerCreateData);

                if (empty($salesH->cpdf_url)) {
                    $cleanTrxNo = str_replace('#', '', $salesH->cnotransaction);
                    $datePart = $salesH->dtransaction ? \Carbon\Carbon::parse($salesH->dtransaction)->format('Ymd') : now()->format('Ymd');
                    $salesH->cpdf_url = "https://pos.matahaticafe.com/pos/invoice/invoice_{$cleanTrxNo}_{$datePart}.pdf";
                    $salesH->save();
                }
            }

            foreach ($detailsToInsert as &$detail) {
                $detail['nid_transaction'] = $salesH->nid;
                $detailModelClass::create($detail);
            }

            // Update voucher redeem count if applicable
            if ($salesH->nid_voucher && $salesH->cstatus === MposSalesH::STATUS_PAID) {
                DB::table('mpos_voucher')->where('nid', $salesH->nid_voucher)->increment('nredeem');
            }

            DB::commit();

            // Trigger generate invoice PDF saat transaksi dibuat/dibayar
            try {
                if (class_exists(\App\Services\InvoicePdfService::class)) {
                    app(\App\Services\InvoicePdfService::class)->generate($salesH);
                }
            } catch (\Throwable $e) {
                Log::error('Gagal generate invoice PDF setelah commit: ' . $e->getMessage(), [
                    'exception' => $e,
                    'cnotransaction' => $salesH->cnotransaction,
                ]);
            }

            $salesH->load(['details', 'payment', 'paymentDual', 'outlet', 'posUser.user']);
            $salesH->setAttribute('ctransaction_category', $category);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dibuat',
                'data' => [
                    'transaction' => $salesH,
                    'details' => $salesH->details,
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal membuat transaksi POS: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            $errorMessage = $e->getMessage();
            if ($e instanceof \Illuminate\Database\UniqueConstraintViolationException || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) || str_contains($errorMessage, 'Duplicate entry')) {
                $errorMessage = 'Gagal memproses transaksi: Terjadi bentrokan nomor transaksi. Silakan coba tekan Selesai/Gantung sekali lagi.';
            } else {
                $errorMessage = 'Gagal memproses transaksi: ' . $errorMessage;
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected function generateTransactionNumber(int $outletId, ?int $queue = null, string $category = MposTaxSetting::CATEGORY_ONLINE): array
    {
        [$headerModelClass] = $this->modelClassesForCategory($category);
        $code = strtoupper($category) === MposTaxSetting::CATEGORY_OFFLINE ? 'OFF' : 'C62';
        $prefix = '#' . $outletId . $code;
        $datePart = now()->format('ymd');
        $fullPrefix = $prefix . $datePart;

        if (! $queue) {
            $lastTrx = $headerModelClass::where('cnotransaction', 'like', $fullPrefix . '%')
                ->lockForUpdate()
                ->orderBy('nid', 'desc')
                ->first();

            if ($lastTrx) {
                $lastNumStr = substr($lastTrx->cnotransaction, strlen($fullPrefix));
                $queue = ((int) $lastNumStr) + 1;
            } else {
                $queue = 1;
            }
        }

        do {
            $queueStr = str_pad($queue, 8, '0', STR_PAD_LEFT);
            $cnotransaction = $fullPrefix . $queueStr;
            $exists = $headerModelClass::where('cnotransaction', $cnotransaction)->lockForUpdate()->exists();
            if ($exists) {
                $queue++;
            }
        } while ($exists);

        return [
            'cnotransaction' => $cnotransaction,
            'nqueue' => $queue,
        ];
    }

    public function destroy(Request $request, string $id)
    {
        DB::beginTransaction();
        try {
            $category = $request->input('ctransaction_category') ?? $request->input('category');
            $transaction = $this->findTransactionByIdOrNo($id, $category);
            if (! empty($transaction)) {
                $detailClass = $this->detailClassFor($transaction);
                $detailClass::where('nid_transaction', $transaction->nid)->delete();
                $transaction->delete();
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi berhasil dihapus',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menghapus transaksi: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil riwayat transaksi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function voidTransaction(Request $request, string $id)
    {
        $request->validate([
            'cvoid_note' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $category = $request->input('ctransaction_category') ?? $request->input('category');
            $transaction = $this->findTransactionByIdOrNo($id, $category, ['details']);

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan',
                ], 404);
            }

            $headerClass = $this->headerClassFor($transaction);
            $detailClass = $this->detailClassFor($transaction);

            if (!empty($transaction->nid_parent_transaction)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat membatalkan transaksi pembatalan (Reversal).',
                ], 422);
            }

            $alreadyReversed = $headerClass::where('nid_parent_transaction', $transaction->nid)->exists();
            if ($alreadyReversed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi ini sudah memiliki transaksi pembatalan (Reversal).',
                ], 422);
            }

            $wasStockDeducted = in_array($transaction->cstatus, [MposSalesH::STATUS_PAID, MposSalesH::STATUS_COMPLETED], true);

            // Catat alasan pembatalan pada transaksi asli (tanpa mengubah status cstatus)
            $transaction->cvoid_note = $request->cvoid_note;
            if ($transaction->cstatus === MposSalesH::STATUS_VOID || $transaction->cstatus === MposSalesH::STATUS_REFUND) {
                $transaction->cstatus = MposSalesH::STATUS_COMPLETED;
            }
            $transaction->save();

            // Buat transaksi reversal baru
            $reversalTrxNo = $transaction->cnotransaction . '-VOID';
            $reversalHeaderData = [
                'cnotransaction' => $reversalTrxNo,
                'dtransaction' => now(),
                'nid_parent_transaction' => $transaction->nid,
                'nid_customer' => $transaction->nid_customer,
                'nid_user' => $transaction->nid_user,
                'nid_shift' => $transaction->nid_shift,
                'nid_outlet' => $transaction->nid_outlet,
                'nid_voucher' => $transaction->nid_voucher,
                'nid_payment' => $transaction->nid_payment,
                'cname_customer' => $transaction->cname_customer,
                'nqueue' => $transaction->nqueue,
                'cordertype' => $transaction->cordertype,
                'nvisitor' => $transaction->nvisitor,
                'ctable' => $transaction->ctable,
                'nsubtotal' => -1 * abs((float) $transaction->nsubtotal),
                'ndiscount' => -1 * abs((float) $transaction->ndiscount),
                'ntax' => -1 * abs((float) $transaction->ntax),
                'ngrandtotal' => -1 * abs((float) $transaction->ngrandtotal),
                'npaid' => -1 * abs((float) $transaction->npaid),
                'nchange' => 0,
                'nitem' => -1 * abs((int) $transaction->nitem),
                'cnote' => $transaction->cnote,
                'cstatus' => MposSalesH::STATUS_VOID,
                'cvoid_note' => $request->cvoid_note,
            ];
            if (static::hasSalesHCostColumns()) {
                $reversalHeaderData['ntotal_cost'] = -1 * abs((float) ($transaction->ntotal_cost ?? 0));
                $reversalHeaderData['ntotal_profit'] = -1 * (float) ($transaction->ntotal_profit ?? 0);
            }

            if ($headerClass === MposSalesoffH::class) {
                $reversalHeaderData['ctax_mode'] = $transaction->ctax_mode ?? MposTaxSetting::TAX_MODE_NONE;
                $reversalHeaderData['ntax_percent'] = (float) ($transaction->ntax_percent ?? 0);
                $reversalHeaderData['ndpp'] = -1 * abs((float) ($transaction->ndpp ?? 0));
            }

            $reversalTransaction = $headerClass::create($reversalHeaderData);

            $reversedQtys = [];
            $hasDetailCostCol = static::hasSalesDCostColumn();

            if ($transaction->details) {
                foreach ($transaction->details as $detail) {
                    $revDetailRow = [
                        'nid_transaction' => $reversalTransaction->nid,
                        'nid_product' => $detail->nid_product,
                        'cname' => $detail->cname,
                        'nqty' => -1 * abs((int) $detail->nqty),
                        'nprice' => $detail->nprice,
                        'nsubtotal' => -1 * abs((float) $detail->nsubtotal),
                        'cnote' => $detail->cnote,
                    ];
                    if ($hasDetailCostCol) {
                        $revDetailRow['ncost'] = (float) ($detail->ncost ?? 0);
                    }
                    $detailClass::create($revDetailRow);

                    $remainingQty = max(0, (int) $detail->nqty - (int) ($detail->nqty_void ?? 0) - (int) ($detail->nqty_refund ?? 0));
                    if ($remainingQty > 0 && !empty($detail->nid_product)) {
                        $prodId = (int) $detail->nid_product;
                        $reversedQtys[$prodId] = ($reversedQtys[$prodId] ?? 0) + $remainingQty;
                    }
                }
            }

            if ($wasStockDeducted && !empty($reversedQtys)) {
                app(RecipeStockService::class)->restoreForReversal($reversedQtys, (int) $transaction->nid_outlet);
            }

            if ($transaction->nid_shift) {
                $shift = MposShift::find($transaction->nid_shift);
                if ($shift) {
                    $shift->ncancellation_cash = $shift->ncancellation_cash + abs((float) $transaction->ngrandtotal);
                    $shift->save();
                }
            }

            DB::commit();

            $reversalTransaction->load(['details', 'parentTransaction']);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pembatalan (VOID) berhasil dibuat',
                'data' => $reversalTransaction,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal membatalkan transaksi: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membatalkan transaksi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function refundTransaction(Request $request, string $id)
    {
        $request->validate([
            'crefund_note' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $category = $request->input('ctransaction_category') ?? $request->input('category');
            $transaction = $this->findTransactionByIdOrNo($id, $category, ['details']);

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan',
                ], 404);
            }

            $headerClass = $this->headerClassFor($transaction);
            $detailClass = $this->detailClassFor($transaction);

            if (!empty($transaction->nid_parent_transaction)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat me-refund transaksi pembatalan (Reversal).',
                ], 422);
            }

            $alreadyReversed = $headerClass::where('nid_parent_transaction', $transaction->nid)->exists();
            if ($alreadyReversed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi ini sudah memiliki transaksi pembatalan (Reversal).',
                ], 422);
            }

            $wasStockDeducted = in_array($transaction->cstatus, [MposSalesH::STATUS_PAID, MposSalesH::STATUS_COMPLETED], true);

            // Catat alasan refund pada transaksi asli (tanpa mengubah status cstatus)
            $transaction->crefund_note = $request->crefund_note;
            if ($transaction->cstatus === MposSalesH::STATUS_VOID || $transaction->cstatus === MposSalesH::STATUS_REFUND) {
                $transaction->cstatus = MposSalesH::STATUS_COMPLETED;
            }
            $transaction->save();

            // Buat transaksi reversal refund baru
            $reversalTrxNo = $transaction->cnotransaction . '-REFUND';
            $reversalHeaderData = [
                'cnotransaction' => $reversalTrxNo,
                'dtransaction' => now(),
                'nid_parent_transaction' => $transaction->nid,
                'nid_customer' => $transaction->nid_customer,
                'nid_user' => $transaction->nid_user,
                'nid_shift' => $transaction->nid_shift,
                'nid_outlet' => $transaction->nid_outlet,
                'nid_voucher' => $transaction->nid_voucher,
                'nid_payment' => $transaction->nid_payment,
                'cname_customer' => $transaction->cname_customer,
                'nqueue' => $transaction->nqueue,
                'cordertype' => $transaction->cordertype,
                'nvisitor' => $transaction->nvisitor,
                'ctable' => $transaction->ctable,
                'nsubtotal' => -1 * abs((float) $transaction->nsubtotal),
                'ndiscount' => -1 * abs((float) $transaction->ndiscount),
                'ntax' => -1 * abs((float) $transaction->ntax),
                'ngrandtotal' => -1 * abs((float) $transaction->ngrandtotal),
                'npaid' => -1 * abs((float) $transaction->npaid),
                'nchange' => 0,
                'nitem' => -1 * abs((int) $transaction->nitem),
                'cnote' => $transaction->cnote,
                'cstatus' => MposSalesH::STATUS_REFUND,
                'crefund_note' => $request->crefund_note,
            ];
            if (static::hasSalesHCostColumns()) {
                $reversalHeaderData['ntotal_cost'] = -1 * abs((float) ($transaction->ntotal_cost ?? 0));
                $reversalHeaderData['ntotal_profit'] = -1 * (float) ($transaction->ntotal_profit ?? 0);
            }

            if ($headerClass === MposSalesoffH::class) {
                $reversalHeaderData['ctax_mode'] = $transaction->ctax_mode ?? MposTaxSetting::TAX_MODE_NONE;
                $reversalHeaderData['ntax_percent'] = (float) ($transaction->ntax_percent ?? 0);
                $reversalHeaderData['ndpp'] = -1 * abs((float) ($transaction->ndpp ?? 0));
            }

            $reversalTransaction = $headerClass::create($reversalHeaderData);

            $reversedQtys = [];
            $hasDetailCostCol = static::hasSalesDCostColumn();

            if ($transaction->details) {
                foreach ($transaction->details as $detail) {
                    $revDetailRow = [
                        'nid_transaction' => $reversalTransaction->nid,
                        'nid_product' => $detail->nid_product,
                        'cname' => $detail->cname,
                        'nqty' => -1 * abs((int) $detail->nqty),
                        'nprice' => $detail->nprice,
                        'nsubtotal' => -1 * abs((float) $detail->nsubtotal),
                        'cnote' => $detail->cnote,
                    ];
                    if ($hasDetailCostCol) {
                        $revDetailRow['ncost'] = (float) ($detail->ncost ?? 0);
                    }
                    $detailClass::create($revDetailRow);

                    $remainingQty = max(0, (int) $detail->nqty - (int) ($detail->nqty_void ?? 0) - (int) ($detail->nqty_refund ?? 0));
                    if ($remainingQty > 0 && !empty($detail->nid_product)) {
                        $prodId = (int) $detail->nid_product;
                        $reversedQtys[$prodId] = ($reversedQtys[$prodId] ?? 0) + $remainingQty;
                    }
                }
            }

            if ($wasStockDeducted && !empty($reversedQtys)) {
                app(RecipeStockService::class)->restoreForReversal($reversedQtys, (int) $transaction->nid_outlet);
            }

            if ($transaction->nid_shift) {
                $shift = MposShift::find($transaction->nid_shift);
                if ($shift) {
                    $shift->nrefund_cash = $shift->nrefund_cash + abs((float) $transaction->ngrandtotal);
                    $shift->save();
                }
            }

            DB::commit();

            $reversalTransaction->load(['details', 'parentTransaction']);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi refund (REFUND) berhasil dibuat',
                'data' => $reversalTransaction,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal me-refund transaksi: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat me-refund transaksi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function markFinished(Request $request, string $id)
    {
        try {
            $category = $request->input('ctransaction_category') ?? $request->input('category');
            $transaction = $this->findTransactionByIdOrNo($id, $category);

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan.',
                ], 404);
            }

            $transaction->cstatus = MposSalesH::STATUS_COMPLETED;
            $transaction->save();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil disajikan/selesai.',
                'data' => $transaction,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui status selesai transaksi: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui status transaksi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function voidItems(Request $request, string $id)
    {
        return $this->processItemAction($request, $id, 'VOID');
    }

    public function refundItems(Request $request, string $id)
    {
        return $this->processItemAction($request, $id, 'REFUND');
    }

    protected function processItemAction(Request $request, string $id, string $actionType)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.detail_id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:1',
            'reason' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $category = $request->input('ctransaction_category') ?? $request->input('category');
            $transaction = $this->findTransactionByIdOrNo($id, $category, [], true);

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan',
                ], 404);
            }

            $headerClass = $this->headerClassFor($transaction);
            $detailClass = $this->detailClassFor($transaction);

            if ($transaction->cstatus === MposSalesH::STATUS_VOID || $transaction->cstatus === MposSalesH::STATUS_REFUND || $transaction->cstatus === MposSalesH::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => "Transaksi tidak dapat diproses karena status saat ini: {$transaction->cstatus}",
                ], 422);
            }

            if (!empty($transaction->nid_parent_transaction)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat melakukan pembatalan item pada transaksi Reversal.',
                ], 422);
            }

            $wasStockDeducted = in_array($transaction->cstatus, [MposSalesH::STATUS_PAID, MposSalesH::STATUS_COMPLETED], true);
            $hasDetailCostCol = static::hasSalesDCostColumn();

            $totalActionAmount = 0;
            $totalActionCost = 0.0;
            $totalActionQty = 0;
            $processedItems = [];
            $reversalDetailsToCreate = [];
            $reversedQtys = [];

            foreach ($request->items as $itemReq) {
                $detailId = $itemReq['detail_id'];
                $requestQty = $itemReq['qty'];

                $detail = $detailClass::where('nid', $detailId)
                    ->where('nid_transaction', $transaction->nid)
                    ->first();

                if (! $detail) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Item dengan detail_id {$detailId} bukan bagian dari transaksi ini atau tidak ditemukan.",
                    ], 422);
                }

                $qtyAvailable = $detail->nqty - $detail->nqty_void - $detail->nqty_refund;

                if ($qtyAvailable <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Item '{$detail->cname}' sudah habis diproses sebelumnya.",
                    ], 422);
                }

                if ($requestQty > $qtyAvailable) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Quantity '{$detail->cname}' yang dapat diproses hanya {$qtyAvailable}.",
                    ], 422);
                }

                if ($actionType === 'VOID') {
                    $detail->nqty_void += $requestQty;
                } else {
                    $detail->nqty_refund += $requestQty;
                }
                $detail->save();

                $nprice = (float) $detail->nprice;
                $unitCost = (float) ($detail->ncost ?? 0);
                $nsubtotal = $requestQty * $nprice;

                $totalActionAmount += $nsubtotal;
                $totalActionCost += ($requestQty * $unitCost);
                $totalActionQty += $requestQty;

                if (!empty($detail->nid_product)) {
                    $prodId = (int) $detail->nid_product;
                    $reversedQtys[$prodId] = ($reversedQtys[$prodId] ?? 0) + (int) $requestQty;
                }

                $revDetailRow = [
                    'nid_product' => $detail->nid_product,
                    'cname' => $detail->cname,
                    'nqty' => -1 * abs($requestQty),
                    'nprice' => $nprice,
                    'nsubtotal' => -1 * abs($nsubtotal),
                    'cnote' => $detail->cnote,
                ];
                if ($hasDetailCostCol) {
                    $revDetailRow['ncost'] = $unitCost;
                }
                $reversalDetailsToCreate[] = $revDetailRow;

                $processedItems[] = [
                    'detail_id' => $detail->nid,
                    'qty' => $requestQty,
                    'qty_void' => $detail->nqty_void,
                    'qty_refund' => $detail->nqty_refund,
                    'qty_available' => $detail->nqty - $detail->nqty_void - $detail->nqty_refund,
                ];
            }

            if ($wasStockDeducted && !empty($reversedQtys)) {
                app(RecipeStockService::class)->restoreForReversal($reversedQtys, (int) $transaction->nid_outlet);
            }

            // Buat record Transaksi Partial Reversal Baru
            $existingPartialCount = $headerClass::where('nid_parent_transaction', $transaction->nid)->count();
            $seq = $existingPartialCount + 1;
            $reversalTrxNo = $transaction->cnotransaction . "-PARTIAL-{$seq}";

            $statusToSet = $actionType === 'VOID' ? MposSalesH::STATUS_VOID : MposSalesH::STATUS_REFUND;

            $partialHeaderData = [
                'cnotransaction' => $reversalTrxNo,
                'dtransaction' => now(),
                'nid_parent_transaction' => $transaction->nid,
                'nid_customer' => $transaction->nid_customer,
                'nid_user' => $transaction->nid_user,
                'nid_shift' => $transaction->nid_shift,
                'nid_outlet' => $transaction->nid_outlet,
                'nid_voucher' => $transaction->nid_voucher,
                'nid_payment' => $transaction->nid_payment,
                'cname_customer' => $transaction->cname_customer,
                'nqueue' => $transaction->nqueue,
                'cordertype' => $transaction->cordertype,
                'nvisitor' => $transaction->nvisitor,
                'ctable' => $transaction->ctable,
                'nsubtotal' => -1 * abs($totalActionAmount),
                'ndiscount' => 0,
                'ntax' => 0,
                'ngrandtotal' => -1 * abs($totalActionAmount),
                'npaid' => -1 * abs($totalActionAmount),
                'nchange' => 0,
                'nitem' => -1 * abs($totalActionQty),
                'cnote' => $transaction->cnote,
                'cstatus' => $statusToSet,
                'cvoid_note' => $actionType === 'VOID' ? $request->reason : null,
                'crefund_note' => $actionType === 'REFUND' ? $request->reason : null,
            ];
            if (static::hasSalesHCostColumns()) {
                $partialHeaderData['ntotal_cost'] = -1 * abs(round($totalActionCost, 2));
                $partialHeaderData['ntotal_profit'] = -1 * round($totalActionAmount - $totalActionCost, 2);
            }

            if ($headerClass === MposSalesoffH::class) {
                $partialHeaderData['ctax_mode'] = $transaction->ctax_mode ?? MposTaxSetting::TAX_MODE_NONE;
                $partialHeaderData['ntax_percent'] = (float) ($transaction->ntax_percent ?? 0);
                $partialHeaderData['ndpp'] = -1 * abs($totalActionAmount);
            }

            $reversalTransaction = $headerClass::create($partialHeaderData);

            foreach ($reversalDetailsToCreate as &$revDetail) {
                $revDetail['nid_transaction'] = $reversalTransaction->nid;
                $detailClass::create($revDetail);
            }

            if ($transaction->nid_shift) {
                $shift = MposShift::find($transaction->nid_shift);
                if ($shift) {
                    if ($actionType === 'VOID') {
                        $shift->ncancellation_cash = $shift->ncancellation_cash + $totalActionAmount;
                    } else if ($actionType === 'REFUND') {
                        $shift->nrefund_cash = $shift->nrefund_cash + $totalActionAmount;
                    }
                    $shift->save();
                }
            }

            $this->resolveTransactionItemActionStatus($transaction);

            DB::commit();

            $transaction->refresh();
            $transaction->load(['details', 'reversals']);
            $actionWord = $actionType === 'VOID' ? 'dibatalkan' : 'dikembalikan';

            return response()->json([
                'success' => true,
                'message' => "Item berhasil {$actionWord}.",
                'data' => [
                    'transaction_id' => $transaction->nid,
                    'transaction' => $transaction,
                    'reversal_transaction' => $reversalTransaction,
                    'status' => $transaction->cstatus,
                    'items' => $processedItems,
                ]
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Gagal melakukan {$actionType} item: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => "Terjadi kesalahan saat melakukan {$actionType} item.",
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected function resolveTransactionItemActionStatus(MposSalesH|MposSalesoffH $transaction)
    {
        $detailClass = $this->detailClassFor($transaction);
        $details = $detailClass::where('nid_transaction', $transaction->nid)->get();
        $totalOriginalQty = $details->sum('nqty');
        $totalVoidQty = $details->sum('nqty_void');
        $totalRefundQty = $details->sum('nqty_refund');

        $totalProcessed = $totalVoidQty + $totalRefundQty;
        $totalAvailable = $totalOriginalQty - $totalProcessed;

        if ($totalAvailable == 0) {
            if ($totalVoidQty == $totalOriginalQty) {
                $transaction->cstatus = MposSalesH::STATUS_VOID;
                $transaction->cvoid_note = 'Fully voided by item-level actions';
                $transaction->save();
            } else if ($totalRefundQty == $totalOriginalQty) {
                $transaction->cstatus = MposSalesH::STATUS_REFUND;
                $transaction->crefund_note = 'Fully refunded by item-level actions';
                $transaction->save();
            }
        }
    }

    public function settlePayment(Request $request, string $id)
    {
        $validated = $request->validate([
            'nid_payment' => 'required|integer',
            'namount' => 'required|numeric|min:0.01',
            'creference_no' => 'nullable|string|max:255',
            'cnote' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $category = $request->input('ctransaction_category') ?? $request->input('category');
            $transaction = $this->findTransactionByIdOrNo($id, $category, ['details'], true);

            if (!$transaction) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan.',
                ], 404);
            }

            $wasAlreadyPaid = in_array($transaction->cstatus, [MposSalesH::STATUS_PAID, MposSalesH::STATUS_COMPLETED], true);

            $settleAmount = (float) $validated['namount'];
            $newPaid = (float) $transaction->npaid + $settleAmount;
            $grandTotal = (float) $transaction->ngrandtotal;

            $newRemaining = max(0, $grandTotal - $newPaid);
            $status = ($newRemaining <= 0) ? MposSalesH::STATUS_PAID : MposSalesH::STATUS_PENDING;
            $change = max(0, $newPaid - $grandTotal);

            // Potong stok produk & bahan baku saat transaksi PENDING/DRAFT berubah menjadi PAID
            if ($status === MposSalesH::STATUS_PAID && !$wasAlreadyPaid) {
                $requestedQtys = [];
                foreach ($transaction->details as $detail) {
                    $activeQty = max(0, (int) $detail->nqty - (int) ($detail->nqty_void ?? 0) - (int) ($detail->nqty_refund ?? 0));
                    if ($activeQty > 0 && !empty($detail->nid_product)) {
                        $prodId = (int) $detail->nid_product;
                        $requestedQtys[$prodId] = ($requestedQtys[$prodId] ?? 0) + $activeQty;
                    }
                }

                if (!empty($requestedQtys)) {
                    $deductResult = app(RecipeStockService::class)->deductForSale($requestedQtys, (int) $transaction->nid_outlet);
                    if (! $deductResult['success']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => $deductResult['message'],
                        ], 422);
                    }
                }
            }

            $transaction->update([
                'npaid' => $newPaid,
                'nremaining_amount' => $newRemaining,
                'nchange' => $change,
                'cstatus' => $status,
                'dpayment_date' => ($status === MposSalesH::STATUS_PAID || $status === MposSalesH::STATUS_COMPLETED || $transaction->dpayment_date === null) ? now() : $transaction->dpayment_date,
            ]);

            DB::commit();

            $transaction->load(['details.product', 'payment', 'paymentDual', 'outlet', 'posUser.user']);

            return response()->json([
                'success' => true,
                'message' => 'Pelunasan berhasil diproses',
                'data' => [
                    'transaction' => $transaction,
                    'details' => $transaction->details,
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal memproses pelunasan: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses pelunasan.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
