<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Imports\ProductRecipesImport;
use App\Models\MposIngredients;
use App\Models\MposProduct;
use App\Models\MposRecipe;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ProductRecipeController extends Controller
{
    public function store(Request $request, string $product)
    {
        $validated = $request->validate([
            'nid_ingredient' => 'required|exists:mpos_product,nid',
            'nqty' => 'nullable|numeric|min:0.01',
            'outlet_ids' => 'nullable|array',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
        ], [
            'nid_ingredient.required' => 'Pilih bahan baku terlebih dahulu.',
            'nid_ingredient.exists' => 'Bahan baku tidak ditemukan di master produk.',
            'nqty.min' => 'Kuantitas bahan baku minimal 0.01.',
        ]);

        $parentProduct = MposProduct::findOrFail($product);
        $selectedIngredient = MposProduct::findOrFail($validated['nid_ingredient']);

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
                $ingredientInOutlet = MposProduct::where('nid_outlet', $menuProd->nid_outlet)
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
                        $newIngProduct = $selectedIngredient->replicate();
                        $newIngProduct->nid_outlet = $menuProd->nid_outlet;
                        $newIngProduct->nqty = 0;
                        $newIngProduct->save();
                        $ingredientInOutlet = $newIngProduct;
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
                    $primaryRecipe = $saved->load(['ingredient', 'legacyIngredient']);
                }
            }

            DB::commit();

            $ingItem = $primaryRecipe->ingredient ?: ($primaryRecipe->legacyIngredient ?: $selectedIngredient);
            $itemPayload = [
                'nid' => $primaryRecipe->nid,
                'ingredientId' => $primaryRecipe->nid_ingredient,
                'name' => strtoupper((string) ($ingItem->cname ?? $selectedIngredient->cname)),
                'qty' => (float) $primaryRecipe->nqty,
                'unit' => $ingItem->csatuan ?: ($selectedIngredient->csatuan ?: 'PCS'),
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
        $recipeRow = MposRecipe::with(['ingredient', 'legacyIngredient'])
            ->where('nid_product', $parentProduct->nid)
            ->where('nid', $recipe)
            ->firstOrFail();

        $newQty = (float) $validated['nqty'];
        $ing = $recipeRow->ingredient ?: $recipeRow->legacyIngredient;
        $ingredientName = optional($ing)->cname;

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
                    $matchingProdIds = MposProduct::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $ingredientName))])
                        ->pluck('nid');

                    $matchingLegacyIds = MposIngredients::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $ingredientName))])
                        ->pluck('nid');

                    $allMatchingIds = $matchingProdIds->merge($matchingLegacyIds)->unique()->filter()->values();

                    if ($allMatchingIds->isNotEmpty()) {
                        MposRecipe::whereIn('nid_product', $siblingProductIds)
                            ->whereIn('nid_ingredient', $allMatchingIds)
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
                        'name' => strtoupper((string) ($ing?->cname ?? 'BAHAN BAKU')),
                        'qty' => (float) $recipeRow->nqty,
                        'unit' => $ing?->csatuan ?: 'PCS',
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
        $recipeRow = MposRecipe::with(['ingredient', 'legacyIngredient'])
            ->where('nid_product', $parentProduct->nid)
            ->where('nid', $recipe)
            ->firstOrFail();

        $ing = $recipeRow->ingredient ?: $recipeRow->legacyIngredient;
        $ingredientName = optional($ing)->cname;

        DB::beginTransaction();
        try {
            $recipeRow->delete();

            // Hapus juga dari produk serupa di outlet lain agar tetap konsisten
            if ($ingredientName) {
                $siblingProductIds = MposProduct::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $parentProduct->cname))])
                    ->where('nid', '!=', $parentProduct->nid)
                    ->pluck('nid');

                if ($siblingProductIds->isNotEmpty()) {
                    $matchingProdIds = MposProduct::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $ingredientName))])
                        ->pluck('nid');

                    $matchingLegacyIds = MposIngredients::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $ingredientName))])
                        ->pluck('nid');

                    $allMatchingIds = $matchingProdIds->merge($matchingLegacyIds)->unique()->filter()->values();

                    if ($allMatchingIds->isNotEmpty()) {
                        MposRecipe::whereIn('nid_product', $siblingProductIds)
                            ->whereIn('nid_ingredient', $allMatchingIds)
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

    public function import(Request $request, string $product): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
        ]);

        $parentProduct = MposProduct::findOrFail($product);
        $onlyProductName = $request->boolean('only_current_product') ? $parentProduct->cname : null;

        $importer = new ProductRecipesImport((int) $parentProduct->nid_outlet, $onlyProductName);
        Excel::import($importer, $request->file('file'));

        $summary = $importer->summary;
        $importedCount = $summary['imported_count'];
        $menuCount = count($summary['menus']);

        if ($importedCount === 0 && $summary['skipped_count'] === 0) {
            return redirect()->back()
                ->with('warning', 'Tidak ada data resep/bundle yang ditemukan. Pastikan header berisi NAMA MENU dan ISI BUNDLE.')
                ->with('active_tab', 'resep');
        }

        $message = $importedCount > 0
            ? "Import resep selesai: {$importedCount} item bahan untuk {$menuCount} menu berhasil diproses."
            : 'Tidak ada item resep yang berhasil diimport.';

        $redirect = redirect()->back()
            ->with($importedCount > 0 ? 'success' : 'warning', $message)
            ->with('active_tab', 'resep');

        if ($summary['skipped_count'] > 0) {
            $redirect->with('recipe_import_warnings', [
                'count' => $summary['skipped_count'],
                'reasons' => $summary['skipped_reasons'],
            ]);
        }

        return $redirect;
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
