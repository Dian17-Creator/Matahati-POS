<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposProduct;
use App\Models\MposGrpProduct;
use App\Models\MposRecipe;
use App\Models\MposIngredients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function apiIndex()
    {
        $products = MposProduct::with('category')->get();
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function index()
    {
        $products = MposProduct::with(['category', 'recipes.ingredient'])->paginate(10);
        $categories = MposGrpProduct::all();
        $ingredients = MposIngredients::all();

        return view('products.index', compact('products', 'categories', 'ingredients'));
    }

    public function store(Request $request)
    {
        $messages = [
            'nid_ingredient.required_with' => 'Anda mengaktifkan fitur Resep, mohon tambahkan minimal 1 Bahan Baku.',
            'nqty.required_with' => 'Kuantitas bahan baku harus diisi.',
            'nid_ingredient.*.exists' => 'Bahan baku yang dipilih tidak valid.',
            'nqty.*.numeric' => 'Kuantitas harus berupa angka.',
        ];

        $request->validate([
            'cname' => 'required|string|max:255',
            'nid_category' => 'required|exists:mpos_grp_product,nid',
            'nprice' => 'required|numeric|min:0',
            'cstatus' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'has_recipe' => 'nullable',
            'nid_ingredient' => 'required_with:has_recipe|array',
            'nid_ingredient.*' => 'required_with:has_recipe|exists:mpos_ingredients,nid',
            'nqty' => 'required_with:has_recipe|array',
            'nqty.*' => 'required_with:has_recipe|numeric|min:0.01',
        ], $messages);

        $data = $request->except(['photo', 'has_recipe', 'nid_ingredient', 'nqty', 'modal_id']);
        $data['fcombo'] = $request->has('fcombo') ? 1 : 0;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/product'), $filename);
            $data['cphotos'] = 'uploads/product/' . $filename;
        }

        DB::beginTransaction();
        try {
            $product = MposProduct::create($data);

            if ($request->has('has_recipe')) {
                $ingredients = $request->nid_ingredient ?? [];
                $qtys = $request->nqty ?? [];

                if (count($ingredients) !== count(array_unique($ingredients))) {
                    DB::rollBack();
                    return back()->with('error', 'Terdapat bahan baku yang duplikat untuk resep ini.')->withInput();
                }

                foreach ($ingredients as $index => $ingredientId) {
                    MposRecipe::create([
                        'nid_product' => $product->nid,
                        'nid_ingredient' => $ingredientId,
                        'nqty' => $qtys[$index],
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('products.index')
                ->with('success', 'Produk berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, string $id)
    {
        $messages = [
            'nid_ingredient.required_with' => 'Anda mengaktifkan fitur Resep, mohon tambahkan minimal 1 Bahan Baku.',
            'nqty.required_with' => 'Kuantitas bahan baku harus diisi.',
            'nid_ingredient.*.exists' => 'Bahan baku yang dipilih tidak valid.',
            'nqty.*.numeric' => 'Kuantitas harus berupa angka.',
        ];

        $request->validate([
            'cname' => 'required|string|max:255',
            'nid_category' => 'required|exists:mpos_grp_product,nid',
            'nprice' => 'required|numeric|min:0',
            'cstatus' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'has_recipe' => 'nullable',
            'nid_ingredient' => 'required_with:has_recipe|array',
            'nid_ingredient.*' => 'required_with:has_recipe|exists:mpos_ingredients,nid',
            'nqty' => 'required_with:has_recipe|array',
            'nqty.*' => 'required_with:has_recipe|numeric|min:0.01',
        ], $messages);

        $product = MposProduct::findOrFail($id);
        $data = $request->except(['photo', 'has_recipe', 'nid_ingredient', 'nqty', 'modal_id']);
        $data['fcombo'] = $request->has('fcombo') ? 1 : 0;

        if ($request->hasFile('photo')) {
            if ($product->cphotos && file_exists(public_path($product->cphotos))) {
                unlink(public_path($product->cphotos));
            }
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/product'), $filename);
            $data['cphotos'] = 'uploads/product/' . $filename;
        }

        DB::beginTransaction();
        try {
            $product->update($data);

            // Selalu hapus resep lama
            MposRecipe::where('nid_product', $id)->delete();

            if ($request->has('has_recipe')) {
                $ingredients = $request->nid_ingredient ?? [];
                $qtys = $request->nqty ?? [];

                if (count($ingredients) !== count(array_unique($ingredients))) {
                    DB::rollBack();
                    return back()->with('error', 'Terdapat bahan baku yang duplikat untuk resep ini.')->withInput();
                }

                foreach ($ingredients as $index => $ingredientId) {
                    MposRecipe::create([
                        'nid_product' => $product->nid,
                        'nid_ingredient' => $ingredientId,
                        'nqty' => $qtys[$index],
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('products.index')
                ->with('success', 'Produk berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $product = MposProduct::findOrFail($id);

            if ($product->cphotos && file_exists(public_path($product->cphotos))) {
                unlink(public_path($product->cphotos));
            }

            MposRecipe::where('nid_product', $id)->delete();
            $product->delete();

            DB::commit();
            return redirect()->route('products.index')
                ->with('success', 'Produk berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
