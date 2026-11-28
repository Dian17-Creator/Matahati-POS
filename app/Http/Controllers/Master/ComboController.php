<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposCombo;
use App\Models\MposGrpProduct;
use App\Models\MposProduct;
use App\Traits\ResolvesOutletScope;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ComboController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request): View
    {
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $isSuperAdmin = $this->isSuperAdminUser();
        $outlets = $this->getAvailableOutlets();

        $query = MposProduct::with(['outlet', 'comboItems.product'])
            ->where('fcombo', 1);

        if (! empty($selectedOutletId)) {
            $query->where('nid_outlet', $selectedOutletId);
        } elseif (! $isSuperAdmin) {
            $query->whereIn('nid_outlet', $allowedOutletIds);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('cname', 'like', "%{$search}%");
        }

        $comboProducts = $query->orderBy('cname')
            ->orderBy('nid_outlet')
            ->paginate(10)
            ->withQueryString();

        $copySourceQuery = MposProduct::with(['outlet', 'comboItems'])
            ->where('fcombo', 1);
        if (! empty($selectedOutletId)) {
            $copySourceQuery->where('nid_outlet', $selectedOutletId);
        } elseif (! $isSuperAdmin) {
            $copySourceQuery->whereIn('nid_outlet', $allowedOutletIds);
        }
        $allSourceCombos = $copySourceQuery->orderBy('cname')->get();

        return view('combos.index', compact(
            'comboProducts',
            'allSourceCombos',
            'outlets',
            'selectedOutletId',
            'isSuperAdmin',
            'allowedOutletIds'
        ));
    }

    public function create(Request $request): View
    {
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $isSuperAdmin = $this->isSuperAdminUser();
        $isOutletLocked = $this->isOutletLocked();
        $outlets = $this->getAvailableOutlets();

        $regularProductsQuery = MposProduct::with('outlet')
            ->where('fcombo', 0)
            ->where('cstatus', 'ACTIVE');

        if (! empty($selectedOutletId)) {
            $regularProductsQuery->where('nid_outlet', $selectedOutletId);
        } elseif (! $isSuperAdmin) {
            $regularProductsQuery->whereIn('nid_outlet', $allowedOutletIds);
        }

        $regularProducts = $regularProductsQuery
            ->orderBy('cname')
            ->get(['nid', 'cname', 'nid_outlet', 'nprice', 'cphotos'])
            ->unique(fn (MposProduct $p): string => strtolower(trim((string) $p->cname)))
            ->values();

        return view('combos.create', compact(
            'outlets',
            'selectedOutletId',
            'allowedOutletIds',
            'isSuperAdmin',
            'isOutletLocked',
            'regularProducts'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'nprice' => $this->normalizePriceInput($request->input('nprice')),
            'nprice_online' => $this->normalizePriceInput($request->input('nprice_online')),
            'nprice_buy' => $this->normalizePriceInput($request->input('nprice_buy')),
        ]);

        $validated = $request->validate([
            'cname' => 'required|string|max:255',
            'nid_outlets' => 'required|array|min:1',
            'nid_outlets.*' => 'required|integer|exists:mpos_outlet,nid',
            'nprice' => 'required|numeric|min:0',
            'nprice_online' => 'nullable|numeric|min:0',
            'nprice_buy' => 'nullable|numeric|min:0',
            'dpublish_date' => 'nullable|date',
            'is_published' => 'nullable|boolean',
            'photo' => 'nullable|image|max:2048',
            'nid_product' => 'required|array|min:1',
            'nid_product.*' => 'required|integer|exists:mpos_product,nid',
            'nqty' => 'required|array|min:1',
            'nqty.*' => 'required|numeric|min:1',
        ]);

        $products = $validated['nid_product'];
        $qtys = $validated['nqty'];

        if (count($products) !== count(array_unique($products))) {
            return back()->with('error', 'Terdapat produk isi yang duplikat dalam satu kombo.')->withInput();
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/product'), $filename);
            $photoPath = 'uploads/product/' . $filename;
        }

        $isPublished = $request->boolean('is_published', true);
        $status = $isPublished ? 'ACTIVE' : 'INACTIVE';
        $priceOffline = (float) $validated['nprice'];
        $priceOnline = isset($validated['nprice_online']) && (float) $validated['nprice_online'] > 0
            ? (float) $validated['nprice_online']
            : $priceOffline;
        $priceBuy = (float) ($validated['nprice_buy'] ?? 0);
        $publishDate = ! empty($validated['dpublish_date']) ? $validated['dpublish_date'] : null;

        $selectedChildProducts = MposProduct::whereIn('nid', $products)->get()->keyBy('nid');
        $createdIds = [];

        DB::beginTransaction();
        try {
            foreach ($validated['nid_outlets'] as $outletId) {
                $outletIdInt = (int) $outletId;
                $defaultCategory = MposGrpProduct::where('nid_outlet', $outletIdInt)
                    ->orderByRaw("CASE WHEN UPPER(cname) IN ('PAKET', 'COMBO', 'OTHERS') THEN 0 ELSE 1 END")
                    ->orderBy('nid')
                    ->first();

                if (! $defaultCategory) {
                    $defaultCategory = MposGrpProduct::create([
                        'cname' => 'PAKET',
                        'nid_outlet' => $outletIdInt,
                        'fshow_pos' => 1,
                        'ftrack_stock' => 1,
                    ]);
                }

                $comboProduct = MposProduct::create([
                    'cname' => trim($validated['cname']),
                    'nid_category' => $defaultCategory->nid,
                    'nid_outlet' => $outletIdInt,
                    'nprice' => $priceOffline,
                    'nprice_online' => $priceOnline,
                    'nprice_buy' => $priceBuy,
                    'nqty' => 0,
                    'cstatus' => $status,
                    'fcombo' => 1,
                    'cphotos' => $photoPath,
                    'dcreated' => Carbon::now(),
                    'dpublish_date' => $publishDate,
                ]);

                $createdIds[] = $comboProduct->nid;

                foreach ($products as $index => $childId) {
                    $sourceChild = $selectedChildProducts->get((int) $childId);
                    $resolvedChildId = (int) $childId;

                    if ($sourceChild && (int) $sourceChild->nid_outlet !== $outletIdInt) {
                        $matched = MposProduct::where('nid_outlet', $outletIdInt)
                            ->where('fcombo', 0)
                            ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $sourceChild->cname))])
                            ->first(['nid']);
                        if ($matched) {
                            $resolvedChildId = (int) $matched->nid;
                        }
                    }

                    MposCombo::create([
                        'nid_combo_product' => $comboProduct->nid,
                        'nid_product' => $resolvedChildId,
                        'nqty' => (float) ($qtys[$index] ?? 1),
                    ]);
                }
            }

            DB::commit();

            if (count($createdIds) === 1) {
                return redirect()->route('combos.show', $createdIds[0])
                    ->with('success', 'Paket kombo berhasil ditambahkan.');
            }

            return redirect()->route('combos.index')
                ->with('success', 'Paket kombo berhasil ditambahkan ke ' . count($createdIds) . ' outlet.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Request $request, string $id): View
    {
        $combo = MposProduct::with(['outlet', 'comboItems.product.category'])
            ->where('fcombo', 1)
            ->findOrFail($id);

        $allowedOutletIds = $this->getUserAllowedOutletIds();
        if (! $this->isSuperAdminUser() && ! in_array((int) $combo->nid_outlet, $allowedOutletIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke paket kombo outlet ini.');
        }

        $regularProducts = MposProduct::where('fcombo', 0)
            ->where('cstatus', 'ACTIVE')
            ->where('nid_outlet', $combo->nid_outlet)
            ->orderBy('cname')
            ->get(['nid', 'cname', 'nprice', 'cphotos']);

        return view('combos.show', compact('combo', 'regularProducts'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $combo = MposProduct::where('fcombo', 1)->findOrFail($id);

        $mergePrices = [];
        foreach (['nprice', 'nprice_online', 'nprice_buy'] as $priceField) {
            if ($request->has($priceField)) {
                $mergePrices[$priceField] = $this->normalizePriceInput($request->input($priceField));
            }
        }
        if (! empty($mergePrices)) {
            $request->merge($mergePrices);
        }

        $validated = $request->validate([
            'cname' => 'sometimes|required|string|max:255',
            'nprice' => 'sometimes|required|numeric|min:0',
            'nprice_online' => 'nullable|numeric|min:0',
            'nprice_buy' => 'nullable|numeric|min:0',
            'dpublish_date' => 'nullable|date',
            'is_published' => 'nullable|boolean',
            'photo' => 'nullable|image|max:2048',
        ]);

        $updateData = [];
        if (array_key_exists('cname', $validated)) {
            $updateData['cname'] = trim($validated['cname']);
        }
        if (array_key_exists('nprice', $validated)) {
            $updateData['nprice'] = (float) $validated['nprice'];
        }
        if ($request->has('nprice_online')) {
            $updateData['nprice_online'] = (float) ($validated['nprice_online'] ?? 0);
        }
        if ($request->has('nprice_buy')) {
            $updateData['nprice_buy'] = (float) ($validated['nprice_buy'] ?? 0);
        }
        if ($request->has('dpublish_date')) {
            $updateData['dpublish_date'] = ! empty($validated['dpublish_date']) ? $validated['dpublish_date'] : null;
        }
        if ($request->has('has_publish_toggle')) {
            $updateData['cstatus'] = $request->boolean('is_published') ? 'ACTIVE' : 'INACTIVE';
        }

        if ($request->hasFile('photo')) {
            if ($combo->cphotos && file_exists(public_path($combo->cphotos))) {
                $usageCount = MposProduct::where('cphotos', $combo->cphotos)
                    ->where('nid', '!=', $combo->nid)
                    ->count();
                if ($usageCount === 0) {
                    unlink(public_path($combo->cphotos));
                }
            }
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/product'), $filename);
            $updateData['cphotos'] = 'uploads/product/' . $filename;
        }

        if (! empty($updateData)) {
            $updateData['updated_at'] = Carbon::now();
            $combo->update($updateData);
        }

        return redirect()->route('combos.show', $combo->nid)
            ->with('success', 'Informasi paket kombo berhasil diperbarui.');
    }

    public function updateItems(Request $request, string $id): RedirectResponse
    {
        $combo = MposProduct::where('fcombo', 1)->findOrFail($id);

        $validated = $request->validate([
            'nid_product' => 'required|array|min:1',
            'nid_product.*' => 'required|integer|exists:mpos_product,nid',
            'nqty' => 'required|array|min:1',
            'nqty.*' => 'required|numeric|min:1',
        ]);

        $products = $validated['nid_product'];
        $qtys = $validated['nqty'];

        if (count($products) !== count(array_unique($products))) {
            return back()->with('error', 'Terdapat produk isi yang duplikat dalam satu kombo.')->withInput();
        }

        if (in_array((int) $combo->nid, array_map('intval', $products), true)) {
            return back()->with('error', 'Produk kombo utama tidak boleh menjadi isi dari dirinya sendiri.')->withInput();
        }

        DB::beginTransaction();
        try {
            MposCombo::where('nid_combo_product', $combo->nid)->delete();

            foreach ($products as $index => $productId) {
                MposCombo::create([
                    'nid_combo_product' => $combo->nid,
                    'nid_product' => (int) $productId,
                    'nqty' => (float) ($qtys[$index] ?? 1),
                ]);
            }

            DB::commit();

            return redirect()->route('combos.show', $combo->nid)
                ->with('success', 'Isi produk dalam paket berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $combo = MposProduct::where('fcombo', 1)->findOrFail($id);

            if ($combo->cphotos && file_exists(public_path($combo->cphotos))) {
                $usageCount = MposProduct::where('cphotos', $combo->cphotos)
                    ->where('nid', '!=', $combo->nid)
                    ->count();
                if ($usageCount === 0) {
                    unlink(public_path($combo->cphotos));
                }
            }

            MposCombo::where('nid_combo_product', $combo->nid)->delete();
            $combo->delete();

            DB::commit();

            return redirect()->route('combos.index')
                ->with('success', 'Paket kombo berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus paket kombo: ' . $e->getMessage());
        }
    }

    public function copy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source_outlet_id' => 'required|integer|exists:mpos_outlet,nid',
            'target_outlet_ids' => 'required|array|min:1',
            'target_outlet_ids.*' => 'required|integer|exists:mpos_outlet,nid',
            'combo_ids' => 'required|array|min:1',
            'combo_ids.*' => 'required|integer|exists:mpos_product,nid',
            'overwrite_existing' => 'nullable|boolean',
        ], [
            'target_outlet_ids.required' => 'Pilih minimal 1 outlet tujuan.',
            'target_outlet_ids.min' => 'Pilih minimal 1 outlet tujuan.',
            'combo_ids.required' => 'Pilih minimal 1 paket produk yang akan disalin.',
            'combo_ids.min' => 'Pilih minimal 1 paket produk yang akan disalin.',
        ]);

        $sourceOutletId = (int) $validated['source_outlet_id'];
        $targetOutletIds = collect($validated['target_outlet_ids'])
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => $id === $sourceOutletId)
            ->unique()
            ->values()
            ->all();

        if (empty($targetOutletIds)) {
            return back()->with('error', 'Outlet tujuan harus berbeda dengan outlet asal.');
        }

        if (! $this->isSuperAdminUser()) {
            $allowedOutletIds = $this->getUserAllowedOutletIds();
            if (! in_array($sourceOutletId, $allowedOutletIds, true)) {
                return back()->with('error', 'Anda tidak memiliki akses ke outlet asal.');
            }
            foreach ($targetOutletIds as $targetId) {
                if (! in_array($targetId, $allowedOutletIds, true)) {
                    return back()->with('error', 'Anda tidak memiliki akses ke salah satu outlet tujuan.');
                }
            }
        }

        $overwriteExisting = $request->boolean('overwrite_existing');

        $sourceCombos = MposProduct::with(['category', 'comboItems.product.category'])
            ->where('nid_outlet', $sourceOutletId)
            ->where('fcombo', 1)
            ->whereIn('nid', $validated['combo_ids'])
            ->get();

        if ($sourceCombos->isEmpty()) {
            return back()->with('error', 'Paket produk asal tidak ditemukan.');
        }

        $copiedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($targetOutletIds as $targetOutletId) {
                foreach ($sourceCombos as $sourceCombo) {
                    $existingCombo = MposProduct::where('nid_outlet', $targetOutletId)
                        ->where('fcombo', 1)
                        ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $sourceCombo->cname))])
                        ->first();

                    if ($existingCombo && ! $overwriteExisting) {
                        $skippedCount++;
                        continue;
                    }

                    $targetCategoryId = $this->resolveTargetCategoryId($targetOutletId, $sourceCombo->category);

                    $payload = [
                        'cname' => $sourceCombo->cname,
                        'nid_category' => $targetCategoryId,
                        'nid_outlet' => $targetOutletId,
                        'nprice' => $sourceCombo->nprice,
                        'nprice_online' => $sourceCombo->nprice_online,
                        'nprice_buy' => $sourceCombo->nprice_buy ?? 0,
                        'nqty' => 0,
                        'cstatus' => $sourceCombo->cstatus ?? 'ACTIVE',
                        'fcombo' => 1,
                        'cphotos' => $sourceCombo->cphotos,
                        'dpublish_date' => $sourceCombo->dpublish_date,
                    ];

                    if ($existingCombo) {
                        $existingCombo->update($payload);
                        MposCombo::where('nid_combo_product', $existingCombo->nid)->delete();
                        $targetCombo = $existingCombo;
                        $updatedCount++;
                    } else {
                        $payload['dcreated'] = Carbon::now();
                        $targetCombo = MposProduct::create($payload);
                        $copiedCount++;
                    }

                    foreach ($sourceCombo->comboItems as $comboItem) {
                        $sourceChild = $comboItem->product;
                        if (! $sourceChild) {
                            continue;
                        }

                        $targetChild = MposProduct::where('nid_outlet', $targetOutletId)
                            ->where('fcombo', 0)
                            ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $sourceChild->cname))])
                            ->first();

                        if (! $targetChild) {
                            $childCategoryId = $this->resolveTargetCategoryId($targetOutletId, $sourceChild->category);
                            $targetChild = $sourceChild->replicate();
                            $targetChild->nid_outlet = $targetOutletId;
                            $targetChild->nid_category = $childCategoryId;
                            $targetChild->nqty = 0;
                            $targetChild->dcreated = Carbon::now();
                            $targetChild->save();
                        }

                        MposCombo::create([
                            'nid_combo_product' => $targetCombo->nid,
                            'nid_product' => $targetChild->nid,
                            'nqty' => $comboItem->nqty,
                        ]);
                    }
                }
            }

            DB::commit();

            $parts = [];
            if ($copiedCount > 0) {
                $parts[] = "{$copiedCount} paket berhasil disalin";
            }
            if ($updatedCount > 0) {
                $parts[] = "{$updatedCount} paket diperbarui";
            }
            if ($skippedCount > 0) {
                $parts[] = "{$skippedCount} paket dilewati karena sudah ada";
            }

            $message = ! empty($parts)
                ? 'Salin paket selesai: ' . implode(', ', $parts) . '.'
                : 'Tidak ada paket yang disalin.';

            return redirect()->route('combos.index')
                ->with(($copiedCount > 0 || $updatedCount > 0) ? 'success' : 'warning', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menyalin paket produk: ' . $e->getMessage());
        }
    }

    private function resolveTargetCategoryId(int $targetOutletId, ?MposGrpProduct $sourceCategory): int
    {
        if ($sourceCategory) {
            $matched = MposGrpProduct::where('nid_outlet', $targetOutletId)
                ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $sourceCategory->cname))])
                ->first();
            if ($matched) {
                return (int) $matched->nid;
            }

            $created = MposGrpProduct::create([
                'cname' => $sourceCategory->cname,
                'nid_outlet' => $targetOutletId,
                'fshow_pos' => (bool) ($sourceCategory->fshow_pos ?? true),
                'ftrack_stock' => (bool) ($sourceCategory->ftrack_stock ?? true),
            ]);

            return (int) $created->nid;
        }

        $fallback = MposGrpProduct::where('nid_outlet', $targetOutletId)
            ->orderByRaw("CASE WHEN UPPER(cname) IN ('PAKET', 'COMBO', 'OTHERS') THEN 0 ELSE 1 END")
            ->orderBy('nid')
            ->first();

        if ($fallback) {
            return (int) $fallback->nid;
        }

        $createdFallback = MposGrpProduct::create([
            'cname' => 'PAKET',
            'nid_outlet' => $targetOutletId,
            'fshow_pos' => 1,
            'ftrack_stock' => 1,
        ]);

        return (int) $createdFallback->nid;
    }

    protected function normalizePriceInput(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = preg_replace('/[^\d]/', '', (string) $value);

        return $cleaned === '' ? '0' : $cleaned;
    }
}
