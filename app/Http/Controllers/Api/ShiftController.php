<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MposCashMovement;
use App\Models\MposSalesH;
use App\Models\MposSalesoffH;
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

        $category = \App\Models\MposOutlet::resolveTransactionTypeForOutlet((int) $shift->nid_outlet)
            ?? ($request->input('ctransaction_category') ?? $request->input('category'));

        // Hitung real-time total gross sales & cash_sales
        $totalSales = $this->calculateShiftTotalSales($shift->nid, $category);
        $cashSales = $this->calculateShiftCashSales($shift->nid, $category);
        $cashRefund = $this->calculateShiftCashRefund($shift->nid, $category);
        $cashCancellation = $this->calculateShiftCashCancellation($shift->nid, $category);

        $expectedCash = $shift->nopening_cash + $cashSales - $cashRefund - $cashCancellation + $shift->ncash_in - $shift->ncash_out;

        $data = $shift->toArray();
        $data['nsales_cash'] = (float) $totalSales;
        $data['cash_sales'] = (float) $cashSales;
        $data['nexpected_cash'] = (float) $expectedCash;
        $data['ncancellation_cash'] = (float) $cashCancellation;
        $data['nrefund_cash'] = (float) $cashRefund;
        $data = $this->attachShiftMetrics($data, $shift->nid, $category);

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
            $prefix = 'SH-' . now()->format('Ymd') . '-';
            $latest = MposShift::where('cshift_no', 'like', $prefix . '%')
                ->orderBy('cshift_no', 'desc')
                ->first();

            $sequence = 1;
            if ($latest) {
                $lastSequence = (int) substr($latest->cshift_no, -4);
                $sequence = $lastSequence + 1;
            }

            do {
                $cshiftNo = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
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
            Log::error('Gagal membuka shift: ' . $e->getMessage(), ['exception' => $e]);

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

            $category = \App\Models\MposOutlet::resolveTransactionTypeForOutlet((int) $shift->nid_outlet)
            ?? ($request->input('ctransaction_category') ?? $request->input('category'));

            $totalSales = $this->calculateShiftTotalSales($shift->nid, $category);
            $cashSales = $this->calculateShiftCashSales($shift->nid, $category);
            $cashRefund = $this->calculateShiftCashRefund($shift->nid, $category);
            $cashCancellation = $this->calculateShiftCashCancellation($shift->nid, $category);

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

            $data = $this->attachShiftMetrics($shift->toArray(), $shift->nid, $category);

            return response()->json([
                'success' => true,
                'message' => 'Shift berhasil ditutup.',
                'data' => $data,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menutup shift: ' . $e->getMessage(), ['exception' => $e]);

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

        $outletFilter = $request->input('nid_outlet') ?? $request->input('outlet_id');
        if ($outletFilter) {
            $query->where('nid_outlet', $outletFilter);
        }

        if ($request->filled('date')) {
            $query->whereDate('dopened_at', $request->input('date'));
        }

        $query->orderBy('dopened_at', 'desc');

        $perPage = (int) $request->input('per_page', 15);
        $shifts = $query->paginate($perPage);

        $category = \App\Models\MposOutlet::resolveTransactionTypeForOutlet((int) $shift->nid_outlet)
            ?? ($request->input('ctransaction_category') ?? $request->input('category'));

        $shiftItems = collect($shifts->items())->map(function ($item) use ($category) {
            return $this->attachShiftMetrics($item->toArray(), $item->nid, $category);
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

        $category = \App\Models\MposOutlet::resolveTransactionTypeForOutlet((int) $shift->nid_outlet)
            ?? ($request->input('ctransaction_category') ?? $request->input('category'));

        $totalSales = $this->calculateShiftTotalSales($shift->nid, $category);
        $cashSales = $this->calculateShiftCashSales($shift->nid, $category);

        $data = $shift->toArray();
        $data['nsales_cash'] = $shift->cstatus === 'OPEN' ? (float) $totalSales : (float) $shift->nsales_cash;
        $data['cash_sales'] = (float) $cashSales;

        if ($shift->cstatus === 'OPEN') {
            $cashRefund = $this->calculateShiftCashRefund($shift->nid, $category);
            $cashCancellation = $this->calculateShiftCashCancellation($shift->nid, $category);

            $data['ncancellation_cash'] = $cashCancellation;
            $data['nrefund_cash'] = $cashRefund;
            $data['nexpected_cash'] = $shift->nopening_cash + $cashSales - $cashRefund - $cashCancellation + $shift->ncash_in - $shift->ncash_out;
        }

        $data = $this->attachShiftMetrics($data, $shift->nid, $category);

        return response()->json([
            'success' => true,
            'message' => 'Detail shift berhasil diambil.',
            'data' => $data,
        ], 200);
    }

    protected function attachShiftMetrics(array $data, int $shiftId, ?string $category = null): array
    {
        $validStatuses = [
            MposSalesH::STATUS_PAID,
            MposSalesH::STATUS_COMPLETED,
            MposSalesH::STATUS_VOID,
            MposSalesH::STATUS_CANCELLED,
            MposSalesH::STATUS_REFUND,
        ];

        if (empty($category) && !empty($data['nid_outlet'])) {
            $category = \App\Models\MposOutlet::resolveTransactionTypeForOutlet((int) $data['nid_outlet']);
        }

        $cat = strtoupper(trim((string) $category));

        $totalReceipts = (int) $this->sumAcrossSalesTables($shiftId, function ($modelClass) use ($shiftId, $validStatuses): float {
            return (float) $modelClass::where('nid_shift', $shiftId)
                ->whereIn('cstatus', $validStatuses)
                ->whereNull('nid_parent_transaction')
                ->count();
        }, $category);

        $totalPax = (int) $this->sumAcrossSalesTables($shiftId, function ($modelClass) use ($shiftId, $validStatuses): float {
            return (float) $modelClass::where('nid_shift', $shiftId)
                ->whereIn('cstatus', $validStatuses)
                ->whereNull('nid_parent_transaction')
                ->sum('nvisitor');
        }, $category);

        $subtotal = $this->sumAcrossSalesTables($shiftId, function ($modelClass) use ($shiftId, $validStatuses): float {
            return (float) $modelClass::where('nid_shift', $shiftId)
                ->whereIn('cstatus', $validStatuses)
                ->whereNull('nid_parent_transaction')
                ->sum('nsubtotal');
        }, $category);

        $discount = $this->sumAcrossSalesTables($shiftId, function ($modelClass) use ($shiftId, $validStatuses): float {
            return (float) $modelClass::where('nid_shift', $shiftId)
                ->whereIn('cstatus', $validStatuses)
                ->whereNull('nid_parent_transaction')
                ->sum('ndiscount');
        }, $category);

        $cashSales = $this->calculateShiftCashSales($shiftId, $category);
        $cashRefund = $this->calculateShiftCashRefund($shiftId, $category);
        $cashCancellation = $this->calculateShiftCashCancellation($shiftId, $category);

        $totalCancellation = $this->sumAcrossSalesTables($shiftId, function ($modelClass) use ($shiftId): float {
            return abs((float) $modelClass::where('nid_shift', $shiftId)
                ->whereIn('cstatus', [MposSalesH::STATUS_VOID, MposSalesH::STATUS_CANCELLED])
                ->sum('ngrandtotal'));
        }, $category);

        $onlineQuery = DB::table('mpos_sales_h as sh')
            ->leftJoin('mpos_payment as p', 'sh.nid_payment', '=', 'p.nid')
            ->where('sh.nid_shift', $shiftId)
            ->whereIn('sh.cstatus', $validStatuses)
            ->select(
                DB::raw("COALESCE(p.cname, 'Lainnya') as payment_name"),
                DB::raw("CASE WHEN sh.nid_parent_transaction IS NULL AND sh.cstatus IN ('PAID', 'COMPLETED', 'VOID', 'REFUND', 'CANCELLED') THEN sh.ngrandtotal ELSE 0 END as sales_amt"),
                DB::raw("CASE WHEN sh.cstatus = 'REFUND' THEN ABS(sh.ngrandtotal) ELSE 0 END as refund_amt"),
                DB::raw("CASE WHEN sh.cstatus IN ('VOID', 'CANCELLED') THEN ABS(sh.ngrandtotal) ELSE 0 END as void_amt")
            );

        $offlineQuery = DB::table('mpos_sls_h as soh')
            ->leftJoin('mpos_payment as p', 'soh.nid_payment', '=', 'p.nid')
            ->where('soh.nid_shift', $shiftId)
            ->whereIn('soh.cstatus', $validStatuses)
            ->select(
                DB::raw("COALESCE(p.cname, 'Lainnya') as payment_name"),
                DB::raw("CASE WHEN soh.nid_parent_transaction IS NULL AND soh.cstatus IN ('PAID', 'COMPLETED', 'VOID', 'REFUND', 'CANCELLED') THEN soh.ngrandtotal ELSE 0 END as sales_amt"),
                DB::raw("CASE WHEN soh.cstatus = 'REFUND' THEN ABS(soh.ngrandtotal) ELSE 0 END as refund_amt"),
                DB::raw("CASE WHEN soh.cstatus IN ('VOID', 'CANCELLED') THEN ABS(soh.ngrandtotal) ELSE 0 END as void_amt")
            );

        if ($cat === 'OFFLINE') {
            $unioned = $offlineQuery;
        } elseif ($cat === 'ONLINE') {
            $unioned = $onlineQuery;
        } else {
            $unioned = $onlineQuery->unionAll($offlineQuery);
        }

        $paymentBreakdown = DB::query()
            ->fromSub($unioned, 'combined_sales')
            ->select(
                'payment_name',
                DB::raw('SUM(sales_amt) as sales'),
                DB::raw('SUM(refund_amt) as refund'),
                DB::raw('SUM(void_amt) as void')
            )
            ->groupBy('payment_name')
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

        $offlineSalesTotal = (float) MposSalesoffH::where('nid_shift', $shiftId)
            ->whereIn('cstatus', $validStatuses)
            ->whereNull('nid_parent_transaction')
            ->sum('ngrandtotal');

        $offlineTaxTotal = (float) MposSalesoffH::where('nid_shift', $shiftId)
            ->whereIn('cstatus', $validStatuses)
            ->whereNull('nid_parent_transaction')
            ->sum('ntax');

        $onlineSalesTotal = (float) MposSalesH::where('nid_shift', $shiftId)
            ->whereIn('cstatus', $validStatuses)
            ->whereNull('nid_parent_transaction')
            ->sum('ngrandtotal');

        $data['total_receipts'] = $totalReceipts;
        $data['total_pax'] = $totalPax > 0 ? $totalPax : $totalReceipts;
        $data['subtotal'] = $subtotal;
        $data['discount_amount'] = $discount;
        $data['cash_sales'] = $cashSales;
        $data['cash_refund'] = $cashRefund;
        $data['cash_cancellation'] = $cashCancellation;
        $data['total_cancellation'] = $totalCancellation;
        $data['offline_sales_total'] = $offlineSalesTotal;
        $data['offline_tax_total'] = $offlineTaxTotal;
        $data['online_sales_total'] = $onlineSalesTotal;
        $data['payment_breakdown'] = $paymentBreakdown;

        return $data;
    }

    /**
     * @return array<int, class-string<MposSalesH|MposSalesoffH>>
     */
    protected function salesModels(?string $category = null): array
    {
        $cat = strtoupper(trim((string) $category));
        if ($cat === 'OFFLINE') {
            return [MposSalesoffH::class];
        }
        if ($cat === 'ONLINE') {
            return [MposSalesH::class];
        }
        return [MposSalesH::class, MposSalesoffH::class];
    }

    protected function sumAcrossSalesTables(int $shiftId, callable $callback, ?string $category = null): float
    {
        $total = 0.0;
        foreach ($this->salesModels($category) as $modelClass) {
            $total += (float) $callback($modelClass, $shiftId);
        }

        return $total;
    }

    protected function calculateShiftTotalSales(int $shiftId, ?string $category = null): float
    {
        return $this->sumAcrossSalesTables($shiftId, function ($modelClass) use ($shiftId): float {
            return (float) $modelClass::where('nid_shift', $shiftId)
                ->whereIn('cstatus', [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                    MposSalesH::STATUS_VOID,
                    MposSalesH::STATUS_REFUND,
                    MposSalesH::STATUS_CANCELLED,
                ])
                ->whereNull('nid_parent_transaction')
                ->sum('ngrandtotal');
        }, $category);
    }

    /**
     * Menghitung total cash sales untuk shift dari mpos_sales_h dan mpos_sls_h.
     */
    protected function calculateShiftCashSales(int $shiftId, ?string $category = null): float
    {
        return $this->sumAcrossSalesTables($shiftId, function ($modelClass) use ($shiftId): float {
            return (float) $modelClass::where('nid_shift', $shiftId)
                ->whereIn('cstatus', [
                    MposSalesH::STATUS_PAID,
                    MposSalesH::STATUS_COMPLETED,
                    MposSalesH::STATUS_VOID,
                    MposSalesH::STATUS_REFUND,
                    MposSalesH::STATUS_CANCELLED,
                ])
                ->whereNull('nid_parent_transaction')
                ->whereHas('payment', function ($query): void {
                    $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
                })
                ->sum('ngrandtotal');
        }, $category);
    }

    protected function calculateShiftCashRefund(int $shiftId, ?string $category = null): float
    {
        return $this->sumAcrossSalesTables($shiftId, function ($modelClass) use ($shiftId): float {
            return abs((float) $modelClass::where('nid_shift', $shiftId)
                ->where('cstatus', MposSalesH::STATUS_REFUND)
                ->whereHas('payment', function ($query): void {
                    $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
                })
                ->sum('ngrandtotal'));
        }, $category);
    }

    protected function calculateShiftCashCancellation(int $shiftId, ?string $category = null): float
    {
        return $this->sumAcrossSalesTables($shiftId, function ($modelClass) use ($shiftId): float {
            return abs((float) $modelClass::where('nid_shift', $shiftId)
                ->whereIn('cstatus', [MposSalesH::STATUS_VOID, MposSalesH::STATUS_CANCELLED])
                ->whereHas('payment', function ($query): void {
                    $query->whereRaw('LOWER(cname) LIKE ?', ['%cash%']);
                })
                ->sum('ngrandtotal'));
        }, $category);
    }
}
