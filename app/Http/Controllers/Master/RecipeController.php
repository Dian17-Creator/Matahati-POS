<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposRecipe;
use App\Models\MposProduct;
use App\Models\MposIngredients;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = MposRecipe::with(['product', 'ingredient'])->paginate(10);
        $products = MposProduct::where('fcombo', 0)->get();
        $ingredients = MposIngredients::all();
        
        return view('recipes.index', compact('recipes', 'products', 'ingredients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nid_product' => 'required|exists:mpos_product,nid',
            'nid_ingredient' => 'required|exists:mpos_ingredients,nid',
            'nqty' => 'required|numeric|min:0.01',
        ]);

        MposRecipe::create($request->all());

        return redirect()->route('recipes.index')
            ->with('success', 'Bahan resep berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nid_product' => 'required|exists:mpos_product,nid',
            'nid_ingredient' => 'required|exists:mpos_ingredients,nid',
            'nqty' => 'required|numeric|min:0.01',
        ]);

        $recipe = MposRecipe::findOrFail($id);
        $recipe->update($request->all());

        return redirect()->route('recipes.index')
            ->with('success', 'Bahan resep berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $recipe = MposRecipe::findOrFail($id);
        $recipe->delete();

        return redirect()->route('recipes.index')
            ->with('success', 'Bahan resep berhasil dihapus.');
    }
}
