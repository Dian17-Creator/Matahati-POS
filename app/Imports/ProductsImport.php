<?php

namespace App\Imports;

use App\Models\MposProduct;
use App\Models\MposGrpProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class ProductsImport implements ToCollection, WithHeadingRow
{
    protected $outletId;
    public $results = [
        'total' => 0,
        'success' => 0,
        'updated' => 0,
        'failed' => 0,
        'duplicate' => 0,
        'errors' => []
    ];

    public function __construct($outletId)
    {
        $this->outletId = $outletId;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            // Kita skip jika row kosong semua
            if (!isset($row['name']) && !isset($row['category'])) {
                continue;
            }

            $this->results['total']++;
            $rowNum = $index + 2; // +1 untuk 0-index, +1 untuk header

            // Validasi nama
            $name = isset($row['name']) ? trim($row['name']) : null;
            if (empty($name)) {
                $this->results['failed']++;
                $this->results['errors'][] = "Row {$rowNum}: Nama product kosong";
                continue;
            }

            // Validasi Kategori
            $categoryName = isset($row['category']) ? trim($row['category']) : null;
            if (empty($categoryName)) {
                $this->results['failed']++;
                $this->results['errors'][] = "Row {$rowNum} ({$name}): Category kosong";
                continue;
            }

            // Combine name and variant
            $variantName = isset($row['variant_names']) ? trim($row['variant_names']) : null;
            if (empty($variantName) || strtolower($variantName) == 'null' || strtolower($variantName) == 'kosong') {
                $cname = $name;
            } else {
                $cname = $name . ' - ' . $variantName;
            }

            // Lookup Category menggunakan case-insensitive perbandingan yang aman di db
            $category = MposGrpProduct::where('nid_outlet', $this->outletId)
                ->where(DB::raw('LOWER(cname)'), strtolower($categoryName))
                ->first();

            if (!$category) {
                $this->results['failed']++;
                $this->results['errors'][] = "Row {$rowNum} ({$cname}): Category '{$categoryName}' tidak ditemukan untuk outlet id {$this->outletId}";
                continue;
            }

            // Parse Harga
            $posSellPrice = isset($row['pos_sell_price']) ? floatval(preg_replace('/[^0-9.]/', '', $row['pos_sell_price'])) : 0;
            $sellPrice = isset($row['sell_price']) && trim($row['sell_price']) !== '' ? floatval(preg_replace('/[^0-9.]/', '', $row['sell_price'])) : 0;

            // Parse foto
            // Jika ada foto, kita simpan teksnya. Jika null, null
            $photo = isset($row['photo_1']) && trim($row['photo_1']) !== '' ? trim($row['photo_1']) : null;

            // Parse status
            $published = isset($row['published']) ? trim($row['published']) : '';
            $status = 'ACTIVE';
            if (in_array(strtolower($published), ['tidak aktif', 'false', '0', ''], true) && $published !== '1') {
                if (strtolower($published) !== '') {
                    $status = 'INACTIVE';
                }
            }

            // Cek Produk Berdasarkan cname dan nid_outlet
            $existing = MposProduct::where('cname', $cname)
                ->where('nid_outlet', $this->outletId)
                ->first();

            // Insert atau Update Database menggunakan Transaction per baris
            try {
                DB::beginTransaction();
                
                if ($existing) {
                    $existing->update([
                        'nid_category' => $category->nid,
                        'nprice' => $posSellPrice,
                        'nprice_online' => $sellPrice,
                        'cphotos' => $photo,
                        'cstatus' => $status,
                    ]);
                    $this->results['updated']++;
                } else {
                    MposProduct::create([
                        'cname' => $cname,
                        'nid_category' => $category->nid,
                        'nid_outlet' => $this->outletId,
                        'nprice' => $posSellPrice,
                        'nprice_online' => $sellPrice,
                        'cphotos' => $photo,
                        'cstatus' => $status,
                        'fcombo' => 0
                    ]);
                    $this->results['success']++;
                }
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->results['failed']++;
                $this->results['errors'][] = "Row {$rowNum} ({$cname}): Gagal menyimpan ke database. Error: " . $e->getMessage();
            }
        }
    }
}
