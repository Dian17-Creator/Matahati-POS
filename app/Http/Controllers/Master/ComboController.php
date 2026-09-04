<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposCombo;
use App\Models\MposProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComboController extends Controller
{
    public function index()
    {
        // Ambil ID produk kombo secara unik dan pagination
        $paginatedComboProducts = MposCombo::select('nid_combo_product')
            ->distinct()
            ->paginate(10);
            
        // Ambil semua detail isi untuk produk kombo yang tampil di halaman ini
        $comboIds = $paginatedComboProducts->pluck('nid_combo_product');
        
        $combos = MposCombo::with(['comboProduct', 'product'])
            ->whereIn('nid_combo_product', $comboIds)
            ->get()
            ->groupBy('nid_combo_product');
        
        // Hanya produk dengan fcombo = 1
        $comboProducts = MposProduct::where('fcombo', 1)->get();
        // Produk biasa yang menjadi isi kombo
        $regularProducts = MposProduct::where('fcombo', 0)->get();
        
        return view('combos.index', compact('combos', 'paginatedComboProducts', 'comboProducts', 'regularProducts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nid_combo_product' => 'required|exists:mpos_product,nid',
            'nid_product' => 'required|array|min:1',
            'nid_product.*' => 'required|exists:mpos_product,nid',
            'nqty' => 'required|array|min:1',
            'nqty.*' => 'required|numeric|min:1',
        ]);

        $products = $request->nid_product;
        $qtys = $request->nqty;

        if (count($products) !== count(array_unique($products))) {
            return back()->with('error', 'Terdapat produk isi yang duplikat dalam satu kombo.')->withInput();
        }

        if (in_array($request->nid_combo_product, $products)) {
            return back()->with('error', 'Produk kombo utama tidak boleh menjadi isi dari dirinya sendiri.')->withInput();
        }

        DB::beginTransaction();
        try {
            $exists = MposCombo::where('nid_combo_product', $request->nid_combo_product)->exists();
            if ($exists) {
                DB::rollBack();
                return back()->with('error', 'Produk kombo ini sudah memiliki isi. Silakan gunakan fitur Edit.')->withInput();
            }

            foreach ($products as $index => $productId) {
                MposCombo::create([
                    'nid_combo_product' => $request->nid_combo_product,
                    'nid_product' => $productId,
                    'nqty' => $qtys[$index],
                ]);
            }
            DB::commit();
            return redirect()->route('combos.index')
                ->with('success', 'Produk kombo beserta isinya berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nid_combo_product' => 'required|exists:mpos_product,nid',
            'nid_product' => 'required|array|min:1',
            'nid_product.*' => 'required|exists:mpos_product,nid',
            'nqty' => 'required|array|min:1',
            'nqty.*' => 'required|numeric|min:1',
        ]);

        $products = $request->nid_product;
        $qtys = $request->nqty;

        if (count($products) !== count(array_unique($products))) {
            return back()->with('error', 'Terdapat produk isi yang duplikat dalam satu kombo.')->withInput();
        }

        if (in_array($request->nid_combo_product, $products)) {
            return back()->with('error', 'Produk kombo utama tidak boleh menjadi isi dari dirinya sendiri.')->withInput();
        }

        DB::beginTransaction();
        try {
            // Hapus semua isi kombo yang lama berdasarkan nid_combo_product
            MposCombo::where('nid_combo_product', $id)->delete();

            // Insert data isi kombo yang baru
            foreach ($products as $index => $productId) {
                MposCombo::create([
                    'nid_combo_product' => $request->nid_combo_product,
                    'nid_product' => $productId,
                    'nqty' => $qtys[$index],
                ]);
            }
            DB::commit();
            return redirect()->route('combos.index')
                ->with('success', 'Isi produk kombo berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            // Hapus seluruh isi kombo
            MposCombo::where('nid_combo_product', $id)->delete();
            DB::commit();
            return redirect()->route('combos.index')
                ->with('success', 'Seluruh isi produk kombo berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
