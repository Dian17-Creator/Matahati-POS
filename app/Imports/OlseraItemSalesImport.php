<?php

namespace App\Imports;

use App\Models\MposCust;
use App\Models\MposProduct;
use App\Models\MposSalesD;
use App\Models\MposSalesH;
use App\Models\MposUser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class OlseraItemSalesImport implements ToCollection, WithHeadingRow
{
    protected int $outletId;
    protected ?int $importingUserId;
    protected bool $isDryRun;

    public array $results = [
        'total_rows' => 0,
        'valid_orders_count' => 0,
        'valid_items_count' => 0,
        'header_new_count' => 0,
        'header_attach_count' => 0,
        'duplicate_orders_count' => 0,
        'unmatched_products_count' => 0,
        'conflict_orders_count' => 0,
        'invalid_rows_count' => 0,
        'saved_orders_count' => 0,
        'saved_items_count' => 0,
        'valid_total_amount' => 0.0,
        'issues' => [], // ['row' => int, 'order_no' => string, 'item' => string, 'type' => string, 'message' => string]
        'prepared_orders' => [], // Data grouped per order ready for DB execution
    ];

    public function __construct(int $outletId, ?int $importingUserId = null, bool $isDryRun = true)
    {
        $this->outletId = $outletId;

        // Resolve valid mpos_user ID for foreign key constraint fk_mpos_sales_h_user
        $validMposUser = null;
        if ($importingUserId) {
            $validMposUser = MposUser::where('nid', $importingUserId)->first()
                ?? MposUser::where('nid_user', $importingUserId)->first();
        }

        if (!$validMposUser) {
            $validMposUser = MposUser::where('nid_outlet', $outletId)->first()
                ?? MposUser::first();
        }

        $this->importingUserId = $validMposUser?->nid;
        $this->isDryRun = $isDryRun;
    }

    public function collection(Collection $rows): void
    {
        // 1. Preload master products for this outlet (and global fallback) to avoid N+1 query
        $outletProducts = MposProduct::where('nid_outlet', $this->outletId)->get()->keyBy(function ($item) {
            return strtolower($this->normalizeWhitespace($item->cname));
        });

        $allProducts = MposProduct::all()->keyBy(function ($item) {
            return strtolower($this->normalizeWhitespace($item->cname));
        });

        // 2. Preload existing orders for this outlet (both OLSERA and non-OLSERA to detect conflicts)
        $existingHeaders = MposSalesH::where('nid_outlet', $this->outletId)
            ->get(['nid', 'cnotransaction', 'dtransaction', 'ngrandtotal', 'cstatus', 'csource'])
            ->keyBy(function ($item) {
                return $this->normalizeWhitespace((string) $item->cnotransaction);
            });

        // Preload counts of details in existing transactions to detect duplicates
        $existingDetailCounts = MposSalesD::whereIn('nid_transaction', $existingHeaders->pluck('nid'))
            ->select('nid_transaction', DB::raw('COUNT(*) as detail_count'))
            ->groupBy('nid_transaction')
            ->pluck('detail_count', 'nid_transaction');

        // Group rows by order_no to maintain 1 header to many details relationship
        $groupedOrders = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +1 zero index, +1 header row

            // Normalize row keys
            $normalizedRow = [];
            foreach ($row as $k => $v) {
                $cleanKey = strtolower(trim(str_replace([' ', '-', '.'], '_', (string) $k)));
                $normalizedRow[$cleanKey] = $v;
            }

            // Skip completely empty rows
            if (empty(array_filter($normalizedRow, fn($v) => !is_null($v) && trim((string)$v) !== ''))) {
                continue;
            }

            $this->results['total_rows']++;

            // Extract Order No
            $rawOrderNo = $this->extractField($normalizedRow, ['order_no', 'orderno', 'no_pesanan', 'order_number']);
            $orderNo = $this->normalizeWhitespace((string) $rawOrderNo);

            if (empty($orderNo)) {
                $this->results['invalid_rows_count']++;
                $this->results['issues'][] = [
                    'row' => $rowNum,
                    'order_no' => '-',
                    'item' => '-',
                    'type' => 'INVALID',
                    'message' => 'Nomor pesanan (order no) kosong.',
                ];
                continue;
            }

            // Extract Item Name
            $rawItemName = $this->extractField($normalizedRow, ['item_name', 'itemname', 'nama_item', 'nama_produk', 'item', 'product_name']);
            $itemName = $this->normalizeWhitespace((string) $rawItemName);

            if (empty($itemName)) {
                $this->results['invalid_rows_count']++;
                $this->results['issues'][] = [
                    'row' => $rowNum,
                    'order_no' => $orderNo,
                    'item' => '-',
                    'type' => 'INVALID',
                    'message' => 'Nama item (item name) kosong.',
                ];
                continue;
            }

            // Extract Item Qty
            $rawQty = $this->extractField($normalizedRow, ['qty', 'quantity', 'jumlah', 'kuantitas']);
            $qty = $this->parseStrictPositiveInteger($rawQty);

            if ($qty === null) {
                $this->results['invalid_rows_count']++;
                $this->results['issues'][] = [
                    'row' => $rowNum,
                    'order_no' => $orderNo,
                    'item' => $itemName,
                    'type' => 'INVALID',
                    'message' => "Kuantitas item (qty: '{$rawQty}') tidak valid atau <= 0.",
                ];
                continue;
            }

            // Extract Item Price, Subtotal, Cost, Discount
            $price = $this->parseDecimal($this->extractField($normalizedRow, ['price', 'harga', 'unit_price', 'harga_satuan']), 0.0);
            $costPerUnit = $this->parseDecimal($this->extractField($normalizedRow, ['cost_perunit', 'costperunit', 'modal_satuan', 'cost', 'modal']), 0.0);
            $rawAmount = $this->extractField($normalizedRow, ['amount', 'subtotal', 'total_harga']);
            $subtotalItem = $rawAmount !== null ? $this->parseDecimal($rawAmount, $qty * $price) : ($qty * $price);
            
            $rawDiscount = $this->extractField($normalizedRow, ['discount_amount', 'discountamount', 'discount', 'diskon']);
            $discountItem = $this->parseDecimal($rawDiscount, 0.0);

            $rawTotalCost = $this->extractField($normalizedRow, ['total_cost', 'totalcost', 'total_modal']);
            $totalCostItem = $rawTotalCost !== null ? $this->parseDecimal($rawTotalCost, $qty * $costPerUnit) : ($qty * $costPerUnit);

            $rawProfit = $this->extractField($normalizedRow, ['profit', 'laba', 'profit_amount']);
            $profitItem = $rawProfit !== null ? $this->parseDecimal($rawProfit, ($subtotalItem - $discountItem) - $totalCostItem) : (($subtotalItem - $discountItem) - $totalCostItem);

            // Item SKU
            $rawSku = $this->extractField($normalizedRow, ['item_sku', 'itemsku', 'sku', 'barcode', 'kode_produk']);
            $itemSku = $rawSku ? $this->normalizeWhitespace((string)$rawSku) : null;

            // Product Resolution: Match by item name (exact case-insensitive)
            $searchKey = strtolower($itemName);
            $matchedProduct = $outletProducts->get($searchKey) ?? $allProducts->get($searchKey);

            if (!$matchedProduct) {
                $this->results['unmatched_products_count']++;
                $this->results['issues'][] = [
                    'row' => $rowNum,
                    'order_no' => $orderNo,
                    'item' => $itemName,
                    'type' => 'UNMATCHED_PRODUCT',
                    'message' => "Produk '{$itemName}' tidak ditemukan di master produk Matahati POS. Karena integritas foreign key database, baris ini ditolak.",
                ];
                continue;
            }

            // Extract Order Date and Time: prioritize existing header date from mpos_sales_h if already in DB
            $dtransaction = null;
            if ($existingHeaders->has($orderNo)) {
                $existingHeader = $existingHeaders->get($orderNo);
                if (!empty($existingHeader->dtransaction)) {
                    $dtransaction = Carbon::parse($existingHeader->dtransaction);
                }
            }

            $rawDate = $this->extractField($normalizedRow, ['order_date', 'orderdate', 'tanggal']);
            $rawTime = $this->extractField($normalizedRow, ['order_time', 'ordertime', 'jam', 'waktu']);

            if (!$dtransaction) {
                $dtransaction = $this->parseDateTime($rawDate, $rawTime);
            }

            if (!$dtransaction) {
                $this->results['invalid_rows_count']++;
                $this->results['issues'][] = [
                    'row' => $rowNum,
                    'order_no' => $orderNo,
                    'item' => $itemName,
                    'type' => 'INVALID',
                    'message' => "Format tanggal/waktu transaksi tidak valid ('{$rawDate}' '{$rawTime}').",
                ];
                continue;
            }

            // Extract Status (paid)
            $rawPaid = $this->extractField($normalizedRow, ['paid', 'status', 'status_pembayaran']);
            $cleanPaid = is_string($rawPaid) ? trim($rawPaid) : (string)$rawPaid;
            if ($cleanPaid !== '1' && strtolower($cleanPaid) !== 'paid' && $cleanPaid !== '') {
                // If paid status is explicitly not paid (e.g. 0 / unpaid / void)
                $this->results['invalid_rows_count']++;
                $this->results['issues'][] = [
                    'row' => $rowNum,
                    'order_no' => $orderNo,
                    'item' => $itemName,
                    'type' => 'INVALID',
                    'message' => "Status pembayaran 'paid = {$cleanPaid}' belum berstatus PAID.",
                ];
                continue;
            }

            // Customer and Cashier Metadata
            $servedBy = trim((string) $this->extractField($normalizedRow, ['served_by', 'servedby', 'kasir', 'sales_name']));
            $customerName = trim((string) $this->extractField($normalizedRow, ['customer_name', 'customername', 'pelanggan', 'customer']));
            $rawNotes = $this->extractField($normalizedRow, ['notes_order', 'notesorder', 'notes', 'catatan', 'keterangan']);

            // Group by Order No
            if (!isset($groupedOrders[$orderNo])) {
                $groupedOrders[$orderNo] = [
                    'order_no' => $orderNo,
                    'dtransaction' => $dtransaction,
                    'served_by' => $servedBy,
                    'customer_name' => $customerName,
                    'notes' => !empty($rawNotes) ? trim((string)$rawNotes) : null,
                    'items' => [],
                    'total_qty' => 0,
                    'total_subtotal' => 0.0,
                    'total_discount' => 0.0,
                    'total_net_amount' => 0.0,
                    'total_cost' => 0.0,
                    'total_profit' => 0.0,
                ];
            }

            $groupedOrders[$orderNo]['items'][] = [
                'row_num' => $rowNum,
                'product_id' => $matchedProduct->nid,
                'cname' => $itemName,
                'nqty' => $qty,
                'nprice' => $price,
                'ncost' => $costPerUnit,
                'nsubtotal' => $subtotalItem,
                'discount' => $discountItem,
                'total_cost' => $totalCostItem,
                'profit' => $profitItem,
                'sku' => $itemSku,
            ];

            $groupedOrders[$orderNo]['total_qty'] += $qty;
            $groupedOrders[$orderNo]['total_subtotal'] += $subtotalItem;
            $groupedOrders[$orderNo]['total_discount'] += $discountItem;
            $groupedOrders[$orderNo]['total_net_amount'] += max(0, $subtotalItem - $discountItem);
            $groupedOrders[$orderNo]['total_cost'] += $totalCostItem;
            $groupedOrders[$orderNo]['total_profit'] += $profitItem;
        }

        // 3. Process Grouped Orders and Determine DB Actions
        foreach ($groupedOrders as $orderNo => $order) {
            if (empty($order['items'])) {
                continue;
            }

            // Check against existing headers
            if ($existingHeaders->has($orderNo)) {
                $existingHeader = $existingHeaders->get($orderNo);

                // Conflict check: if order number exists from a non-OLSERA source (native POS)
                if ($existingHeader->csource !== 'OLSERA') {
                    $this->results['conflict_orders_count']++;
                    $this->results['issues'][] = [
                        'row' => $order['items'][0]['row_num'],
                        'order_no' => $orderNo,
                        'item' => count($order['items']) . ' items',
                        'type' => 'CONFLICT',
                        'message' => "Nomor pesanan '{$orderNo}' sudah terdaftar sebagai transaksi POS asli. Ditolak untuk mencegah penimpaan data.",
                    ];
                    continue;
                }

                // Check if this existing header already has details
                $detailCount = $existingDetailCounts->get($existingHeader->nid, 0);
                if ($detailCount > 0) {
                    // Already has details -> DUPLICATE to avoid duplicating detail items
                    $this->results['duplicate_orders_count']++;
                    $this->results['issues'][] = [
                        'row' => $order['items'][0]['row_num'],
                        'order_no' => $orderNo,
                        'item' => count($order['items']) . ' items',
                        'type' => 'DUPLICATE',
                        'message' => "Transaksi '{$orderNo}' sudah memiliki detail item di database ({$detailCount} item). Dilewati secara aman.",
                    ];
                    continue;
                }

                // Header exists but has NO details yet (previously imported header-only) -> ATTACH DETAILS
                $this->results['header_attach_count']++;
                $order['action'] = 'ATTACH_DETAILS';
                $order['target_header_nid'] = $existingHeader->nid;
            } else {
                // Completely new order -> CREATE NEW HEADER & DETAILS
                $this->results['header_new_count']++;
                $order['action'] = 'CREATE_HEADER_AND_DETAILS';
                $order['target_header_nid'] = null;
            }

            $this->results['valid_orders_count']++;
            $this->results['valid_items_count'] += count($order['items']);
            $this->results['valid_total_amount'] += $order['total_net_amount'];
            $this->results['prepared_orders'][] = $order;
        }

        // 4. Execution Mode: If not dry-run, execute database operations
        if (!$this->isDryRun) {
            foreach ($this->results['prepared_orders'] as $order) {
                $headerNid = null;

                if ($order['action'] === 'ATTACH_DETAILS') {
                    $headerNid = $order['target_header_nid'];

                    // Update header totals if cost/profit/discount were previously null
                    MposSalesH::where('nid', $headerNid)->update([
                        'ntotal_cost' => $order['total_cost'],
                        'ntotal_profit' => $order['total_profit'],
                    ]);
                } else {
                    // CREATE_HEADER_AND_DETAILS
                    $cnote = !empty($order['notes']) ? $order['notes'] : null;

                    $newHeader = MposSalesH::create([
                        'cnotransaction' => $order['order_no'],
                        'dtransaction' => $order['dtransaction']->format('Y-m-d H:i:s'),
                        'nid_outlet' => $this->outletId,
                        'nid_user' => $this->importingUserId,
                        'nid_customer' => null,
                        'cname_customer' => !empty($order['customer_name']) ? $order['customer_name'] : 'Umum',
                        'nid_shift' => null,
                        'nid_payment' => null,
                        'nid_voucher' => null,
                        'nqueue' => null,
                        'cordertype' => MposSalesH::ORDER_TYPE_TAKE_AWAY,
                        'nvisitor' => 1,
                        'ctable' => null,
                        'nsubtotal' => $order['total_subtotal'],
                        'ndiscount' => $order['total_discount'],
                        'ntax' => 0.0,
                        'nservice_charge' => 0.0,
                        'nrounding' => 0.0,
                        'ngrandtotal' => $order['total_net_amount'],
                        'npaid' => $order['total_net_amount'],
                        'nchange' => 0.0,
                        'nitem' => $order['total_qty'],
                        'cnote' => $cnote,
                        'cstatus' => MposSalesH::STATUS_PAID,
                        'csource' => 'OLSERA',
                        'ntotal_cost' => $order['total_cost'],
                        'ntotal_profit' => $order['total_profit'],
                        'dcreated' => now()->format('Y-m-d H:i:s'),
                    ]);

                    $headerNid = $newHeader->nid;
                }

                // Insert details into mpos_sales_d linked to this header
                foreach ($order['items'] as $item) {
                    $itemNote = !empty($item['sku']) ? "SKU: {$item['sku']}" : null;

                    MposSalesD::create([
                        'nid_transaction' => $headerNid,
                        'nid_product' => $item['product_id'],
                        'cname' => $item['cname'],
                        'nqty' => $item['nqty'],
                        'nqty_void' => 0,
                        'nqty_refund' => 0,
                        'nprice' => $item['nprice'],
                        'ncost' => $item['ncost'],
                        'nsubtotal' => $item['nsubtotal'],
                        'cnote' => $itemNote,
                        'dcreated' => now()->format('Y-m-d H:i:s'),
                    ]);

                    $this->results['saved_items_count']++;
                }

                $this->results['saved_orders_count']++;
            }
        }
    }

    protected function normalizeWhitespace(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    protected function extractField(array $row, array $aliases): mixed
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $row) && $row[$alias] !== null && $row[$alias] !== '') {
                return $row[$alias];
            }
        }
        return null;
    }

    protected function parseStrictPositiveInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $intVal = (int) $value;
            return $intVal > 0 ? $intVal : null;
        }

        $clean = preg_replace('/[^\d]/', '', (string)$value);
        if ($clean !== '') {
            $intVal = (int) $clean;
            return $intVal > 0 ? $intVal : null;
        }

        return null;
    }

    protected function parseDecimal(mixed $value, float $default = 0.0): float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = str_replace(['Rp', 'rp', ' ', ','], '', (string) $value);
        return is_numeric($clean) ? (float) $clean : $default;
    }

    protected function parseDateTime(mixed $rawDate, mixed $rawTime): ?Carbon
    {
        if (empty($rawDate) && empty($rawTime)) {
            return null;
        }

        try {
            // Case 1: rawTime already contains full datetime string e.g. "2026-10-09 16:43:03"
            if (!empty($rawTime) && is_string($rawTime) && preg_match('/\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}/', $rawTime)) {
                return Carbon::parse(trim($rawTime));
            }

            // Case 2: Excel Serial Date Number
            if (is_numeric($rawDate)) {
                $dtObject = ExcelDate::excelToDateTimeObject((float) $rawDate);
                $dt = Carbon::instance($dtObject);

                if (!empty($rawTime) && is_numeric($rawTime)) {
                    $seconds = (int) round((float) $rawTime * 86400);
                    $dt->startOfDay()->addSeconds($seconds);
                } elseif (!empty($rawTime) && is_string($rawTime)) {
                    $timeCarbon = Carbon::parse(trim($rawTime));
                    $dt->setTime($timeCarbon->hour, $timeCarbon->minute, $timeCarbon->second);
                }
                return $dt;
            }

            // Case 3: String Date extraction
            $datePart = null;
            if (!empty($rawDate)) {
                $datePart = Carbon::parse(trim((string)$rawDate))->format('Y-m-d');
            }

            // Time component extraction
            $timePart = '00:00:00';
            if (!empty($rawTime)) {
                if (is_numeric($rawTime)) {
                    $seconds = (int) round((float) $rawTime * 86400);
                    $timePart = gmdate('H:i:s', $seconds);
                } else {
                    $timePart = Carbon::parse(trim((string)$rawTime))->format('H:i:s');
                }
            }

            if ($datePart) {
                return Carbon::parse("{$datePart} {$timePart}");
            }

            return null;

        } catch (\Throwable $e) {
            return null;
        }
    }
}
