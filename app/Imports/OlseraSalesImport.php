<?php

namespace App\Imports;

use App\Models\MposCust;
use App\Models\MposSalesH;
use App\Models\MposUser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class OlseraSalesImport implements ToCollection, WithHeadingRow
{
    protected int $outletId;
    protected ?int $importingUserId;
    protected bool $isDryRun;

    public array $results = [
        'total_rows' => 0,
        'valid_rows' => 0,
        'valid_subtotal' => 0.0,
        'valid_net_amount' => 0.0,
        'duplicate_skipped' => 0,
        'duplicate_net_amount' => 0.0,
        'conflict_skipped' => 0,
        'conflict_net_amount' => 0.0,
        'invalid_rows' => 0,
        'invalid_net_amount' => 0.0,
        'saved_count' => 0,
        'issues' => [], // ['row' => int, 'order_no' => string, 'type' => 'DUPLICATE'|'CONFLICT'|'INVALID', 'message' => string]
        'valid_records' => [], // Records ready for insertion
    ];

    public function __construct(int $outletId, ?int $importingUserId = null, bool $isDryRun = true)
    {
        $this->outletId = $outletId;

        // Pastikan importingUserId selalu valid terhadap foreign key fk_mpos_sales_h_user (mpos_user.nid)
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
        // Cache existing orders for this outlet and OLSERA source to avoid N+1 query
        $existingOrders = MposSalesH::where('csource', 'OLSERA')
            ->where('nid_outlet', $this->outletId)
            ->get(['nid', 'cnotransaction', 'dtransaction', 'ngrandtotal', 'cstatus'])
            ->keyBy(function ($item) {
                return $this->normalizeWhitespace((string) $item->cnotransaction);
            });

        // Set to track duplicates within the current uploaded file
        $seenInFile = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +1 zero-index, +1 header row

            // Normalisasi key header ke lowercase & replace spasi dengan underscore
            $normalizedRow = [];
            foreach ($row as $k => $v) {
                $cleanKey = strtolower(trim(str_replace([' ', '-', '.'], '_', (string) $k)));
                $normalizedRow[$cleanKey] = $v;
            }

            // Abaikan baris kosong total
            if (empty(array_filter($normalizedRow, fn($v) => !is_null($v) && trim((string)$v) !== ''))) {
                continue;
            }

            $this->results['total_rows']++;

            // 1. Ekstraksi dan Validasi Order No
            $rawOrderNo = $this->extractField($normalizedRow, ['order_no', 'orderno', 'no_pesanan', 'order_number']);
            $orderNo = $this->normalizeWhitespace((string) $rawOrderNo);

            if (empty($orderNo)) {
                $this->results['invalid_rows']++;
                $this->results['issues'][] = [
                    'row' => $rowNum,
                    'order_no' => '-',
                    'type' => 'INVALID',
                    'message' => 'Nomor pesanan (order no) kosong setelah normalisasi.',
                ];
                continue;
            }

            // 2. Ekstraksi dan Validasi Tanggal & Waktu
            $rawDate = $this->extractField($normalizedRow, ['order_date', 'orderdate', 'tanggal']);
            $rawTime = $this->extractField($normalizedRow, ['order_time', 'ordertime', 'jam', 'waktu']);

            $dtransaction = $this->parseDateTime($rawDate, $rawTime);
            if (!$dtransaction) {
                $this->results['invalid_rows']++;
                $this->results['issues'][] = [
                    'row' => $rowNum,
                    'order_no' => $orderNo,
                    'type' => 'INVALID',
                    'message' => "Format tanggal/waktu tidak valid (order_date: '{$rawDate}', order_time: '{$rawTime}'). Komponen waktu wajib tersedia.",
                ];
                continue;
            }

            // 3. Ekstraksi dan Validasi Total Qty (nitem)
            $rawQty = $this->extractField($normalizedRow, ['total_qty', 'totalqty', 'qty', 'item', 'total_item']);
            $nitem = $this->parseStrictPositiveInteger($rawQty);

            if ($nitem === null) {
                $this->results['invalid_rows']++;
                $this->results['issues'][] = [
                    'row' => $rowNum,
                    'order_no' => $orderNo,
                    'type' => 'INVALID',
                    'message' => "Kolom total qty kosong, non-numerik, atau <= 0 (nilai: '{$rawQty}'). Baris dilewati.",
                ];
                continue;
            }

            // 4. Ekstraksi dan Validasi Status Pembayaran (paid)
            $rawPaid = $this->extractField($normalizedRow, ['paid', 'status_pembayaran', 'status']);
            $cleanPaid = is_string($rawPaid) ? trim($rawPaid) : (string)$rawPaid;

            // Berdasarkan sampel 28 baris yang bernilai 1: dipetakan ke PAID
            if ($cleanPaid === '1' || strtolower($cleanPaid) === 'paid') {
                $cstatus = MposSalesH::STATUS_PAID;
            } else {
                // Nilai selain 1 tidak ditebak, dilaporkan sebagai data bermasalah
                $this->results['invalid_rows']++;
                $this->results['issues'][] = [
                    'row' => $rowNum,
                    'order_no' => $orderNo,
                    'type' => 'INVALID',
                    'message' => "Status pembayaran 'paid = {$cleanPaid}' belum dikenali (sampel saat ini hanya mendukung paid = 1 / PAID).",
                ];
                continue;
            }

            // 5. Ekstraksi Nominal Keuangan
            $nsubtotal = $this->parseDecimal($this->extractField($normalizedRow, ['subtotal', 'total_penjualan']), 0.0);
            $ngrandtotal = $this->parseDecimal($this->extractField($normalizedRow, ['net_amount', 'netamount', 'penjualan_nett']), 0.0);
            $ndiscount = $this->parseDecimal($this->extractField($normalizedRow, ['discount', 'diskon']), 0.0);
            $ntax = $this->parseDecimal($this->extractField($normalizedRow, ['tax_included', 'taxincluded', 'tax', 'pajak']), 0.0);
            $nrounding = $this->parseDecimal($this->extractField($normalizedRow, ['rounding', 'pembulatan']), 0.0);

            // Modal & Laba (nullable, jika tidak ada/kosong/strip disimpan null)
            $ntotalCost = $this->parseNullableDecimal($this->extractField($normalizedRow, ['product_cost', 'productcost', 'modal']));
            $ntotalProfit = $this->parseNullableDecimal($this->extractField($normalizedRow, ['nett_profit', 'nettprofit', 'laba']));

            // Kolom total amount sebagai instrumen pembanding audit (Benchmark)
            $rawTotalAmount = $this->extractField($normalizedRow, ['total_amount', 'totalamount']);
            $totalAmountBenchmark = $rawTotalAmount !== null ? $this->parseDecimal($rawTotalAmount, $ngrandtotal) : null;

            // Karena paid = 1 (PAID), npaid = net amount
            $npaid = $ngrandtotal;
            $nchange = 0.0;

            // 6. Metadata Pelanggan dan Kasir
            $rawCustomerId = $this->extractField($normalizedRow, ['customer_id', 'customerid', 'id_customer', 'membership_no', 'cmembership_no']);
            $customerName = trim((string) $this->extractField($normalizedRow, ['customer_name', 'customername', 'pelanggan', 'customer']));
            $customerPhone = trim((string) $this->extractField($normalizedRow, ['customer_phone', 'customerphone', 'phone', 'telepon', 'no_hp']));

            $customerData = $this->resolveCustomer($rawCustomerId, $customerName, $customerPhone);
            $nidCustomer = $customerData['nid_customer'];
            $cnameCustomer = $customerData['cname_customer'];

            $servedBy = trim((string) $this->extractField($normalizedRow, ['served_by', 'servedby', 'kasir', 'sales_name', 'salesname']));
            $salesEmail = trim((string) $this->extractField($normalizedRow, ['sales_email', 'salesemail', 'email_kasir']));
            $rowCashierUserId = $this->resolveCashierNid($servedBy, $salesEmail);

            $rawNotes = $this->extractField($normalizedRow, ['notes', 'catatan', 'keterangan']);
            $cnote = !empty($rawNotes) ? trim((string)$rawNotes) : null;

            // 7. Deteksi Duplikasi Internal File (File-Level Duplicate)
            if (isset($seenInFile[$orderNo])) {
                $this->results['duplicate_skipped']++;
                $this->results['duplicate_net_amount'] += $ngrandtotal;
                $this->results['issues'][] = [
                    'row' => $rowNum,
                    'order_no' => $orderNo,
                    'type' => 'DUPLICATE',
                    'message' => "Nomor pesanan duplikat dengan baris sebelumnya pada file yang sama.",
                ];
                continue;
            }
            $seenInFile[$orderNo] = true;

            // 8. Deteksi Duplikasi & Konflik Terhadap Database
            if ($existingOrders->has($orderNo)) {
                $existing = $existingOrders->get($orderNo);
                $existingDate = $existing->dtransaction ? Carbon::parse($existing->dtransaction)->format('Y-m-d') : null;
                $isSameDate = ($existingDate === $dtransaction->format('Y-m-d'));
                $isSameGrandtotal = (abs((float)$existing->ngrandtotal - (float)$ngrandtotal) < 0.01);
                $isSameStatus = ($existing->cstatus === $cstatus);

                if ($isSameDate && $isSameGrandtotal && $isSameStatus) {
                    // Data Identik: Dilewati secara aman
                    $this->results['duplicate_skipped']++;
                    $this->results['duplicate_net_amount'] += $ngrandtotal;
                    $this->results['issues'][] = [
                        'row' => $rowNum,
                        'order_no' => $orderNo,
                        'type' => 'DUPLICATE',
                        'message' => "Data transaksi identik sudah ada di database (dilewati).",
                    ];
                } else {
                    // Data Konflik: Nomor sama tapi nominal/tanggal/status berbeda
                    $this->results['conflict_skipped']++;
                    $this->results['conflict_net_amount'] += $ngrandtotal;
                    $dbTotalFormatted = number_format((float)$existing->ngrandtotal, 0, ',', '.');
                    $excelTotalFormatted = number_format($ngrandtotal, 0, ',', '.');
                    $this->results['issues'][] = [
                        'row' => $rowNum,
                        'order_no' => $orderNo,
                        'type' => 'CONFLICT',
                        'message' => "Nomor pesanan sudah ada dengan data berbeda (DB: Rp {$dbTotalFormatted} vs Excel: Rp {$excelTotalFormatted}). Data lama tidak diubah.",
                    ];
                }
                continue;
            }

            // 9. Baris Valid Lolos Validasi
            $this->results['valid_rows']++;
            $this->results['valid_subtotal'] += $nsubtotal;
            $this->results['valid_net_amount'] += $ngrandtotal;

            $recordData = [
                'cnotransaction' => $orderNo,
                'dtransaction' => $dtransaction->format('Y-m-d H:i:s'),
                'nid_outlet' => $this->outletId,
                'nid_user' => $rowCashierUserId,
                'nid_customer' => $nidCustomer,
                'cname_customer' => $cnameCustomer,
                'nid_shift' => null, // Wajib NULL untuk transaksi historis
                'nid_payment' => null,
                'nid_voucher' => null,
                'nqueue' => null,
                'cordertype' => MposSalesH::ORDER_TYPE_TAKE_AWAY, // Tipe pesanan import Olsera: TAKE_AWAY
                'nvisitor' => 1,
                'ctable' => null,
                'nsubtotal' => $nsubtotal,
                'ndiscount' => $ndiscount,
                'ntax' => $ntax,
                'nservice_charge' => 0.0,
                'nrounding' => $nrounding,
                'ngrandtotal' => $ngrandtotal,
                'npaid' => $npaid,
                'nchange' => $nchange,
                'nitem' => $nitem,
                'cnote' => $cnote,
                'cstatus' => $cstatus,
                'csource' => 'OLSERA',
                'ntotal_cost' => $ntotalCost,
                'ntotal_profit' => $ntotalProfit,
                'dcreated' => now()->format('Y-m-d H:i:s'),
            ];

            if ($this->isDryRun) {
                // Di mode preview, simpan representasi data untuk konfirmasi
                $this->results['valid_records'][] = $recordData;
            } else {
                // Di mode konfirmasi/eksekusi, lakukan insert ke mpos_sales_h
                MposSalesH::create($recordData);
                $this->results['saved_count']++;
            }
        }
    }

    /**
     * Normalisasi spasi: trim dan reduksi multiple whitespace menjadi satu spasi.
     */
    protected function normalizeWhitespace(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Mengambil field berdasarkan daftar alias key case-insensitive.
     */
    protected function extractField(array $row, array $aliases): mixed
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $row) && $row[$alias] !== null && trim((string)$row[$alias]) !== '') {
                return $row[$alias];
            }
        }
        return null;
    }

    /**
     * Parse tanggal dari order_date dan jam dari order_time.
     * Mengabaikan komponen 00:00:00 bawaan order_date.
     */
    protected function parseDateTime(mixed $rawDate, mixed $rawTime): ?Carbon
    {
        if (empty($rawDate) || empty($rawTime)) {
            return null;
        }

        try {
            // 1. Ekstraksi Tanggal (Y-m-d)
            if (is_numeric($rawDate)) {
                $dateObj = ExcelDate::excelToDateTimeObject($rawDate);
                $datePart = $dateObj->format('Y-m-d');
            } else {
                $parsedDate = Carbon::parse(trim((string)$rawDate));
                $datePart = $parsedDate->format('Y-m-d');
            }

            // 2. Ekstraksi Jam (H:i:s)
            if (is_numeric($rawTime)) {
                $timeObj = ExcelDate::excelToDateTimeObject($rawTime);
                $timePart = $timeObj->format('H:i:s');
            } else {
                $parsedTime = Carbon::parse(trim((string)$rawTime));
                $timePart = $parsedTime->format('H:i:s');
            }

            return Carbon::createFromFormat('Y-m-d H:i:s', "{$datePart} {$timePart}");
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parse integer strictly positive (> 0).
     */
    protected function parseStrictPositiveInteger(mixed $value): ?int
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9]/', '', (string)$value);
        if ($clean === '' || !is_numeric($clean)) {
            return null;
        }

        $int = (int) $clean;
        return $int > 0 ? $int : null;
    }

    /**
     * Parse decimal aman sanitasi simbol mata uang, koma, spasi.
     */
    protected function parseDecimal(mixed $value, float $default = 0.0): float
    {
        if ($value === null || trim((string)$value) === '') {
            return $default;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string)$value);
        return is_numeric($clean) ? (float)$clean : $default;
    }

    /**
     * Parse nullable decimal. Mengembalikan null jika kosong atau strip '-'.
     */
    protected function parseNullableDecimal(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string)$value);
        if ($str === '' || $str === '-' || strtolower($str) === 'null') {
            return null;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', $str);
        return is_numeric($clean) ? (float)$clean : null;
    }

    /**
     * Resolusi kasir berdasarkan nama 'served by' atau email 'sales email'.
     * Mencocokkan ke pengguna POS (mpos_user) dan master pengguna (muser).
     */
    protected function resolveCashierNid(?string $servedBy, ?string $salesEmail = null): int
    {
        $cleanServedBy = !empty($servedBy) ? strtolower(trim($servedBy)) : null;
        $cleanEmail = !empty($salesEmail) ? strtolower(trim($salesEmail)) : null;

        if (!$cleanServedBy && !$cleanEmail) {
            return $this->importingUserId;
        }

        // 1. Cek kecocokan di mpos_user (Pengguna POS) untuk outlet ini
        $outletPosUsers = MposUser::with('user')
            ->where('nid_outlet', $this->outletId)
            ->get();

        foreach ($outletPosUsers as $posUser) {
            $u = $posUser->user;
            if (!$u) continue;

            $uName = strtolower(trim($u->cname ?? ''));
            $uFull = strtolower(trim($u->cfullname ?? ''));
            $uEmail = strtolower(trim($u->cemail ?? ''));

            if ($cleanServedBy && ($uName === $cleanServedBy || $uFull === $cleanServedBy)) {
                return $posUser->nid;
            }
            if ($cleanEmail && ($uEmail === $cleanEmail || str_contains($cleanEmail, $uEmail) || str_contains($uEmail, $cleanEmail))) {
                return $posUser->nid;
            }
        }

        // 2. Cek kecocokan di mpos_user lintas outlet
        $allPosUsers = MposUser::with('user')->get();
        foreach ($allPosUsers as $posUser) {
            $u = $posUser->user;
            if (!$u) continue;

            $uName = strtolower(trim($u->cname ?? ''));
            $uFull = strtolower(trim($u->cfullname ?? ''));
            $uEmail = strtolower(trim($u->cemail ?? ''));

            if (($cleanServedBy && ($uName === $cleanServedBy || $uFull === $cleanServedBy)) ||
                ($cleanEmail && $uEmail && ($uEmail === $cleanEmail || str_contains($cleanEmail, $uEmail) || str_contains($uEmail, $cleanEmail)))) {
                
                // Pastikan user ini juga terdaftar di mpos_user untuk outlet yang sedang diimpor
                if (!$this->isDryRun) {
                    $outletUser = MposUser::firstOrCreate([
                        'nid_user' => $posUser->nid_user,
                        'nid_outlet' => $this->outletId,
                    ], [
                        'fcashier' => 1,
                    ]);
                    return $outletUser->nid;
                }
                return $posUser->nid;
            }
        }

        // 3. Cek kecocokan di tabel master muser
        $allMusers = DB::table('muser')->get(['nid', 'cname', 'cfullname', 'cemail']);
        $matchedMuser = null;

        // 3a. Exact match
        foreach ($allMusers as $u) {
            $uName = strtolower(trim($u->cname ?? ''));
            $uFull = strtolower(trim($u->cfullname ?? ''));
            $uEmail = strtolower(trim($u->cemail ?? ''));

            if ($cleanServedBy && ($uName === $cleanServedBy || $uFull === $cleanServedBy)) {
                $matchedMuser = $u;
                break;
            }
            if ($cleanEmail && $uEmail && ($uEmail === $cleanEmail || str_contains($cleanEmail, $uEmail) || str_contains($uEmail, $cleanEmail))) {
                $matchedMuser = $u;
                break;
            }
        }

        // 3b. Substring / contains match
        if (!$matchedMuser && $cleanServedBy) {
            foreach ($allMusers as $u) {
                $uName = strtolower(trim($u->cname ?? ''));
                $uFull = strtolower(trim($u->cfullname ?? ''));

                if ($uName !== '' && (str_contains($cleanServedBy, $uName) || str_contains($uName, $cleanServedBy))) {
                    $matchedMuser = $u;
                    break;
                }
                if ($uFull !== '' && (str_contains($cleanServedBy, $uFull) || str_contains($uFull, $cleanServedBy))) {
                    $matchedMuser = $u;
                    break;
                }
            }
        }

        // 3c. Fuzzy similarity match (>= 75%)
        if (!$matchedMuser && $cleanServedBy) {
            $bestPercent = 0;
            foreach ($allMusers as $u) {
                $uName = strtolower(trim($u->cname ?? ''));
                if ($uName !== '') {
                    similar_text($cleanServedBy, $uName, $percent);
                    if ($percent > $bestPercent && $percent >= 75) {
                        $bestPercent = $percent;
                        $matchedMuser = $u;
                    }
                }
            }
        }

        if ($matchedMuser) {
            if (!$this->isDryRun) {
                $posUser = MposUser::firstOrCreate([
                    'nid_user' => $matchedMuser->nid,
                    'nid_outlet' => $this->outletId,
                ], [
                    'fcashier' => 1,
                ]);
                return $posUser->nid;
            }
            // Di mode dry-run, jika sudah ada di DB ambil nid-nya, jika belum gunakan importingUserId
            $existing = MposUser::where('nid_user', $matchedMuser->nid)->where('nid_outlet', $this->outletId)->first();
            return $existing ? $existing->nid : $this->importingUserId;
        }

        // Fallback: gunakan akun yang mengimpor
        return $this->importingUserId;
    }

    /**
     * Resolusi pelanggan berdasarkan 'customer id' (cmembership_no pada mpos_cust).
     * Jika tidak ditemukan, fallback mencari via nomor telepon.
     */
    protected function resolveCustomer(mixed $rawCustomerId, ?string $customerName, ?string $customerPhone = null): array
    {
        $cleanCustomerId = $rawCustomerId !== null ? trim((string)$rawCustomerId) : null;
        $cleanName = !empty($customerName) ? trim((string)$customerName) : null;

        $nidCustomer = null;
        $finalName = $cleanName;

        if (!empty($cleanCustomerId)) {
            // 1. Cari berdasarkan cmembership_no (prioritas outlet aktif, lalu seluruh outlet)
            $matchedCust = MposCust::where('cmembership_no', $cleanCustomerId)
                ->where('nid_outlet', $this->outletId)
                ->first()
                ?? MposCust::where('cmembership_no', $cleanCustomerId)->first();

            if ($matchedCust) {
                $nidCustomer = $matchedCust->nid;
                if ($finalName === null) {
                    $finalName = $matchedCust->cname;
                }
            }
        }

        // 2. Jika belum ditemukan via cmembership_no, coba cari via nomor telepon jika tersedia
        if (!$nidCustomer && !empty($customerPhone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $customerPhone);
            if ($cleanPhone !== '') {
                $matchedCust = MposCust::whereRaw("REPLACE(REPLACE(cphone, '+', ''), ' ', '') LIKE ?", ["%{$cleanPhone}%"])->first();
                if ($matchedCust) {
                    $nidCustomer = $matchedCust->nid;
                    if ($finalName === null) {
                        $finalName = $matchedCust->cname;
                    }
                }
            }
        }

        return [
            'nid_customer' => $nidCustomer,
            'cname_customer' => $finalName,
        ];
    }
}
