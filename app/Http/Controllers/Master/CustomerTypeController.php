<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposCustType;
use Illuminate\Http\Request;

class CustomerTypeController extends Controller
{
    public function index()
    {
        $customerTypes = MposCustType::paginate(10);
        return view('customer-types.index', compact('customerTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cname' => 'required|string|max:100',
        ]);

        MposCustType::create($request->all());

        return redirect()->route('customer-types.index')
            ->with('success', 'Kategori pelanggan berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'cname' => 'required|string|max:100',
        ]);

        $customerType = MposCustType::findOrFail($id);
        $customerType->update($request->all());

        return redirect()->route('customer-types.index')
            ->with('success', 'Kategori pelanggan berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $customerType = MposCustType::findOrFail($id);
        
        // Prevent deleting if there are customers using this type
        if ($customerType->customers()->exists()) {
            return redirect()->route('customer-types.index')
                ->with('error', 'Kategori tidak dapat dihapus karena sudah digunakan oleh pelanggan.');
        }

        $customerType->delete();

        return redirect()->route('customer-types.index')
            ->with('success', 'Kategori pelanggan berhasil dihapus.');
    }
}
