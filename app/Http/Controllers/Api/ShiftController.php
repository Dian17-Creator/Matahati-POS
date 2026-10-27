<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MposCashMovement;
use App\Models\MposSalesH;
use App\Models\MposSalesPayment;
use App\Models\MposShift;
use App\Models\MposUser;
use App\Models\Muser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShiftController extends Controller
{
    /**
     * Helper untuk mendapatkan MposUser dari request/auth.
     */
    protected function getMposUser(Request $request)
    {
        $authUser = null;

        try {
            $authUser = $request->user() ?? Auth::user();
        } catch (\Throwable $e) {
            $authUser = null;
        }

        if (! $authUser) {
            $bearer = $request->bearerToken();
            if ($bearer) {
                if (str_starts_with($bearer, 'logged_in_')) {
                    $userId = (int) substr($bearer, strlen('logged_in_'));
                    $authUser = Muser::find($userId);
                } elseif (is_numeric($bearer)) {
                    $authUser = Muser::find((int) $bearer);
                }
            }
        }

        if ($authUser) {
            $mposUser = MposUser::where('nid_user', $authUser->nid ?? $authUser->id)->first();
            if ($mposUser) {
                return $mposUser;
            }
        }

        // Fallback: check input parameter 'nid_user' or 'user_id'
        $nidUser = $request->input('nid_user') ?? $request->input('user_id');
        if ($nidUser) {
            $mposUser = MposUser::where('nid_user', $nidUser)->first() ?? MposUser::where('nid', $nidUser)->first();
            if ($mposUser) {
                return $mposUser;
            }
        }

        // Ultimate fallback: return first active MposUser
        return MposUser::first();
    }

    public function current(Request $request)
    {
        $mposUser = $this->getMposUser($request);

        if (! $mposUser) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi atau data user POS tidak valid.',
            ], 401);
        }

        $outletId = $request->input('nid_outlet');
        if (! $outletId) {
            return response()->json([
                'success' => false,
                'message' => 'Outlet tidak disertakan.',
            ], 422);
        }

        $shift = MposShift::with(['outlet', 'user.user'])
            ->where('nid_user', $mposUser->nid)
            ->where('nid_outlet', $outletId)
            ->where('cstatus', 'OPEN')
            ->first();

        if (! $shift) {
            return response()->json([
                'success' => true,
                'data' => null,
            ], 200);
        }

        // Hitung real-time total gross sales & cash_sales (transaksi utama)
        $totalSales = MposSalesH::where('nid_shift', $shift->nid)
            ->whereIn('cstatus', [
                MposSalesH::STATUS_PAID,
                MposSalesH::STATUS_COMPLETED,
                MposSalesH::STATUS_VOID,
                MposSalesH::STATUS_REFUND,
                MposSalesH::STATUS_CANCELLED,
            ])
            ->whereNull('nid_parent_transaction')
            ->sum('ngrandtotal');

        $cashSales = $this->calculateShiftCashSales($shift->nid);

        $cashRefund = abs((float) MposSalesH::where('nid_shift', $shift->nid)
            ->where('cstatus', MposSalesH::STATUS_REFUND)
            ->whereHas('payment', function ($query) {
                $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
            })->sum('ngrandtotal'));

        $cashCancellation = abs((float) MposSalesH::where('nid_shift', $shift->nid)
            ->whereIn('cstatus', [MposSalesH::STATUS_VOID, MposSalesH::STATUS_CANCELLED])
            ->whereHas('payment', function ($query) {
                $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
            })->sum('ngrandtotal'));

        $expectedCash = $shift->nopening_cash + $cashSales - $cashRefund - $cashCancellation + $shift->ncash_in - $shift->ncash_out;

        $data = $shift->toArray();
        $data['nsales_cash'] = (float) $totalSales;
        $data['cash_sales'] = (float) $cashSales;
        $data['nexpected_cash'] = (float) $expectedCash;
        $data['ncancellation_cash'] = (float) $cashCancellation;
        $data['nrefund_cash'] = (float) $cashRefund;
        $data = $this->attachShiftMetrics($data, $shift->nid);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function open(Request $request)
    {
        $validated = $request->validate([
            'nid_outlet' => 'required|integer',
            'nopening_cash' => 'required|numeric|min:0',
        ]);

        $mposUser = $this->getMposUser($request);

        if (! $mposUser) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi atau data user POS tidak valid.',
            ], 401);
        }

        $outletId = $validated['nid_outlet'];

        // Cek shift OPEN
        $openShift = MposShift::where('nid_user', $mposUser->nid)
            ->where('nid_outlet', $outletId)
            ->where('cstatus', 'OPEN')
            ->first();

        if ($openShift) {
            return response()->json([
                'success' => false,
                'message' => 'Kasir masih memiliki shift aktif di outlet ini. Tutup shift sebelumnya terlebih dahulu.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Generate cshift_no unik: SH-{YYMMDD}-{sequence}
            $prefix = 'SH-'.now()->format('Ymd').'-';
            $latest = MposShift::where('cshift_no', 'like', $prefix.'%')
                ->orderBy('cshift_no', 'desc')
                ->first();

            $sequence = 1;
            if ($latest) {
                $lastSequence = (int) substr($latest->cshift_no, -4);
                $sequence = $lastSequence + 1;
            }

            do {
                $cshiftNo = $prefix.str_pad($sequence, 4, '0', STR_PAD_LEFT);
                $exists = MposShift::where('cshift_no', $cshiftNo)->exists();
                if ($exists) {
                    $sequence++;
                }
            } while ($exists);

            $shift = MposShift::create([
                'nid_outlet' => $outletId,
                'nid_user' => $mposUser->nid,
                'cshift_no' => $cshiftNo,
                'dopened_at' => now(),
                'dclosed_at' => null,
                'nopening_cash' => $validated['nopening_cash'],
                'nsales_cash' => 0,
                'nrefund_cash' => 0,
                'ncancellation_cash' => 0,
                'ncash_in' => 0,
                'ncash_out' => 0,
                'nexpected_cash' => null,
                'nactual_cash' => null,
                'ndifference' => null,
                'nid_closed_by' => null,
                'cstatus' => 'OPEN',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Shift berhasil dibuka.',
                'data' => $shift,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal membuka shift: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuka shift.',
            ], 500);
        }
    }

    public function cashIn(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        return $this->handleCashMovement($request, 'CASH_IN', $validated['amount']);
    }

    public function cashOut(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        return $this->handleCashMovement($request, 'CASH_OUT', $validated['amount']);
    }

    protected function handleCashMovement(Request $request, $type, $amount)
    {
        $mposUser = $this->getMposUser($request);

        if (! $mposUser) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        $shift = MposShift::where('nid_user', $mposUser->nid)
            ->where('cstatus', 'OPEN')
            ->first();

        if (! $shift) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki shift yang sedang OPEN.',
            ], 422);
        }

        if ($type === 'CASH_IN') {
            $shift->ncash_in += $amount;
        } else {
            $shift->ncash_out += $amount;
        }

        $shift->save();

        return response()->json([
            'success' => true,
            'message' => $type === 'CASH_IN' ? 'Kas masuk berhasil dicatat.' : 'Kas keluar berhasil dicatat.',
            'data' => $shift,
        ], 200);
    }

    public function close(Request $request)
    {
        $validated = $request->validate([
            'nactual_cash' => 'required|numeric|min:0',
        ]);

        $mposUser = $this->getMposUser($request);

        if (! $mposUser) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        DB::beginTransaction();
        try {
            // Gunakan lockForUpdate untuk mencegah race condition
            $shift = MposShift::where('nid_user', $mposUser->nid)
                ->where('cstatus', 'OPEN')
                ->lockForUpdate()
                ->first();

            if (! $shift) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Shift tidak ditemukan atau sudah ditutup sebelumnya.',
                ], 422);
            }

            // Hitung gross sales (seluruh metode pembayaran transaksi utama)
            $totalSales = MposSalesH::where('nid_shift', $shift->nid)
                ->whereIn('cstatus', [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                    MposSalesH::STATUS_VOID,
                    MposSalesH::STATUS_REFUND,
                    MposSalesH::STATUS_CANCELLED,
                ])
                ->whereNull('nid_parent_transaction')
                ->sum('ngrandtotal');

            // Hitung hanya gross cash sales
            $cashSales = MposSalesH::where('nid_shift', $shift->nid)
                ->whereIn('cstatus', [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                    MposSalesH::STATUS_VOID,
                    MposSalesH::STATUS_REFUND,
                    MposSalesH::STATUS_CANCELLED,
                ])
                ->whereNull('nid_parent_transaction')
                ->whereHas('payment', function ($query) {
                    $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
                })->sum('ngrandtotal');

            $cashRefund = abs((float) MposSalesH::where('nid_shift', $shift->nid)
                ->where('cstatus', MposSalesH::STATUS_REFUND)
                ->whereHas('payment', function ($query) {
                    $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
                })->sum('ngrandtotal'));

            $cashCancellation = abs((float) MposSalesH::where('nid_shift', $shift->nid)
                ->whereIn('cstatus', [MposSalesH::STATUS_VOID, MposSalesH::STATUS_CANCELLED])
                ->whereHas('payment', function ($query) {
                    $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
                })->sum('ngrandtotal'));

            $expectedCash = $shift->nopening_cash + $cashSales - $cashRefund - $cashCancellation + $shift->ncash_in - $shift->ncash_out;
            $actualCash = (float) $validated['nactual_cash'];
            $difference = $actualCash - $expectedCash;

            $shift->update([
                'nsales_cash' => $totalSales,
                'nrefund_cash' => $cashRefund,
                'ncancellation_cash' => $cashCancellation,
                'nexpected_cash' => $expectedCash,
                'nactual_cash' => $actualCash,
                'ndifference' => $difference,
                'nid_closed_by' => $mposUser->nid,
                'dclosed_at' => now(),
                'cstatus' => 'CLOSED',
            ]);

            DB::commit();

            $data = $this->attachShiftMetrics($shift->toArray(), $shift->nid);

            return response()->json([
                'success' => true,
                'message' => 'Shift berhasil ditutup.',
                'data' => $data,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menutup shift: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menutup shift.',
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $mposUser = $this->getMposUser($request);

        if (! $mposUser) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        $query = MposShift::with(['outlet'])
            ->where('nid_user', $mposUser->nid)
            ->where('cstatus', 'CLOSED');

        if ($request->filled('nid_outlet')) {
            $query->where('nid_outlet', $request->input('nid_outlet'));
        }

        if ($request->filled('date')) {
            $query->whereDate('dopened_at', $request->input('date'));
        }

        $query->orderBy('dopened_at', 'desc');

        $perPage = (int) $request->input('per_page', 15);
        $shifts = $query->paginate($perPage);

        $shiftItems = collect($shifts->items())->map(function ($item) {
            return $this->attachShiftMetrics($item->toArray(), $item->nid);
        });

        return response()->json([
            'success' => true,
            'message' => 'Riwayat shift berhasil diambil.',
            'data' => $shiftItems,
            'pagination' => [
                'current_page' => $shifts->currentPage(),
                'last_page' => $shifts->lastPage(),
                'per_page' => $shifts->perPage(),
                'total' => $shifts->total(),
            ],
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $mposUser = $this->getMposUser($request);

        if (! $mposUser) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        $shift = MposShift::with(['outlet', 'user.user'])
            ->where('nid', $id)
            ->first();

        if (! $shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift tidak ditemukan.',
            ], 404);
        }

        if ($shift->nid_user !== $mposUser->nid) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke shift ini.',
            ], 403);
        }

        $totalSales = MposSalesH::where('nid_shift', $shift->nid)
            ->whereIn('cstatus', [
                MposSalesH::STATUS_PAID,
                MposSalesH::STATUS_COMPLETED,
                MposSalesH::STATUS_VOID,
                MposSalesH::STATUS_REFUND,
                MposSalesH::STATUS_CANCELLED,
            ])
            ->whereNull('nid_parent_transaction')
            ->sum('ngrandtotal');

        $cashSales = $this->calculateShiftCashSales($shift->nid);

        $data = $shift->toArray();
        $data['nsales_cash'] = $shift->cstatus === 'OPEN' ? (float) $totalSales : (float) $shift->nsales_cash;
        $data['cash_sales'] = (float) $cashSales;

        // expected cash and others are already in shift if closed, or calculated if open
        if ($shift->cstatus === 'OPEN') {
            $cashRefund = abs((float) MposSalesH::where('nid_shift', $shift->nid)
                ->where('cstatus', MposSalesH::STATUS_REFUND)
                ->whereHas('payment', function ($query) {
                    $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
                })->sum('ngrandtotal'));

            $cashCancellation = abs((float) MposSalesH::where('nid_shift', $shift->nid)
                ->whereIn('cstatus', [MposSalesH::STATUS_VOID, MposSalesH::STATUS_CANCELLED])
                ->whereHas('payment', function ($query) {
                    $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
                })->sum('ngrandtotal'));

            $data['ncancellation_cash'] = $cashCancellation;
            $data['nrefund_cash'] = $cashRefund;
            $data['nexpected_cash'] = $shift->nopening_cash + $cashSales - $cashRefund - $cashCancellation + $shift->ncash_in - $shift->ncash_out;
        }

        $data = $this->attachShiftMetrics($data, $shift->nid);

        return response()->json([
            'success' => true,
            'message' => 'Detail shift berhasil diambil.',
            'data' => $data,
        ], 200);
    }

    protected function attachShiftMetrics(array $data, int $shiftId): array
    {
        $validStatuses = [
            MposSalesH::STATUS_PAID,
            MposSalesH::STATUS_COMPLETED,
            MposSalesH::STATUS_VOID,
            MposSalesH::STATUS_CANCELLED,
            MposSalesH::STATUS_REFUND,
        ];

        $totalReceipts = MposSalesH::where('nid_shift', $shiftId)
            ->whereIn('cstatus', [
                MposSalesH::STATUS_PAID,
                MposSalesH::STATUS_COMPLETED,
                MposSalesH::STATUS_VOID,
                MposSalesH::STATUS_REFUND,
                MposSalesH::STATUS_CANCELLED,
            ])
            ->whereNull('nid_parent_transaction')
            ->count();

        $totalPax = (int) MposSalesH::where('nid_shift', $shiftId)
            ->whereIn('cstatus', [
                MposSalesH::STATUS_PAID,
                MposSalesH::STATUS_COMPLETED,
                MposSalesH::STATUS_VOID,
                MposSalesH::STATUS_REFUND,
                MposSalesH::STATUS_CANCELLED,
            ])
            ->whereNull('nid_parent_transaction')
            ->sum('nvisitor');

        $subtotal = (float) MposSalesH::where('nid_shift', $shiftId)
            ->whereIn('cstatus', [
                MposSalesH::STATUS_PAID,
                MposSalesH::STATUS_COMPLETED,
                MposSalesH::STATUS_VOID,
                MposSalesH::STATUS_REFUND,
                MposSalesH::STATUS_CANCELLED,
            ])
            ->whereNull('nid_parent_transaction')
            ->sum('nsubtotal');

        $discount = (float) MposSalesH::where('nid_shift', $shiftId)
            ->whereIn('cstatus', [
                MposSalesH::STATUS_PAID,
                MposSalesH::STATUS_COMPLETED,
                MposSalesH::STATUS_VOID,
                MposSalesH::STATUS_REFUND,
                MposSalesH::STATUS_CANCELLED,
            ])
            ->whereNull('nid_parent_transaction')
            ->sum('ndiscount');

        $cashSales = $this->calculateShiftCashSales($shiftId);

        $cashFullRefundSum = abs((float) MposSalesH::where('nid_shift', $shiftId)
            ->where('cstatus', MposSalesH::STATUS_REFUND)
            ->whereNull('nid_parent_transaction')
            ->whereHas('payment', function ($query) {
                $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
            })->sum('ngrandtotal'));

        $cashReversalRefundSum = abs((float) MposSalesH::where('nid_shift', $shiftId)
            ->where('cstatus', MposSalesH::STATUS_REFUND)
            ->whereNotNull('nid_parent_transaction')
            ->whereHas('payment', function ($query) {
                $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
            })->sum('ngrandtotal'));

        $cashRefund = $cashFullRefundSum + $cashReversalRefundSum;

        $cashFullVoidSum = abs((float) MposSalesH::where('nid_shift', $shiftId)
            ->whereIn('cstatus', [MposSalesH::STATUS_VOID, MposSalesH::STATUS_CANCELLED])
            ->whereNull('nid_parent_transaction')
            ->whereHas('payment', function ($query) {
                $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
            })->sum('ngrandtotal'));

        $cashReversalVoidSum = abs((float) MposSalesH::where('nid_shift', $shiftId)
            ->whereIn('cstatus', [MposSalesH::STATUS_VOID, MposSalesH::STATUS_CANCELLED])
            ->whereNotNull('nid_parent_transaction')
            ->whereHas('payment', function ($query) {
                $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
            })->sum('ngrandtotal'));

        $cashCancellation = $cashFullVoidSum + $cashReversalVoidSum;

        $totalFullVoidSum = abs((float) MposSalesH::where('nid_shift', $shiftId)
            ->whereIn('cstatus', [MposSalesH::STATUS_VOID, MposSalesH::STATUS_CANCELLED])
            ->whereNull('nid_parent_transaction')
            ->sum('ngrandtotal'));

        $totalReversalVoidSum = abs((float) MposSalesH::where('nid_shift', $shiftId)
            ->whereIn('cstatus', [MposSalesH::STATUS_VOID, MposSalesH::STATUS_CANCELLED])
            ->whereNotNull('nid_parent_transaction')
            ->sum('ngrandtotal'));

        $totalCancellation = $totalFullVoidSum + $totalReversalVoidSum;

        $hasSalesPayment = MposSalesPayment::where('nid_shift', $shiftId)->exists();
        if ($hasSalesPayment) {
            $paymentBreakdown = DB::table('mpos_sales_payment')
                ->leftJoin('mpos_payment', 'mpos_sales_payment.nid_payment', '=', 'mpos_payment.nid')
                ->where('mpos_sales_payment.nid_shift', $shiftId)
                ->select(
                    DB::raw("COALESCE(mpos_payment.cname, 'Lainnya') as payment_name"),
                    DB::raw("SUM(CASE WHEN mpos_sales_payment.cstatus = 'PAID' THEN mpos_sales_payment.namount ELSE 0 END) as sales"),
                    DB::raw("SUM(CASE WHEN mpos_sales_payment.cstatus = 'REFUND' THEN ABS(mpos_sales_payment.namount) ELSE 0 END) as refund"),
                    DB::raw("SUM(CASE WHEN mpos_sales_payment.cstatus = 'VOID' THEN ABS(mpos_sales_payment.namount) ELSE 0 END) as void")
                )
                ->groupBy(DB::raw("COALESCE(mpos_payment.cname, 'Lainnya')"))
                ->get()
                ->map(function ($row) {
                    $sales = (float) $row->sales;
                    $refund = (float) $row->refund;
                    $void = (float) $row->void;
                    return [
                        'name' => $row->payment_name,
                        'sales' => $sales,
                        'refund' => $refund,
                        'void' => $void,
                        'net' => max(0, $sales - $refund - $void),
                    ];
                });
        } else {
            $paymentBreakdown = DB::table('mpos_sales_h')
                ->leftJoin('mpos_payment', 'mpos_sales_h.nid_payment', '=', 'mpos_payment.nid')
                ->where('mpos_sales_h.nid_shift', $shiftId)
                ->whereIn('mpos_sales_h.cstatus', $validStatuses)
                ->select(
                    DB::raw("COALESCE(mpos_payment.cname, 'Lainnya') as payment_name"),
                    DB::raw("SUM(CASE WHEN mpos_sales_h.nid_parent_transaction IS NULL AND mpos_sales_h.cstatus IN ('PAID', 'COMPLETED', 'VOID', 'REFUND', 'CANCELLED') THEN mpos_sales_h.ngrandtotal ELSE 0 END) as sales"),
                    DB::raw("SUM(CASE WHEN mpos_sales_h.cstatus = 'REFUND' THEN ABS(mpos_sales_h.ngrandtotal) ELSE 0 END) as refund"),
                    DB::raw("SUM(CASE WHEN mpos_sales_h.cstatus IN ('VOID', 'CANCELLED') THEN ABS(mpos_sales_h.ngrandtotal) ELSE 0 END) as void")
                )
                ->groupBy(DB::raw("COALESCE(mpos_payment.cname, 'Lainnya')"))
                ->get()
                ->map(function ($row) {
                    $sales = (float) $row->sales;
                    $refund = (float) $row->refund;
                    $void = (float) $row->void;
                    return [
                        'name' => $row->payment_name,
                        'sales' => $sales,
                        'refund' => $refund,
                        'void' => $void,
                        'net' => max(0, $sales - $refund - $void),
                    ];
                });
        }

        $data['total_receipts'] = $totalReceipts;
        $data['total_pax'] = $totalPax > 0 ? $totalPax : $totalReceipts;
        $data['subtotal'] = $subtotal;
        $data['discount_amount'] = $discount;
        $data['cash_sales'] = $cashSales;
        $data['cash_refund'] = $cashRefund;
        $data['cash_cancellation'] = $cashCancellation;
        $data['total_cancellation'] = $totalCancellation;
        $data['payment_breakdown'] = $paymentBreakdown;

        return $data;
    }

    /**
     * Menghitung total cash sales untuk shift dengan memprioritaskan mpos_sales_payment.
     */
    protected function calculateShiftCashSales(int $shiftId): float
    {
        $hasSalesPayment = MposSalesPayment::where('nid_shift', $shiftId)->exists();
        if ($hasSalesPayment) {
            return (float) MposSalesPayment::where('nid_shift', $shiftId)
                ->where('cstatus', MposSalesPayment::STATUS_PAID)
                ->whereHas('payment', function ($query) {
                    $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
                })->sum('namount');
        }

        return (float) MposSalesH::where('nid_shift', $shiftId)
            ->whereIn('cstatus', [
                MposSalesH::STATUS_PAID,
                MposSalesH::STATUS_COMPLETED,
                MposSalesH::STATUS_VOID,
                MposSalesH::STATUS_REFUND,
                MposSalesH::STATUS_CANCELLED,
            ])
            ->whereNull('nid_parent_transaction')
            ->whereHas('payment', function ($query) {
                $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
            })->sum('ngrandtotal');
    }
}
