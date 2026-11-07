<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\MposIngredients;
use App\Models\MposOutlet;
use App\Models\MposProduct;
use App\Models\MposPurchaseD;
use App\Models\MposPurchaseH;
use App\Models\MposStockMutation;
use App\Models\MposSupplier;
use App\Traits\ResolvesOutletScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request)
    {
        $statusTab = strtolower($request->input('status', $request->input('tab', 'pending')));
        if (!in_array($statusTab, ['pending', 'received', 'cancelled', 'all'])) {
            $statusTab = 'pending';
        }
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $supplierId = $request->input('supplier_id');
        $outletId = $this->resolveOutletIdFromRequest($request, 'outlet_id');
        $paymentStatus = $request->input('payment_status');
        $search = $request->input('search');

        $outlets = $this->getAvailableOutlets();
        $isOutletLocked = $this->isOutletLocked();
        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $supplierQuery = MposSupplier::where('factive', 1)->with('outlet');
        if (!empty($outletId)) {
            $supplierQuery->where(function ($q) use ($outletId) {
                $q->where('nid_outlet', $outletId)
                  ->orWhereNull('nid_outlet');
            });
        } elseif (!$this->isSuperAdminUser()) {
            $supplierQuery->where(function ($q) use ($allowedOutletIds) {
                $q->whereIn('nid_outlet', $allowedOutletIds)
                  ->orWhereNull('nid_outlet');
            });
        }
        $suppliers = $supplierQuery->orderBy('cname')->get();

        // Base query for counts
        $baseQuery = MposPurchaseH::query();
        if (!empty($outletId)) {
            $baseQuery->where('nid_outlet', $outletId);
        } elseif (!$this->isSuperAdminUser()) {
            $baseQuery->whereIn('nid_outlet', $allowedOutletIds);
        }

        $countPending = (clone $baseQuery)->where('cstatus', MposPurchaseH::STATUS_PENDING)->count();
        $countReceived = (clone $baseQuery)->where('cstatus', MposPurchaseH::STATUS_RECEIVED)->count();
        $countCancelled = (clone $baseQuery)->where('cstatus', MposPurchaseH::STATUS_CANCELLED)->count();
        $countAll = (clone $baseQuery)->count();

        // Filtered query for listing
        $query = MposPurchaseH::with(['supplier', 'outlet', 'user'])
            ->withCount('details');

        if (!empty($outletId)) {
            $query->where('nid_outlet', $outletId);
        } elseif (!$this->isSuperAdminUser()) {
            $query->whereIn('nid_outlet', $allowedOutletIds);
        }

        // Apply Tab / Status Filter
        if ($statusTab === 'pending') {
            $query->where('cstatus', MposPurchaseH::STATUS_PENDING);
        } elseif ($statusTab === 'received') {
            $query->where('cstatus', MposPurchaseH::STATUS_RECEIVED);
        } elseif ($statusTab === 'cancelled') {
            $query->where('cstatus', MposPurchaseH::STATUS_CANCELLED);
        }

        if (!empty($startDate)) {
            $query->where('dpurchase', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if (!empty($endDate)) {
            $query->where('dpurchase', '<=', Carbon::parse($endDate)->endOfDay());
        }

        if (!empty($supplierId)) {
            $query->where('nid_supplier', $supplierId);
        }

        if (!empty($paymentStatus)) {
            $query->where('cpayment_status', $paymentStatus);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('cno_purchase', 'like', "%{$search}%")
                  ->orWhere('cno_reference', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('cname', 'like', "%{$search}%");
                  });
            });
        }

        $purchases = $query->orderBy('dpurchase', 'desc')
            ->orderBy('nid', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Return AJAX JSON response for real-time fetching without page reload
        if ($request->ajax() || $request->wantsJson() || $request->has('ajax')) {
            return response()->json([
                'success' => true,
                'html' => view('purchases.partials.table', compact('purchases', 'statusTab'))->render(),
                'counts' => [
                    'pending' => $countPending,
                    'received' => $countReceived,
                    'cancelled' => $countCancelled,
                    'all' => $countAll,
                ],
            ]);
        }

        $productsQuery = MposProduct::where('cstatus', 'ACTIVE');
        if (!$this->isSuperAdminUser()) {
            $productsQuery->whereIn('nid_outlet', $allowedOutletIds);
        }
        $productColumns = ['nid', 'cname', 'nid_outlet', 'nprice', 'nprice_buy', 'nqty'];
        if (MposProduct::hasProductTypeColumn()) {
            $productColumns[] = 'cproduct_type';
        }
        if (MposProduct::hasSatuanColumn()) {
            $productColumns[] = 'csatuan';
        }
        $products = $productsQuery->orderBy('cname')->get($productColumns);

        $ingredientsQuery = MposIngredients::query();
        if (!$this->isSuperAdminUser()) {
            $ingredientsQuery->where(function ($iq) use ($allowedOutletIds) {
                $iq->whereIn('nid_outlet', $allowedOutletIds)
                   ->orWhereNull('nid_outlet');
            });
        }
        $ingredients = $ingredientsQuery->orderBy('cname')->get();

        return view('purchases.index', compact(
            'purchases',
            'statusTab',
            'countPending',
            'countReceived',
            'countCancelled',
            'countAll',
            'suppliers',
            'outlets',
            'outletId',
            'supplierId',
            'startDate',
            'endDate',
            'paymentStatus',
            'search',
            'isOutletLocked',
            'products',
            'ingredients'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nid_supplier' => 'required|exists:mpos_supplier,nid',
            'dpurchase' => 'required|date',
            'nid_outlet' => 'required|exists:mpos_outlet,nid',
            'ddue_date' => 'nullable|date',
            'cno_reference' => 'nullable|string|max:100',
            'cnote' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.citem_type' => 'nullable|string|in:PRODUCT,INGREDIENT,PRODUK,SETENGAH_JADI,BAHAN_BAKU',
            'items.*.item_id' => 'nullable|integer',
            'items.*.nid_product' => 'nullable|integer',
            'items.*.nid_ingredient' => 'nullable|integer',
            'items.*.nqty' => 'nullable|integer|min:1',
            'items.*.ncost' => 'nullable|numeric|min:0',
        ], [
            'nid_supplier.required' => 'Pilih supplier terlebih dahulu.',
            'dpurchase.required' => 'Tanggal beli wajib diisi.',
            'nid_outlet.required' => 'Pilih outlet tujuan barang masuk.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error_modal', 'create_po');
        }

        DB::beginTransaction();
        try {
            $dpurchase = Carbon::parse($request->input('dpurchase'));
            $poNumber = MposPurchaseH::generatePurchaseNumber($dpurchase);

            $purchase = MposPurchaseH::create([
                'cno_purchase' => $poNumber,
                'dpurchase' => $dpurchase,
                'nid_supplier' => $request->input('nid_supplier'),
                'nid_outlet' => $request->input('nid_outlet'),
                'nid_user' => Auth::id() ?? 1,
                'cstatus' => MposPurchaseH::STATUS_PENDING,
                'cpayment_status' => MposPurchaseH::PAYMENT_UNPAID,
                'ddue_date' => $request->input('ddue_date') ? Carbon::parse($request->input('ddue_date')) : null,
                'cno_reference' => $request->input('cno_reference'),
                'ntotal_amount' => 0,
                'cnote' => $request->input('cnote'),
                'dcreated' => now(),
            ]);

            $totalAmount = 0;
            $itemsCount = 0;

            if ($request->has('items') && is_array($request->input('items'))) {
                foreach ($request->input('items') as $itemData) {
                    $rawType = strtoupper(trim((string) ($itemData['citem_type'] ?? MposPurchaseD::TYPE_PRODUK)));
                    if (in_array($rawType, [MposPurchaseD::TYPE_BAHAN_BAKU, MposPurchaseD::TYPE_INGREDIENT], true)) {
                        $itemType = MposPurchaseD::TYPE_BAHAN_BAKU;
                    } elseif ($rawType === MposPurchaseD::TYPE_SETENGAH_JADI) {
                        $itemType = MposPurchaseD::TYPE_SETENGAH_JADI;
                    } else {
                        $itemType = MposPurchaseD::TYPE_PRODUK;
                    }

                    $selectedId = $itemData['item_id']
                        ?? ($itemData['nid_product'] ?? ($itemData['nid_ingredient'] ?? null));

                    $qty = (int) ($itemData['nqty'] ?? 0);
                    $cost = (float) ($itemData['ncost'] ?? 0);

                    if (!empty($selectedId) && $qty > 0) {
                        $subtotal = $qty * $cost;

                        if (MposProduct::where('nid', $selectedId)->exists()) {
                            MposPurchaseD::create([
                                'nid_purchase' => $purchase->nid,
                                'citem_type' => $itemType,
                                'nid_product' => (int) $selectedId,
                                'nid_ingredient' => null,
                                'nqty' => $qty,
                                'ncost' => $cost,
                                'nsubtotal' => $subtotal,
                            ]);
                        } elseif (MposIngredients::where('nid', $selectedId)->exists()) {
                            // Fallback jika masih ada data legacy dari mpos_ingredients
                            MposPurchaseD::create([
                                'nid_purchase' => $purchase->nid,
                                'citem_type' => MposPurchaseD::TYPE_BAHAN_BAKU,
                                'nid_product' => null,
                                'nid_ingredient' => (int) $selectedId,
                                'nqty' => $qty,
                                'ncost' => $cost,
                                'nsubtotal' => $subtotal,
                            ]);
                        } else {
                            DB::rollBack();
                            return redirect()->back()
                                ->with('error', 'Item yang dipilih tidak ditemukan.')
                                ->withInput()
                                ->with('error_modal', 'create_po');
                        }

                        $totalAmount += $subtotal;
                        $itemsCount++;
                    }
                }
            }

            $purchase->ntotal_amount = $totalAmount;
            $purchase->save();

            DB::commit();

            $msg = $itemsCount > 0
                ? "Pesanan Pembelian {$purchase->cno_purchase} berhasil dibuat dengan {$itemsCount} item."
                : "Pesanan Pembelian {$purchase->cno_purchase} berhasil dibuat.";

            return redirect()->route('purchases.show', $purchase->nid)->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal membuat pesanan pembelian: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function importPdf(Request $request)
    {
        $request->validate([
            'nid_supplier' => 'required|exists:mpos_supplier,nid',
            'nid_outlet' => 'required|exists:mpos_outlet,nid',
            'pdf_file' => 'required|file|mimes:pdf|max:10240',
        ], [
            'nid_supplier.required' => 'Supplier wajib dipilih.',
            'nid_outlet.required' => 'Outlet tujuan wajib dipilih.',
            'pdf_file.required' => 'File PDF wajib diunggah.',
            'pdf_file.mimes' => 'Format file harus berupa PDF (.pdf).',
            'pdf_file.max' => 'Ukuran file PDF maksimal 10MB.',
        ]);

        try {
            $file = $request->file('pdf_file');
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file->getPathname());
            $fullText = $pdf->getText();

            if (empty(trim($fullText))) {
                return redirect()->back()
                    ->with('error', 'Dokumen PDF tidak terbaca atau tidak memiliki teks yang dapat diekstrak.')
                    ->withInput();
            }

            // 1. Ekstrak No Pembelian Supplier / No Referensi
            $supplierPoRef = null;
            if (preg_match('#\b(PO\d{8,}[A-Za-z0-9_-]*)\b#i', $fullText, $mPo)) {
                $supplierPoRef = trim($mPo[1]);
            } elseif (preg_match('#(?:\bNo\.?\s*Pembelian|Purchase\s*Order\s*No\.?)\s*[:\s]+([A-Za-z0-9_-]+)#i', $fullText, $m)) {
                $cand = trim($m[1]);
                if (!in_array(strtolower($cand), ['penerima', 'tgl', 'tanggal', 'kepada', 'kode'])) {
                    $supplierPoRef = $cand;
                }
            }

            // 2. Ekstrak Tanggal Beli
            $dpurchase = now();
            if (preg_match('#(?:\bTgl\.?\s*Beli|Purchase\s*Date)\s*[:\s]+([0-9]{1,2}\s*[-/ ]\s*[A-Za-z0-9]{2,9}\s*[-/ ]\s*[0-9]{4})#i', $fullText, $m)) {
                $dpurchase = $this->parsePdfDate($m[1]);
            }

            // 3. Ekstrak Jatuh Tempo
            $dueDate = null;
            if (preg_match('#(?:\bJatuh\s*Tempo(?:\s*Pembayaran)?|Due\s*Date)\s*[:\s]*([0-9]{1,2}\s*[-/ ]\s*[A-Za-z0-9]{2,9}\s*[-/ ]\s*[0-9]{4})#i', $fullText, $m)) {
                $dueDate = $this->parsePdfDate($m[1]);
            }

            // 4. Ekstrak Item-item Pembelian
            $lines = preg_split('/\r\n|\r|\n/', $fullText);
            $parsedItems = [];

            // Pre-processing: sambung baris angka yang terpotong/wrapped ke baris baru (khususnya nominal di Olsera APK)
            $processedLines = [];
            for ($idx = 0; $idx < count($lines); $idx++) {
                $cur = trim(preg_replace('/\t+/', ' ', $lines[$idx]));
                if (empty($cur)) {
                    continue;
                }

                // Jika baris saat ini adalah angka murni desimal/ribuan dan baris sebelumnya menggantung setelah IDR/Rp
                if (preg_match('#^[0-9.,]+$#', $cur) && count($processedLines) > 0) {
                    $lastIdx = count($processedLines) - 1;
                    if (preg_match('#(?:IDR|Rp|\s)$#i', $processedLines[$lastIdx])) {
                        $processedLines[$lastIdx] .= ' ' . $cur;
                        continue;
                    }
                }

                $processedLines[] = $cur;
            }

            // Pola 1: Baris tabel lengkap dalam 1 baris
            // Format: [Nama Produk] [Qty][Unit] [IDR/Rp] [Harga] [Diskon opsional] [IDR/Rp] [Total]
            // Catatan: Menggunakan \s* antara nama, qty, dan unit agar fleksibel saat APK Olsera merapatkan karakter, misal ')20pack' atau '30Pack'
            $tableRowPattern = '#^(.+?)\s*(\d+(?:[.,]\d+)?)\s*([A-Za-z]+)\s+(?:IDR|Rp)?\s*([0-9.,]+)(?:\s+(?:[0-9.,]+%?|-))?\s+(?:IDR|Rp)?\s*([0-9.,]+)$#i';

            // Pola 2: Multi-line lookahead (nama produk di baris sebelumnya, angka di baris ini)
            $numbersOnlyPattern = '#^(\d+(?:[.,]\d+)?)\s*([A-Za-z]+)\s+(?:IDR|Rp)?\s*([0-9.,]+)(?:\s+(?:[0-9.,]+%?|-))?\s+(?:IDR|Rp)?\s*([0-9.,]+)$#i';

            // Pola 3: Fallback tanpa unit pengukuran
            $fallbackPattern = '#^(.+?)\s*(\d+(?:[.,]\d+)?)\s+(?:IDR|Rp)?\s*([0-9.,]+)\s+(?:IDR|Rp)?\s*([0-9.,]+)$#i';

            $ignoreKeywords = [
                'foto', 'nama', 'unit pengukuran', 'pengukuran', 'no. pembelian', 'kepada :',
                'iqos matahati', 'jl. ir soekarno', 'penerima :', 'total pembelian',
                'subtotal', 'jumlah terbayar', 'pembayaran yang belum lunas',
                'tanggal pembayaran', 'jatuh tempo pembayaran', 'kode supplier',
                'telpon:', 'telepon:', 'email:', 'diskon', 'total harga', 'pembelian'
            ];

            $cleanLines = [];
            foreach ($processedLines as $line) {
                $lowerLine = strtolower($line);
                $shouldSkip = false;
                foreach ($ignoreKeywords as $kw) {
                    if (str_starts_with($lowerLine, $kw) || $lowerLine === $kw) {
                        $shouldSkip = true;
                        break;
                    }
                }
                if ($shouldSkip) {
                    continue;
                }

                $cleanLines[] = $line;
            }

            for ($i = 0; $i < count($cleanLines); $i++) {
                $currentLine = $cleanLines[$i];

                $rawItemName = null;
                $qty = 0;
                $unit = 'PCS';
                $cost = 0.0;
                $subtotal = 0.0;

                if (preg_match($tableRowPattern, $currentLine, $matches)) {
                    $rawItemName = trim($matches[1]);
                    $qty = (int) $this->parseIndonesianNumber($matches[2]);
                    $unit = trim($matches[3]);
                    $cost = $this->parseIndonesianNumber($matches[4]);
                    $subtotal = isset($matches[5]) ? $this->parseIndonesianNumber($matches[5]) : ($qty * $cost);
                } elseif ($i > 0 && preg_match($numbersOnlyPattern, $currentLine, $matches)) {
                    $prevLine = $cleanLines[$i - 1];
                    if (!preg_match($numbersOnlyPattern, $prevLine) && !preg_match($tableRowPattern, $prevLine)) {
                        $rawItemName = trim($prevLine);
                        $qty = (int) $this->parseIndonesianNumber($matches[1]);
                        $unit = trim($matches[2]);
                        $cost = $this->parseIndonesianNumber($matches[3]);
                        $subtotal = isset($matches[4]) ? $this->parseIndonesianNumber($matches[4]) : ($qty * $cost);
                    }
                } elseif (preg_match($fallbackPattern, $currentLine, $matches)) {
                    $rawItemName = trim($matches[1]);
                    $qty = (int) $this->parseIndonesianNumber($matches[2]);
                    $cost = $this->parseIndonesianNumber($matches[3]);
                    $subtotal = isset($matches[4]) ? $this->parseIndonesianNumber($matches[4]) : ($qty * $cost);
                }

                if ($rawItemName && $qty > 0) {
                    $cleanName = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $rawItemName));
                    $parsedItems[] = [
                        'raw_name' => $rawItemName,
                        'clean_name' => $cleanName,
                        'qty' => $qty,
                        'unit' => strtoupper($unit ?: 'PCS'),
                        'cost' => $cost,
                        'subtotal' => $subtotal > 0 ? $subtotal : ($qty * $cost),
                    ];
                }
            }

            if (empty($parsedItems)) {
                return redirect()->back()
                    ->with('error', 'Tidak ditemukan item produk pada dokumen PDF tersebut. Pastikan format tabel sesuai.')
                    ->withInput();
            }

            $outletId = $request->input('nid_outlet');

            DB::beginTransaction();

            $poNumber = MposPurchaseH::generatePurchaseNumber($dpurchase);
            $note = 'Hasil import otomatis dari dokumen PDF';
            if ($supplierPoRef) {
                $note .= " (No. Ref Supplier: {$supplierPoRef})";
            }

            $purchase = MposPurchaseH::create([
                'cno_purchase' => $poNumber,
                'dpurchase' => $dpurchase,
                'nid_supplier' => $request->input('nid_supplier'),
                'nid_outlet' => $outletId,
                'nid_user' => Auth::id() ?? 1,
                'cstatus' => MposPurchaseH::STATUS_PENDING,
                'cpayment_status' => MposPurchaseH::PAYMENT_UNPAID,
                'ddue_date' => $dueDate,
                'cno_reference' => $supplierPoRef,
                'ntotal_amount' => 0,
                'cnote' => $note,
                'dcreated' => now(),
            ]);

            $totalAmount = 0;
            $itemsCount = 0;

            foreach ($parsedItems as $item) {
                $cname = $item['clean_name'];
                $itemCost = (float) $item['cost'];
                $itemQty = (int) $item['qty'];
                $itemSubtotal = (float) $item['subtotal'];

                // 1. Prioritaskan EXACT MATCH di outlet tujuan
                $product = MposProduct::where('nid_outlet', $outletId)
                    ->where(function ($q) use ($item, $cname) {
                        $q->whereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim($cname))])
                          ->orWhereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim($item['raw_name']))]);
                    })
                    ->first();

                // 2. Jika tidak ada exact match, gunakan pencarian partial dengan memfilter agar tidak tertukar ke prefix KDR
                if (!$product) {
                    $partialQuery = MposProduct::where('nid_outlet', $outletId)
                        ->where(function ($q) use ($item, $cname) {
                            $q->whereRaw('LOWER(cname) LIKE ?', ['%' . strtolower($cname) . '%'])
                              ->orWhereRaw('LOWER(?) LIKE CONCAT("%", LOWER(cname), "%")', [strtolower($cname)]);
                        });

                    if (!str_contains(strtolower($cname), 'kdr')) {
                        $partialQuery->whereRaw('LOWER(cname) NOT LIKE ?', ['kdr%']);
                    }

                    $product = $partialQuery->first();
                }

                // 3. Jika tidak ditemukan di outlet ini, cari exact match di outlet lain untuk disalin
                if (!$product) {
                    $otherProductQuery = MposProduct::where(function ($q) use ($item, $cname) {
                        $q->whereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim($cname))])
                          ->orWhereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim($item['raw_name']))]);
                    });

                    if (!str_contains(strtolower($cname), 'kdr')) {
                        $otherProductQuery->whereRaw('LOWER(cname) NOT LIKE ?', ['kdr%']);
                    }

                    $otherProduct = $otherProductQuery->first();

                    if ($otherProduct) {
                        $productPayload = [
                            'cname' => $otherProduct->cname,
                            'nid_category' => $otherProduct->nid_category,
                            'nid_outlet' => $outletId,
                            'nprice' => $otherProduct->nprice,
                            'nprice_online' => $otherProduct->nprice_online,
                            'nprice_buy' => $itemCost,
                            'nqty' => 0,
                            'cphotos' => $otherProduct->cphotos,
                            'cstatus' => 'ACTIVE',
                            'fcombo' => 0,
                        ];
                        if (MposProduct::hasSatuanColumn()) {
                            $productPayload['csatuan'] = $otherProduct->csatuan ?: ($item['unit'] ?? 'PCS');
                        }
                        if (MposProduct::hasProductTypeColumn()) {
                            $productPayload['cproduct_type'] = $otherProduct->cproduct_type ?? MposPurchaseD::TYPE_PRODUK;
                        }
                        $product = MposProduct::create($productPayload);
                    }
                }

                // 4. Jika benar-benar belum ada di database, buat produk baru
                if (!$product) {
                    $firstCat = \App\Models\MposGrpProduct::where('nid_outlet', $outletId)->first()
                        ?? \App\Models\MposGrpProduct::first();

                    $productPayload = [
                        'cname' => $cname,
                        'nid_category' => $firstCat ? $firstCat->nid : 1,
                        'nid_outlet' => $outletId,
                        'nprice' => $itemCost,
                        'nprice_online' => $itemCost,
                        'nprice_buy' => $itemCost,
                        'nqty' => 0,
                        'cstatus' => 'ACTIVE',
                        'fcombo' => 0,
                    ];
                    if (MposProduct::hasSatuanColumn()) {
                        $productPayload['csatuan'] = $item['unit'] ?? 'PCS';
                    }
                    if (MposProduct::hasProductTypeColumn()) {
                        $productPayload['cproduct_type'] = MposPurchaseD::TYPE_PRODUK;
                    }
                    $product = MposProduct::create($productPayload);
                } else {
                    // Update nprice_buy bila ada harga baru dari invoice
                    if ($itemCost > 0) {
                        $product->nprice_buy = $itemCost;
                        $product->save();
                    }
                }

                MposPurchaseD::create([
                    'nid_purchase' => $purchase->nid,
                    'citem_type' => $product->cproduct_type ?? MposPurchaseD::TYPE_PRODUK,
                    'nid_product' => $product->nid,
                    'nid_ingredient' => null,
                    'nqty' => $itemQty,
                    'ncost' => $itemCost,
                    'nsubtotal' => $itemSubtotal,
                ]);

                $totalAmount += $itemSubtotal;
                $itemsCount++;
            }

            $purchase->ntotal_amount = $totalAmount;
            $purchase->save();

            DB::commit();

            return redirect()->route('purchases.show', $purchase->nid)->with(
                'success',
                "Pesanan Pembelian {$purchase->cno_purchase} berhasil di-import dari PDF dengan {$itemsCount} item produk. Silakan verifikasi penerimaan barang dan status pembayaran."
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal meng-import PDF: ' . $e->getMessage())
                ->withInput();
        }
    }

    protected function parsePdfDate(?string $rawDate): Carbon
    {
        if (empty($rawDate)) {
            return now();
        }
        $rawDate = trim(preg_replace('/\s+/', ' ', $rawDate));
        try {
            return Carbon::parse($rawDate);
        } catch (\Exception $e) {
            $idMonths = [
                'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
                'Mei' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Agu' => 'Aug',
                'Agt' => 'Aug', 'Sep' => 'Sep', 'Okt' => 'Oct', 'Nov' => 'Nov',
                'Des' => 'Dec'
            ];
            $engDate = str_ireplace(array_keys($idMonths), array_values($idMonths), $rawDate);
            try {
                return Carbon::parse($engDate);
            } catch (\Exception $e2) {
                return now();
            }
        }
    }

    protected function parseIndonesianNumber(string $str): float
    {
        $clean = trim($str);
        $clean = preg_replace('/^(?:IDR|Rp)\s*/i', '', $clean);
        $clean = trim($clean);

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (str_contains($clean, '.')) {
            if (preg_match('/\.\d{3}(?:\.|$)/', $clean)) {
                $clean = str_replace('.', '', $clean);
            }
        } elseif (str_contains($clean, ',')) {
            if (preg_match('/,\d{3}(?:,|$)/', $clean)) {
                $clean = str_replace(',', '', $clean);
            } else {
                $clean = str_replace(',', '.', $clean);
            }
        }
        return (float) preg_replace('/[^0-9.]/', '', $clean);
    }

    public function show(string $id)
    {
        $purchase = MposPurchaseH::with([
            'supplier',
            'outlet',
            'user',
            'details.product.category',
            'details.ingredient',
        ])->findOrFail($id);

        // Kelompokkan produk per nama, utamakan record di outlet PO
        $productsQuery = MposProduct::where('cstatus', 'ACTIVE')->orderBy('cname');
        $productColumns = ['nid', 'cname', 'nid_outlet', 'nprice', 'nprice_buy', 'nqty'];
        if (MposProduct::hasProductTypeColumn()) {
            $productColumns[] = 'cproduct_type';
        }
        if (MposProduct::hasSatuanColumn()) {
            $productColumns[] = 'csatuan';
        }
        $products = $productsQuery->get($productColumns)
            ->groupBy(fn ($item) => strtoupper(trim((string) $item->cname)))
            ->map(function ($group) use ($purchase) {
                $matched = $group->firstWhere('nid_outlet', (int) $purchase->nid_outlet);
                if ($matched) {
                    return $matched;
                }
                $tmpl = clone $group->first();
                $tmpl->nqty = 0;
                return $tmpl;
            })
            ->sortBy('cname', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Kelompokkan bahan baku per nama, utamakan record di outlet PO
        $ingredients = MposIngredients::orderBy('cname')
            ->get()
            ->groupBy(fn ($item) => strtoupper(trim((string) $item->cname)))
            ->map(function ($group) use ($purchase) {
                $matched = $group->firstWhere('nid_outlet', (int) $purchase->nid_outlet);
                if ($matched) {
                    return $matched;
                }
                $tmpl = clone $group->first();
                $tmpl->nstock = 0;
                return $tmpl;
            })
            ->sortBy('cname', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('purchases.show', compact('purchase', 'products', 'ingredients'));
    }

    public function addItem(Request $request, string $id)
    {
        $purchase = MposPurchaseH::findOrFail($id);

        if ($purchase->cstatus !== MposPurchaseH::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Item tidak dapat ditambah karena pesanan sudah diproses atau dibatalkan.');
        }

        $rawType = strtoupper(trim((string) $request->input('citem_type', MposPurchaseD::TYPE_PRODUK)));
        if (in_array($rawType, [MposPurchaseD::TYPE_BAHAN_BAKU, MposPurchaseD::TYPE_INGREDIENT], true)) {
            $itemType = MposPurchaseD::TYPE_BAHAN_BAKU;
        } elseif ($rawType === MposPurchaseD::TYPE_SETENGAH_JADI) {
            $itemType = MposPurchaseD::TYPE_SETENGAH_JADI;
        } else {
            $itemType = MposPurchaseD::TYPE_PRODUK;
        }

        $rules = [
            'citem_type' => 'nullable|string|in:PRODUCT,INGREDIENT,PRODUK,SETENGAH_JADI,BAHAN_BAKU',
            'nqty' => 'required|integer|min:1',
            'ncost' => 'required|numeric|min:0',
            'nid_product' => 'nullable|integer',
            'nid_ingredient' => 'nullable|integer',
        ];
        $messages = [
            'nqty.min' => 'Jumlah kuantitas minimal 1.',
            'ncost.min' => 'Harga beli tidak boleh negatif.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error_modal', 'add_product');
        }

        $selectedProductId = (int) $request->input('nid_product');
        $selectedIngredientId = (int) $request->input('nid_ingredient');

        if (empty($selectedProductId) && empty($selectedIngredientId)) {
            return redirect()->back()
                ->with('error', 'Pilih item yang akan ditambahkan.')
                ->withInput()
                ->with('error_modal', 'add_product');
        }

        $qty = (int) $request->input('nqty');
        $cost = (float) $request->input('ncost');
        $subtotal = $qty * $cost;

        if (!empty($selectedProductId) && MposProduct::where('nid', $selectedProductId)->exists()) {
            $existingItem = MposPurchaseD::where('nid_purchase', $purchase->nid)
                ->where('nid_product', $selectedProductId)
                ->first();

            if ($existingItem) {
                $existingItem->nqty += $qty;
                $existingItem->ncost = $cost;
                $existingItem->nsubtotal = $existingItem->nqty * $cost;
                $existingItem->citem_type = $itemType;
                $existingItem->save();
            } else {
                MposPurchaseD::create([
                    'nid_purchase' => $purchase->nid,
                    'citem_type' => $itemType,
                    'nid_product' => $selectedProductId,
                    'nid_ingredient' => null,
                    'nqty' => $qty,
                    'ncost' => $cost,
                    'nsubtotal' => $subtotal,
                ]);
            }
            $labelMsg = 'Item berhasil ditambahkan ke pesanan.';
        } elseif (!empty($selectedIngredientId) && MposIngredients::where('nid', $selectedIngredientId)->exists()) {
            $existingItem = MposPurchaseD::where('nid_purchase', $purchase->nid)
                ->where('citem_type', MposPurchaseD::TYPE_BAHAN_BAKU)
                ->where('nid_ingredient', $selectedIngredientId)
                ->first();

            if ($existingItem) {
                $existingItem->nqty += $qty;
                $existingItem->ncost = $cost;
                $existingItem->nsubtotal = $existingItem->nqty * $cost;
                $existingItem->save();
            } else {
                MposPurchaseD::create([
                    'nid_purchase' => $purchase->nid,
                    'citem_type' => MposPurchaseD::TYPE_BAHAN_BAKU,
                    'nid_product' => null,
                    'nid_ingredient' => $selectedIngredientId,
                    'nqty' => $qty,
                    'ncost' => $cost,
                    'nsubtotal' => $subtotal,
                ]);
            }
            $labelMsg = 'Bahan baku berhasil ditambahkan ke pesanan.';
        } else {
            return redirect()->back()
                ->with('error', 'Item yang dipilih tidak ditemukan.')
                ->withInput()
                ->with('error_modal', 'add_product');
        }

        // Recalculate total amount
        $purchase->ntotal_amount = (float) MposPurchaseD::where('nid_purchase', $purchase->nid)->sum('nsubtotal');
        $purchase->save();

        return redirect()->back()->with('success', $labelMsg);
    }

    public function removeItem(string $id, string $detailId)
    {
        $purchase = MposPurchaseH::findOrFail($id);

        if ($purchase->cstatus !== MposPurchaseH::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Item tidak dapat dihapus karena pesanan sudah diproses.');
        }

        $item = MposPurchaseD::where('nid_purchase', $purchase->nid)
            ->where('nid', $detailId)
            ->firstOrFail();

        $item->delete();

        // Recalculate total amount
        $purchase->ntotal_amount = (float) MposPurchaseD::where('nid_purchase', $purchase->nid)->sum('nsubtotal');
        $purchase->save();

        return redirect()->back()->with('success', 'Item berhasil dihapus dari pesanan.');
    }

    /**
     * Konfirmasi Barang Diterima (Goods Receipt):
     * Otomatis menambah stok ke mpos_product (jika Produk) atau mpos_ingredients (jika Bahan Baku)
     */
    public function receive(Request $request, string $id)
    {
        $purchase = MposPurchaseH::with(['details.product', 'details.ingredient'])->findOrFail($id);

        if ($purchase->cstatus !== MposPurchaseH::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Status pesanan saat ini tidak dapat dikonfirmasi diterima.');
        }

        if ($purchase->details->isEmpty()) {
            return redirect()->back()->with('error', 'Pesanan belum memiliki item barang. Tambahkan item terlebih dahulu.');
        }

        $validator = Validator::make($request->all(), [
            'dreceived' => 'required|date',
            'cno_reference' => 'nullable|string|max:100',
            'creceived_by' => 'nullable|string|max:255',
        ], [
            'dreceived.required' => 'Tanggal terima barang wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $receivedDate = Carbon::parse($request->input('dreceived'));
            $receivedBy = $request->input('creceived_by') ?: (Auth::user()?->email ?: (Auth::user()?->cname ?: 'Admin'));

            // 1. Generate nomor dan dokumen Stok Masuk (IN...) otomatis
            $stockInNumber = MposStockMutation::generateNumber('IN', $receivedDate);
            $totalQty = (float) $purchase->details->sum('nqty');

            // 2. Update status PO menjadi RECEIVED
            $purchase->cstatus = MposPurchaseH::STATUS_RECEIVED;
            $purchase->dreceived = $receivedDate;
            $purchase->cno_reference = $request->input('cno_reference') ?: $stockInNumber;
            $purchase->creceived_by = $receivedBy;
            $purchase->save();

            // 3. Tambah stok sesuai tipe item di mpos_product & catat mutasi stok
            foreach ($purchase->details as $item) {
                if ($item->isIngredient()) {
                    $ingredient = $item->ingredient;
                    if ($ingredient) {
                        if ((int) $ingredient->nid_outlet === (int) $purchase->nid_outlet) {
                            $ingredient->nstock = (float) $ingredient->nstock + (float) $item->nqty;
                            $ingredient->save();
                        } elseif (empty($ingredient->nid_outlet)) {
                            $ingredient->nid_outlet = $purchase->nid_outlet;
                            $ingredient->nstock = (float) $ingredient->nstock + (float) $item->nqty;
                            $ingredient->save();
                        } else {
                            $outletIngredient = MposIngredients::where('nid_outlet', $purchase->nid_outlet)
                                ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $ingredient->cname))])
                                ->first();

                            if ($outletIngredient) {
                                $outletIngredient->nstock = (float) $outletIngredient->nstock + (float) $item->nqty;
                                $outletIngredient->save();
                                $item->nid_ingredient = $outletIngredient->nid;
                                $item->save();
                            } else {
                                $newIngPayload = [
                                    'cname' => $ingredient->cname,
                                    'nid_outlet' => $purchase->nid_outlet,
                                    'csatuan' => $ingredient->csatuan ?: 'PCS',
                                    'nstock' => (float) $item->nqty,
                                ];
                                if (MposIngredients::hasPriceColumn()) {
                                    $newIngPayload['nprice'] = (float) ($ingredient->nprice ?: $item->ncost ?: 0);
                                }
                                $newIngredient = MposIngredients::create($newIngPayload);
                                $item->nid_ingredient = $newIngredient->nid;
                                $item->save();
                            }
                        }
                    }
                } else {
                    $product = $item->product;
                    if ($product) {
                        if ((int) $product->nid_outlet === (int) $purchase->nid_outlet) {
                            $product->nqty = (float) $product->nqty + (float) $item->nqty;
                            $product->save();
                        } else {
                            // Cari apakah produk dengan nama yang sama sudah ada di outlet tujuan
                            $outletProduct = MposProduct::where('nid_outlet', $purchase->nid_outlet)
                                ->where('cname', $product->cname)
                                ->first();

                            if ($outletProduct) {
                                $outletProduct->nqty = (float) $outletProduct->nqty + (float) $item->nqty;
                                $outletProduct->save();
                                $item->nid_product = $outletProduct->nid;
                                $item->save();
                            } else {
                                // Duplikasi produk ke outlet tujuan dengan kuantitas barang masuk
                                $newProduct = $product->replicate();
                                $newProduct->nid_outlet = $purchase->nid_outlet;
                                $newProduct->nqty = (float) $item->nqty;
                                $newProduct->save();
                                $item->nid_product = $newProduct->nid;
                                $item->save();
                            }
                        }
                    }
                }

                MposStockMutation::create([
                    'cno_ref' => $stockInNumber,
                    'dmutation' => $receivedDate,
                    'ctype' => MposStockMutation::TYPE_IN,
                    'nid_outlet' => $purchase->nid_outlet,
                    'nid_user' => Auth::id() ?? 1,
                    'citem_type' => MposStockMutation::normalizeItemType($item->citem_type),
                    'nid_product' => $item->nid_product,
                    'nid_ingredient' => $item->nid_ingredient,
                    'nqty' => (float) $item->nqty,
                    'ncost' => (float) $item->ncost,
                    'nsubtotal' => (float) $item->nsubtotal,
                    'creason' => 'Pembelian',
                    'cstatus' => MposStockMutation::STATUS_POSTED,
                    'cnote' => 'PO: ' . $purchase->cno_purchase,
                    'dcreated' => Carbon::now(),
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Konfirmasi berhasil! Barang telah diterima dan stok Produk / Bahan Baku telah otomatis bertambah.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses penerimaan barang: ' . $e->getMessage());
        }
    }

    public function cancel(Request $request, string $id)
    {
        $purchase = MposPurchaseH::findOrFail($id);

        if ($purchase->cstatus !== MposPurchaseH::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Pesanan yang sudah diproses atau dibatalkan tidak dapat diubah.');
        }

        $purchase->cstatus = MposPurchaseH::STATUS_CANCELLED;
        $purchase->save();

        return redirect()->back()->with('success', "Pesanan {$purchase->cno_purchase} berhasil dibatalkan.");
    }

    public function updatePayment(Request $request, string $id)
    {
        $purchase = MposPurchaseH::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'cpayment_status' => 'required|in:UNPAID,PAID',
            'dpayment' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $purchase->cpayment_status = $request->input('cpayment_status');
        $purchase->dpayment = $request->input('cpayment_status') === 'PAID'
            ? ($request->input('dpayment') ? Carbon::parse($request->input('dpayment')) : now())
            : null;
        $purchase->save();

        return redirect()->back()->with('success', 'Status pembayaran berhasil diperbarui.');
    }

    public function print(string $id)
    {
        $purchase = MposPurchaseH::with([
            'supplier',
            'outlet',
            'user',
            'details.product',
            'details.ingredient',
        ])->findOrFail($id);

        return view('purchases.print', compact('purchase'));
    }
}
