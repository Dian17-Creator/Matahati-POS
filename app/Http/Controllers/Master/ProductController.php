<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposProduct;
use App\Models\MposGrpProduct;
use App\Models\MposRecipe;
use App\Models\MposIngredients;
use App\Models\MposOutlet;
use App\Models\MposCustType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'outlet_id' => 'required|exists:mpos_outlet,nid',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $importer = new \App\Imports\ProductsImport($request->outlet_id);
            \Maatwebsite\Excel\Facades\Excel::import($importer, $request->file('file'));

            return redirect()->back()->with('import_results', $importer->results);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat import: ' . $e->getMessage());
        }
    }
    public function apiIndex(Request $request)
    {
        $query = MposProduct::with('category')->where('cstatus', 'ACTIVE');

        if ($request->filled('nid_outlet')) {
            $query->where('nid_outlet', $request->input('nid_outlet'));
        } elseif ($request->filled('outlet_id')) {
            $query->where('nid_outlet', $request->input('outlet_id'));
        }

        $customerId = $request->input('nid_customer') ?? $request->input('customer_id');
        $isReseller = false;
        $resellerTypeId = null;

        if (!empty($customerId)) {
            $customer = \App\Models\MposCust::with('type')->find($customerId);
            if ($customer && $customer->type) {
                $typeName = strtoupper(trim((string) $customer->type->cname));
                if ($typeName === 'RESELLER') {
                    $isReseller = true;
                    $resellerTypeId = $customer->type->nid;
                }
            }
        }

        if ($isReseller && $resellerTypeId) {
            // Filter: Hanya sertakan produk yang memiliki harga reseller untuk nid_cust_type ini
            $query->whereHas('productPrices', function ($q) use ($resellerTypeId) {
                $q->where('nid_cust_type', $resellerTypeId)
                  ->where('nqty_start', 1);
            });

            $query->with(['productPrices' => function ($q) use ($resellerTypeId) {
                $q->where('nid_cust_type', $resellerTypeId)
                  ->where('nqty_start', 1);
            }]);
        }

        $products = $query->get();

        if ($isReseller && $resellerTypeId) {
            // Override nprice dengan harga reseller dari mpos_product_price
            foreach ($products as $product) {
                $resellerPriceObj = $product->productPrices->first();
                if ($resellerPriceObj && $resellerPriceObj->nprice !== null) {
                    $product->nprice = (string) $resellerPriceObj->nprice;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function index(Request $request)
    {
        $query = MposProduct::with(['category', 'recipes.ingredient', 'outlet']);

        if ($request->has('nid_outlet') && $request->nid_outlet != '') {
            $query->where('nid_outlet', $request->nid_outlet);
        }

        if ($request->has('nid_category') && $request->nid_category != '') {
            $query->where('nid_category', $request->nid_category);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('cname', 'like', "%{$search}%");
        }

        // Group by cname so we only show 1 row per product name
        $query->whereIn('nid', function($q) use ($request) {
            $q->select(DB::raw('MIN(nid)'))
              ->from('mpos_product')
              ->groupBy('cname');

            if ($request->has('nid_outlet') && $request->nid_outlet != '') {
                $q->where('nid_outlet', $request->nid_outlet);
            }
            if ($request->has('nid_category') && $request->nid_category != '') {
                $q->where('nid_category', $request->nid_category);
            }
        });

        $products = $query->paginate(10)->withQueryString();

        $categories = MposGrpProduct::whereIn('nid', function($q) {
            $q->select(DB::raw('MIN(nid)'))
              ->from('mpos_grp_product')
              ->groupBy('cname');
        })->orderBy('cname')->get();

        $ingredients = MposIngredients::all();
        $outlets = MposOutlet::orderBy('cname')->get();

        return view('products.index', compact('products', 'categories', 'ingredients', 'outlets'));
    }

    public function show(string $id)
    {
        $product = MposProduct::with(['category', 'outlet', 'productPrices.customerType', 'recipes.ingredient'])->findOrFail($id);
        $customerTypes = MposCustType::where('nid_outlet', $product->nid_outlet)->orderBy('cname')->get();
        
        $categories = \App\Models\MposGrpProduct::whereIn('nid', function($q) {
            $q->select(\Illuminate\Support\Facades\DB::raw('MIN(nid)'))
              ->from('mpos_grp_product')
              ->groupBy('cname');
        })->orderBy('cname')->get();

        $ingredients = \App\Models\MposIngredients::all();
        $outlets = \App\Models\MposOutlet::orderBy('cname')->get();

        return view('products.show', compact('product', 'customerTypes', 'categories', 'ingredients', 'outlets'));
    }

    public function store(Request $request)
    {
        $messages = [
            'nid_ingredient.required_with' => 'Anda mengaktifkan fitur Resep, mohon tambahkan minimal 1 Bahan Baku.',
            'nqty.required_with' => 'Kuantitas bahan baku harus diisi.',
            'nid_ingredient.*.exists' => 'Bahan baku yang dipilih tidak valid.',
            'nqty.*.numeric' => 'Kuantitas harus berupa angka.',
        ];

        if (!$request->has('has_recipe')) {
            $request->request->remove('nid_ingredient');
            $request->request->remove('nqty');
        }

        $request->validate([
            'cname' => 'required|string|max:255',
            'nid_category' => 'required|exists:mpos_grp_product,nid',
            'nprice' => 'required|numeric|min:0',
            'nprice_online' => 'nullable|numeric|min:0',
            'cstatus' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
            'stock_qty' => 'nullable|integer|min:0',
            'has_recipe' => 'nullable',
            'nid_ingredient' => 'required_with:has_recipe|array',
            'nid_ingredient.*' => 'required_with:has_recipe|exists:mpos_ingredients,nid',
            'nqty' => 'required_with:has_recipe|array',
            'nqty.*' => 'required_with:has_recipe|numeric|min:0.01',
        ], $messages);

        $data = $request->except(['photo', 'has_recipe', 'nid_ingredient', 'nqty', 'modal_id', 'outlet_ids', 'stock_qty']);
        $data['fcombo'] = $request->has('fcombo') ? 1 : 0;
        $data['nprice_online'] = $request->input('nprice_online') ?: 0.00;
        $data['nqty'] = $request->input('stock_qty') ?: 0;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/product'), $filename);
            $data['cphotos'] = 'uploads/product/' . $filename;
        }

        DB::beginTransaction();
        try {
            $ingredients = $request->nid_ingredient ?? [];
            $qtys = $request->nqty ?? [];

            if ($request->has('has_recipe') && count($ingredients) !== count(array_unique($ingredients))) {
                DB::rollBack();
                return back()->with('error', 'Terdapat bahan baku yang duplikat untuk resep ini.')->withInput();
            }

            foreach ($request->outlet_ids as $outletId) {
                // Cek duplicate
                $exists = MposProduct::where('cname', $data['cname'])
                            ->where('nid_outlet', $outletId)
                            ->exists();

                if (!$exists) {
                    $productData = $data;
                    $productData['nid_outlet'] = $outletId;
                    $product = MposProduct::create($productData);

                    if ($request->has('has_recipe')) {
                        foreach ($ingredients as $index => $ingredientId) {
                            MposRecipe::create([
                                'nid_product' => $product->nid,
                                'nid_ingredient' => $ingredientId,
                                'nqty' => $qtys[$index],
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->back()
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

        if (!$request->has('has_recipe')) {
            $request->request->remove('nid_ingredient');
            $request->request->remove('nqty');
        }

        $request->validate([
            'cname' => 'required|string|max:255',
            'nid_category' => 'required|exists:mpos_grp_product,nid',
            'nprice' => 'required|numeric|min:0',
            'nprice_online' => 'nullable|numeric|min:0',
            'cstatus' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
            'stock_qty' => 'nullable|integer|min:0',
            'has_recipe' => 'nullable',
            'nid_ingredient' => 'required_with:has_recipe|array',
            'nid_ingredient.*' => 'required_with:has_recipe|exists:mpos_ingredients,nid',
            'nqty' => 'required_with:has_recipe|array',
            'nqty.*' => 'required_with:has_recipe|numeric|min:0.01',
        ], $messages);

        $product = MposProduct::findOrFail($id);
        $data = $request->except(['photo', 'has_recipe', 'nid_ingredient', 'nqty', 'modal_id', 'outlet_ids', 'stock_qty']);
        $data['fcombo'] = $request->has('fcombo') ? 1 : 0;
        $data['nprice_online'] = $request->input('nprice_online') ?: 0.00;
        if ($request->has('stock_qty')) {
            $data['nqty'] = $request->input('stock_qty') ?: 0;
        }

        if ($request->hasFile('photo')) {
            // Check if other products use this photo before unlink
            if ($product->cphotos && file_exists(public_path($product->cphotos))) {
                $usageCount = MposProduct::where('cphotos', $product->cphotos)->where('nid', '!=', $product->nid)->count();
                if ($usageCount === 0) {
                    unlink(public_path($product->cphotos));
                }
            }
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/product'), $filename);
            $data['cphotos'] = 'uploads/product/' . $filename;
        } else {
            $data['cphotos'] = $product->cphotos; // Keep for duplication
        }

        $originalCname = $product->cname;

        DB::beginTransaction();
        try {
            // Update current product
            $product->update($data);

            // Selalu hapus resep lama untuk product current
            MposRecipe::where('nid_product', $id)->delete();

            $ingredients = $request->nid_ingredient ?? [];
            $qtys = $request->nqty ?? [];

            if ($request->has('has_recipe') && count($ingredients) !== count(array_unique($ingredients))) {
                DB::rollBack();
                return back()->with('error', 'Terdapat bahan baku yang duplikat untuk resep ini.')->withInput();
            }

            if ($request->has('has_recipe')) {
                foreach ($ingredients as $index => $ingredientId) {
                    MposRecipe::create([
                        'nid_product' => $product->nid,
                        'nid_ingredient' => $ingredientId,
                        'nqty' => $qtys[$index],
                    ]);
                }
            }

            // Loop untuk memproses produk di outlet tambahan / outlet yang dicentang
            foreach ($request->outlet_ids as $outletId) {
                // Skip outlet dari produk yang sedang diedit agar tidak ter-update ulang / duplikat
                if ($product->nid_outlet == $outletId) {
                    continue;
                }

                // Cek apakah produk ini (berdasarkan nama asli) sudah ada di target outlet
                $existingProduct = MposProduct::where('cname', $originalCname)
                            ->where('nid_outlet', $outletId)
                            ->first();

                if ($existingProduct) {
                    // Jika sudah ada, UPDATE data produk tersebut dengan data yang baru
                    $existingProduct->update($data);

                    // Selalu hapus resep lama untuk product target ini
                    MposRecipe::where('nid_product', $existingProduct->nid)->delete();

                    if ($request->has('has_recipe')) {
                        foreach ($ingredients as $index => $ingredientId) {
                            MposRecipe::create([
                                'nid_product' => $existingProduct->nid,
                                'nid_ingredient' => $ingredientId,
                                'nqty' => $qtys[$index],
                            ]);
                        }
                    }
                } else {
                    // Jika belum ada, berarti user benar-benar menambahkan outlet baru untuk produk ini
                    // Cek duplicate nama baru di target outlet
                    $exists = MposProduct::where('cname', $data['cname'])
                                ->where('nid_outlet', $outletId)
                                ->exists();

                    if (!$exists) {
                        $newProductData = $data;
                        $newProductData['nid_outlet'] = $outletId;

                        $newProduct = MposProduct::create($newProductData);

                        // Duplicate resep untuk produk baru
                        if ($request->has('has_recipe')) {
                            foreach ($ingredients as $index => $ingredientId) {
                                MposRecipe::create([
                                    'nid_product' => $newProduct->nid,
                                    'nid_ingredient' => $ingredientId,
                                    'nqty' => $qtys[$index],
                                ]);
                            }
                        }
                    }
                }
            }

            // Hapus produk di outlet yang tidak dicentang (unchecked)
            $unselectedOutlets = MposProduct::where('cname', $originalCname)
                ->whereNotIn('nid_outlet', $request->outlet_ids)
                ->get();

            foreach ($unselectedOutlets as $unselected) {
                // Hapus resep
                MposRecipe::where('nid_product', $unselected->nid)->delete();
                // Hapus produk
                $unselected->delete();
            }

            DB::commit();
            return redirect()->back()
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
                // Check if other products use this photo before unlink
                $usageCount = MposProduct::where('cphotos', $product->cphotos)->where('nid', '!=', $product->nid)->count();
                if ($usageCount === 0) {
                    unlink(public_path($product->cphotos));
                }
            }

            MposRecipe::where('nid_product', $id)->delete();
            $product->delete();

            DB::commit();
            return redirect()->back()
                ->with('success', 'Produk berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
