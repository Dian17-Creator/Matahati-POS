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
            ->whereDate('dend', '>=', $today)
            ->where(function ($q) {
                $q->whereNull('nqty')
                  ->orWhereColumn('nredeem', '<', 'nqty');
            });

        if ($request->filled('code')) {
            $query->where('ckode', $request->query('code'));
        }

        $vouchers = $query->orderBy('dend', 'asc')->get();

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
        $this->validateVoucher($request);

        $data = $request->all();
        // Set default redeem to 0 for new vouchers if not provided
        if (!isset($data['nredeem'])) {
            $data['nredeem'] = 0;
        }

        MposVoucher::create($data);

        return redirect()->route('vouchers.index')
            ->with('success', 'Voucher berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $this->validateVoucher($request);

        $voucher = MposVoucher::findOrFail($id);
        $voucher->update($request->all());

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

    private function validateVoucher(Request $request)
    {
        $request->validate([
            'ckode' => 'required|string|max:50',
            'cdesc' => 'nullable|string',
            'dstart' => 'required|date',
            'dend' => 'required|date|after_or_equal:dstart',
            'nqty' => 'required|integer|min:1',
            'nredeem' => 'nullable|integer|min:0|lte:nqty',
            'nmin_spend' => 'nullable|numeric|min:0',
            'cstatus' => 'required|string',
        ], [
            'dend.after_or_equal' => 'Tanggal berakhir tidak boleh lebih kecil dari tanggal mulai.',
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

        $request->validate([
            'ndisc_percent' => 'nullable|numeric|min:0|max:100',
            'ndisc_amount' => 'nullable|numeric|min:0',
        ]);
    }
}
