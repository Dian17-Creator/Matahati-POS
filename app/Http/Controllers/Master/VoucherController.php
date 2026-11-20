<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposVoucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
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

    public function index()
    {
        $vouchers = MposVoucher::paginate(10);
        return view('vouchers.index', compact('vouchers'));
    }

    public function store(Request $request)
    {
        $data = $this->validateVoucher($request);
        MposVoucher::create($data);

        return redirect()->route('vouchers.index')
            ->with('success', 'Voucher berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $voucher = MposVoucher::findOrFail($id);
        $data = $this->validateVoucher($request, $voucher);
        $voucher->update($data);

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
     * Validate and normalize voucher payload, supporting unlimited end date and quantity.
     *
     * @return array<string, mixed>
     */
    private function validateVoucher(Request $request, ?MposVoucher $existingVoucher = null): array
    {
        $isUnlimitedEndDate = $request->boolean('unlimited_dend');
        $isUnlimitedQty = $request->boolean('unlimited_nqty');

        $validated = $request->validate([
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

        return [
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
    }
}
