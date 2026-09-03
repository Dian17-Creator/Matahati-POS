<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposCombo;
use App\Models\MposProduct;
use Illuminate\Http\Request;

class ComboController extends Controller
{
    public function index()
    {
        $combos = MposCombo::with(['comboProduct', 'product'])->paginate(10);
        
        // Hanya produk dengan fcombo = 1
        $comboProducts = MposProduct::where('fcombo', 1)->get();
        // Produk biasa yang menjadi isi kombo
        $regularProducts = MposProduct::where('fcombo', 0)->get();
        
        return view('combos.index', compact('combos', 'comboProducts', 'regularProducts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nid_combo_product' => 'required|exists:mpos_product,nid',
            'nid_product' => 'required|exists:mpos_product,nid',
            'nqty' => 'required|numeric|min:1',
        ]);

        if ($request->nid_combo_product == $request->nid_product) {
            return back()->with('error', 'Produk isi tidak boleh sama dengan produk kombo utama.')->withInput();
        }

        MposCombo::create($request->all());

        return redirect()->route('combos.index')
            ->with('success', 'Isi produk kombo berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nid_combo_product' => 'required|exists:mpos_product,nid',
            'nid_product' => 'required|exists:mpos_product,nid',
            'nqty' => 'required|numeric|min:1',
        ]);

        if ($request->nid_combo_product == $request->nid_product) {
            return back()->with('error', 'Produk isi tidak boleh sama dengan produk kombo utama.')->withInput();
        }

        $combo = MposCombo::findOrFail($id);
        $combo->update($request->all());

        return redirect()->route('combos.index')
            ->with('success', 'Isi produk kombo berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $combo = MposCombo::findOrFail($id);
        $combo->delete();

        return redirect()->route('combos.index')
            ->with('success', 'Isi produk kombo berhasil dihapus.');
    }
}
