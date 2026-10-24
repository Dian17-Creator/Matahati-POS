<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\MposOutlet;
use App\Models\MposProduct;
use App\Models\MposPurchaseD;
use App\Models\MposPurchaseH;
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
        $products = $productsQuery->orderBy('cname')->get(['nid', 'cname', 'nid_outlet', 'nprice', 'nqty']);

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
            'products'
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
            'items.*.nid_product' => 'nullable|exists:mpos_product,nid',
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
                    $productId = $itemData['nid_product'] ?? null;
                    $qty = (int) ($itemData['nqty'] ?? 0);
                    $cost = (float) ($itemData['ncost'] ?? 0);

                    if (!empty($productId) && $qty > 0) {
                        $subtotal = $qty * $cost;
                        MposPurchaseD::create([
                            'nid_purchase' => $purchase->nid,
                            'nid_product' => $productId,
                            'nqty' => $qty,
                            'ncost' => $cost,
                            'nsubtotal' => $subtotal,
                        ]);
                        $totalAmount += $subtotal;
                        $itemsCount++;
                    }
                }
            }

            $purchase->ntotal_amount = $totalAmount;
            $purchase->save();

            DB::commit();

            $msg = $itemsCount > 0 
                ? "Pesanan Pembelian {$purchase->cno_purchase} berhasil dibuat dengan {$itemsCount} item produk."
                : "Pesanan Pembelian {$purchase->cno_purchase} berhasil dibuat.";

            return redirect()->route('purchases.show', $purchase->nid)->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal membuat pesanan pembelian: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(string $id)
    {
        $purchase = MposPurchaseH::with([
            'supplier',
            'outlet',
            'user',
            'details.product.category',
        ])->findOrFail($id);

        // Ambil produk pada outlet yang sama untuk autocomplete / select
        $products = MposProduct::where('nid_outlet', $purchase->nid_outlet)
            ->where('cstatus', 'ACTIVE')
            ->orderBy('cname')
            ->get();

        if ($products->isEmpty()) {
            $products = MposProduct::where('cstatus', 'ACTIVE')
                ->orderBy('cname')
                ->get()
                ->unique('cname');
        }

        return view('purchases.show', compact('purchase', 'products'));
    }

    public function addItem(Request $request, string $id)
    {
        $purchase = MposPurchaseH::findOrFail($id);

        if ($purchase->cstatus !== MposPurchaseH::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Item tidak dapat ditambah karena pesanan sudah diproses atau dibatalkan.');
        }

        $validator = Validator::make($request->all(), [
            'nid_product' => 'required|exists:mpos_product,nid',
            'nqty' => 'required|integer|min:1',
            'ncost' => 'required|numeric|min:0',
        ], [
            'nid_product.required' => 'Pilih produk yang akan ditambahkan.',
            'nqty.min' => 'Jumlah kuantitas minimal 1.',
            'ncost.min' => 'Harga beli tidak boleh negatif.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error_modal', 'add_product');
        }

        $qty = (int) $request->input('nqty');
        $cost = (float) $request->input('ncost');
        $subtotal = $qty * $cost;

        // Cek jika produk sudah ada di PO ini, update atau tambahkan baru
        $existingItem = MposPurchaseD::where('nid_purchase', $purchase->nid)
            ->where('nid_product', $request->input('nid_product'))
            ->first();

        if ($existingItem) {
            $existingItem->nqty += $qty;
            $existingItem->ncost = $cost;
            $existingItem->nsubtotal = $existingItem->nqty * $cost;
            $existingItem->save();
        } else {
            MposPurchaseD::create([
                'nid_purchase' => $purchase->nid,
                'nid_product' => $request->input('nid_product'),
                'nqty' => $qty,
                'ncost' => $cost,
                'nsubtotal' => $subtotal,
            ]);
        }

        // Recalculate total amount
        $purchase->ntotal_amount = (float) MposPurchaseD::where('nid_purchase', $purchase->nid)->sum('nsubtotal');
        $purchase->save();

        return redirect()->back()->with('success', 'Produk berhasil ditambahkan ke pesanan.');
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

        return redirect()->back()->with('success', 'Produk berhasil dihapus dari pesanan.');
    }

    /**
     * Konfirmasi Barang Diterima (Goods Receipt):
     * Otomatis menambah stok produk (mpos_product.nqty)
     */
    public function receive(Request $request, string $id)
    {
        $purchase = MposPurchaseH::with('details.product')->findOrFail($id);

        if ($purchase->cstatus !== MposPurchaseH::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Status pesanan saat ini tidak dapat dikonfirmasi diterima.');
        }

        if ($purchase->details->isEmpty()) {
            return redirect()->back()->with('error', 'Pesanan belum memiliki item produk. Tambahkan produk terlebih dahulu.');
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
            // 1. Update status PO menjadi RECEIVED
            $purchase->cstatus = MposPurchaseH::STATUS_RECEIVED;
            $purchase->dreceived = Carbon::parse($request->input('dreceived'));
            $purchase->cno_reference = $request->input('cno_reference');
            $purchase->creceived_by = $request->input('creceived_by') ?: (Auth::user()?->cname ?: 'Admin');
            $purchase->save();

            // 2. Tambah stok untuk setiap produk di PO
            foreach ($purchase->details as $item) {
                $product = $item->product;
                if ($product) {
                    if ((int) $product->nid_outlet === (int) $purchase->nid_outlet) {
                        $product->nqty = (int) $product->nqty + (int) $item->nqty;
                        $product->save();
                    } else {
                        // Cari apakah produk dengan nama yang sama sudah ada di outlet tujuan
                        $outletProduct = MposProduct::where('nid_outlet', $purchase->nid_outlet)
                            ->where('cname', $product->cname)
                            ->first();

                        if ($outletProduct) {
                            $outletProduct->nqty = (int) $outletProduct->nqty + (int) $item->nqty;
                            $outletProduct->save();
                            $item->nid_product = $outletProduct->nid;
                            $item->save();
                        } else {
                            // Duplikasi produk ke outlet tujuan dengan kuantitas barang masuk
                            $newProduct = $product->replicate();
                            $newProduct->nid_outlet = $purchase->nid_outlet;
                            $newProduct->nqty = (int) $item->nqty;
                            $newProduct->save();
                            $item->nid_product = $newProduct->nid;
                            $item->save();
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Konfirmasi berhasil! Barang telah diterima dan stok produk telah otomatis bertambah.');
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
        ])->findOrFail($id);

        return view('purchases.print', compact('purchase'));
    }
}
