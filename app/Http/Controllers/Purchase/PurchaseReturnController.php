<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\MposIngredients;
use App\Models\MposProduct;
use App\Models\MposPurchaseD;
use App\Models\MposPurchaseH;
use App\Models\MposStockMutation;
use App\Traits\ResolvesOutletScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PurchaseReturnController extends Controller
{
    use ResolvesOutletScope;

    /**
     * Ambil daftar PO berstatus RECEIVED yang bisa diretur, difilter berdasarkan supplier & scope outlet.
     */
    public function getPurchasesBySupplier(Request $request): JsonResponse
    {
        $supplierId = $request->input('supplier_id');
        $allowedOutletIds = $this->getUserAllowedOutletIds();

        $query = MposPurchaseH::onlyPurchases()
            ->where('cstatus', MposPurchaseH::STATUS_RECEIVED)
            ->with(['supplier', 'outlet'])
            ->withSum('details as total_purchased_qty', 'nqty');

        if (!$this->isSuperAdminUser()) {
            $query->whereIn('nid_outlet', $allowedOutletIds);
        }

        if (!empty($supplierId)) {
            $query->where('nid_supplier', (int) $supplierId);
        }

        $purchases = $query->orderBy('dpurchase', 'desc')
            ->orderBy('nid', 'desc')
            ->limit(200)
            ->get();

        $purchaseIds = $purchases->pluck('nid')->all();
        $returnedQtyByPo = [];
        if (!empty($purchaseIds)) {
            $returnedQtyByPo = MposPurchaseD::query()
                ->join('mpos_purchase_h as ret_h', 'mpos_purchase_d.nid_purchase', '=', 'ret_h.nid')
                ->where('ret_h.cdoc_type', MposPurchaseH::DOC_TYPE_RETURN)
                ->whereIn('ret_h.nid_purchase_ref', $purchaseIds)
                ->where('ret_h.cstatus', '!=', MposPurchaseH::STATUS_CANCELLED)
                ->groupBy('ret_h.nid_purchase_ref')
                ->select('ret_h.nid_purchase_ref', DB::raw('SUM(mpos_purchase_d.nqty) as total_returned'))
                ->pluck('total_returned', 'nid_purchase_ref')
                ->toArray();
        }

        $results = $purchases->map(function (MposPurchaseH $po) use ($returnedQtyByPo) {
            $totalPurchased = (int) ($po->total_purchased_qty ?? 0);
            $totalReturned = (int) ($returnedQtyByPo[$po->nid] ?? 0);
            $remainingQty = max(0, $totalPurchased - $totalReturned);

            return [
                'nid' => $po->nid,
                'cno_purchase' => $po->cno_purchase,
                'dpurchase_formatted' => $po->dpurchase ? Carbon::parse($po->dpurchase)->format('d M Y') : '-',
                'nid_supplier' => $po->nid_supplier,
                'supplier_name' => $po->supplier?->cname ?? '-',
                'nid_outlet' => $po->nid_outlet,
                'outlet_name' => $po->outlet?->cname ?? '-',
                'ntotal_amount' => (float) $po->ntotal_amount,
                'total_purchased_qty' => $totalPurchased,
                'total_returned_qty' => $totalReturned,
                'remaining_qty' => $remainingQty,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'purchases' => $results,
        ]);
    }

    /**
     * Ambil daftar item dari nomor PO terpilih beserta stok outlet saat ini dan sisa qty yang bisa diretur.
     */
    public function getPurchaseItems(Request $request, string $purchaseId): JsonResponse
    {
        $purchase = MposPurchaseH::onlyPurchases()
            ->where('cstatus', MposPurchaseH::STATUS_RECEIVED)
            ->with(['supplier', 'outlet', 'details.product.category', 'details.ingredient'])
            ->findOrFail($purchaseId);

        $this->authorizeOutletAccess($purchase);

        $excludeReturnId = $request->filled('exclude_return_id')
            ? (int) $request->input('exclude_return_id')
            : null;

        $items = $this->buildReturnablePoItems($purchase, $excludeReturnId);

        return response()->json([
            'success' => true,
            'purchase' => [
                'nid' => $purchase->nid,
                'cno_purchase' => $purchase->cno_purchase,
                'dpurchase_formatted' => $purchase->dpurchase ? Carbon::parse($purchase->dpurchase)->format('d M Y') : '-',
                'nid_supplier' => $purchase->nid_supplier,
                'supplier_name' => $purchase->supplier?->cname ?? '-',
                'nid_outlet' => $purchase->nid_outlet,
                'outlet_name' => $purchase->outlet?->cname ?? '-',
                'ntotal_amount' => (float) $purchase->ntotal_amount,
            ],
            'items' => array_values($items),
        ]);
    }

    /**
     * Simpan draft Retur Pembelian (PENDING) beserta item retur dari modal Tambah Retur.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'nid_purchase_ref' => 'required|integer|exists:mpos_purchase_h,nid',
            'nid_supplier' => 'nullable|integer|exists:mpos_supplier,nid',
            'dpurchase' => 'required|date',
            'cnote' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.nid_purchase_detail_ref' => 'required|integer',
            'items.*.nqty' => 'required|integer|min:1',
            'items.*.csatuan' => 'nullable|string|max:50',
            'items.*.ncost' => 'nullable|numeric|min:0',
            'items.*.creason' => 'nullable|string|max:150',
            'items.*.cnote' => 'nullable|string',
        ], [
            'nid_purchase_ref.required' => 'Pilih nomor pembelian terlebih dahulu.',
            'dpurchase.required' => 'Tanggal retur wajib diisi.',
            'items.required' => 'Minimal harus ada 1 barang yang diretur.',
            'items.min' => 'Minimal harus ada 1 barang yang diretur.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', $validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $sourcePo = MposPurchaseH::onlyPurchases()
                ->where('cstatus', MposPurchaseH::STATUS_RECEIVED)
                ->with(['details.product', 'details.ingredient'])
                ->findOrFail($request->input('nid_purchase_ref'));

            $this->authorizeOutletAccess($sourcePo);

            $requestedSupplierId = $request->input('nid_supplier');
            if (!empty($requestedSupplierId) && (int) $requestedSupplierId !== (int) $sourcePo->nid_supplier) {
                throw new \RuntimeException('Supplier yang dipilih tidak sesuai dengan Nomor Pembelian asal.');
            }

            $returnableMap = $this->buildReturnablePoItems($sourcePo);

            $normalizedRows = [];
            $qtyPerDetailRef = [];
            $totalAmount = 0.0;

            foreach ($request->input('items', []) as $row) {
                $detailRefId = (int) ($row['nid_purchase_detail_ref'] ?? 0);
                $qty = (int) ($row['nqty'] ?? 0);

                if ($detailRefId <= 0 || $qty <= 0) {
                    continue;
                }

                if (!isset($returnableMap[$detailRefId])) {
                    throw new \RuntimeException('Barang yang dipilih tidak berasal dari Nomor Pembelian tersebut.');
                }

                $qtyPerDetailRef[$detailRefId] = ($qtyPerDetailRef[$detailRefId] ?? 0) + $qty;
                $poItem = $returnableMap[$detailRefId];

                if ($qtyPerDetailRef[$detailRefId] > $poItem['qty_remaining']) {
                    throw new \RuntimeException("QTY retur {$poItem['item_name']} melebihi sisa yang dapat diretur ({$poItem['qty_remaining']} {$poItem['unit']}).");
                }

                $unitCost = isset($row['ncost']) && $row['ncost'] !== '' ? (float) $row['ncost'] : (float) $poItem['ncost'];
                $subtotal = $qty * $unitCost;
                $totalAmount += $subtotal;

                $normalizedRows[] = [
                    'nid_purchase_detail_ref' => $detailRefId,
                    'citem_type' => $poItem['citem_type'],
                    'nid_product' => $poItem['nid_product'],
                    'nid_ingredient' => $poItem['nid_ingredient'],
                    'nqty' => $qty,
                    'csatuan' => !empty($row['csatuan']) ? trim((string) $row['csatuan']) : $poItem['unit'],
                    'ncost' => $unitCost,
                    'nsubtotal' => $subtotal,
                    'creason' => !empty($row['creason']) ? trim((string) $row['creason']) : null,
                    'cnote' => !empty($row['cnote']) ? trim((string) $row['cnote']) : null,
                ];
            }

            if (empty($normalizedRows)) {
                throw new \RuntimeException('Minimal harus ada 1 barang dengan QTY retur lebih dari 0.');
            }

            $dreturn = Carbon::parse($request->input('dpurchase'));
            $returnNumber = MposPurchaseH::generateReturnNumber($dreturn);

            $returnHeader = MposPurchaseH::create([
                'cdoc_type' => MposPurchaseH::DOC_TYPE_RETURN,
                'cno_purchase' => $returnNumber,
                'nid_purchase_ref' => $sourcePo->nid,
                'cno_purchase_ref' => $sourcePo->cno_purchase,
                'dpurchase' => $dreturn,
                'nid_supplier' => $sourcePo->nid_supplier,
                'nid_outlet' => $sourcePo->nid_outlet,
                'nid_user' => Auth::id() ?? 1,
                'cstatus' => MposPurchaseH::STATUS_PENDING,
                'cpayment_status' => MposPurchaseH::PAYMENT_UNPAID,
                'ntotal_amount' => $totalAmount,
                'cnote' => $request->input('cnote'),
                'dcreated' => now(),
            ]);

            foreach ($normalizedRows as $detailPayload) {
                $detailPayload['nid_purchase'] = $returnHeader->nid;
                MposPurchaseD::create($detailPayload);
            }

            DB::commit();

            return redirect()->route('purchases.returns.show', $returnHeader->nid)
                ->with('success', "Draft Retur {$returnNumber} berhasil dibuat. Periksa rincian lalu klik Post Sekarang untuk memproses Stok Keluar.");
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan retur pembelian: ' . $e->getMessage());
        }
    }

    /**
     * Halaman Rincian Retur Pembelian.
     */
    public function show(string $id): View
    {
        $return = MposPurchaseH::onlyReturns()->with([
            'supplier',
            'outlet',
            'user',
            'purchaseRef.details.product.category',
            'purchaseRef.details.ingredient',
            'details.product.category',
            'details.ingredient',
            'details.purchaseDetailRef',
        ])->findOrFail($id);

        $this->authorizeOutletAccess($return);

        $availablePoItems = [];
        if ($return->purchaseRef) {
            $availablePoItems = array_values($this->buildReturnablePoItems($return->purchaseRef, (int) $return->nid));
        }

        return view('purchases.return_show', compact('return', 'availablePoItems'));
    }

    /**
     * Update catatan dokumen retur selama masih berstatus PENDING.
     */
    public function updateNote(Request $request, string $id): RedirectResponse
    {
        $return = $this->findPendingReturn($id);

        $validated = $request->validate([
            'cnote' => 'nullable|string',
        ]);

        $return->cnote = $validated['cnote'] ?? null;
        $return->save();

        return redirect()->back()->with('success', 'Catatan retur berhasil diperbarui.');
    }

    /**
     * Tambah / perbarui item retur pada dokumen yang masih PENDING.
     */
    public function storeItem(Request $request, string $id): RedirectResponse
    {
        $return = $this->findPendingReturn($id);

        $validator = Validator::make($request->all(), [
            'nid_purchase_detail_ref' => 'required|integer',
            'nqty' => 'required|integer|min:1',
            'csatuan' => 'nullable|string|max:50',
            'ncost' => 'nullable|numeric|min:0',
            'creason' => 'nullable|string|max:150',
            'cnote' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->with('error', $validator->errors()->first());
        }

        $sourcePo = $return->purchaseRef()->with(['details.product', 'details.ingredient'])->first();
        if (!$sourcePo) {
            return redirect()->back()->with('error', 'Nomor pembelian referensi tidak ditemukan.');
        }

        $detailRefId = (int) $request->input('nid_purchase_detail_ref');
        $qty = (int) $request->input('nqty');

        $returnableMap = $this->buildReturnablePoItems($sourcePo, (int) $return->nid);
        if (!isset($returnableMap[$detailRefId])) {
            return redirect()->back()->with('error', 'Barang tersebut tidak ada pada PO referensi.');
        }

        $poItem = $returnableMap[$detailRefId];
        $existingItem = MposPurchaseD::where('nid_purchase', $return->nid)
            ->where('nid_purchase_detail_ref', $detailRefId)
            ->first();

        $totalQty = $qty + ($existingItem ? (int) $existingItem->nqty : 0);
        if ($totalQty > $poItem['qty_remaining']) {
            return redirect()->back()->with('error', "Total QTY retur melebihi sisa qty yang bisa diretur ({$poItem['qty_remaining']} {$poItem['unit']}).");
        }

        $unitCost = $request->filled('ncost') ? (float) $request->input('ncost') : (float) $poItem['ncost'];
        $satuan = $request->filled('csatuan') ? trim((string) $request->input('csatuan')) : $poItem['unit'];

        if ($existingItem) {
            $existingItem->nqty = $totalQty;
            $existingItem->csatuan = $satuan;
            $existingItem->ncost = $unitCost;
            $existingItem->nsubtotal = $totalQty * $unitCost;
            if ($request->filled('creason')) {
                $existingItem->creason = trim((string) $request->input('creason'));
            }
            if ($request->filled('cnote')) {
                $existingItem->cnote = trim((string) $request->input('cnote'));
            }
            $existingItem->save();
        } else {
            MposPurchaseD::create([
                'nid_purchase' => $return->nid,
                'nid_purchase_detail_ref' => $detailRefId,
                'citem_type' => $poItem['citem_type'],
                'nid_product' => $poItem['nid_product'],
                'nid_ingredient' => $poItem['nid_ingredient'],
                'nqty' => $qty,
                'csatuan' => $satuan,
                'ncost' => $unitCost,
                'nsubtotal' => $qty * $unitCost,
                'creason' => $request->filled('creason') ? trim((string) $request->input('creason')) : null,
                'cnote' => $request->filled('cnote') ? trim((string) $request->input('cnote')) : null,
            ]);
        }

        $this->recalculateReturnTotal($return);

        return redirect()->back()->with('success', 'Item retur berhasil ditambahkan.');
    }

    /**
     * Perbarui qty / satuan / alasan item retur selama dokumen masih PENDING.
     */
    public function updateItem(Request $request, string $id, string $detailId): RedirectResponse
    {
        $return = $this->findPendingReturn($id);

        $item = MposPurchaseD::where('nid_purchase', $return->nid)
            ->where('nid', $detailId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'nqty' => 'required|integer|min:1',
            'csatuan' => 'nullable|string|max:50',
            'ncost' => 'nullable|numeric|min:0',
            'creason' => 'nullable|string|max:150',
            'cnote' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->with('error', $validator->errors()->first());
        }

        $newQty = (int) $request->input('nqty');
        $sourcePo = $return->purchaseRef()->with(['details.product', 'details.ingredient'])->first();

        if ($sourcePo && !empty($item->nid_purchase_detail_ref)) {
            $returnableMap = $this->buildReturnablePoItems($sourcePo, (int) $return->nid);
            $poItem = $returnableMap[(int) $item->nid_purchase_detail_ref] ?? null;
            if ($poItem && $newQty > $poItem['qty_remaining']) {
                return redirect()->back()->with('error', "QTY retur {$poItem['item_name']} melebihi sisa retur PO ({$poItem['qty_remaining']} {$poItem['unit']}).");
            }
        }

        $unitCost = $request->filled('ncost') ? (float) $request->input('ncost') : (float) $item->ncost;
        $item->nqty = $newQty;
        $item->csatuan = $request->filled('csatuan') ? trim((string) $request->input('csatuan')) : $item->csatuan;
        $item->ncost = $unitCost;
        $item->nsubtotal = $newQty * $unitCost;
        $item->creason = $request->has('creason') ? (trim((string) $request->input('creason')) ?: null) : $item->creason;
        $item->cnote = $request->has('cnote') ? (trim((string) $request->input('cnote')) ?: null) : $item->cnote;
        $item->save();

        $this->recalculateReturnTotal($return);

        return redirect()->back()->with('success', 'Item retur berhasil diperbarui.');
    }

    /**
     * Hapus item dari dokumen retur yang masih PENDING.
     */
    public function destroyItem(string $id, string $detailId): RedirectResponse
    {
        $return = $this->findPendingReturn($id);

        $item = MposPurchaseD::where('nid_purchase', $return->nid)
            ->where('nid', $detailId)
            ->firstOrFail();

        $item->delete();
        $this->recalculateReturnTotal($return);

        return redirect()->back()->with('success', 'Item retur berhasil dihapus.');
    }

    /**
     * Post Sekarang: finalisasi retur dan otomatis catat Stok Keluar (MposStockMutation TYPE_OUT)
     * serta mengurangi stok fisik produk / bahan baku di outlet.
     */
    public function post(Request $request, string $id): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $return = MposPurchaseH::onlyReturns()
                ->with(['details.product', 'details.ingredient', 'purchaseRef.details.product', 'purchaseRef.details.ingredient'])
                ->lockForUpdate()
                ->findOrFail($id);

            $this->authorizeOutletAccess($return);

            if ($return->cstatus !== MposPurchaseH::STATUS_PENDING) {
                throw new \RuntimeException('Dokumen retur sudah diposting atau dibatalkan.');
            }

            if ($return->details->isEmpty()) {
                throw new \RuntimeException('Tambahkan minimal 1 item retur sebelum memposting.');
            }

            $returnableMap = [];
            if ($return->purchaseRef) {
                $returnableMap = $this->buildReturnablePoItems($return->purchaseRef, (int) $return->nid);
            }

            $mutationDate = $return->dpurchase ? Carbon::parse($return->dpurchase) : Carbon::now();
            $stockOutNumber = MposStockMutation::generateNumber(MposStockMutation::TYPE_OUT, $mutationDate);
            $postedBy = Auth::user()?->email ?: (Auth::user()?->cname ?: 'Admin');

            foreach ($return->details as $item) {
                $qty = (float) $item->nqty;
                if ($qty <= 0) {
                    continue;
                }

                if (!empty($item->nid_purchase_detail_ref) && isset($returnableMap[(int) $item->nid_purchase_detail_ref])) {
                    $maxAllowed = $returnableMap[(int) $item->nid_purchase_detail_ref]['qty_remaining'];
                    if ($qty > $maxAllowed) {
                        $itemName = $returnableMap[(int) $item->nid_purchase_detail_ref]['item_name'];
                        throw new \RuntimeException("QTY retur {$itemName} melebihi sisa retur PO ({$maxAllowed}).");
                    }
                }

                if ($item->isIngredient() && $item->nid_ingredient) {
                    $ingredient = MposIngredients::lockForUpdate()->find($item->nid_ingredient);
                    if (!$ingredient) {
                        throw new \RuntimeException('Data bahan baku pada item retur tidak ditemukan.');
                    }

                    $currentStock = (float) ($ingredient->nstock ?? 0);
                    if ($currentStock < $qty) {
                        throw new \RuntimeException("Stok {$ingredient->cname} tidak mencukupi (tersedia: {$currentStock}, retur: {$qty}).");
                    }

                    $ingredient->nstock = $currentStock - $qty;
                    $ingredient->save();
                } elseif ($item->nid_product) {
                    $product = MposProduct::lockForUpdate()->find($item->nid_product);
                    if (!$product) {
                        throw new \RuntimeException('Data produk pada item retur tidak ditemukan.');
                    }

                    $currentStock = (float) ($product->nqty ?? 0);
                    if ($currentStock < $qty) {
                        throw new \RuntimeException("Stok {$product->cname} tidak mencukupi (tersedia: {$currentStock}, retur: {$qty}).");
                    }

                    $product->nqty = $currentStock - $qty;
                    $product->save();
                }

                MposStockMutation::create([
                    'cno_ref' => $stockOutNumber,
                    'dmutation' => $mutationDate,
                    'ctype' => MposStockMutation::TYPE_OUT,
                    'nid_outlet' => $return->nid_outlet,
                    'nid_user' => Auth::id() ?? 1,
                    'citem_type' => MposStockMutation::normalizeItemType($item->citem_type),
                    'nid_product' => $item->nid_product,
                    'nid_ingredient' => $item->nid_ingredient,
                    'nqty' => $qty,
                    'ncost' => (float) $item->ncost,
                    'nsubtotal' => (float) $item->nsubtotal,
                    'creason' => $item->creason ?: 'Retur Pembelian',
                    'cstatus' => MposStockMutation::STATUS_POSTED,
                    'cnote' => 'Retur: ' . $return->cno_purchase . ($return->cno_purchase_ref ? ' (PO: ' . $return->cno_purchase_ref . ')' : ''),
                    'dcreated' => Carbon::now(),
                    'dupdated' => Carbon::now(),
                ]);
            }

            $return->cstatus = MposPurchaseH::STATUS_POSTED;
            $return->cno_reference = $stockOutNumber;
            $return->dreceived = Carbon::now();
            $return->creceived_by = $postedBy;
            $return->save();

            DB::commit();

            return redirect()->back()->with('success', "Retur {$return->cno_purchase} berhasil diposting! Stok otomatis berkurang dan tercatat sebagai Stok Keluar ({$stockOutNumber}).");
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal memposting retur: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan draft retur (PENDING -> CANCELLED).
     */
    public function cancel(string $id): RedirectResponse
    {
        $return = $this->findPendingReturn($id);
        $return->cstatus = MposPurchaseH::STATUS_CANCELLED;
        $return->save();

        return redirect()->back()->with('success', "Retur {$return->cno_purchase} berhasil dibatalkan.");
    }

    /**
     * Hapus permanen draft retur yang masih PENDING.
     */
    public function destroy(string $id): RedirectResponse
    {
        $return = $this->findPendingReturn($id);
        $returnNo = $return->cno_purchase;

        DB::transaction(function () use ($return) {
            MposPurchaseD::where('nid_purchase', $return->nid)->delete();
            $return->delete();
        });

        return redirect()->route('purchases.index', ['tab' => 'return'])
            ->with('success', "Draft Retur {$returnNo} berhasil dihapus.");
    }

    /**
     * Update status Pembayaran / Pengembalian dana dari supplier.
     */
    public function updatePayment(Request $request, string $id): RedirectResponse
    {
        $return = MposPurchaseH::onlyReturns()->findOrFail($id);
        $this->authorizeOutletAccess($return);

        if ($return->cstatus === MposPurchaseH::STATUS_CANCELLED) {
            return redirect()->back()->with('error', 'Dokumen retur yang dibatalkan tidak dapat diubah status pembayarannya.');
        }

        $validator = Validator::make($request->all(), [
            'cpayment_status' => 'required|in:UNPAID,PAID',
            'dpayment' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $return->cpayment_status = $request->input('cpayment_status');
        $return->dpayment = $request->input('cpayment_status') === MposPurchaseH::PAYMENT_PAID
            ? ($request->input('dpayment') ? Carbon::parse($request->input('dpayment')) : now())
            : null;
        $return->save();

        return redirect()->back()->with('success', 'Status pembayaran / pengembalian berhasil diperbarui.');
    }

    /**
     * Cetak bukti Retur Pembelian.
     */
    public function print(string $id): View
    {
        $return = MposPurchaseH::onlyReturns()->with([
            'supplier',
            'outlet',
            'user',
            'purchaseRef',
            'details.product',
            'details.ingredient',
        ])->findOrFail($id);

        $this->authorizeOutletAccess($return);

        return view('purchases.return_print', compact('return'));
    }

    protected function findPendingReturn(string $id): MposPurchaseH
    {
        $return = MposPurchaseH::onlyReturns()->findOrFail($id);
        $this->authorizeOutletAccess($return);

        if ($return->cstatus !== MposPurchaseH::STATUS_PENDING) {
            abort(403, 'Dokumen retur yang sudah diposting atau dibatalkan tidak dapat diubah.');
        }

        return $return;
    }

    protected function authorizeOutletAccess(MposPurchaseH $purchase): void
    {
        if ($this->isSuperAdminUser()) {
            return;
        }

        $allowedOutletIds = $this->getUserAllowedOutletIds();
        if (!in_array((int) $purchase->nid_outlet, $allowedOutletIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke outlet dokumen ini.');
        }
    }

    /**
     * Susun daftar item PO beserta stok outlet terkini & sisa kuota yang masih dapat diretur.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildReturnablePoItems(MposPurchaseH $sourcePo, ?int $excludeReturnId = null): array
    {
        $returnedQuery = MposPurchaseD::query()
            ->join('mpos_purchase_h as ret_h', 'mpos_purchase_d.nid_purchase', '=', 'ret_h.nid')
            ->where('ret_h.cdoc_type', MposPurchaseH::DOC_TYPE_RETURN)
            ->where('ret_h.nid_purchase_ref', $sourcePo->nid)
            ->where('ret_h.cstatus', '!=', MposPurchaseH::STATUS_CANCELLED)
            ->whereNotNull('mpos_purchase_d.nid_purchase_detail_ref');

        if ($excludeReturnId !== null) {
            $returnedQuery->where('ret_h.nid', '!=', $excludeReturnId);
        }

        $returnedQtyMap = $returnedQuery
            ->groupBy('mpos_purchase_d.nid_purchase_detail_ref')
            ->select('mpos_purchase_d.nid_purchase_detail_ref', DB::raw('SUM(mpos_purchase_d.nqty) as total_returned'))
            ->pluck('total_returned', 'nid_purchase_detail_ref')
            ->toArray();

        $itemsMap = [];
        foreach ($sourcePo->details as $detail) {
            $qtyPurchased = (int) $detail->nqty;
            $qtyReturned = (int) ($returnedQtyMap[$detail->nid] ?? 0);
            $qtyRemaining = max(0, $qtyPurchased - $qtyReturned);

            $isIng = $detail->isIngredient();
            $itemName = $isIng
                ? ($detail->ingredient?->cname ?? 'Bahan Baku')
                : ($detail->product?->cname ?? 'Produk');

            $currentStock = $isIng
                ? (float) ($detail->ingredient?->nstock ?? 0)
                : (float) ($detail->product?->nqty ?? 0);

            $itemsMap[(int) $detail->nid] = [
                'nid_purchase_detail_ref' => (int) $detail->nid,
                'citem_type' => $detail->getItemTypeNormalized(),
                'item_type_label' => $detail->getItemTypeLabel(),
                'nid_product' => $detail->nid_product ? (int) $detail->nid_product : null,
                'nid_ingredient' => $detail->nid_ingredient ? (int) $detail->nid_ingredient : null,
                'item_name' => $itemName,
                'unit' => $detail->getItemUnit(),
                'current_stock' => $currentStock,
                'qty_purchased' => $qtyPurchased,
                'qty_returned' => $qtyReturned,
                'qty_remaining' => $qtyRemaining,
                'ncost' => (float) $detail->ncost,
            ];
        }

        return $itemsMap;
    }

    protected function recalculateReturnTotal(MposPurchaseH $return): void
    {
        $return->ntotal_amount = (float) MposPurchaseD::where('nid_purchase', $return->nid)->sum('nsubtotal');
        $return->save();
    }
}
