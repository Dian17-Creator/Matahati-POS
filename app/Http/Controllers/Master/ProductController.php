<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposProduct;
use App\Models\MposGrpProduct;
use App\Models\MposRecipe;
use App\Models\MposIngredients;
use App\Models\MposOutlet;
use App\Models\MposCustType;
use App\Traits\ResolvesOutletScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProductController extends Controller
{
    use ResolvesOutletScope;
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
        $query = MposProduct::with('category')
            ->where('cstatus', 'ACTIVE');

        if (\App\Models\MposGrpProduct::hasShowPosColumn()) {
            $query->where(function ($q) {
                $q->whereDoesntHave('category')
                  ->orWhereHas('category', function ($cq) {
                      $cq->where('fshow_pos', 1);
                  });
            });
        }

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

        foreach ($products as $product) {
            $product->ftrack_stock = $product->category ? (bool) $product->category->ftrack_stock : true;
        }

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function index(Request $request)
    {
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $isCashierOnly = $this->isCashierOnlyUser();
        $isSuperAdmin = $this->isSuperAdminUser();
        $isOutletLocked = $this->isOutletLocked();
        $outlets = $this->getAvailableOutlets();
        $allowedOutletIds = $this->getUserAllowedOutletIds();

        $query = MposProduct::with(['category', 'recipes.ingredient', 'outlet']);

        if (!empty($selectedOutletId)) {
            $query->where('nid_outlet', $selectedOutletId);
        } elseif (!$this->isSuperAdminUser()) {
            $query->whereIn('nid_outlet', $allowedOutletIds);
        }

        $matchedCategoryIds = [];
        if ($request->filled('nid_category')) {
            $selectedCategory = MposGrpProduct::find($request->nid_category);
            if ($selectedCategory) {
                $matchedCategoryIds = MposGrpProduct::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $selectedCategory->cname))])
                    ->pluck('nid')
                    ->all();
            } else {
                $matchedCategoryIds = [(int) $request->nid_category];
            }

            $query->whereIn('nid_category', $matchedCategoryIds);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('cname', 'like', "%{$search}%");
        }

        // Group by cname so we only show 1 row per product name
        $query->whereIn('nid', function($q) use ($request, $selectedOutletId, $allowedOutletIds, $matchedCategoryIds) {
            $q->select(DB::raw('MIN(nid)'))
              ->from('mpos_product')
              ->groupBy('cname');

            if (!empty($selectedOutletId)) {
                $q->where('nid_outlet', $selectedOutletId);
            } elseif (!$this->isSuperAdminUser()) {
                $q->whereIn('nid_outlet', $allowedOutletIds);
            }
            if (!empty($matchedCategoryIds)) {
                $q->whereIn('nid_category', $matchedCategoryIds);
            } elseif ($request->filled('nid_category')) {
                $q->where('nid_category', $request->nid_category);
            }
        });

        $products = $query->paginate(10)->withQueryString();

        $categoriesQuery = MposGrpProduct::whereIn('nid', function($q) use ($selectedOutletId, $allowedOutletIds) {
            $q->select(DB::raw('MIN(nid)'))
              ->from('mpos_grp_product')
              ->groupBy('cname');

            if (!empty($selectedOutletId)) {
                $q->where('nid_outlet', $selectedOutletId);
            } elseif (!$this->isSuperAdminUser()) {
                $q->whereIn('nid_outlet', $allowedOutletIds);
            }
        });

        if (!empty($selectedOutletId)) {
            $categoriesQuery->where('nid_outlet', $selectedOutletId);
        } elseif (!$this->isSuperAdminUser()) {
            $categoriesQuery->whereIn('nid_outlet', $allowedOutletIds);
        }

        $categories = $categoriesQuery->orderBy('cname')->get();

        $ingredientsQuery = MposIngredients::query();
        if (!$this->isSuperAdminUser()) {
            $ingredientsQuery->where(function ($iq) use ($allowedOutletIds) {
                $iq->whereIn('nid_outlet', $allowedOutletIds)
                   ->orWhereNull('nid_outlet');
            });
        }
        $ingredients = $ingredientsQuery->orderBy('cname')
            ->get(['nid', 'cname', 'nid_outlet', 'csatuan', 'nstock'])
            ->groupBy(fn ($item) => strtoupper(trim((string) $item->cname)))
            ->map(function ($group) use ($selectedOutletId) {
                if (!empty($selectedOutletId)) {
                    $matched = $group->firstWhere('nid_outlet', (int) $selectedOutletId);
                    if ($matched) {
                        return $matched;
                    }
                }
                return $group->sortByDesc('nstock')->first();
            })
            ->sortBy('cname', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $satuans = MposProduct::SATUAN_LIST;

        return view('products.index', compact('products', 'categories', 'ingredients', 'outlets', 'selectedOutletId', 'isCashierOnly', 'isSuperAdmin', 'isOutletLocked', 'allowedOutletIds', 'satuans'));
    }

    public function show(Request $request, string $id)
    {
        $product = MposProduct::with(['category', 'outlet', 'productPrices.customerType', 'recipes.ingredient', 'recipes.legacyIngredient', 'updatedBy'])->findOrFail($id);
        $customerTypes = MposCustType::where('nid_outlet', $product->nid_outlet)->orderBy('cname')->get();
        
        $categories = \App\Models\MposGrpProduct::whereIn('nid', function($q) {
            $q->select(\Illuminate\Support\Facades\DB::raw('MIN(nid)'))
              ->from('mpos_grp_product')
              ->groupBy('cname');
        })->orderBy('cname')->get();

        $activeOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet') ?: $product->nid_outlet;
        $allowedOutletIds = $this->getUserAllowedOutletIds();

        // Ambil bahan baku dari master mpos_product (kecuali produk yang sedang dibuka)
        $materialsQuery = MposProduct::where('cstatus', 'ACTIVE')
            ->where('nid', '!=', $product->nid);

        if (!empty($activeOutletId)) {
            $materialsQuery->where(function ($mq) use ($activeOutletId) {
                $mq->where('nid_outlet', $activeOutletId)
                   ->orWhereNull('nid_outlet');
            });
        } elseif (!$this->isSuperAdminUser()) {
            $materialsQuery->where(function ($mq) use ($allowedOutletIds) {
                $mq->whereIn('nid_outlet', $allowedOutletIds)
                   ->orWhereNull('nid_outlet');
            });
        }

        // Tampilkan produk yang merupakan Bahan Baku atau Setengah Jadi (jika ada kolomnya), atau semua produk jika belum diklasifikasi
        if (MposProduct::hasProductTypeColumn()) {
            $materialsQuery->where(function ($mq) {
                $mq->whereIn('cproduct_type', ['BAHAN_BAKU', 'SETENGAH_JADI'])
                   ->orWhereNull('cproduct_type');
            });
        }

        $rawMaterials = $materialsQuery
            ->orderBy('cname')
            ->get();

        // Kelompokkan berdasarkan nama bahan baku & utamakan stok dari outlet produk / outlet aktif
        $availableMaterials = $rawMaterials
            ->groupBy(fn ($item) => strtoupper(trim((string) $item->cname)))
            ->map(function ($group) use ($activeOutletId, $product) {
                return $group->firstWhere('nid_outlet', (int) $product->nid_outlet)
                    ?? $group->firstWhere('nid_outlet', (int) $activeOutletId)
                    ?? $group->sortByDesc('nqty')->first();
            })
            ->sortBy('cname', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $ingredients = $availableMaterials;
        $outlets = \App\Models\MposOutlet::orderBy('cname')->get();

        $existingRecipeData = $product->recipes->map(function ($r) {
            $ing = $r->ingredient ?: $r->legacyIngredient;
            return [
                'id' => (string) $r->nid,
                'nid' => $r->nid,
                'productId' => $r->nid_ingredient,
                'name' => strtoupper((string) ($ing?->cname ?? 'BAHAN BAKU')),
                'qty' => (float) ($r->nqty ?? 1),
                'unit' => $ing?->csatuan ?: 'PCS',
                'conversion' => 1,
            ];
        })->values()->all();

        $materialsJson = collect($availableMaterials ?? [])->map(function ($m) {
            return [
                'nid' => $m->nid,
                'cname' => $m->cname,
                'nstock' => (float) ($m->nqty ?? 0),
                'csatuan' => $m->csatuan ?: 'PCS',
            ];
        })->values()->all();

        $satuans = MposProduct::SATUAN_LIST;

        return view('products.show', compact('product', 'customerTypes', 'categories', 'ingredients', 'availableMaterials', 'outlets', 'existingRecipeData', 'materialsJson', 'satuans'));
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
            'cproduct_type' => 'nullable|string|in:PRODUK,SETENGAH_JADI,BAHAN_BAKU',
            'csatuan' => 'nullable|string|max:50',
            'nid_category' => 'required|exists:mpos_grp_product,nid',
            'nprice' => 'required|numeric|min:0',
            'nprice_online' => 'nullable|numeric|min:0',
            'nprice_buy' => 'nullable|numeric|min:0',
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

        $data = $request->except(['photo', 'has_recipe', 'nid_ingredient', 'nqty', 'modal_id', 'outlet_ids', 'stock_qty', 'fshow_pos', 'csatuan', 'sync_recipe']);
        if (MposProduct::hasProductTypeColumn()) {
            $data['cproduct_type'] = $request->input('cproduct_type', 'PRODUK');
        } else {
            unset($data['cproduct_type']);
        }
        if (MposProduct::hasSatuanColumn()) {
            $data['csatuan'] = $request->input('csatuan', 'PCS') ?: 'PCS';
        } else {
            unset($data['csatuan']);
        }
        $data['fcombo'] = $request->has('fcombo') ? 1 : 0;
        $data['nprice_online'] = $request->input('nprice_online') ?: 0.00;
        $data['nprice_buy'] = $request->input('nprice_buy') ?: 0.00;
        $data['nqty'] = $request->input('stock_qty') ?: 0;
        $data['dcreated'] = Carbon::now();
        $data['updated_by'] = Auth::id() ?? 1;
        $data['updated_at'] = Carbon::now();

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
                            $srcIng = MposIngredients::find($ingredientId);
                            $targetIngId = $ingredientId;
                            if ($srcIng) {
                                $matchedIng = MposIngredients::where('nid_outlet', $outletId)
                                    ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $srcIng->cname))])
                                    ->first();
                                if (!$matchedIng) {
                                    $ingPayload = [
                                        'cname' => $srcIng->cname,
                                        'nid_outlet' => $outletId,
                                        'csatuan' => $srcIng->csatuan ?: 'PCS',
                                        'nstock' => 0,
                                    ];
                                    if (MposIngredients::hasPriceColumn()) {
                                        $ingPayload['nprice'] = (float) ($srcIng->nprice ?? 0);
                                    }
                                    $matchedIng = MposIngredients::create($ingPayload);
                                }
                                $targetIngId = $matchedIng->nid;
                            }

                            MposRecipe::create([
                                'nid_product' => $product->nid,
                                'nid_ingredient' => $targetIngId,
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
            'cproduct_type' => 'nullable|string|in:PRODUK,SETENGAH_JADI,BAHAN_BAKU',
            'csatuan' => 'nullable|string|max:50',
            'nid_category' => 'required|exists:mpos_grp_product,nid',
            'nprice' => 'required|numeric|min:0',
            'nprice_online' => 'nullable|numeric|min:0',
            'nprice_buy' => 'nullable|numeric|min:0',
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

        $product = MposProduct::with('recipes')->findOrFail($id);
        $shouldSyncRecipe = $request->boolean('sync_recipe');
        $data = $request->except(['photo', 'has_recipe', 'nid_ingredient', 'nqty', 'modal_id', 'outlet_ids', 'stock_qty', 'fshow_pos', 'csatuan', 'sync_recipe']);
        if (MposProduct::hasProductTypeColumn()) {
            if ($request->has('cproduct_type')) {
                $data['cproduct_type'] = $request->input('cproduct_type', 'PRODUK');
            }
        } else {
            unset($data['cproduct_type']);
        }
        if (MposProduct::hasSatuanColumn()) {
            if ($request->has('csatuan')) {
                $data['csatuan'] = $request->input('csatuan', 'PCS') ?: 'PCS';
            }
        } else {
            unset($data['csatuan']);
        }
        $data['fcombo'] = $request->has('fcombo') ? 1 : 0;
        $data['nprice_online'] = $request->input('nprice_online') ?: 0.00;
        if ($request->has('nprice_buy')) {
            $data['nprice_buy'] = $request->input('nprice_buy') ?: 0.00;
        }
        if ($request->has('stock_qty')) {
            $data['nqty'] = $request->input('stock_qty') ?: 0;
        }
        $data['updated_by'] = Auth::id() ?? 1;
        $data['updated_at'] = Carbon::now();

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

            $ingredients = $request->nid_ingredient ?? [];
            $qtys = $request->nqty ?? [];

            if ($shouldSyncRecipe) {
                if ($request->has('has_recipe') && count($ingredients) !== count(array_unique($ingredients))) {
                    DB::rollBack();
                    return back()->with('error', 'Terdapat bahan baku yang duplikat untuk resep ini.')->withInput();
                }

                // Hapus resep lama untuk product current jika disinkronkan dari modal Edit Produk
                MposRecipe::where('nid_product', $id)->delete();

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

            $currentRecipes = MposRecipe::with('ingredient')->where('nid_product', $product->nid)->get();

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

                    if ($shouldSyncRecipe) {
                        MposRecipe::where('nid_product', $existingProduct->nid)->delete();

                        if ($request->has('has_recipe')) {
                            foreach ($ingredients as $index => $ingredientId) {
                                $srcIng = MposIngredients::find($ingredientId);
                                $targetIngId = $ingredientId;
                                if ($srcIng) {
                                    $matchedIng = MposIngredients::where('nid_outlet', $outletId)
                                        ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $srcIng->cname))])
                                        ->first();
                                    if (!$matchedIng) {
                                        $matchedIng = MposIngredients::create([
                                            'cname' => $srcIng->cname,
                                            'nid_outlet' => $outletId,
                                            'csatuan' => $srcIng->csatuan ?: 'PCS',
                                            'nstock' => 0,
                                        ]);
                                    }
                                    $targetIngId = $matchedIng->nid;
                                }

                                MposRecipe::create([
                                    'nid_product' => $existingProduct->nid,
                                    'nid_ingredient' => $targetIngId,
                                    'nqty' => $qtys[$index],
                                ]);
                            }
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
                        $newProductData['dcreated'] = Carbon::now();

                        $newProduct = MposProduct::create($newProductData);

                        // Duplicate resep untuk produk baru dengan bahan baku di outlet tujuan
                        foreach ($currentRecipes as $rec) {
                            $targetIngId = $rec->nid_ingredient;
                            if ($rec->ingredient) {
                                $matchedIng = MposIngredients::where('nid_outlet', $outletId)
                                    ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $rec->ingredient->cname))])
                                    ->first();
                                if (!$matchedIng) {
                                    $ingPayload = [
                                        'cname' => $rec->ingredient->cname,
                                        'nid_outlet' => $outletId,
                                        'csatuan' => $rec->ingredient->csatuan ?: 'PCS',
                                        'nstock' => 0,
                                    ];
                                    if (MposIngredients::hasPriceColumn()) {
                                        $ingPayload['nprice'] = (float) ($rec->ingredient->nprice ?? 0);
                                    }
                                    $matchedIng = MposIngredients::create($ingPayload);
                                }
                                $targetIngId = $matchedIng->nid;
                            }

                            MposRecipe::create([
                                'nid_product' => $newProduct->nid,
                                'nid_ingredient' => $targetIngId,
                                'nqty' => $rec->nqty,
                            ]);
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
