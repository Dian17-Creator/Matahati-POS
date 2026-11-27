<?php

namespace App\Imports;

use App\Models\MposDeliveryD;
use App\Models\MposDeliveryS;
use App\Models\MposProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FoodDeliveryImport implements ToCollection, WithHeadingRow
{
    /**
     * @var array{
     *     imported_count: int,
     *     platforms: array<string, int>,
     *     skipped_count: int,
     *     skipped_reasons: array<int, string>
     * }
     */
    public array $summary = [
        'imported_count' => 0,
        'platforms' => [],
        'skipped_count' => 0,
        'skipped_reasons' => [],
    ];

    public function __construct(
        public int $outletId,
        public ?string $onlyPlatform = null,
    ) {
    }

    public function collection(Collection $rows): void
    {
        if ($this->outletId <= 0 || $rows->isEmpty()) {
            return;
        }

        MposDeliveryS::ensureDefaultsForOutlet($this->outletId);

        /** @var Collection<string, MposDeliveryS> $settingsByPlatform */
        $settingsByPlatform = MposDeliveryS::where('nid_outlet', $this->outletId)
            ->get()
            ->keyBy(fn (MposDeliveryS $item): string => strtoupper((string) $item->cplatform));

        /** @var Collection<string, MposProduct> $productsByName */
        $productsByName = MposProduct::where('nid_outlet', $this->outletId)
            ->orderByRaw("CASE WHEN UPPER(cstatus) = 'ACTIVE' THEN 0 ELSE 1 END ASC")
            ->get(['nid', 'cname', 'nprice'])
            ->keyBy(fn (MposProduct $product): string => $this->normalizeName((string) $product->cname));

        $normalizedOnlyPlatform = $this->onlyPlatform !== null
            ? $this->normalizePlatform($this->onlyPlatform)
            : null;

        DB::transaction(function () use ($rows, $settingsByPlatform, $productsByName, $normalizedOnlyPlatform): void {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $rowArray = is_array($row) ? $row : $row->toArray();

                $rawType = trim((string) ($rowArray['type'] ?? ''));
                $rawProduct = trim((string) ($rowArray['product'] ?? ''));
                $rawVariant = trim((string) ($rowArray['variant'] ?? ''));
                $rawCombo = trim((string) ($rowArray['product_combo'] ?? ''));
                $rawAddon = trim((string) ($rowArray['product_addon'] ?? ''));

                if ($rawType === '' && $rawProduct === '' && $rawCombo === '' && $rawVariant === '' && $rawAddon === '') {
                    continue;
                }

                $platformCode = $this->normalizePlatform($rawType);
                if ($platformCode === '' || ! $settingsByPlatform->has($platformCode)) {
                    $this->addSkipped($rowNumber, "Merchant/type '{$rawType}' tidak dikenali.");
                    continue;
                }

                if ($normalizedOnlyPlatform !== null && $platformCode !== $normalizedOnlyPlatform) {
                    continue;
                }

                if ($rawAddon !== '') {
                    $this->addSkipped($rowNumber, "Baris addon '{$rawAddon}' dilewati (POS belum mendukung harga delivery per addon).");
                    continue;
                }

                $product = $this->resolveProduct($productsByName, $rawProduct, $rawVariant, $rawCombo);
                if (! $product) {
                    $label = $rawProduct !== '' ? $rawProduct : $rawCombo;
                    if ($rawVariant !== '') {
                        $label .= " ({$rawVariant})";
                    }
                    $this->addSkipped($rowNumber, "Produk '{$label}' tidak ditemukan pada outlet ini.");
                    continue;
                }

                $finalPrice = $this->resolveFinalPrice($rowArray, (float) $product->nprice);
                if ($finalPrice === null) {
                    $this->addSkipped($rowNumber, "Harga untuk '{$product->cname}' tidak valid.");
                    continue;
                }

                /** @var MposDeliveryS $setting */
                $setting = $settingsByPlatform->get($platformCode);

                MposDeliveryD::updateOrCreate(
                    [
                        'nid_delivery_s' => $setting->nid,
                        'nid_product' => $product->nid,
                    ],
                    [
                        'nprice' => $finalPrice,
                        'bis_active' => true,
                    ]
                );

                $this->summary['imported_count']++;
                $platformLabel = $setting->cname;
                $this->summary['platforms'][$platformLabel] = ($this->summary['platforms'][$platformLabel] ?? 0) + 1;
            }
        });
    }

    /**
     * @param  Collection<string, MposProduct>  $productsByName
     */
    private function resolveProduct(Collection $productsByName, string $productName, string $variantName, string $comboName): ?MposProduct
    {
        if ($productName !== '' && $variantName !== '') {
            $variantCandidates = [
                "{$productName} - {$variantName}",
                "{$productName} ({$variantName})",
                "{$productName} {$variantName}",
            ];

            foreach ($variantCandidates as $candidate) {
                $found = $productsByName->get($this->normalizeName($candidate));
                if ($found) {
                    return $found;
                }
            }

            return null;
        }

        if ($productName !== '') {
            return $productsByName->get($this->normalizeName($productName));
        }

        if ($comboName !== '') {
            return $productsByName->get($this->normalizeName($comboName));
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveFinalPrice(array $row, float $fallbackNormalPrice): ?float
    {
        $total = $this->parseNumber($row['total'] ?? null);
        if ($total !== null && $total >= 0) {
            return round($total, 2);
        }

        $normalPrice = $this->parseNumber($row['normal_price'] ?? null) ?? $fallbackNormalPrice;
        $markingPrice = $this->parseNumber($row['marking_price'] ?? null) ?? 0.0;
        $amountType = strtoupper(trim((string) ($row['marking_amount_type'] ?? 'PRICE')));
        $markingType = strtoupper(trim((string) ($row['marking_type'] ?? 'UP')));

        $delta = str_contains($amountType, 'PERCENT') || $amountType === '%'
            ? ($normalPrice * ($markingPrice / 100))
            : $markingPrice;

        $calculated = $markingType === 'DOWN'
            ? ($normalPrice - $delta)
            : ($normalPrice + $delta);

        return max(0.0, round($calculated, 2));
    }

    private function parseNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $cleaned = trim((string) $value);
        if ($cleaned === '') {
            return null;
        }

        $cleaned = str_ireplace(['rp', 'idr', ' '], '', $cleaned);

        if (is_numeric($cleaned)) {
            return (float) $cleaned;
        }

        // Handle thousand-separated strings like "28.955" or "28,955".
        $withoutSeparators = preg_replace('/[.,](?=\d{3}(?:\D|$))/', '', $cleaned);
        $withoutSeparators = str_replace(',', '.', (string) $withoutSeparators);

        return is_numeric($withoutSeparators) ? (float) $withoutSeparators : null;
    }

    private function normalizePlatform(string $rawType): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $rawType) ?? '');

        return match ($clean) {
            'GOFOOD', 'GOJEK' => MposDeliveryS::PLATFORM_GOFOOD,
            'GRABFOOD', 'GRAB' => MposDeliveryS::PLATFORM_GRABFOOD,
            'SHOPEEFOOD', 'SHOPEE' => MposDeliveryS::PLATFORM_SHOPEEFOOD,
            'TRAVELOKAEATS', 'TRAVELOKA' => MposDeliveryS::PLATFORM_TRAVELOKAEATS,
            default => $clean,
        };
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? $name));
    }

    private function addSkipped(int $rowNumber, string $reason): void
    {
        $this->summary['skipped_count']++;
        if (count($this->summary['skipped_reasons']) < 20) {
            $this->summary['skipped_reasons'][] = "Baris {$rowNumber}: {$reason}";
        }
    }
}
