<?php

namespace App\Imports;

use App\Models\MposProduct;
use App\Models\MposGrpProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OlseraStockMovementImport implements ToCollection, WithHeadingRow
{
    protected int $outletId;

    public array $results = [
        'total' => 0,
        'updated' => 0,
        'created' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    public function __construct(int $outletId)
    {
        $this->outletId = $outletId;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            // Pastikan data row berbentuk array
            $rowArray = $row instanceof Collection ? $row->toArray() : (array) $row;

            $productName = isset($rowArray['product']) ? trim((string) $rowArray['product']) : (isset($rowArray['nama_produk']) ? trim((string) $rowArray['nama_produk']) : '');
            if (empty($productName)) {
                continue;
            }

            $this->results['total']++;

            // Ambil beginning stock (mendukung format Olsera 'begining', 'beginning', atau 'awal')
            $beginning = null;
            if (isset($rowArray['begining']) && is_numeric($rowArray['begining'])) {
                $beginning = (float) $rowArray['begining'];
            } elseif (isset($rowArray['beginning']) && is_numeric($rowArray['beginning'])) {
                $beginning = (float) $rowArray['beginning'];
            } elseif (isset($rowArray['awal']) && is_numeric($rowArray['awal'])) {
                $beginning = (float) $rowArray['awal'];
            }

            // Ambil balance / sisa jika ada
            $balance = null;
            if (isset($rowArray['balance']) && is_numeric($rowArray['balance'])) {
                $balance = (float) $rowArray['balance'];
            } elseif (isset($rowArray['sisa']) && is_numeric($rowArray['sisa'])) {
                $balance = (float) $rowArray['sisa'];
            }

            $incoming = isset($rowArray['incoming']) && is_numeric($rowArray['incoming']) ? (float) $rowArray['incoming'] : 0.0;
            $sales = isset($rowArray['sales']) && is_numeric($rowArray['sales']) ? (float) $rowArray['sales'] : 0.0;
            $outgoing = isset($rowArray['outgoing']) && is_numeric($rowArray['outgoing']) ? (float) $rowArray['outgoing'] : 0.0;

            // Target stock untuk mpos_product.nqty:
            // 1. Jika kolom balance tersedia pada file Excel Olsera, gunakan balance
            // 2. Jika tidak ada balance tapi ada begining, gunakan formula begining + incoming - sales - outgoing
            if ($balance !== null) {
                $targetStock = $balance;
            } elseif ($beginning !== null) {
                $targetStock = $beginning + $incoming - $sales - $outgoing;
            } else {
                $this->results['skipped']++;
                $this->results['errors'][] = "Baris {$rowNum} ({$productName}): Kolom 'begining' atau 'balance' tidak ditemukan/bukan angka.";
                continue;
            }

            // 1. Cari exact match di outlet tujuan
            $product = MposProduct::where('nid_outlet', $this->outletId)
                ->whereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim($productName))])
                ->first();

            // 2. Jika tidak ada exact match, cari partial match
            if (!$product) {
                $product = MposProduct::where('nid_outlet', $this->outletId)
                    ->where(function ($q) use ($productName) {
                        $q->whereRaw('LOWER(cname) LIKE ?', ['%' . strtolower(trim($productName)) . '%'])
                          ->orWhereRaw('LOWER(?) LIKE CONCAT("%", LOWER(cname), "%")', [strtolower(trim($productName))]);
                    })->first();
            }

            // 3. Jika belum ada di outlet ini, cari di outlet lain untuk diduplikasi
            if (!$product) {
                $otherProduct = MposProduct::whereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim($productName))])
                    ->first();

                if ($otherProduct) {
                    $category = MposGrpProduct::where('nid_outlet', $this->outletId)
                        ->whereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim($otherProduct->category?->cname ?? 'TEREA'))])
                        ->first() ?? MposGrpProduct::where('nid_outlet', $this->outletId)->first() ?? MposGrpProduct::first();

                    $product = MposProduct::create([
                        'cname' => $otherProduct->cname,
                        'nid_category' => $category ? $category->nid : 1,
                        'nid_outlet' => $this->outletId,
                        'nprice' => $otherProduct->nprice,
                        'nprice_online' => $otherProduct->nprice_online,
                        'nprice_buy' => $otherProduct->nprice_buy,
                        'nqty' => $targetStock,
                        'cstatus' => 'ACTIVE',
                        'fcombo' => 0,
                    ]);
                    $this->results['created']++;
                    continue;
                } else {
                    // Buat master baru jika benar-benar produk baru
                    $category = MposGrpProduct::where('nid_outlet', $this->outletId)->first() ?? MposGrpProduct::first();
                    $product = MposProduct::create([
                        'cname' => $productName,
                        'nid_category' => $category ? $category->nid : 1,
                        'nid_outlet' => $this->outletId,
                        'nprice' => 0,
                        'nprice_online' => 0,
                        'nprice_buy' => 0,
                        'nqty' => $targetStock,
                        'cstatus' => 'ACTIVE',
                        'fcombo' => 0,
                    ]);
                    $this->results['created']++;
                    continue;
                }
            }

            // Update stok nqty di mpos_product
            $product->nqty = $targetStock;
            $product->save();
            $this->results['updated']++;
        }
    }
}
