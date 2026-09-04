<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposGrpProduct;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = MposGrpProduct::paginate(10);
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'cname' => 'required|string|max:255'
        ]);

        MposGrpProduct::create($request->all());

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        $category = MposGrpProduct::findOrFail($id);
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'cname' => 'required|string|max:255'
        ]);

        $category = MposGrpProduct::findOrFail($id);
        $category->update($request->all());

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $category = MposGrpProduct::findOrFail($id);
        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
