<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Imports\FoodDeliveryImport;
use App\Models\MposDeliveryD;
use App\Models\MposDeliveryS;
use App\Models\MposOutlet;
use App\Models\MposProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class FoodDeliverySettingController extends Controller
{
    public function index(Request $request): View
    {
        $selectedOutletId = TaxSettingWebController::resolveSelectedOutletId();
        $selectedOutlet = $selectedOutletId > 0 ? MposOutlet::find($selectedOutletId) : null;
        $platforms = new Collection();

        if ($selectedOutletId > 0) {
            MposDeliveryS::ensureDefaultsForOutlet($selectedOutletId);

            $order = array_keys(MposDeliveryS::DEFAULT_PLATFORMS);

            $platforms = MposDeliveryS::where('nid_outlet', $selectedOutletId)
                ->withCount(['details as active_products_count' => function ($query): void {
                    $query->where('bis_active', true);
                }])
                ->get()
                ->sortBy(function (MposDeliveryS $item) use ($order): int {
                    $pos = array_search(strtoupper((string) $item->cplatform), $order, true);

                    return $pos === false ? 999 : (int) $pos;
                })
                ->values();
        }

        return view('settings.pos_food_delivery', compact(
            'selectedOutletId',
            'selectedOutlet',
            'platforms'
        ));
    }

    public function show(Request $request, string $platform): View
    {
        $normalizedPlatform = $this->normalizePlatform($platform);
        $selectedOutletId = TaxSettingWebController::resolveSelectedOutletId();
        $selectedOutlet = $selectedOutletId > 0 ? MposOutlet::find($selectedOutletId) : null;

        $deliverySetting = null;
        $addedDetails = new Collection();
        $availableProducts = new Collection();
        $categories = new Collection();
        $preset = $this->getPreset($selectedOutletId, $normalizedPlatform);

        if ($selectedOutletId > 0) {
            MposDeliveryS::ensureDefaultsForOutlet($selectedOutletId);

            $deliverySetting = MposDeliveryS::where('nid_outlet', $selectedOutletId)
                ->where('cplatform', $normalizedPlatform)
                ->firstOrFail();

            $addedDetails = MposDeliveryD::with(['product.category'])
                ->where('nid_delivery_s', $deliverySetting->nid)
                ->where('bis_active', true)
                ->get()
                ->filter(fn (MposDeliveryD $detail): bool => $detail->product !== null)
                ->sortBy(fn (MposDeliveryD $detail): string => strtolower((string) ($detail->product?->cname ?? '')))
                ->values();

            $addedProductIds = $addedDetails->pluck('nid_product')->all();

            $allOutletProducts = MposProduct::with('category')
                ->where('nid_outlet', $selectedOutletId)
                ->where('cstatus', 'ACTIVE')
                ->orderBy('cname')
                ->get(['nid', 'cname', 'nid_category', 'nprice', 'nprice_online', 'nqty', 'cphotos']);

            $availableProducts = $allOutletProducts
                ->reject(fn (MposProduct $product): bool => in_array((int) $product->nid, $addedProductIds, true))
                ->values();

            $categories = $availableProducts
                ->pluck('category')
                ->filter()
                ->unique('nid')
                ->sortBy('cname')
                ->values();
        }

        return view('settings.pos_food_delivery_show', compact(
            'selectedOutletId',
            'selectedOutlet',
            'deliverySetting',
            'addedDetails',
            'availableProducts',
            'categories',
            'preset',
            'normalizedPlatform'
        ));
    }

    public function toggle(Request $request, string $platform): RedirectResponse
    {
        $normalizedPlatform = $this->normalizePlatform($platform);

        $validated = $request->validate([
            'nid_outlet' => 'required|integer|exists:mpos_outlet,nid',
        ]);

        $outletId = (int) $validated['nid_outlet'];
        MposDeliveryS::ensureDefaultsForOutlet($outletId);

        $setting = MposDeliveryS::where('nid_outlet', $outletId)
            ->where('cplatform', $normalizedPlatform)
            ->firstOrFail();

        $setting->bis_active = $request->boolean('bis_active');
        $setting->save();

        $statusLabel = $setting->bis_active ? 'Aktif (ON)' : 'Tidak aktif (OFF)';

        return back()->with('success', "Status {$setting->cname} berhasil diubah menjadi {$statusLabel}.");
    }

    public function updatePreset(Request $request, string $platform): RedirectResponse
    {
        $normalizedPlatform = $this->normalizePlatform($platform);

        $validated = $request->validate([
            'nid_outlet' => 'required|integer|exists:mpos_outlet,nid',
            'mode' => 'required|string|in:markup,markdown',
            'unit' => 'required|string|in:percent,idr',
            'value' => 'required|numeric|min:0',
        ]);

        $outletId = (int) $validated['nid_outlet'];
        $preset = [
            'mode' => $validated['mode'],
            'unit' => $validated['unit'],
            'value' => (float) $validated['value'],
        ];

        session()->put($this->presetSessionKey($outletId, $normalizedPlatform), $preset);

        if ($request->boolean('apply_existing')) {
            MposDeliveryS::ensureDefaultsForOutlet($outletId);
            $setting = MposDeliveryS::where('nid_outlet', $outletId)
                ->where('cplatform', $normalizedPlatform)
                ->firstOrFail();

            $existingDetails = MposDeliveryD::with('product')
                ->where('nid_delivery_s', $setting->nid)
                ->where('bis_active', true)
                ->get();

            foreach ($existingDetails as $detail) {
                if (! $detail->product) {
                    continue;
                }
                $detail->nprice = $this->calculatePresetPrice((float) $detail->product->nprice, $preset);
                $detail->save();
            }
        }

        return redirect()
            ->route('settings.pos.food-delivery.show', ['platform' => strtolower($normalizedPlatform)])
            ->with('success', 'Preset harga berhasil disimpan.');
    }

    public function addProducts(Request $request, string $platform): RedirectResponse
    {
        $normalizedPlatform = $this->normalizePlatform($platform);

        $validated = $request->validate([
            'nid_outlet' => 'required|integer|exists:mpos_outlet,nid',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:mpos_product,nid',
            'mode' => 'nullable|string|in:markup,markdown',
            'unit' => 'nullable|string|in:percent,idr',
            'value' => 'nullable|numeric|min:0',
        ]);

        $outletId = (int) $validated['nid_outlet'];
        $applyExisting = $request->boolean('apply_existing');
        $productIds = $validated['product_ids'] ?? [];

        if (empty($productIds) && ! $applyExisting) {
            return back()->with('error', 'Pilih minimal 1 produk atau centang "Terapkan juga ke semua produk yang sudah ditambahkan".');
        }

        MposDeliveryS::ensureDefaultsForOutlet($outletId);

        $setting = MposDeliveryS::where('nid_outlet', $outletId)
            ->where('cplatform', $normalizedPlatform)
            ->firstOrFail();

        $preset = $this->getPreset($outletId, $normalizedPlatform);
        if ($request->filled('mode') && $request->filled('unit')) {
            $preset = [
                'mode' => $validated['mode'],
                'unit' => $validated['unit'],
                'value' => (float) ($validated['value'] ?? 0),
            ];
            session()->put($this->presetSessionKey($outletId, $normalizedPlatform), $preset);
        }

        $products = empty($productIds)
            ? new Collection()
            : MposProduct::where('nid_outlet', $outletId)
                ->whereIn('nid', $productIds)
                ->get(['nid', 'nprice']);

        DB::transaction(function () use ($setting, $products, $preset, $applyExisting): void {
            if ($applyExisting) {
                $existingDetails = MposDeliveryD::with('product')
                    ->where('nid_delivery_s', $setting->nid)
                    ->where('bis_active', true)
                    ->get();

                foreach ($existingDetails as $detail) {
                    if (! $detail->product) {
                        continue;
                    }
                    $detail->nprice = $this->calculatePresetPrice((float) $detail->product->nprice, $preset);
                    $detail->save();
                }
            }

            foreach ($products as $product) {
                $calculatedPrice = $this->calculatePresetPrice((float) $product->nprice, $preset);

                MposDeliveryD::updateOrCreate(
                    [
                        'nid_delivery_s' => $setting->nid,
                        'nid_product' => $product->nid,
                    ],
                    [
                        'nprice' => $calculatedPrice,
                        'bis_active' => true,
                    ]
                );
            }
        });

        $count = $products->count();
        $message = $count > 0
            ? "{$count} produk berhasil ditambahkan ke {$setting->cname}."
            : "Harga semua produk {$setting->cname} berhasil diperbarui.";

        return redirect()
            ->route('settings.pos.food-delivery.show', ['platform' => strtolower($normalizedPlatform)])
            ->with('success', $message);
    }

    public function updateProductPrice(Request $request, string $platform, int $productId): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $normalizedPlatform = $this->normalizePlatform($platform);

        $validated = $request->validate([
            'nid_outlet' => 'required|integer|exists:mpos_outlet,nid',
            'nprice' => 'required|numeric|min:0',
        ]);

        $outletId = (int) $validated['nid_outlet'];
        $setting = MposDeliveryS::where('nid_outlet', $outletId)
            ->where('cplatform', $normalizedPlatform)
            ->firstOrFail();

        $price = (float) $validated['nprice'];

        MposDeliveryD::updateOrCreate(
            [
                'nid_delivery_s' => $setting->nid,
                'nid_product' => $productId,
            ],
            [
                'nprice' => $price,
                'bis_active' => true,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'nprice' => $price,
                'formatted_price' => 'IDR ' . number_format($price, 0, ',', '.'),
            ]);
        }

        return redirect()
            ->route('settings.pos.food-delivery.show', ['platform' => strtolower($normalizedPlatform)])
            ->with('success', "Harga produk {$setting->cname} berhasil diperbarui.");
    }

    public function destroyProducts(Request $request, string $platform): RedirectResponse
    {
        $normalizedPlatform = $this->normalizePlatform($platform);

        $validated = $request->validate([
            'nid_outlet' => 'required|integer|exists:mpos_outlet,nid',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|integer',
        ]);

        $outletId = (int) $validated['nid_outlet'];
        $setting = MposDeliveryS::where('nid_outlet', $outletId)
            ->where('cplatform', $normalizedPlatform)
            ->firstOrFail();

        MposDeliveryD::where('nid_delivery_s', $setting->nid)
            ->whereIn('nid_product', $validated['product_ids'])
            ->delete();

        $count = count($validated['product_ids']);

        return redirect()
            ->route('settings.pos.food-delivery.show', ['platform' => strtolower($normalizedPlatform)])
            ->with('success', "{$count} produk berhasil dihapus dari {$setting->cname}.");
    }

    public function updateProducts(Request $request, string $platform): RedirectResponse
    {
        $normalizedPlatform = $this->normalizePlatform($platform);

        $validated = $request->validate([
            'nid_outlet' => 'required|integer|exists:mpos_outlet,nid',
            'bis_active' => 'nullable|boolean',
            'products' => 'nullable|array',
            'products.*.nid_product' => 'required|integer|exists:mpos_product,nid',
            'products.*.nprice' => 'nullable|numeric|min:0',
            'products.*.bis_active' => 'nullable|boolean',
        ]);

        $outletId = (int) $validated['nid_outlet'];
        MposDeliveryS::ensureDefaultsForOutlet($outletId);

        $setting = MposDeliveryS::where('nid_outlet', $outletId)
            ->where('cplatform', $normalizedPlatform)
            ->firstOrFail();

        DB::transaction(function () use ($request, $setting, $validated): void {
            if ($request->has('has_status_toggle')) {
                $setting->bis_active = $request->boolean('bis_active');
                $setting->save();
            }

            foreach ($validated['products'] ?? [] as $row) {
                $productId = (int) $row['nid_product'];
                $isActive = ! empty($row['bis_active']);
                $price = (float) ($row['nprice'] ?? 0);

                MposDeliveryD::updateOrCreate(
                    [
                        'nid_delivery_s' => $setting->nid,
                        'nid_product' => $productId,
                    ],
                    [
                        'nprice' => $price,
                        'bis_active' => $isActive,
                    ]
                );
            }
        });

        return redirect()
            ->route('settings.pos.food-delivery.show', ['platform' => strtolower($normalizedPlatform)])
            ->with('success', "Pengaturan produk & harga {$setting->cname} berhasil disimpan.");
    }

    public function importAll(Request $request): RedirectResponse
    {
        return $this->handleImport($request, null);
    }

    public function import(Request $request, string $platform): RedirectResponse
    {
        $normalizedPlatform = $this->normalizePlatform($platform);
        $onlyPlatform = $request->boolean('only_current_platform') ? $normalizedPlatform : null;

        return $this->handleImport($request, $onlyPlatform);
    }

    private function handleImport(Request $request, ?string $onlyPlatform): RedirectResponse
    {
        $validated = $request->validate([
            'nid_outlet' => 'required|integer|exists:mpos_outlet,nid',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
        ]);

        $outletId = (int) $validated['nid_outlet'];
        $importer = new FoodDeliveryImport($outletId, $onlyPlatform);

        Excel::import($importer, $request->file('file'));

        $summary = $importer->summary;
        $importedCount = $summary['imported_count'];

        if ($importedCount === 0 && $summary['skipped_count'] === 0) {
            return back()->with('warning', 'Tidak ada baris data yang cocok untuk diimport.');
        }

        $breakdown = collect($summary['platforms'])
            ->map(fn (int $count, string $name): string => "{$name}: {$count}")
            ->implode(', ');

        $message = $importedCount > 0
            ? "Import selesai: {$importedCount} produk berhasil diproses" . ($breakdown !== '' ? " ({$breakdown})." : '.')
            : 'Tidak ada produk yang berhasil diimport.';

        $redirect = back()->with($importedCount > 0 ? 'success' : 'warning', $message);

        if ($summary['skipped_count'] > 0) {
            $redirect->with('import_warnings', [
                'count' => $summary['skipped_count'],
                'reasons' => $summary['skipped_reasons'],
            ]);
        }

        return $redirect;
    }

    /**
     * @return array{mode: string, unit: string, value: float}
     */
    protected function getPreset(int $outletId, string $platform): array
    {
        $default = [
            'mode' => 'markup',
            'unit' => 'percent',
            'value' => 0.0,
        ];

        if ($outletId <= 0) {
            return $default;
        }

        $saved = session()->get($this->presetSessionKey($outletId, $platform));
        if (! is_array($saved)) {
            return $default;
        }

        return [
            'mode' => in_array($saved['mode'] ?? '', ['markup', 'markdown'], true) ? $saved['mode'] : 'markup',
            'unit' => in_array($saved['unit'] ?? '', ['percent', 'idr'], true) ? $saved['unit'] : 'percent',
            'value' => max(0.0, (float) ($saved['value'] ?? 0)),
        ];
    }

    protected function presetSessionKey(int $outletId, string $platform): string
    {
        return "delivery_preset_{$outletId}_{$platform}";
    }

    /**
     * @param  array{mode: string, unit: string, value: float}  $preset
     */
    protected function calculatePresetPrice(float $basePrice, array $preset): float
    {
        $value = (float) $preset['value'];
        if ($value <= 0) {
            return max(0.0, round($basePrice));
        }

        $delta = $preset['unit'] === 'percent'
            ? ($basePrice * ($value / 100))
            : $value;

        $result = $preset['mode'] === 'markdown'
            ? ($basePrice - $delta)
            : ($basePrice + $delta);

        return max(0.0, round($result));
    }

    protected function normalizePlatform(string $platform): string
    {
        $normalized = strtoupper(trim($platform));
        if (! array_key_exists($normalized, MposDeliveryS::DEFAULT_PLATFORMS)) {
            abort(404, 'Platform food delivery tidak ditemukan.');
        }

        return $normalized;
    }
}
