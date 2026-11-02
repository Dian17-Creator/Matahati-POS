<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposIngredients;
use App\Models\MposProduct;
use App\Models\MposRecipe;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductRecipeController extends Controller
{
    public function store(Request $request, string $product)
    {
        $validated = $request->validate([
            'nid_ingredient' => 'required|exists:mpos_ingredients,nid',
            'nqty' => 'nullable|numeric|min:0.01',
            'outlet_ids' => 'nullable|array',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
        ], [
            'nid_ingredient.required' => 'Pilih bahan baku terlebih dahulu.',
            'nid_ingredient.exists' => 'Bahan baku tidak ditemukan di master bahan baku.',
            'nqty.min' => 'Kuantitas bahan baku minimal 0.01.',
        ]);

        $parentProduct = MposProduct::findOrFail($product);
        $selectedIngredient = MposIngredients::findOrFail($validated['nid_ingredient']);

        $qty = isset($validated['nqty']) ? (float) $validated['nqty'] : 1.0;
        $requestedOutletIds = collect($validated['outlet_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->push((int) $parentProduct->nid_outlet)
            ->unique()
            ->values()
            ->all();

        DB::beginTransaction();
        try {
            $targetMenuProducts = MposProduct::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $parentProduct->cname))])
                ->whereIn('nid_outlet', $requestedOutletIds)
                ->get();

            if (!$targetMenuProducts->contains('nid', $parentProduct->nid)) {
                $targetMenuProducts->push($parentProduct);
            }

            $primaryRecipe = null;

            foreach ($targetMenuProducts as $menuProd) {
                $ingredientInOutlet = MposIngredients::where('nid_outlet', $menuProd->nid_outlet)
                    ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $selectedIngredient->cname))])
                    ->first();

                if (!$ingredientInOutlet) {
                    if ((int) $selectedIngredient->nid_outlet === (int) $menuProd->nid_outlet || empty($selectedIngredient->nid_outlet)) {
                        $ingredientInOutlet = $selectedIngredient;
                        if (empty($ingredientInOutlet->nid_outlet) && !empty($menuProd->nid_outlet)) {
                            $ingredientInOutlet->nid_outlet = $menuProd->nid_outlet;
                            $ingredientInOutlet->save();
                        }
                    } else {
                        $ingPayload = [
                            'cname' => $selectedIngredient->cname,
                            'nid_outlet' => $menuProd->nid_outlet,
                            'csatuan' => $selectedIngredient->csatuan ?: 'PCS',
                            'nstock' => 0,
                        ];
                        if (MposIngredients::hasPriceColumn()) {
                            $ingPayload['nprice'] = (float) ($selectedIngredient->nprice ?? 0);
                        }
                        $ingredientInOutlet = MposIngredients::create($ingPayload);
                    }
                }

                $existing = MposRecipe::where('nid_product', $menuProd->nid)
                    ->where('nid_ingredient', $ingredientInOutlet->nid)
                    ->first();

                if ($existing) {
                    $existing->nqty = (float) $existing->nqty + $qty;
                    $existing->save();
                    $saved = $existing;
                } else {
                    $saved = MposRecipe::create([
                        'nid_product' => $menuProd->nid,
                        'nid_ingredient' => $ingredientInOutlet->nid,
                        'nqty' => $qty,
                    ]);
                }

                if ((int) $menuProd->nid === (int) $parentProduct->nid) {
                    $primaryRecipe = $saved->load('ingredient');
                }
            }

            DB::commit();

            $itemPayload = [
                'nid' => $primaryRecipe->nid,
                'ingredientId' => $primaryRecipe->nid_ingredient,
                'name' => strtoupper((string) ($primaryRecipe->ingredient->cname ?? $selectedIngredient->cname)),
                'qty' => (float) $primaryRecipe->nqty,
                'unit' => $primaryRecipe->ingredient->csatuan ?: ($selectedIngredient->csatuan ?: 'PCS'),
                'conversion' => 1,
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Bahan atau resep berhasil disimpan.',
                    'item' => $itemPayload,
                ]);
            }

            return redirect()->back()
                ->with('success', 'Bahan atau resep berhasil disimpan.')
                ->with('active_tab', 'resep');
        } catch (QueryException $e) {
            DB::rollBack();
            return $this->errorResponse($request, 'Gagal menyimpan resep: ' . $e->getMessage(), 500);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse($request, 'Terjadi kesalahan: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $product, string $recipe)
    {
        $validated = $request->validate([
            'nqty' => 'required|numeric|min:0.01',
        ], [
            'nqty.required' => 'Qty bahan baku wajib diisi.',
            'nqty.min' => 'Qty bahan baku minimal 0.01.',
        ]);

        $parentProduct = MposProduct::findOrFail($product);
        $recipeRow = MposRecipe::with('ingredient')
            ->where('nid_product', $parentProduct->nid)
            ->where('nid', $recipe)
            ->firstOrFail();

        $newQty = (float) $validated['nqty'];
        $ingredientName = optional($recipeRow->ingredient)->cname;

        DB::beginTransaction();
        try {
            $recipeRow->nqty = $newQty;
            $recipeRow->save();

            // Sinkronkan perubahan Qty ke produk serupa di outlet lain (jika ada)
            if ($ingredientName) {
                $siblingProductIds = MposProduct::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $parentProduct->cname))])
                    ->where('nid', '!=', $parentProduct->nid)
                    ->pluck('nid');

                if ($siblingProductIds->isNotEmpty()) {
                    $matchingIngredientIds = MposIngredients::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $ingredientName))])
                        ->pluck('nid');

                    if ($matchingIngredientIds->isNotEmpty()) {
                        MposRecipe::whereIn('nid_product', $siblingProductIds)
                            ->whereIn('nid_ingredient', $matchingIngredientIds)
                            ->update(['nqty' => $newQty]);
                    }
                }
            }

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kuantitas resep berhasil diperbarui.',
                    'item' => [
                        'nid' => $recipeRow->nid,
                        'ingredientId' => $recipeRow->nid_ingredient,
                        'name' => strtoupper((string) ($recipeRow->ingredient->cname ?? 'BAHAN BAKU')),
                        'qty' => (float) $recipeRow->nqty,
                        'unit' => $recipeRow->ingredient->csatuan ?: 'PCS',
                        'conversion' => 1,
                    ],
                ]);
            }

            return redirect()->back()
                ->with('success', 'Kuantitas resep berhasil diperbarui.')
                ->with('active_tab', 'resep');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse($request, 'Gagal memperbarui kuantitas: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, string $product, string $recipe)
    {
        $parentProduct = MposProduct::findOrFail($product);
        $recipeRow = MposRecipe::with('ingredient')
            ->where('nid_product', $parentProduct->nid)
            ->where('nid', $recipe)
            ->firstOrFail();

        $ingredientName = optional($recipeRow->ingredient)->cname;

        DB::beginTransaction();
        try {
            $recipeRow->delete();

            // Hapus juga dari produk serupa di outlet lain agar tetap konsisten
            if ($ingredientName) {
                $siblingProductIds = MposProduct::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $parentProduct->cname))])
                    ->where('nid', '!=', $parentProduct->nid)
                    ->pluck('nid');

                if ($siblingProductIds->isNotEmpty()) {
                    $matchingIngredientIds = MposIngredients::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $ingredientName))])
                        ->pluck('nid');

                    if ($matchingIngredientIds->isNotEmpty()) {
                        MposRecipe::whereIn('nid_product', $siblingProductIds)
                            ->whereIn('nid_ingredient', $matchingIngredientIds)
                            ->delete();
                    }
                }
            }

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Bahan baku berhasil dihapus dari resep.',
                ]);
            }

            return redirect()->back()
                ->with('success', 'Bahan baku berhasil dihapus dari resep.')
                ->with('active_tab', 'resep');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse($request, 'Gagal menghapus bahan resep: ' . $e->getMessage(), 500);
        }
    }

    private function errorResponse(Request $request, string $message, int $status = 422)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return redirect()->back()
            ->with('error', $message)
            ->with('active_tab', 'resep')
            ->withInput();
    }
}
