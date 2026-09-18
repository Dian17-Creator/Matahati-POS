<?php

namespace App\Http\Controllers;

use App\Models\MposProduct;
use App\Models\MposSalesD;
use App\Models\MposSalesH;
use App\Models\MposUser;
use App\Models\Muser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
                'outlet'
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
                    ]
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
                'data' => $transactions
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

    public function show($id)
    {
        try {
            $transaction = MposSalesH::with([
                'details.product',
                'payment',
                'customer',
                'voucher',
                'posUser.user',
                'outlet'
            ])
                ->where(function ($query) use ($id) {
                    if (is_numeric($id)) {
                        $query->where('nid', (int) $id)->orWhere('cnotransaction', $id);
                    } else {
                        $query->where('cnotransaction', $id);
                    }
                })
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail transaksi berhasil diambil',
                'data' => $transaction
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
            'nid_customer' => 'nullable|integer',
            'nid_outlet' => 'required|integer',
            'nid_payment' => 'nullable|integer',
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

            'details' => 'nullable|array',
            'details.*.nid_product' => 'required_with:details|integer',
            'details.*.nqty' => 'required_with:details|integer|min:1',
            'details.*.cnote' => 'nullable|string|max:500',

            'ccancel_note' => 'nullable|string|max:1000',
            'cstatus' => 'nullable|string|max:50',
        ]);

        // 1. Resolve & Authenticate nid_user
        $authUser = $request->user() ?? Auth::user();

        if (!$authUser) {
            $bearer = $request->bearerToken();
            if ($bearer && str_starts_with($bearer, 'logged_in_')) {
                $userId = (int) substr($bearer, strlen('logged_in_'));
                $authUser = Muser::find($userId);
            }
        }

        $mposUser = null;
        if ($authUser) {
            $mposUser = MposUser::where('nid_user', $authUser->nid ?? $authUser->id)->first();
        } elseif ($request->filled('nid_user')) {
            $mposUser = MposUser::find($request->input('nid_user'))
                ?? MposUser::where('nid_user', $request->input('nid_user'))->first();
        }

        if (!$mposUser) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi atau data user POS (mpos_user) tidak valid.',
            ], 401);
        }

        if (!$mposUser->fowner && !$mposUser->fcashier && !$mposUser->fcaptain) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak memiliki hak akses kasir/POS.',
            ], 403);
        }

        $nidUser = $mposUser->nid;

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

                if (!$product) {
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

                $price = (float) $product->nprice;
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

        // 4. Generate nomor transaksi & nomor antrean sesuai format POS (#2C62{YYMMDD}{8-digit sequence})
        $trxData = $this->generateTransactionNumber(
            (int) $validated['nid_outlet'],
            $validated['nqueue'] ?? null
        );
        $cnotransaction = $trxData['cnotransaction'];
        $nqueue = $trxData['nqueue'];

        // 5. Atomic database transaction
        DB::beginTransaction();
        try {
            $salesH = MposSalesH::create([
                'cnotransaction' => $cnotransaction,
                'dtransaction' => now(),
                'nid_customer' => $validated['nid_customer'] ?? null,
                'nid_user' => $nidUser,
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
                'cstatus' => $status,
                'ccancel_note' => $validated['ccancel_note'] ?? null,
            ]);

            foreach ($detailsToInsert as &$detail) {
                $detail['nid_transaction'] = $salesH->nid;
                MposSalesD::create($detail);
            }

            DB::commit();

            $salesH->load('details');

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

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses transaksi.',
            ], 500);
        }
    }

    protected function generateTransactionNumber(int $outletId, ?int $queue = null): array
    {
        $prefix = '#2C62';
        $datePart = now()->format('ymd');

        if (!$queue) {
            $today = now()->format('Y-m-d');
            $maxQueue = MposSalesH::whereDate('dtransaction', $today)
                ->where('nid_outlet', $outletId)
                ->max('nqueue');
            $queue = ($maxQueue ?? 0) + 1;
        }

        $queueStr = str_pad($queue, 8, '0', STR_PAD_LEFT);
        return [
            'cnotransaction' => $prefix . $datePart . $queueStr,
            'nqueue' => $queue,
        ];
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $transaction = MposSalesH::where('nid', $id)->orWhere('cnotransaction', $id)->first();
            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan',
                ], 404);
            }

            MposSalesD::where('nid_transaction', $transaction->nid)->delete();
            $transaction->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dihapus',
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menghapus transaksi: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus transaksi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
