<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposVoucher;
use App\Traits\ResolvesOutletScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    use ResolvesOutletScope;

    public function apiIndex(Request $request)
    {
        $today = now()->toDateString();

        $query = MposVoucher::whereRaw('LOWER(cstatus) = ?', ['active'])
            ->whereDate('dstart', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('dend')
                  ->orWhereDate('dend', '>=', $today);
            })
            ->where(function ($q) {
                $q->whereNull('nqty')
                  ->orWhereColumn('nredeem', '<', 'nqty');
            });

        $outletId = $request->input('nid_outlet') ?? $request->input('outlet_id');
        if ($outletId !== null && $outletId !== '') {
            $query->where(function ($q) use ($outletId) {
                $q->whereNull('nid_outlet')
                  ->orWhere('nid_outlet', (int) $outletId);
            });
        }

        if ($request->filled('code')) {
            $query->where('ckode', $request->query('code'));
        }

        $vouchers = $query
            ->orderByRaw('CASE WHEN dend IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('dend', 'asc')
            ->get()
            ->map(function (MposVoucher $voucher): MposVoucher {
                $voucher->setAttribute('is_unlimited_date', is_null($voucher->dend));
                $voucher->setAttribute('is_unlimited_qty', is_null($voucher->nqty));

                // Ensure non-null values in API JSON so APK models/filters expecting date & int do not fail
                if (is_null($voucher->dend)) {
                    $voucher->dend = '2099-12-31';
                }

                if (is_null($voucher->nqty)) {
                    $voucher->nqty = 999999;
                }

                if (is_null($voucher->nredeem)) {
                    $voucher->nredeem = 0;
                }

                return $voucher;
            });

        return response()->json([
            'success' => true,
            'message' => 'Daftar voucher tersedia berhasil diambil.',
            'data' => $vouchers
        ], 200);
    }

    public function index(Request $request)
    {
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $isSuperAdmin = $this->isSuperAdminUser();
        $outlets = $this->getAvailableOutlets();
        $allowedOutletIds = $this->getUserAllowedOutletIds();

        $query = MposVoucher::with('outlet')
            ->leftJoin('mpos_outlet', 'mpos_voucher.nid_outlet', '=', 'mpos_outlet.nid')
            ->select('mpos_voucher.*');

        if ($selectedOutletId !== null) {
            $query->where(function ($q) use ($selectedOutletId) {
                $q->whereNull('mpos_voucher.nid_outlet')
                  ->orWhere('mpos_voucher.nid_outlet', $selectedOutletId);
            });
        } elseif (! $isSuperAdmin) {
            $query->where(function ($q) use ($allowedOutletIds) {
                $q->whereNull('mpos_voucher.nid_outlet');
                if (! empty($allowedOutletIds)) {
                    $q->orWhereIn('mpos_voucher.nid_outlet', $allowedOutletIds);
                }
            });
        }

        $vouchers = $query
            ->orderBy('mpos_voucher.ckode', 'asc')
            ->orderByRaw('CASE WHEN mpos_voucher.nid_outlet IS NULL THEN 0 ELSE 1 END ASC')
            ->orderBy('mpos_outlet.cname', 'asc')
            ->orderBy('mpos_voucher.nid', 'desc')
            ->paginate(10);

        return view('vouchers.index', compact('vouchers', 'outlets', 'selectedOutletId', 'isSuperAdmin'));
    }

    public function store(Request $request)
    {
        [$data, $targetOutlets] = $this->validateVoucher($request);

        DB::transaction(function () use ($data, $targetOutlets): void {
            foreach ($targetOutlets as $outletId) {
                MposVoucher::create(array_merge($data, [
                    'nid_outlet' => $outletId,
                ]));
            }
        });

        $createdCount = count($targetOutlets);
        $message = $createdCount > 1
            ? "Voucher berhasil ditambahkan untuk {$createdCount} outlet."
            : 'Voucher berhasil ditambahkan.';

        return redirect()->route('vouchers.index')
            ->with('success', $message);
    }

    public function update(Request $request, string $id)
    {
        $voucher = MposVoucher::findOrFail($id);
        [$data, $targetOutlets] = $this->validateVoucher($request, $voucher);

        DB::transaction(function () use ($voucher, $data, $targetOutlets): void {
            $primaryOutletId = $targetOutlets[0];
            $voucher->update(array_merge($data, [
                'nid_outlet' => $primaryOutletId,
            ]));

            // If user checked additional outlets during edit, create new rows for those outlets
            foreach (array_slice($targetOutlets, 1) as $extraOutletId) {
                $alreadyExists = MposVoucher::where('ckode', $data['ckode'])
                    ->where('nid_outlet', $extraOutletId)
                    ->exists();

                if (! $alreadyExists) {
                    MposVoucher::create(array_merge($data, [
                        'nid_outlet' => $extraOutletId,
                        'nredeem' => 0,
                    ]));
                }
            }
        });

        return redirect()->route('vouchers.index')
            ->with('success', 'Voucher berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $voucher = MposVoucher::findOrFail($id);
        $voucher->delete();

        return redirect()->route('vouchers.index')
            ->with('success', 'Voucher berhasil dihapus.');
    }

    /**
     * Validate and normalize voucher payload, supporting unlimited end date/qty and multi-outlet selection.
     *
     * @return array{0: array<string, mixed>, 1: array<int, int|null>}
     */
    private function validateVoucher(Request $request, ?MposVoucher $existingVoucher = null): array
    {
        $isUnlimitedEndDate = $request->boolean('unlimited_dend');
        $isUnlimitedQty = $request->boolean('unlimited_nqty');
        $allOutlets = $request->boolean('all_outlets');

        $validated = $request->validate([
            'all_outlets' => 'nullable|boolean',
            'outlet_ids' => 'nullable|array',
            'outlet_ids.*' => 'integer|exists:mpos_outlet,nid',
            'ckode' => 'required|string|max:50',
            'cdesc' => 'nullable|string',
            'dstart' => 'required|date',
            'dend' => $isUnlimitedEndDate ? 'nullable|date' : 'required|date|after_or_equal:dstart',
            'nqty' => $isUnlimitedQty ? 'nullable|integer|min:1' : 'required|integer|min:1',
            'nredeem' => $isUnlimitedQty ? 'nullable|integer|min:0' : 'nullable|integer|min:0|lte:nqty',
            'nmin_spend' => 'nullable|numeric|min:0',
            'cstatus' => 'required|string',
            'ndisc_percent' => 'nullable|numeric|min:0|max:100',
            'ndisc_amount' => 'nullable|numeric|min:0',
        ], [
            'dend.required' => 'Tanggal berakhir wajib diisi jika tidak memilih tanpa kadaluarsa.',
            'dend.after_or_equal' => 'Tanggal berakhir tidak boleh lebih kecil dari tanggal mulai.',
            'nqty.required' => 'Total kuota wajib diisi jika tidak memilih unlimited.',
            'nredeem.lte' => 'Jumlah redeem tidak boleh lebih besar dari total kuota (nqty).',
        ]);

        $outletIds = array_values(array_unique(array_map('intval', $validated['outlet_ids'] ?? [])));

        if (! $allOutlets && empty($outletIds)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'outlet_ids' => 'Pilih minimal 1 outlet atau centang Semua Outlet.',
            ]);
        }

        if (! $this->isSuperAdminUser()) {
            $allowedOutletIds = $this->getUserAllowedOutletIds();
            if ($allOutlets && count($allowedOutletIds) <= 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'outlet_ids' => 'Anda tidak memiliki akses ke Semua Outlet.',
                ]);
            }

            foreach ($outletIds as $oid) {
                if (! in_array($oid, $allowedOutletIds, true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'outlet_ids' => 'Anda tidak memiliki akses ke salah satu outlet yang dipilih.',
                    ]);
                }
            }
        }

        $targetOutlets = $allOutlets ? [null] : $outletIds;

        foreach ($targetOutlets as $targetOutletId) {
            $duplicateQuery = MposVoucher::whereRaw('LOWER(ckode) = ?', [strtolower($validated['ckode'])]);
            if ($targetOutletId === null) {
                $duplicateQuery->whereNull('nid_outlet');
            } else {
                $duplicateQuery->where('nid_outlet', $targetOutletId);
            }

            if ($existingVoucher) {
                $duplicateQuery->where('nid', '!=', $existingVoucher->nid);
            }

            if ($duplicateQuery->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'ckode' => 'Kode voucher sudah digunakan pada outlet yang dipilih.',
                ]);
            }
        }

        // Custom validation for discount rules
        $hasPercent = !empty($request->ndisc_percent) && $request->ndisc_percent > 0;
        $hasAmount = !empty($request->ndisc_amount) && $request->ndisc_amount > 0;

        if ($hasPercent && $hasAmount) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'ndisc_percent' => 'Voucher hanya boleh menggunakan salah satu jenis diskon (Persen ATAU Nominal).',
                'ndisc_amount' => 'Voucher hanya boleh menggunakan salah satu jenis diskon (Persen ATAU Nominal).',
            ]);
        }

        if (!$hasPercent && (!$hasAmount)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'ndisc_percent' => 'Minimal salah satu diskon (Persen atau Nominal) harus diisi.',
            ]);
        }

        $payload = [
            'ckode' => $validated['ckode'],
            'cdesc' => $validated['cdesc'] ?? null,
            'dstart' => $validated['dstart'],
            'dend' => $isUnlimitedEndDate ? null : $validated['dend'],
            'nqty' => $isUnlimitedQty ? null : (int) $validated['nqty'],
            'nredeem' => isset($validated['nredeem']) && $validated['nredeem'] !== ''
                ? (int) $validated['nredeem']
                : ($existingVoucher?->nredeem ?? 0),
            'nmin_spend' => $validated['nmin_spend'] ?? 0,
            'ndisc_percent' => $validated['ndisc_percent'] ?? 0,
            'ndisc_amount' => $validated['ndisc_amount'] ?? 0,
            'cstatus' => $validated['cstatus'],
        ];

        return [$payload, $targetOutlets];
    }
}
