<?php

namespace App\Imports;

use App\Models\MposProduct;
use App\Models\MposRecipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductRecipesImport implements ToCollection
{
    /**
     * @var array{
     *     imported_count: int,
     *     menus: array<string, bool>,
     *     skipped_count: int,
     *     skipped_reasons: array<int, string>
     * }
     */
    public array $summary = [
        'imported_count' => 0,
        'menus' => [],
        'skipped_count' => 0,
        'skipped_reasons' => [],
    ];

    public function __construct(
        public int $outletId,
        public ?string $onlyProductName = null,
    ) {
    }

    public function collection(Collection $rows): void
    {
        if ($this->outletId <= 0 || $rows->isEmpty()) {
            return;
        }

        [$headerIndex, $headers] = $this->detectHeaderRow($rows);
        if ($headerIndex === null || empty($headers)) {
            return;
        }

        /** @var Collection<string, MposProduct> $productsByName */
        $productsByName = MposProduct::where('nid_outlet', $this->outletId)
            ->orderByRaw("CASE WHEN UPPER(cstatus) = 'ACTIVE' THEN 0 ELSE 1 END ASC")
            ->get(['nid', 'cname'])
            ->keyBy(fn (MposProduct $product): string => $this->normalizeName((string) $product->cname));

        $onlyNormalized = $this->onlyProductName !== null
            ? $this->normalizeName($this->onlyProductName)
            : null;

        DB::transaction(function () use ($rows, $headerIndex, $headers, $productsByName, $onlyNormalized): void {
            $currentMenuName = '';
            $currentMenuType = '';

            foreach ($rows->slice($headerIndex + 1) as $index => $rawRow) {
                $rowNumber = $index + 1;
                $row = $this->mapRowWithHeaders($rawRow, $headers);

                $rawMenu = trim((string) ($row['nama_menu'] ?? $row['menu'] ?? $row['nama_produk'] ?? ''));
                $rawType = strtoupper(trim((string) ($row['tipe_menu'] ?? $row['tipe'] ?? '')));
                $rawIngredient = trim((string) ($row['isi_bundle'] ?? $row['bahan'] ?? $row['bahan_baku'] ?? $row['ingredient'] ?? ''));
                $rawQty = $row['kuantitas'] ?? $row['qty'] ?? $row['jumlah'] ?? null;

                if ($rawMenu === '' && $rawType === '' && $rawIngredient === '' && ($rawQty === null || trim((string) $rawQty) === '')) {
                    continue;
                }

                if ($rawMenu !== '') {
                    $currentMenuName = $rawMenu;
                    $currentMenuType = $rawType;
                } elseif ($rawType !== '') {
                    $currentMenuType = $rawType;
                }

                if ($currentMenuName === '') {
                    continue;
                }

                // Skip SINGLE rows that do not have any recipe/bundle ingredient.
                if ($currentMenuType === 'SINGLE' && $rawIngredient === '') {
                    continue;
                }

                if ($rawIngredient === '') {
                    continue;
                }

                if ($onlyNormalized !== null && $this->normalizeName($currentMenuName) !== $onlyNormalized) {
                    continue;
                }

                $menuProduct = $productsByName->get($this->normalizeName($currentMenuName));
                if (! $menuProduct) {
                    $this->addSkipped($rowNumber, "Menu '{$currentMenuName}' tidak ditemukan di outlet ini.");
                    continue;
                }

                $ingredientProduct = $productsByName->get($this->normalizeName($rawIngredient));
                if (! $ingredientProduct) {
                    $this->addSkipped($rowNumber, "Bahan '{$rawIngredient}' untuk menu '{$currentMenuName}' tidak ditemukan di outlet ini.");
                    continue;
                }

                $qty = $this->parseDecimal($rawQty);
                if ($qty === null || $qty <= 0) {
                    $this->addSkipped($rowNumber, "Kuantitas tidak valid untuk '{$currentMenuName}' -> '{$rawIngredient}'.");
                    continue;
                }

                MposRecipe::updateOrCreate(
                    [
                        'nid_product' => $menuProduct->nid,
                        'nid_ingredient' => $ingredientProduct->nid,
                    ],
                    [
                        'nqty' => $qty,
                    ]
                );

                $this->summary['imported_count']++;
                $this->summary['menus'][$menuProduct->cname] = true;
            }
        });
    }

    /**
     * Auto-detect recipe header row across worksheets (supports row 1, row 2, etc.).
     *
     * @return array{0: int|null, 1: array<int, string>}
     */
    private function detectHeaderRow(Collection $rows): array
    {
        $menuKeys = ['nama_menu', 'menu', 'nama_produk'];
        $ingredientKeys = ['isi_bundle', 'bahan', 'bahan_baku', 'ingredient'];

        foreach ($rows->take(10) as $index => $rawRow) {
            $cells = $rawRow instanceof Collection ? $rawRow->toArray() : (array) $rawRow;
            $headers = [];

            foreach ($cells as $colIndex => $cell) {
                $slug = Str::slug(trim((string) $cell), '_');
                if ($slug !== '') {
                    $headers[(int) $colIndex] = $slug;
                }
            }

            if (
                ! empty(array_intersect($menuKeys, $headers))
                && ! empty(array_intersect($ingredientKeys, $headers))
            ) {
                return [(int) $index, $headers];
            }
        }

        return [null, []];
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, mixed>
     */
    private function mapRowWithHeaders(mixed $rawRow, array $headers): array
    {
        $cells = $rawRow instanceof Collection ? $rawRow->toArray() : (array) $rawRow;
        $mapped = [];

        foreach ($headers as $colIndex => $headerKey) {
            if (array_key_exists($colIndex, $cells)) {
                $mapped[$headerKey] = $cells[$colIndex];
            }
        }

        return $mapped;
    }

    private function parseDecimal(mixed $value): ?float
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

        $cleaned = str_replace(' ', '', $cleaned);

        // Convert Indonesian comma decimals like "0,25" or "2,5" to dot decimals.
        if (str_contains($cleaned, ',') && ! str_contains($cleaned, '.')) {
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (str_contains($cleaned, ',') && str_contains($cleaned, '.')) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        }

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? $name));
    }

    private function addSkipped(int $rowNumber, string $reason): void
    {
        $this->summary['skipped_count']++;
        if (count($this->summary['skipped_reasons']) < 25) {
            $this->summary['skipped_reasons'][] = "Baris {$rowNumber}: {$reason}";
        }
    }
}
