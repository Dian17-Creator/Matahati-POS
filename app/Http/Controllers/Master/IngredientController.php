<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposIngredients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    public function index()
    {
        $ingredients = MposIngredients::paginate(10);
        $type = DB::select('SHOW COLUMNS FROM mpos_ingredients WHERE Field = "csatuan"')[0]->Type;
        preg_match('/^enum\((.*)\)$/', $type, $matches);
        $satuans = [];
        if (isset($matches[1])) {
            foreach(explode(',', $matches[1]) as $value){
                $satuans[] = trim($value, "'");
            }
        }

        return view('ingredients.index', compact('ingredients', 'satuans'));
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

    public function update(Request $request, string $id)
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

    public function destroy(string $id)
    {
        $ingredient = MposIngredients::findOrFail($id);
        $ingredient->delete();

        return redirect()->route('ingredients.index')
            ->with('success', 'Bahan baku berhasil dihapus.');
    }
}
