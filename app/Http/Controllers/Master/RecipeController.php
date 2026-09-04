<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposRecipe;
use App\Models\MposProduct;
use App\Models\MposIngredients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    public function index()
    {
        $paginatedRecipes = MposRecipe::select('nid_product')
            ->distinct()
            ->paginate(10);
            
        $productIds = $paginatedRecipes->pluck('nid_product');
        
        $recipes = MposRecipe::with(['product', 'ingredient'])
            ->whereIn('nid_product', $productIds)
            ->get()
            ->groupBy('nid_product');
            
        $products = MposProduct::where('fcombo', 0)->get();
        $ingredients = MposIngredients::all();
        
        return view('recipes.index', compact('recipes', 'paginatedRecipes', 'products', 'ingredients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nid_product' => 'required|exists:mpos_product,nid',
            'nid_ingredient' => 'required|array|min:1',
            'nid_ingredient.*' => 'required|exists:mpos_ingredients,nid',
            'nqty' => 'required|array|min:1',
            'nqty.*' => 'required|numeric|min:0.01',
        ]);

        $ingredients = $request->nid_ingredient;
        $qtys = $request->nqty;

        if (count($ingredients) !== count(array_unique($ingredients))) {
            return back()->with('error', 'Terdapat bahan baku yang duplikat untuk resep ini.')->withInput();
        }

        DB::beginTransaction();
        try {
            $exists = MposRecipe::where('nid_product', $request->nid_product)->exists();
            if ($exists) {
                DB::rollBack();
                return back()->with('error', 'Produk ini sudah memiliki resep. Silakan gunakan fitur Edit.')->withInput();
            }

            foreach ($ingredients as $index => $ingredientId) {
                MposRecipe::create([
                    'nid_product' => $request->nid_product,
                    'nid_ingredient' => $ingredientId,
                    'nqty' => $qtys[$index],
                ]);
            }
            DB::commit();
            return redirect()->route('recipes.index')
                ->with('success', 'Resep produk berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nid_product' => 'required|exists:mpos_product,nid',
            'nid_ingredient' => 'required|array|min:1',
            'nid_ingredient.*' => 'required|exists:mpos_ingredients,nid',
            'nqty' => 'required|array|min:1',
            'nqty.*' => 'required|numeric|min:0.01',
        ]);

        $ingredients = $request->nid_ingredient;
        $qtys = $request->nqty;

        if (count($ingredients) !== count(array_unique($ingredients))) {
            return back()->with('error', 'Terdapat bahan baku yang duplikat untuk resep ini.')->withInput();
        }

        DB::beginTransaction();
        try {
            // Hapus semua resep yang lama berdasarkan nid_product
            MposRecipe::where('nid_product', $id)->delete();

            // Insert data resep yang baru
            foreach ($ingredients as $index => $ingredientId) {
                MposRecipe::create([
                    'nid_product' => $request->nid_product,
                    'nid_ingredient' => $ingredientId,
                    'nqty' => $qtys[$index],
                ]);
            }
            DB::commit();
            return redirect()->route('recipes.index')
                ->with('success', 'Bahan resep berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // Hapus seluruh bahan resep
            MposRecipe::where('nid_product', $id)->delete();
            DB::commit();
            return redirect()->route('recipes.index')
                ->with('success', 'Seluruh bahan resep berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
