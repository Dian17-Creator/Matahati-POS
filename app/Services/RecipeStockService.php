<?php

namespace App\Services;

use App\Models\MposCombo;
use App\Models\MposIngredients;
use App\Models\MposProduct;
use App\Models\MposRecipe;
use Illuminate\Support\Collection;

class RecipeStockService
{
    /**
     * Hitung HPP per 1 unit untuk kumpulan produk pada outlet tertentu berdasarkan resep & kombo.
     *
     * @param iterable<int, MposProduct> $products
     * @param int $outletId
     * @return array<int, float>
     */
    public function calculateUnitCosts(iterable $products, int $outletId): array
    {
        $unitCosts = [];

        foreach ($products as $product) {
            $unitCosts[(int) $product->nid] = $this->computeProductUnitCost($product, $outletId, 0);
        }

        return $unitCosts;
    }

    /**
     * Validasi & kurangi stok produk (jika ftrack_stock aktif) serta stok bahan baku resep/kombo saat penjualan lunas.
     *
     * @param array<int, int|float> $requestedQtys
     * @param int $outletId
     * @return array{success: bool, message: ?string, total_cost: float}
     */
    public function deductForSale(array $requestedQtys, int $outletId): array
    {
        if (empty($requestedQtys)) {
            return [
                'success' => true,
                'message' => null,
                'total_cost' => 0.0,
            ];
        }

        $requirements = $this->collectRequirements($requestedQtys, $outletId);
        $productDeductions = $requirements['products'];
        $ingredientDeductions = $requirements['ingredients'];

        if (!empty($productDeductions)) {
            $lockedProducts = MposProduct::whereIn('nid', array_keys($productDeductions))
                ->lockForUpdate()
                ->get()
                ->keyBy('nid');

            foreach ($productDeductions as $prodId => $neededQty) {
                $prod = $lockedProducts->get($prodId);
                if (!$prod) {
                    continue;
                }

                $intNeededQty = (int) ceil((float) $neededQty);
                if ((int) $prod->nqty < $intNeededQty) {
                    return [
                        'success' => false,
                        'message' => "Stok tidak cukup untuk '{$prod->cname}'. Sisa stok: {$prod->nqty}",
                        'total_cost' => 0.0,
                    ];
                }

                $prod->nqty = (int) $prod->nqty - $intNeededQty;
                $prod->save();
            }
        }

        $totalIngredientCost = 0.0;

        foreach ($ingredientDeductions as $ingReq) {
            $neededIngQty = (float) $ingReq['qty'];
            if ($neededIngQty <= 0) {
                continue;
            }

            $outletIngredient = $this->resolveLockedOutletIngredient($ingReq['template'], $outletId);
            $outletIngredient->nstock = round((float) $outletIngredient->nstock - $neededIngQty, 2);
            $outletIngredient->save();

            $ingPrice = MposIngredients::hasPriceColumn() ? (float) ($outletIngredient->nprice ?? 0) : 0.0;
            $totalIngredientCost += ($neededIngQty * $ingPrice);
        }

        return [
            'success' => true,
            'message' => null,
            'total_cost' => round($totalIngredientCost, 2),
        ];
    }

    /**
     * Kembalikan stok produk (jika ftrack_stock aktif) serta stok bahan baku saat transaksi lunas di-VOID / REFUND.
     *
     * @param array<int, int|float> $reversedQtys
     * @param int $outletId
     * @return float
     */
    public function restoreForReversal(array $reversedQtys, int $outletId): float
    {
        if (empty($reversedQtys)) {
            return 0.0;
        }

        $requirements = $this->collectRequirements($reversedQtys, $outletId);
        $productRestores = $requirements['products'];
        $ingredientRestores = $requirements['ingredients'];

        if (!empty($productRestores)) {
            $lockedProducts = MposProduct::whereIn('nid', array_keys($productRestores))
                ->lockForUpdate()
                ->get()
                ->keyBy('nid');

            foreach ($productRestores as $prodId => $restoreQty) {
                $prod = $lockedProducts->get($prodId);
                if (!$prod) {
                    continue;
                }

                $intRestoreQty = (int) ceil((float) $restoreQty);
                $prod->nqty = (int) $prod->nqty + $intRestoreQty;
                $prod->save();
            }
        }

        $totalRestoredCost = 0.0;

        foreach ($ingredientRestores as $ingReq) {
            $restoreIngQty = (float) $ingReq['qty'];
            if ($restoreIngQty <= 0) {
                continue;
            }

            $outletIngredient = $this->resolveLockedOutletIngredient($ingReq['template'], $outletId);
            $outletIngredient->nstock = round((float) $outletIngredient->nstock + $restoreIngQty, 2);
            $outletIngredient->save();

            $ingPrice = MposIngredients::hasPriceColumn() ? (float) ($outletIngredient->nprice ?? 0) : 0.0;
            $totalRestoredCost += ($restoreIngQty * $ingPrice);
        }

        return round($totalRestoredCost, 2);
    }

    /**
     * Kumpulkan total kebutuhan stok produk jadi dan bahan baku (termasuk ekspansi isi produk kombo).
     *
     * @param array<int, int|float> $productQtys
     * @param int $outletId
     * @return array{products: array<int, float>, ingredients: array<string, array{template: MposIngredients, qty: float}>}
     */
    protected function collectRequirements(array $productQtys, int $outletId): array
    {
        $productIds = array_keys($productQtys);
        $products = MposProduct::with(['category', 'recipes.ingredient'])
            ->whereIn('nid', $productIds)
            ->get()
            ->keyBy('nid');

        $productDeductions = [];
        $ingredientDeductions = [];

        foreach ($productQtys as $productId => $qty) {
            $numQty = (float) $qty;
            if ($numQty <= 0) {
                continue;
            }

            $product = $products->get($productId);
            if (!$product) {
                continue;
            }

            $this->accumulateProductRequirements(
                $product,
                $numQty,
                $outletId,
                $productDeductions,
                $ingredientDeductions,
                0
            );
        }

        return [
            'products' => $productDeductions,
            'ingredients' => $ingredientDeductions,
        ];
    }

    /**
     * Akumulasi rekursif kebutuhan produk dan resep bahan baku (mendukung produk reguler & produk kombo).
     *
     * @param MposProduct $product
     * @param float $multiplierQty
     * @param int $outletId
     * @param array<int, float> $productDeductions
     * @param array<string, array{template: MposIngredients, qty: float}> $ingredientDeductions
     * @param int $depth
     * @return void
     */
    protected function accumulateProductRequirements(
        MposProduct $product,
        float $multiplierQty,
        int $outletId,
        array &$productDeductions,
        array &$ingredientDeductions,
        int $depth
    ): void {
        if ($depth > 2 || $multiplierQty <= 0) {
            return;
        }

        $recipes = $this->getRecipesForProduct($product);
        foreach ($recipes as $recipe) {
            if (!$recipe->ingredient) {
                continue;
            }

            $ingKey = strtolower(trim((string) $recipe->ingredient->cname));
            if ($ingKey === '') {
                continue;
            }

            $requiredIngQty = $multiplierQty * (float) $recipe->nqty;
            if ($requiredIngQty <= 0) {
                continue;
            }

            if (!isset($ingredientDeductions[$ingKey])) {
                $ingredientDeductions[$ingKey] = [
                    'template' => $recipe->ingredient,
                    'qty' => 0.0,
                ];
            }
            $ingredientDeductions[$ingKey]['qty'] += $requiredIngQty;
        }

        if ((bool) $product->fcombo) {
            $comboItems = $this->getComboItemsForProduct($product);
            if ($comboItems->isNotEmpty()) {
                foreach ($comboItems as $comboItem) {
                    if (!$comboItem->product) {
                        continue;
                    }

                    $childProduct = $this->resolveOutletProduct($comboItem->product, $outletId);
                    $childQty = $multiplierQty * (float) $comboItem->nqty;

                    $this->accumulateProductRequirements(
                        $childProduct,
                        $childQty,
                        $outletId,
                        $productDeductions,
                        $ingredientDeductions,
                        $depth + 1
                    );
                }

                return;
            }
        }

        $isTrackStock = true;
        if ($product->category && isset($product->category->ftrack_stock)) {
            $isTrackStock = (bool) $product->category->ftrack_stock;
        }

        if ($isTrackStock) {
            $prodId = (int) $product->nid;
            $productDeductions[$prodId] = ($productDeductions[$prodId] ?? 0.0) + $multiplierQty;
        }
    }

    /**
     * Hitung HPP per 1 unit produk dari resep langsung dan isi kombo (bila fcombo aktif).
     */
    protected function computeProductUnitCost(MposProduct $product, int $outletId, int $depth): float
    {
        if ($depth > 2 || !MposIngredients::hasPriceColumn()) {
            return 0.0;
        }

        $cost = 0.0;
        $recipes = $this->getRecipesForProduct($product);

        foreach ($recipes as $recipe) {
            if (!$recipe->ingredient) {
                continue;
            }

            $outletIng = MposIngredients::where('nid_outlet', $outletId)
                ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $recipe->ingredient->cname))])
                ->first();

            $ingPrice = $outletIng
                ? (float) ($outletIng->nprice ?? 0)
                : (float) ($recipe->ingredient->nprice ?? 0);

            $cost += ((float) $recipe->nqty * $ingPrice);
        }

        if ((bool) $product->fcombo) {
            $comboItems = $this->getComboItemsForProduct($product);
            foreach ($comboItems as $comboItem) {
                if (!$comboItem->product) {
                    continue;
                }

                $childProduct = $this->resolveOutletProduct($comboItem->product, $outletId);
                $childUnitCost = $this->computeProductUnitCost($childProduct, $outletId, $depth + 1);
                $cost += ((float) $comboItem->nqty * $childUnitCost);
            }
        }

        return round($cost, 2);
    }

    /**
     * Ambil daftar resep untuk produk (dengan fallback pencocokan nama produk bila resep tersimpan di outlet induk).
     *
     * @param MposProduct $product
     * @return Collection<int, MposRecipe>
     */
    protected function getRecipesForProduct(MposProduct $product): Collection
    {
        $recipes = $product->relationLoaded('recipes')
            ? $product->recipes
            : MposRecipe::with('ingredient')->where('nid_product', $product->nid)->get();

        if ($recipes->isNotEmpty()) {
            $recipes->loadMissing('ingredient');
            return $recipes;
        }

        $siblingProductIds = MposProduct::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $product->cname))])
            ->where('nid', '!=', $product->nid)
            ->pluck('nid');

        if ($siblingProductIds->isEmpty()) {
            return collect();
        }

        foreach ($siblingProductIds as $siblingId) {
            $siblingRecipes = MposRecipe::with('ingredient')
                ->where('nid_product', $siblingId)
                ->get();

            if ($siblingRecipes->isNotEmpty()) {
                return $siblingRecipes;
            }
        }

        return collect();
    }

    /**
     * Ambil daftar isi kombo untuk produk kombo (dengan fallback pencocokan nama produk kombo lintas outlet).
     *
     * @param MposProduct $product
     * @return Collection<int, MposCombo>
     */
    protected function getComboItemsForProduct(MposProduct $product): Collection
    {
        $items = MposCombo::with('product.category')
            ->where('nid_combo_product', $product->nid)
            ->get();

        if ($items->isNotEmpty()) {
            return $items;
        }

        $siblingComboIds = MposProduct::where('fcombo', 1)
            ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $product->cname))])
            ->where('nid', '!=', $product->nid)
            ->pluck('nid');

        foreach ($siblingComboIds as $siblingId) {
            $siblingItems = MposCombo::with('product.category')
                ->where('nid_combo_product', $siblingId)
                ->get();

            if ($siblingItems->isNotEmpty()) {
                return $siblingItems;
            }
        }

        return collect();
    }

    /**
     * Temukan record produk yang sesuai dengan outlet transaksi (jika produk kombo menunjuk ke ID outlet lain).
     */
    protected function resolveOutletProduct(MposProduct $sourceProduct, int $outletId): MposProduct
    {
        if ((int) $sourceProduct->nid_outlet === $outletId) {
            return $sourceProduct;
        }

        $matched = MposProduct::with(['category', 'recipes.ingredient'])
            ->where('nid_outlet', $outletId)
            ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $sourceProduct->cname))])
            ->first();

        return $matched ?? $sourceProduct;
    }

    /**
     * Kunci (lockForUpdate) dan kembalikan record bahan baku milik outlet transaksi.
     */
    protected function resolveLockedOutletIngredient(MposIngredients $template, int $outletId): MposIngredients
    {
        $cleanName = trim((string) $template->cname);

        if ((int) $template->nid_outlet === $outletId) {
            $locked = MposIngredients::where('nid', $template->nid)->lockForUpdate()->first();
            if ($locked) {
                return $locked;
            }
        }

        $outletIng = MposIngredients::where('nid_outlet', $outletId)
            ->whereRaw('LOWER(cname) = ?', [strtolower($cleanName)])
            ->lockForUpdate()
            ->first();

        if ($outletIng) {
            return $outletIng;
        }

        if (empty($template->nid_outlet)) {
            $lockedNull = MposIngredients::where('nid', $template->nid)->lockForUpdate()->first();
            if ($lockedNull) {
                $lockedNull->nid_outlet = $outletId;
                $lockedNull->save();
                return $lockedNull;
            }
        }

        $payload = [
            'cname' => $cleanName,
            'nid_outlet' => $outletId,
            'csatuan' => $template->csatuan ?: 'PCS',
            'nstock' => 0,
        ];

        if (MposIngredients::hasPriceColumn()) {
            $payload['nprice'] = (float) ($template->nprice ?? 0);
        }

        return MposIngredients::create($payload);
    }
}

