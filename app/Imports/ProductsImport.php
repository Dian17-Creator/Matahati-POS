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
            // Kita skip jika row kosong semua (mendukung format kita maupun format Olsera)
            if (
                !isset($row['name'])
                && !isset($row['nama_produk'])
                && !isset($row['category'])
                && !isset($row['kategori'])
            ) {
                continue;
            }

            $this->results['total']++;
            $rowNum = $index + 2; // +1 untuk 0-index, +1 untuk header

            // Validasi nama (mendukung 'nama_produk' dan 'name')
            $rawName = $row['nama_produk'] ?? $row['name'] ?? null;
            $name = $rawName !== null ? trim((string) $rawName) : null;
            if (empty($name)) {
                $this->results['failed']++;
                $this->results['errors'][] = "Row {$rowNum}: Nama product kosong";
                continue;
            }

            // Validasi Kategori (mendukung 'kategori' dan 'category')
            $rawCategory = $row['kategori'] ?? $row['category'] ?? null;
            $categoryName = $rawCategory !== null ? trim((string) $rawCategory) : null;
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

            // Parse Harga Jual POS & Online (mendukung format kita: harga_pos, harga_online & format Olsera: pos_sell_price, sell_price)
            $rawPosPrice = $row['harga_pos'] ?? $row['pos_sell_price'] ?? null;
            $posSellPrice = $rawPosPrice !== null && trim((string) $rawPosPrice) !== ''
                ? floatval(preg_replace('/[^0-9.]/', '', (string) $rawPosPrice))
                : 0;

            $rawOnlinePrice = $row['harga_online'] ?? $row['sell_price'] ?? null;
            $sellPrice = $rawOnlinePrice !== null && trim((string) $rawOnlinePrice) !== ''
                ? floatval(preg_replace('/[^0-9.]/', '', (string) $rawOnlinePrice))
                : 0;

            // Parse Harga Beli (harga_beli / buy_price)
            $rawBuyPrice = $row['harga_beli'] ?? $row['buy_price'] ?? $row['buyprice'] ?? null;
            $buyPrice = null;
            if ($rawBuyPrice !== null && trim((string)$rawBuyPrice) !== '') {
                $cleanedBuyPrice = preg_replace('/[^0-9.]/', '', (string)$rawBuyPrice);
                if ($cleanedBuyPrice !== '') {
                    $buyPrice = floatval($cleanedBuyPrice);
                }
            }

            // Parse Stok / QTY (Logika Timpa/Replace Stok)
            $rowArray = is_array($row) ? $row : ($row instanceof Collection ? $row->toArray() : (array) $row);
            $hasStockColumn = array_key_exists('stock_qty', $rowArray)
                || array_key_exists('stock', $rowArray)
                || array_key_exists('qty', $rowArray)
                || array_key_exists('stok', $rowArray)
                || array_key_exists('stok_qty', $rowArray)
                || array_key_exists('quantity', $rowArray);

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

            // Parse foto (hanya timpa jika kolom photo_1 tersedia)
            $hasPhotoColumn = array_key_exists('photo_1', $rowArray);
            $photo = $hasPhotoColumn && trim((string) ($row['photo_1'] ?? '')) !== '' ? trim((string) $row['photo_1']) : null;

            // Parse Tipe Produk, Satuan, Status, dan Kombo jika tersedia di file import
            $productType = null;
            if (array_key_exists('tipe_produk', $rowArray) || array_key_exists('product_type', $rowArray)) {
                $rawType = strtoupper(trim((string) ($row['tipe_produk'] ?? $row['product_type'] ?? '')));
                $productType = match (true) {
                    str_contains($rawType, 'SETENGAH') => 'SETENGAH_JADI',
                    str_contains($rawType, 'BAHAN') => 'BAHAN_BAKU',
                    $rawType !== '' => 'PRODUK',
                    default => null,
                };
            }

            $satuan = null;
            if (array_key_exists('satuan', $rowArray) && trim((string) ($row['satuan'] ?? '')) !== '') {
                $satuan = strtoupper(trim((string) $row['satuan']));
            }

            $status = 'ACTIVE';
            if (array_key_exists('status', $rowArray) && trim((string) ($row['status'] ?? '')) !== '') {
                $status = trim((string) $row['status']);
            }

            $isCombo = null;
            if (array_key_exists('kombo', $rowArray) && trim((string) ($row['kombo'] ?? '')) !== '') {
                $rawCombo = strtolower(trim((string) $row['kombo']));
                $isCombo = in_array($rawCombo, ['ya', 'yes', '1', 'true'], true) ? 1 : 0;
            }

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
                        'cstatus' => $status,
                    ];

                    if ($hasPhotoColumn) {
                        $updateData['cphotos'] = $photo;
                    }

                    if ($productType !== null && MposProduct::hasProductTypeColumn()) {
                        $updateData['cproduct_type'] = $productType;
                    }

                    if ($satuan !== null && MposProduct::hasSatuanColumn()) {
                        $updateData['csatuan'] = $satuan;
                    }

                    if ($isCombo !== null) {
                        $updateData['fcombo'] = $isCombo;
                    }

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
                    $createData = [
                        'cname' => $cname,
                        'nid_category' => $category->nid,
                        'nid_outlet' => $this->outletId,
                        'nprice' => $posSellPrice,
                        'nprice_online' => $sellPrice,
                        'nprice_buy' => $buyPrice ?? 0,
                        'nqty' => $stockQty ?? 0,
                        'cphotos' => $photo,
                        'cstatus' => $status,
                        'fcombo' => $isCombo ?? 0,
                    ];

                    if (MposProduct::hasProductTypeColumn()) {
                        $createData['cproduct_type'] = $productType ?? 'PRODUK';
                    }

                    if (MposProduct::hasSatuanColumn()) {
                        $createData['csatuan'] = $satuan ?? 'PCS';
                    }

                    MposProduct::create($createData);
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
