<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposCust;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = MposCust::paginate(10);
        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'cphone' => 'nullable|string|max:20',
            'cemail' => 'nullable|email|max:255',
            'caddress' => 'nullable|string',
        ]);

        MposCust::create($request->all());

        return redirect()->route('customers.index')
            ->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'cphone' => 'nullable|string|max:20',
            'cemail' => 'nullable|email|max:255',
            'caddress' => 'nullable|string',
        ]);

        $customer = MposCust::findOrFail($id);
        $customer->update($request->all());

        return redirect()->route('customers.index')
            ->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $customer = MposCust::findOrFail($id);
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Pelanggan berhasil dihapus.');
    }
}
