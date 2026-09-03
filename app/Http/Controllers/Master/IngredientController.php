<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposIngredients;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function index()
    {
        $ingredients = MposIngredients::paginate(10);
        return view('ingredients.index', compact('ingredients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'csatuan' => 'required|string|max:50',
            'nstock' => 'required|numeric|min:0',
        ]);

        MposIngredients::create($request->all());

        return redirect()->route('ingredients.index')
            ->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'csatuan' => 'required|string|max:50',
            'nstock' => 'required|numeric|min:0',
        ]);

        $ingredient = MposIngredients::findOrFail($id);
        $ingredient->update($request->all());

        return redirect()->route('ingredients.index')
            ->with('success', 'Bahan baku berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $ingredient = MposIngredients::findOrFail($id);
        $ingredient->delete();

        return redirect()->route('ingredients.index')
            ->with('success', 'Bahan baku berhasil dihapus.');
    }
}
