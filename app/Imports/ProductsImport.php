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
            $variantName = isset($row['variant_names']) ? trim((string)$row['variant_names']) : null;
            if (empty($variantName) && isset($row['alternative_variant_name'])) {
                $variantName = trim((string)$row['alternative_variant_name']);
            }
            if (empty($variantName) || strtolower($variantName) === 'null' || strtolower($variantName) === 'kosong') {
                $cname = $name;
            } else {
                $cname = $name . ' - ' . $variantName;
            }

            // Lookup Category menggunakan case-insensitive perbandingan yang aman di db
            $category = MposGrpProduct::where('nid_outlet', $this->outletId)
                ->where(DB::raw('LOWER(cname)'), strtolower($categoryName))
                ->first();

            if (!$category) {
                // Auto-create kategori untuk outlet ini jika belum terdaftar
                $refCategory = MposGrpProduct::where(DB::raw('LOWER(cname)'), strtolower($categoryName))->first();
                $category = MposGrpProduct::create([
                    'nid_outlet' => $this->outletId,
                    'cname' => $refCategory ? $refCategory->cname : $categoryName,
                    'ftrack_stock' => $refCategory ? (bool)$refCategory->ftrack_stock : true,
                    'fshow_pos' => $refCategory ? (bool)$refCategory->fshow_pos : true,
                ]);
            }

            // Parse Harga Jual POS & Online
            $posSellPrice = isset($row['pos_sell_price']) && trim((string)$row['pos_sell_price']) !== ''
                ? floatval(preg_replace('/[^0-9.]/', '', (string)$row['pos_sell_price']))
                : 0;

            $sellPrice = isset($row['sell_price']) && trim((string)$row['sell_price']) !== ''
                ? floatval(preg_replace('/[^0-9.]/', '', (string)$row['sell_price']))
                : 0;

            // Parse Harga Beli (buy_price)
            $rawBuyPrice = $row['buy_price'] ?? $row['buyprice'] ?? $row['harga_beli'] ?? null;
            $buyPrice = null;
            if ($rawBuyPrice !== null && trim((string)$rawBuyPrice) !== '') {
                $cleanedBuyPrice = preg_replace('/[^0-9.]/', '', (string)$rawBuyPrice);
                if ($cleanedBuyPrice !== '') {
                    $buyPrice = floatval($cleanedBuyPrice);
                }
            }

            // Parse Stok / QTY (Logika Timpa/Replace Stok)
            $hasStockColumn = array_key_exists('stock_qty', $row)
                || array_key_exists('stock', $row)
                || array_key_exists('qty', $row)
                || array_key_exists('stok', $row)
                || array_key_exists('stok_qty', $row)
                || array_key_exists('quantity', $row);

            $rawStockQty = $row['stock_qty'] ?? $row['stock'] ?? $row['qty'] ?? $row['stok'] ?? $row['stok_qty'] ?? $row['quantity'] ?? null;
            $stockQty = null;

            if ($hasStockColumn) {
                if ($rawStockQty !== null && trim((string)$rawStockQty) !== '') {
                    $trimmedStock = trim((string)$rawStockQty);
                    $cleanedStock = preg_replace('/[^0-9.-]/', '', $trimmedStock);
                    if ($cleanedStock !== '' && is_numeric($cleanedStock)) {
                        $stockVal = floatval($cleanedStock);
                        if ($stockVal < 0) {
                            $this->results['failed']++;
                            $this->results['errors'][] = "Row {$rowNum} ({$cname}): Jumlah stok/qty tidak valid atau bernilai negatif ({$trimmedStock})";
                            continue;
                        }
                        $stockQty = $stockVal;
                    } else {
                        $stockQty = 0;
                    }
                } else {
                    // Jika kolom stok disediakan tapi sel kosong, timpa menjadi 0
                    $stockQty = 0;
                }
            }

            // Parse foto
            // Jika ada foto, kita simpan teksnya. Jika null, null
            $photo = isset($row['photo_1']) && trim($row['photo_1']) !== '' ? trim($row['photo_1']) : null;

            // Set status ke ACTIVE secara default (mengabaikan kolom published)
            $status = 'ACTIVE';

            // Cek Produk Berdasarkan cname dan nid_outlet
            $existing = MposProduct::where('cname', $cname)
                ->where('nid_outlet', $this->outletId)
                ->first();

            // Insert atau Update Database menggunakan Transaction per baris
            try {
                DB::beginTransaction();
                
                if ($existing) {
                    $updateData = [
                        'nid_category' => $category->nid,
                        'nprice' => $posSellPrice,
                        'nprice_online' => $sellPrice,
                        'cphotos' => $photo,
                        'cstatus' => $status,
                    ];

                    // Perbarui harga beli (nprice_buy) jika tersedia di baris import
                    if ($buyPrice !== null) {
                        $updateData['nprice_buy'] = $buyPrice;
                    }

                    // Timpa nilai stok (nqty): Berapapun stok lama di sistem langsung digantikan oleh stok Excel
                    if ($stockQty !== null) {
                        $updateData['nqty'] = $stockQty;
                    }

                    $existing->update($updateData);
                    $this->results['updated']++;
                } else {
                    MposProduct::create([
                        'cname' => $cname,
                        'nid_category' => $category->nid,
                        'nid_outlet' => $this->outletId,
                        'nprice' => $posSellPrice,
                        'nprice_online' => $sellPrice,
                        'nprice_buy' => $buyPrice ?? 0,
                        'nqty' => $stockQty ?? 0,
                        'cphotos' => $photo,
                        'cstatus' => $status,
                        'fcombo' => 0,
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
