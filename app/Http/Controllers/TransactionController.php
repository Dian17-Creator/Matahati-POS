<?php

namespace App\Http\Controllers;

use App\Models\MposProduct;
use App\Models\MposSalesD;
use App\Models\MposSalesH;
use App\Models\MposShift;
use App\Models\MposUser;
use App\Models\Muser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = MposSalesH::with([
                'details.product',
                'payment',
                'customer',
                'voucher',
                'posUser.user',
                'outlet',
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
                $query->whereDate('dtransaction', $request->input('date'));
            }

            if ($request->filled('start_date')) {
                $query->whereDate('dtransaction', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('dtransaction', '<=', $request->input('end_date'));
            }

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('cnotransaction', 'like', "%{$search}%")
                        ->orWhere('cname_customer', 'like', "%{$search}%")
                        ->orWhere('ctable', 'like', "%{$search}%");
                });
            }

            $query->orderBy('dtransaction', 'desc')->orderBy('nid', 'desc');

            if ($request->boolean('paginate')) {
                $perPage = (int) $request->input('per_page', 15);
                $transactions = $query->paginate($perPage);

                return response()->json([
                    'success' => true,
                    'message' => 'Riwayat transaksi berhasil diambil',
                    'data' => $transactions->items(),
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'last_page' => $transactions->lastPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                    ],
                ], 200);
            }

            $limit = $request->filled('limit') ? (int) $request->input('limit') : null;
            if ($limit && $limit > 0) {
                $transactions = $query->limit($limit)->get();
            } else {
                $transactions = $query->get();
            }

            return response()->json([
                'success' => true,
                'message' => 'Riwayat transaksi berhasil diambil',
                'data' => $transactions,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil history transaksi: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil riwayat transaksi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $transaction = MposSalesH::with([
                'details.product',
                'payment',
                'customer',
                'voucher',
                'posUser.user',
                'outlet',
            ])
                ->where(function ($query) use ($id) {
                    if (is_numeric($id)) {
                        $query->where('nid', (int) $id)->orWhere('cnotransaction', $id);
                    } else {
                        $query->where('cnotransaction', $id);
                    }
                })
                ->first();

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail transaksi berhasil diambil',
                'data' => $transaction,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil detail transaksi: '.$e->getMessage(), [
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
            'nid_voucher' => 'nullable|integer',
            'cname_customer' => 'nullable|string|max:255',

            'cordertype' => [
                'required',
                'string',
                'in:'.implode(',', MposSalesH::ORDER_TYPES),
            ],

            'nvisitor' => 'nullable|integer|min:1',
            'ctable' => 'nullable|string|max:100',
            'nqueue' => 'nullable|integer|min:1',

            'ndiscount' => 'nullable|numeric|min:0',
            'ntax' => 'nullable|numeric|min:0',
            'npaid' => 'nullable|numeric|min:0',

            'cnote' => 'nullable|string|max:1000',

            'details' => 'nullable|array',
            'details.*.nid_product' => 'required_with:details|integer',
            'details.*.nqty' => 'required_with:details|integer|min:1',
            'details.*.cnote' => 'nullable|string|max:500',

            'ccancel_note' => 'nullable|string|max:1000',
            'cstatus' => 'nullable|string|max:50',
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

        if (! $loggedInMposUser->fowner && ! $loggedInMposUser->fcashier && ! $loggedInMposUser->fcaptain) {
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
            $servedByMposUser = MposUser::where('nid', $requestedNidUser)
                ->where('nid_outlet', $outletId)
                ->first();

            if ($servedByMposUser) {
                if (! $servedByMposUser->fowner && ! $servedByMposUser->fcashier && ! $servedByMposUser->fcaptain) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User "Dilayani Oleh" tidak memiliki hak akses kasir/POS.',
                    ], 403);
                }
                $nidUser = $servedByMposUser->nid;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User "Dilayani Oleh" tidak valid atau tidak bertugas di outlet ini.',
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
        $resellerTypeId = null;

        if (!empty($customerId)) {
            $customer = \App\Models\MposCust::with('type')->find($customerId);
            if ($customer && $customer->type) {
                $typeName = strtoupper(trim((string) $customer->type->cname));
                if ($typeName === 'RESELLER') {
                    $isReseller = true;
                    $resellerTypeId = $customer->type->nid;
                }
            }
        }

        // 2. Fetch products and validate existence & ACTIVE status
        $detailsToInsert = [];
        $nsubtotal = 0;
        $nitem = 0;

        if ($request->has('details') && is_array($request->details) && count($request->details) > 0) {
            $productIds = collect($validated['details'])->pluck('nid_product')->unique()->all();
            $products = MposProduct::whereIn('nid', $productIds)->get()->keyBy('nid');

            foreach ($validated['details'] as $item) {
                $productId = $item['nid_product'];
                $product = $products->get($productId);

                if (! $product) {
                    return response()->json([
                        'success' => false,
                        'message' => "Produk dengan ID {$productId} tidak ditemukan.",
                    ], 422);
                }

                if (strtoupper((string) $product->cstatus) !== 'ACTIVE') {
                    return response()->json([
                        'success' => false,
                        'message' => "Produk '{$product->cname}' sedang tidak aktif dan tidak dapat ditransaksikan.",
                    ], 422);
                }

                $orderType = strtoupper((string) ($validated['cordertype'] ?? ''));
                $isOnlineOrder = ($orderType === 'ONLINE');

                $price = (float) $product->nprice;

                if ($isReseller && $resellerTypeId) {
                    $resellerPriceRecord = \App\Models\MposProductPrice::where('nid_product', $product->nid)
                        ->where('nid_cust_type', $resellerTypeId)
                        ->where('nqty_start', 1)
                        ->first();

                    if ($resellerPriceRecord && $resellerPriceRecord->nprice !== null) {
                        $price = (float) $resellerPriceRecord->nprice;
                    }
                } elseif ($isOnlineOrder && !empty($product->nprice_online) && (float) $product->nprice_online > 0) {
                    $price = (float) $product->nprice_online;
                }

                $qty = (int) $item['nqty'];
                $itemSubtotal = $price * $qty;

                $detailsToInsert[] = [
                    'nid_product' => $product->nid,
                    'cname' => $product->cname,
                    'nqty' => $qty,
                    'nprice' => $price,
                    'nsubtotal' => $itemSubtotal,
                    'cnote' => $item['cnote'] ?? null,
                ];

                $nsubtotal += $itemSubtotal;
                $nitem += $qty;
            }
        }

        // 3. Calculate header totals
        $ndiscount = (float) ($validated['ndiscount'] ?? 0);
        $ntax = (float) ($validated['ntax'] ?? 0);
        $ngrandtotal = max(0, $nsubtotal - $ndiscount + $ntax);

        $npaid = (float) ($validated['npaid'] ?? 0);
        $reqStatus = $validated['cstatus'] ?? null;

        if ($reqStatus === MposSalesH::STATUS_CANCELLED) {
            $nchange = 0;
            $status = MposSalesH::STATUS_CANCELLED;
        } elseif ($reqStatus === MposSalesH::STATUS_DRAFT) {
            $nchange = 0;
            $status = MposSalesH::STATUS_DRAFT;
        } else {
            if ($npaid >= $ngrandtotal) {
                $nchange = $npaid - $ngrandtotal;
                $status = MposSalesH::STATUS_PAID;
            } else {
                $nchange = 0;
                $status = MposSalesH::STATUS_PENDING;
            }
        }

        // 4. Cek apakah ini update DRAFT atau transaksi baru
        $existingDraft = null;
        if (!empty($validated['nid'])) {
            $existingDraft = MposSalesH::where('nid', $validated['nid'])
                ->where('cstatus', MposSalesH::STATUS_DRAFT)
                ->first();
        }

        if (!$existingDraft) {
            // Generate nomor transaksi & nomor antrean sesuai format POS (#2C62{YYMMDD}{8-digit sequence})
            $trxData = $this->generateTransactionNumber(
                (int) $validated['nid_outlet'],
                $validated['nqueue'] ?? null
            );
            $cnotransaction = $trxData['cnotransaction'];
            $nqueue = $trxData['nqueue'];
        } else {
            $cnotransaction = $existingDraft->cnotransaction;
            $nqueue = $existingDraft->nqueue;
        }

        // 5. Atomic database transaction
        DB::beginTransaction();
        try {
            if ($existingDraft) {
                $existingDraft->update([
                    'nid_customer' => $validated['nid_customer'] ?? null,
                    'nid_user' => $nidUser,
                    'nid_shift' => $activeShift->nid,
                    'nid_outlet' => (int) $validated['nid_outlet'],
                    'nid_voucher' => $validated['nid_voucher'] ?? null,
                    'nid_payment' => $validated['nid_payment'] ?? null,
                    'cname_customer' => $validated['cname_customer'] ?? null,
                    'cordertype' => $validated['cordertype'],
                    'nvisitor' => (int) ($validated['nvisitor'] ?? 1),
                    'ctable' => $validated['ctable'] ?? null,
                    'nsubtotal' => $nsubtotal,
                    'ndiscount' => $ndiscount,
                    'ntax' => $ntax,
                    'ngrandtotal' => $ngrandtotal,
                    'npaid' => $npaid,
                    'nchange' => $nchange,
                    'nitem' => $nitem,
                    'cnote' => $validated['cnote'] ?? null,
                    'cstatus' => $status,
                    'ccancel_note' => $validated['ccancel_note'] ?? null,
                ]);
                $salesH = $existingDraft;

                // Hapus detail item lama di mpos_sales_d
                MposSalesD::where('nid_transaction', $salesH->nid)->delete();
            } else {
                $salesH = MposSalesH::create([
                    'cnotransaction' => $cnotransaction,
                    'dtransaction' => now(),
                    'nid_customer' => $validated['nid_customer'] ?? null,
                    'nid_user' => $nidUser,
                    'nid_shift' => $activeShift->nid,
                    'nid_outlet' => (int) $validated['nid_outlet'],
                    'nid_voucher' => $validated['nid_voucher'] ?? null,
                    'nid_payment' => $validated['nid_payment'] ?? null,
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
                    'nchange' => $nchange,
                    'nitem' => $nitem,
                    'cnote' => $validated['cnote'] ?? null,
                    'cstatus' => $status,
                    'ccancel_note' => $validated['ccancel_note'] ?? null,
                ]);
            }

            foreach ($detailsToInsert as &$detail) {
                $detail['nid_transaction'] = $salesH->nid;
                MposSalesD::create($detail);
            }

            // Update voucher redeem count if applicable
            if ($salesH->nid_voucher && $salesH->cstatus === MposSalesH::STATUS_PAID) {
                DB::table('mpos_voucher')->where('nid', $salesH->nid_voucher)->increment('nredeem');
            }

            DB::commit();

            $salesH->load(['details', 'outlet', 'posUser.user']);

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
            Log::error('Gagal membuat transaksi POS: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses transaksi.',
            ], 500);
        }
    }

    protected function generateTransactionNumber(int $outletId, ?int $queue = null): array
    {
        $prefix = '#'.$outletId.'C62';
        $datePart = now()->format('ymd');

        if (! $queue) {
            $maxQueue = MposSalesH::where('cnotransaction', 'like', $prefix.$datePart.'%')->max('nqueue');
            $queue = ((int) $maxQueue) + 1;
        }

        do {
            $queueStr = str_pad($queue, 8, '0', STR_PAD_LEFT);
            $cnotransaction = $prefix.$datePart.$queueStr;
            $exists = MposSalesH::where('cnotransaction', $cnotransaction)->exists();
            if ($exists) {
                $queue++;
            }
        } while ($exists);

        return [
            'cnotransaction' => $cnotransaction,
            'nqueue' => $queue,
        ];
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $transaction = MposSalesH::where('nid', $id)->orWhere('cnotransaction', $id)->first();
            if (! empty($transaction)) {
                MposSalesD::where('nid_transaction', $transaction->nid)->delete();
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
            Log::error('Gagal menghapus transaksi: '.$e->getMessage(), [
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
            $transaction = MposSalesH::where('nid', $id)->orWhere('cnotransaction', $id)->first();

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan',
                ], 404);
            }

            if ($transaction->cstatus === MposSalesH::STATUS_VOID || $transaction->cstatus === MposSalesH::STATUS_REFUND) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi sudah dibatalkan atau di-refund.',
                ], 422);
            }

            $hasItemActions = MposSalesD::where('nid_transaction', $transaction->nid)
                ->where(function ($q) {
                    $q->where('nqty_void', '>', 0)->orWhere('nqty_refund', '>', 0);
                })->exists();

            if ($hasItemActions) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi sudah memiliki proses Void/Refund per item dan tidak dapat diproses sebagai transaksi penuh.',
                ], 422);
            }

            $transaction->cstatus = MposSalesH::STATUS_VOID;
            $transaction->cvoid_note = $request->cvoid_note;
            $transaction->save();

            if ($transaction->nid_shift) {
                $shift = MposShift::find($transaction->nid_shift);
                if ($shift) {
                    $shift->ncancellation_cash = $shift->ncancellation_cash + $transaction->ngrandtotal;
                    $shift->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dibatalkan (VOID)',
                'data' => $transaction,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal membatalkan transaksi: '.$e->getMessage(), [
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
            $transaction = MposSalesH::where('nid', $id)->orWhere('cnotransaction', $id)->first();

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan',
                ], 404);
            }

            if ($transaction->cstatus === MposSalesH::STATUS_VOID || $transaction->cstatus === MposSalesH::STATUS_REFUND) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi sudah dibatalkan atau di-refund.',
                ], 422);
            }

            $hasItemActions = MposSalesD::where('nid_transaction', $transaction->nid)
                ->where(function ($q) {
                    $q->where('nqty_void', '>', 0)->orWhere('nqty_refund', '>', 0);
                })->exists();

            if ($hasItemActions) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi sudah memiliki proses Void/Refund per item dan tidak dapat diproses sebagai transaksi penuh.',
                ], 422);
            }

            $transaction->cstatus = MposSalesH::STATUS_REFUND;
            $transaction->crefund_note = $request->crefund_note;
            $transaction->save();

            if ($transaction->nid_shift) {
                $shift = MposShift::find($transaction->nid_shift);
                if ($shift) {
                    $shift->nrefund_cash = $shift->nrefund_cash + $transaction->ngrandtotal;
                    $shift->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil di-refund',
                'data' => $transaction,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal me-refund transaksi: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat me-refund transaksi.',
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
            $transaction = MposSalesH::where('nid', $id)
                ->orWhere('cnotransaction', $id)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan',
                ], 404);
            }

            if ($transaction->cstatus === MposSalesH::STATUS_VOID || $transaction->cstatus === MposSalesH::STATUS_REFUND || $transaction->cstatus === MposSalesH::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => "Transaksi tidak dapat diproses karena status saat ini: {$transaction->cstatus}",
                ], 422);
            }

            $totalActionAmount = 0;
            $processedItems = [];

            foreach ($request->items as $itemReq) {
                $detailId = $itemReq['detail_id'];
                $requestQty = $itemReq['qty'];

                $detail = MposSalesD::where('nid', $detailId)
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

                $nprice = $detail->nprice;
                $nsubtotal = $requestQty * $nprice;

                $totalActionAmount += $nsubtotal;

                $processedItems[] = [
                    'detail_id' => $detail->nid,
                    'qty' => $requestQty,
                    'qty_void' => $detail->nqty_void,
                    'qty_refund' => $detail->nqty_refund,
                    'qty_available' => $detail->nqty - $detail->nqty_void - $detail->nqty_refund,
                ];
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
            $actionWord = $actionType === 'VOID' ? 'dibatalkan' : 'dikembalikan';

            return response()->json([
                'success' => true,
                'message' => "Item berhasil {$actionWord}.",
                'data' => [
                    'transaction_id' => $transaction->nid,
                    'status' => $transaction->cstatus,
                    'items' => $processedItems,
                ]
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Gagal melakukan {$actionType} item: ".$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => "Terjadi kesalahan saat melakukan {$actionType} item.",
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected function resolveTransactionItemActionStatus(MposSalesH $transaction)
    {
        $details = MposSalesD::where('nid_transaction', $transaction->nid)->get();
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
}
