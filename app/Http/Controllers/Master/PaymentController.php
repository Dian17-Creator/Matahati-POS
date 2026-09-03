<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposPayment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = MposPayment::paginate(10);
        return view('payments.index', compact('payments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
        ]);

        MposPayment::create($request->all());

        return redirect()->route('payments.index')
            ->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
        ]);

        $payment = MposPayment::findOrFail($id);
        $payment->update($request->all());

        return redirect()->route('payments.index')
            ->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $payment = MposPayment::findOrFail($id);
        $payment->delete();

        return redirect()->route('payments.index')
            ->with('success', 'Metode pembayaran berhasil dihapus.');
    }
}
