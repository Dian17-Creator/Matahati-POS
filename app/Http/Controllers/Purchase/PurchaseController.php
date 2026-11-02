<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\MposIngredients;
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
            'items.*.citem_type' => 'nullable|string|in:PRODUCT,INGREDIENT',
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
                    $rawType = strtoupper(trim((string) ($itemData['citem_type'] ?? MposPurchaseD::TYPE_PRODUCT)));
                    $itemType = $rawType === MposPurchaseD::TYPE_INGREDIENT
                        ? MposPurchaseD::TYPE_INGREDIENT
                        : MposPurchaseD::TYPE_PRODUCT;

                    $selectedId = $itemData['item_id']
                        ?? ($itemType === MposPurchaseD::TYPE_INGREDIENT
                            ? ($itemData['nid_ingredient'] ?? null)
                            : ($itemData['nid_product'] ?? null));

                    $qty = (int) ($itemData['nqty'] ?? 0);
                    $cost = (float) ($itemData['ncost'] ?? 0);

                    if (!empty($selectedId) && $qty > 0) {
                        $subtotal = $qty * $cost;

                        if ($itemType === MposPurchaseD::TYPE_INGREDIENT) {
                            if (!MposIngredients::where('nid', $selectedId)->exists()) {
                                DB::rollBack();
                                return redirect()->back()
                                    ->with('error', 'Bahan baku yang dipilih tidak ditemukan.')
                                    ->withInput()
                                    ->with('error_modal', 'create_po');
                            }

                            MposPurchaseD::create([
                                'nid_purchase' => $purchase->nid,
                                'citem_type' => MposPurchaseD::TYPE_INGREDIENT,
                                'nid_product' => null,
                                'nid_ingredient' => (int) $selectedId,
                                'nqty' => $qty,
                                'ncost' => $cost,
                                'nsubtotal' => $subtotal,
                            ]);
                        } else {
                            if (!MposProduct::where('nid', $selectedId)->exists()) {
                                DB::rollBack();
                                return redirect()->back()
                                    ->with('error', 'Produk yang dipilih tidak ditemukan.')
                                    ->withInput()
                                    ->with('error_modal', 'create_po');
                            }

                            MposPurchaseD::create([
                                'nid_purchase' => $purchase->nid,
                                'citem_type' => MposPurchaseD::TYPE_PRODUCT,
                                'nid_product' => (int) $selectedId,
                                'nid_ingredient' => null,
                                'nqty' => $qty,
                                'ncost' => $cost,
                                'nsubtotal' => $subtotal,
                            ]);
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
        $products = MposProduct::where('cstatus', 'ACTIVE')
            ->orderBy('cname')
            ->get()
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

        $itemType = strtoupper(trim((string) $request->input('citem_type', MposPurchaseD::TYPE_PRODUCT))) === MposPurchaseD::TYPE_INGREDIENT
            ? MposPurchaseD::TYPE_INGREDIENT
            : MposPurchaseD::TYPE_PRODUCT;

        $rules = [
            'citem_type' => 'nullable|in:PRODUCT,INGREDIENT',
            'nqty' => 'required|integer|min:1',
            'ncost' => 'required|numeric|min:0',
        ];
        $messages = [
            'nqty.min' => 'Jumlah kuantitas minimal 1.',
            'ncost.min' => 'Harga beli tidak boleh negatif.',
        ];

        if ($itemType === MposPurchaseD::TYPE_INGREDIENT) {
            $rules['nid_ingredient'] = 'required|exists:mpos_ingredients,nid';
            $messages['nid_ingredient.required'] = 'Pilih bahan baku yang akan ditambahkan.';
            $messages['nid_ingredient.exists'] = 'Bahan baku tidak ditemukan.';
        } else {
            $rules['nid_product'] = 'required|exists:mpos_product,nid';
            $messages['nid_product.required'] = 'Pilih produk yang akan ditambahkan.';
            $messages['nid_product.exists'] = 'Produk tidak ditemukan.';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error_modal', 'add_product');
        }

        $qty = (int) $request->input('nqty');
        $cost = (float) $request->input('ncost');
        $subtotal = $qty * $cost;

        if ($itemType === MposPurchaseD::TYPE_INGREDIENT) {
            $ingredientId = (int) $request->input('nid_ingredient');
            $existingItem = MposPurchaseD::where('nid_purchase', $purchase->nid)
                ->where('citem_type', MposPurchaseD::TYPE_INGREDIENT)
                ->where('nid_ingredient', $ingredientId)
                ->first();

            if ($existingItem) {
                $existingItem->nqty += $qty;
                $existingItem->ncost = $cost;
                $existingItem->nsubtotal = $existingItem->nqty * $cost;
                $existingItem->save();
            } else {
                MposPurchaseD::create([
                    'nid_purchase' => $purchase->nid,
                    'citem_type' => MposPurchaseD::TYPE_INGREDIENT,
                    'nid_product' => null,
                    'nid_ingredient' => $ingredientId,
                    'nqty' => $qty,
                    'ncost' => $cost,
                    'nsubtotal' => $subtotal,
                ]);
            }
            $labelMsg = 'Bahan baku berhasil ditambahkan ke pesanan.';
        } else {
            $productId = (int) $request->input('nid_product');
            $existingItem = MposPurchaseD::where('nid_purchase', $purchase->nid)
                ->where(function ($q) {
                    $q->where('citem_type', MposPurchaseD::TYPE_PRODUCT)
                      ->orWhereNull('citem_type');
                })
                ->where('nid_product', $productId)
                ->first();

            if ($existingItem) {
                $existingItem->nqty += $qty;
                $existingItem->ncost = $cost;
                $existingItem->nsubtotal = $existingItem->nqty * $cost;
                $existingItem->save();
            } else {
                MposPurchaseD::create([
                    'nid_purchase' => $purchase->nid,
                    'citem_type' => MposPurchaseD::TYPE_PRODUCT,
                    'nid_product' => $productId,
                    'nid_ingredient' => null,
                    'nqty' => $qty,
                    'ncost' => $cost,
                    'nsubtotal' => $subtotal,
                ]);
            }
            $labelMsg = 'Produk berhasil ditambahkan ke pesanan.';
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
            // 1. Update status PO menjadi RECEIVED
            $purchase->cstatus = MposPurchaseH::STATUS_RECEIVED;
            $purchase->dreceived = Carbon::parse($request->input('dreceived'));
            $purchase->cno_reference = $request->input('cno_reference');
            $purchase->creceived_by = $request->input('creceived_by') ?: (Auth::user()?->cname ?: 'Admin');
            $purchase->save();

            // 2. Tambah stok sesuai tipe item (Produk -> mpos_product, Bahan Baku -> mpos_ingredients)
            foreach ($purchase->details as $item) {
                if ($item->isIngredient()) {
                    $ingredient = $item->ingredient;
                    if ($ingredient) {
                        if ((int) $ingredient->nid_outlet === (int) $purchase->nid_outlet) {
                            $ingredient->nstock = (float) $ingredient->nstock + (int) $item->nqty;
                            $ingredient->save();
                        } elseif (empty($ingredient->nid_outlet)) {
                            $ingredient->nid_outlet = $purchase->nid_outlet;
                            $ingredient->nstock = (float) $ingredient->nstock + (int) $item->nqty;
                            $ingredient->save();
                        } else {
                            $outletIngredient = MposIngredients::where('nid_outlet', $purchase->nid_outlet)
                                ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $ingredient->cname))])
                                ->first();

                            if ($outletIngredient) {
                                $outletIngredient->nstock = (float) $outletIngredient->nstock + (int) $item->nqty;
                                $outletIngredient->save();
                                $item->nid_ingredient = $outletIngredient->nid;
                                $item->save();
                            } else {
                                $newIngPayload = [
                                    'cname' => $ingredient->cname,
                                    'nid_outlet' => $purchase->nid_outlet,
                                    'csatuan' => $ingredient->csatuan ?: 'PCS',
                                    'nstock' => (int) $item->nqty,
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
