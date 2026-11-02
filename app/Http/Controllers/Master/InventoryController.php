<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposIngredients;
use App\Models\MposOutlet;
use App\Models\MposProduct;
use App\Models\MposPurchaseD;
use App\Models\MposPurchaseH;
use App\Models\MposSalesH;
use App\Models\MposStockInD;
use App\Models\MposStockInH;
use App\Traits\ResolvesOutletScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request)
    {
        $activeTab = $request->input('tab', 'stok_masuk');
        $validTabs = ['stok_masuk', 'stok_keluar', 'produksi_stok', 'stok_opname', 'pergerakan_stok'];
        if (!in_array($activeTab, $validTabs)) {
            $activeTab = 'stok_masuk';
        }

        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $outlets = $this->getAvailableOutlets();
        $isOutletLocked = $this->isOutletLocked();
        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $isSuperAdmin = $this->isSuperAdminUser();

        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $stockIns = null;
        $totalStockIn = 0;
        $pendingPurchases = collect();
        $products = null;

        if ($activeTab === 'stok_masuk') {
            $stockInQuery = MposStockInH::with(['supplier', 'outlet', 'user', 'purchase'])
                ->orderBy('dstock_in', 'desc')
                ->orderBy('nid', 'desc');

            if (!empty($selectedOutletId)) {
                $stockInQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $stockInQuery->whereIn('nid_outlet', $allowedOutletIds);
            }

            if (!empty($startDate) && !empty($endDate)) {
                $stockInQuery->whereBetween('dstock_in', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59',
                ]);
            }

            if (!empty($search)) {
                $stockInQuery->where(function ($q) use ($search) {
                    $q->where('cno_stock_in', 'like', "%{$search}%")
                        ->orWhere('cnote', 'like', "%{$search}%")
                        ->orWhere('creceived_by', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($sq) use ($search) {
                            $sq->where('cname', 'like', "%{$search}%");
                        });
                });
            }

            $totalStockIn = (clone $stockInQuery)->count();
            $stockIns = $stockInQuery->paginate($perPage)->withQueryString();

            // Ambil daftar Pembelian (PO) dengan status PENDING untuk modal "Terima dari Pembelian"
            $pendingPurchasesQuery = MposPurchaseH::with(['supplier', 'outlet', 'details'])
                ->where('cstatus', MposPurchaseH::STATUS_PENDING);

            if (!empty($selectedOutletId)) {
                $pendingPurchasesQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $pendingPurchasesQuery->whereIn('nid_outlet', $allowedOutletIds);
            }

            $pendingPurchases = $pendingPurchasesQuery->orderBy('dpurchase', 'desc')->orderBy('nid', 'desc')->get();
        } elseif ($activeTab === 'pergerakan_stok') {
            $startDateTime = $startDate . ' 00:00:00';
            $endDateTime = $endDate . ' 23:59:59';

            // 1. Ambil seluruh data penjualan di periode tersebut
            $salesQuery = DB::table('mpos_sales_d')
                ->join('mpos_sales_h', 'mpos_sales_d.nid_transaction', '=', 'mpos_sales_h.nid')
                ->whereBetween('mpos_sales_h.dtransaction', [$startDateTime, $endDateTime])
                ->where(function ($q) {
                    $q->whereIn('mpos_sales_h.cstatus', [MposSalesH::STATUS_PAID, MposSalesH::STATUS_COMPLETED])
                        ->orWhere(function ($sub) {
                            $sub->whereNotIn('mpos_sales_h.cstatus', [
                                MposSalesH::STATUS_CANCELLED,
                                MposSalesH::STATUS_DRAFT,
                                MposSalesH::STATUS_VOID,
                                MposSalesH::STATUS_REFUND,
                            ])->whereNotNull('mpos_sales_h.nid_payment');
                        });
                });

            if (!empty($selectedOutletId)) {
                $salesQuery->where('mpos_sales_h.nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $salesQuery->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
            }

            $salesRows = $salesQuery->select(
                'mpos_sales_d.nid_product',
                'mpos_sales_h.nid_outlet',
                DB::raw('SUM(GREATEST(0, mpos_sales_d.nqty - mpos_sales_d.nqty_void - mpos_sales_d.nqty_refund)) as total_sales'),
                DB::raw('SUM(GREATEST(0, mpos_sales_d.nqty_refund)) as total_refund')
            )->groupBy('mpos_sales_d.nid_product', 'mpos_sales_h.nid_outlet')->get();

            $productMovements = [];
            $ingredientMovements = [];

            // Preload produk beserta kategori dan resep bahan baku
            $soldProductIds = $salesRows->pluck('nid_product')->filter()->unique()->values();
            $soldProducts = MposProduct::with(['category', 'recipes.ingredient'])->whereIn('nid', $soldProductIds)->get()->keyBy('nid');

            foreach ($salesRows as $r) {
                $prod = $soldProducts->get($r->nid_product);
                if (!$prod) continue;

                $trackStock = $prod->category && isset($prod->category->ftrack_stock)
                    ? (bool) $prod->category->ftrack_stock
                    : true;

                if ($trackStock) {
                    // Produk retail mandiri: lacak langsung di produk
                    $pId = $prod->nid;
                    if (!isset($productMovements[$pId])) {
                        $productMovements[$pId] = [
                            'type' => 'PRODUCT',
                            'id' => $prod->nid,
                            'name' => $prod->cname,
                            'category_name' => $prod->category?->cname ?: 'Produk',
                            'masuk' => 0.0,
                            'penjualan' => 0.0,
                            'pengembalian' => 0.0,
                            'keluar' => 0.0,
                            'sisa' => (float) $prod->nqty,
                        ];
                    }
                    $productMovements[$pId]['penjualan'] += (float) $r->total_sales;
                    $productMovements[$pId]['pengembalian'] += (float) $r->total_refund;
                } else {
                    // Produk resep/olahan (ftrack_stock = 0): lacak bahan baku (mpos_ingredients)
                    foreach ($prod->recipes as $recipe) {
                        if (!$recipe->ingredient) continue;

                        $outletIng = MposIngredients::where('nid_outlet', $r->nid_outlet)
                            ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $recipe->ingredient->cname))])
                            ->first() ?: $recipe->ingredient;

                        $ingId = $outletIng->nid;
                        if (!isset($ingredientMovements[$ingId])) {
                            $displayName = $outletIng->cname . ($outletIng->csatuan ? ' (' . $outletIng->csatuan . ')' : '');
                            $ingredientMovements[$ingId] = [
                                'type' => 'INGREDIENT',
                                'id' => $outletIng->nid,
                                'name' => $displayName,
                                'category_name' => 'Bahan Baku',
                                'masuk' => 0.0,
                                'penjualan' => 0.0,
                                'pengembalian' => 0.0,
                                'keluar' => 0.0,
                                'sisa' => (float) $outletIng->nstock,
                            ];
                        }
                        $ingredientMovements[$ingId]['penjualan'] += ((float) $r->total_sales * (float) $recipe->nqty);
                        $ingredientMovements[$ingId]['pengembalian'] += ((float) $r->total_refund * (float) $recipe->nqty);
                    }
                }
            }

            // 2. Ambil seluruh data Stok Masuk (mpos_stock_in_d) di periode tersebut
            $stockInQuery = DB::table('mpos_stock_in_d')
                ->join('mpos_stock_in_h', 'mpos_stock_in_d.nid_stock_in', '=', 'mpos_stock_in_h.nid')
                ->whereBetween('mpos_stock_in_h.dstock_in', [$startDateTime, $endDateTime])
                ->where('mpos_stock_in_h.cstatus', MposStockInH::STATUS_POSTED);

            if (!empty($selectedOutletId)) {
                $stockInQuery->where('mpos_stock_in_h.nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $stockInQuery->whereIn('mpos_stock_in_h.nid_outlet', $allowedOutletIds);
            }

            $stockInRows = $stockInQuery->select(
                'mpos_stock_in_d.citem_type',
                'mpos_stock_in_d.nid_product',
                'mpos_stock_in_d.nid_ingredient',
                DB::raw('SUM(mpos_stock_in_d.nqty) as total_in')
            )->groupBy('mpos_stock_in_d.citem_type', 'mpos_stock_in_d.nid_product', 'mpos_stock_in_d.nid_ingredient')->get();

            // Preload produk & ingredient stok masuk
            $stockInProdIds = $stockInRows->where('citem_type', 'PRODUCT')->pluck('nid_product')->filter()->unique()->values();
            $stockInProds = MposProduct::with('category')->whereIn('nid', $stockInProdIds)->get()->keyBy('nid');

            $stockInIngIds = $stockInRows->where('citem_type', 'INGREDIENT')->pluck('nid_ingredient')->filter()->unique()->values();
            $stockInIngs = MposIngredients::whereIn('nid', $stockInIngIds)->get()->keyBy('nid');

            foreach ($stockInRows as $r) {
                if ($r->citem_type === 'PRODUCT' && !empty($r->nid_product)) {
                    $prod = $stockInProds->get($r->nid_product);
                    if (!$prod) continue;
                    $trackStock = $prod->category && isset($prod->category->ftrack_stock)
                        ? (bool) $prod->category->ftrack_stock
                        : true;

                    if ($trackStock) {
                        $pId = $prod->nid;
                        if (!isset($productMovements[$pId])) {
                            $productMovements[$pId] = [
                                'type' => 'PRODUCT',
                                'id' => $prod->nid,
                                'name' => $prod->cname,
                                'category_name' => $prod->category?->cname ?: 'Produk',
                                'masuk' => 0.0,
                                'penjualan' => 0.0,
                                'pengembalian' => 0.0,
                                'keluar' => 0.0,
                                'sisa' => (float) $prod->nqty,
                            ];
                        }
                        $productMovements[$pId]['masuk'] += (float) $r->total_in;
                    }
                } elseif ($r->citem_type === 'INGREDIENT' && !empty($r->nid_ingredient)) {
                    $ing = $stockInIngs->get($r->nid_ingredient);
                    if (!$ing) continue;
                    $ingId = $ing->nid;
                    if (!isset($ingredientMovements[$ingId])) {
                        $displayName = $ing->cname . ($ing->csatuan ? ' (' . $ing->csatuan . ')' : '');
                        $ingredientMovements[$ingId] = [
                            'type' => 'INGREDIENT',
                            'id' => $ing->nid,
                            'name' => $displayName,
                            'category_name' => 'Bahan Baku',
                            'masuk' => 0.0,
                            'penjualan' => 0.0,
                            'pengembalian' => 0.0,
                            'keluar' => 0.0,
                            'sisa' => (float) $ing->nstock,
                        ];
                    }
                    $ingredientMovements[$ingId]['masuk'] += (float) $r->total_in;
                }
            }

            // Legacy PO
            $legacyPoQuery = DB::table('mpos_purchase_d')
                ->join('mpos_purchase_h', 'mpos_purchase_d.nid_purchase', '=', 'mpos_purchase_h.nid')
                ->whereBetween('mpos_purchase_h.dreceived', [$startDateTime, $endDateTime])
                ->where('mpos_purchase_h.cstatus', MposPurchaseH::STATUS_RECEIVED)
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('mpos_stock_in_h')
                        ->whereColumn('mpos_stock_in_h.nid_purchase', 'mpos_purchase_h.nid');
                });

            if (!empty($selectedOutletId)) {
                $legacyPoQuery->where('mpos_purchase_h.nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $legacyPoQuery->whereIn('mpos_purchase_h.nid_outlet', $allowedOutletIds);
            }

            $legacyPoRows = $legacyPoQuery->select(
                'mpos_purchase_d.citem_type',
                'mpos_purchase_d.nid_product',
                'mpos_purchase_d.nid_ingredient',
                DB::raw('SUM(mpos_purchase_d.nqty) as total_in')
            )->groupBy('mpos_purchase_d.citem_type', 'mpos_purchase_d.nid_product', 'mpos_purchase_d.nid_ingredient')->get();

            foreach ($legacyPoRows as $r) {
                if ($r->citem_type === 'PRODUCT' && !empty($r->nid_product)) {
                    $prod = MposProduct::with('category')->find($r->nid_product);
                    if (!$prod) continue;
                    $trackStock = $prod->category && isset($prod->category->ftrack_stock) ? (bool)$prod->category->ftrack_stock : true;
                    if ($trackStock) {
                        $pId = $prod->nid;
                        if (!isset($productMovements[$pId])) {
                            $productMovements[$pId] = [
                                'type' => 'PRODUCT',
                                'id' => $prod->nid,
                                'name' => $prod->cname,
                                'category_name' => $prod->category?->cname ?: 'Produk',
                                'masuk' => 0.0,
                                'penjualan' => 0.0,
                                'pengembalian' => 0.0,
                                'keluar' => 0.0,
                                'sisa' => (float) $prod->nqty,
                            ];
                        }
                        $productMovements[$pId]['masuk'] += (float) $r->total_in;
                    }
                } elseif ($r->citem_type === 'INGREDIENT' && !empty($r->nid_ingredient)) {
                    $ing = MposIngredients::find($r->nid_ingredient);
                    if (!$ing) continue;
                    $ingId = $ing->nid;
                    if (!isset($ingredientMovements[$ingId])) {
                        $displayName = $ing->cname . ($ing->csatuan ? ' (' . $ing->csatuan . ')' : '');
                        $ingredientMovements[$ingId] = [
                            'type' => 'INGREDIENT',
                            'id' => $ing->nid,
                            'name' => $displayName,
                            'category_name' => 'Bahan Baku',
                            'masuk' => 0.0,
                            'penjualan' => 0.0,
                            'pengembalian' => 0.0,
                            'keluar' => 0.0,
                            'sisa' => (float) $ing->nstock,
                        ];
                    }
                    $ingredientMovements[$ingId]['masuk'] += (float) $r->total_in;
                }
            }

            // 3. Gabungkan seluruh item yang memiliki pergerakan (masuk > 0, penjualan > 0, pengembalian > 0, keluar > 0)
            $allItems = collect(array_values($productMovements))
                ->merge(array_values($ingredientMovements))
                ->filter(function ($item) {
                    return ($item['masuk'] > 0 || $item['penjualan'] > 0 || $item['pengembalian'] > 0 || $item['keluar'] > 0);
                });

            // Filter pencarian jika ada
            if (!empty($search)) {
                $allItems = $allItems->filter(function ($item) use ($search) {
                    return stripos($item['name'], $search) !== false
                        || stripos($item['category_name'], $search) !== false;
                });
            }

            // Hitung nilai Awal
            $allItems = $allItems->map(function ($item) {
                $awal = $item['sisa'] - $item['masuk'] - $item['pengembalian'] + $item['penjualan'] + $item['keluar'];
                if ($awal < 0) {
                    $awal = 0.0;
                }
                return (object) [
                    'nid' => $item['id'],
                    'item_type' => $item['type'],
                    'cname' => $item['name'],
                    'category_name' => $item['category_name'],
                    'qty_awal' => $awal,
                    'qty_masuk' => $item['masuk'],
                    'qty_pengembalian' => $item['pengembalian'],
                    'qty_penjualan' => $item['penjualan'],
                    'qty_keluar' => $item['keluar'],
                    'qty_sisa' => $item['sisa'],
                ];
            })->sortBy('cname')->values();

            // Paginasi LengthAwarePaginator
            $currentPage = Paginator::resolveCurrentPage() ?: 1;
            $perPage = 15;
            $currentPageItems = $allItems->slice(($currentPage - 1) * $perPage, $perPage)->values();
            $products = new LengthAwarePaginator(
                $currentPageItems,
                $allItems->count(),
                $perPage,
                $currentPage,
                ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
            );
        }

        if ($request->ajax()) {
            if ($activeTab === 'pergerakan_stok') {
                return response()->json([
                    'status' => 'success',
                    'html' => view('inventory.partials.movement_table', compact('products'))->render(),
                    'total_count' => $products->total(),
                ]);
            }
        }

        return view('inventory.index', compact(
            'activeTab',
            'outlets',
            'selectedOutletId',
            'isOutletLocked',
            'startDate',
            'endDate',
            'search',
            'perPage',
            'stockIns',
            'totalStockIn',
            'pendingPurchases',
            'products'
        ));
    }

    /**
     * Terima barang dari Pesanan Pembelian (PO), simpan ke mpos_stock_in_h & mpos_stock_in_d,
     * update stok produk/bahan baku, update status PO ke RECEIVED, lalu redirect ke detail Stok Masuk.
     */
    public function receivePurchase(Request $request, string $purchaseId)
    {
        $purchase = MposPurchaseH::with(['details.product', 'details.ingredient', 'supplier', 'outlet'])
            ->findOrFail($purchaseId);

        if ($purchase->cstatus !== MposPurchaseH::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Pesanan pembelian ini sudah diproses atau dibatalkan.');
        }

        if ($purchase->details->isEmpty()) {
            return redirect()->back()->with('error', 'Pesanan pembelian belum memiliki item produk / bahan baku.');
        }

        DB::beginTransaction();
        try {
            $currentUser = Auth::user();
            $receivedBy = $currentUser?->email ?: ($currentUser?->cname ?: 'Admin');

            // 1. Generate nomor Stok Masuk baru (format: INyymmddxxxxxx)
            $stockInNumber = MposStockInH::generateStockInNumber();

            // 2. Buat header stok masuk di mpos_stock_in_h
            $totalQty = (int) $purchase->details->sum('nqty');
            $stockIn = MposStockInH::create([
                'cno_stock_in' => $stockInNumber,
                'dstock_in' => Carbon::now(),
                'nid_purchase' => $purchase->nid,
                'nid_supplier' => $purchase->nid_supplier,
                'nid_outlet' => $purchase->nid_outlet,
                'nid_user' => Auth::id() ?? 1,
                'creceived_by' => $receivedBy,
                'cstatus' => MposStockInH::STATUS_POSTED,
                'cnote' => $purchase->cno_purchase,
                'ntotal_qty' => $totalQty,
                'ntotal_cost' => (float) $purchase->ntotal_amount,
                'dcreated' => Carbon::now(),
            ]);

            // 3. Simpan detail barang masuk di mpos_stock_in_d & update fisik stok produk/bahan baku
            foreach ($purchase->details as $item) {
                MposStockInD::create([
                    'nid_stock_in' => $stockIn->nid,
                    'citem_type' => $item->citem_type,
                    'nid_product' => $item->nid_product,
                    'nid_ingredient' => $item->nid_ingredient,
                    'nqty' => (int) $item->nqty,
                    'ncost' => (float) $item->ncost,
                    'nsubtotal' => (float) $item->nsubtotal,
                    'cserial_no' => null,
                    'crack' => null,
                    'dcreated' => Carbon::now(),
                ]);

                // Update stok di outlet tujuan
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
                                MposIngredients::create($newIngPayload);
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
                            $outletProduct = MposProduct::where('nid_outlet', $purchase->nid_outlet)
                                ->where('cname', $product->cname)
                                ->first();

                            if ($outletProduct) {
                                $outletProduct->nqty = (int) $outletProduct->nqty + (int) $item->nqty;
                                $outletProduct->save();
                            } else {
                                $newProduct = $product->replicate();
                                $newProduct->nid_outlet = $purchase->nid_outlet;
                                $newProduct->nqty = (int) $item->nqty;
                                $newProduct->save();
                            }
                        }
                    }
                }
            }

            // 4. Update status Pesanan Pembelian menjadi RECEIVED
            $purchase->cstatus = MposPurchaseH::STATUS_RECEIVED;
            $purchase->dreceived = Carbon::now();
            $purchase->creceived_by = $receivedBy;
            $purchase->cno_reference = $purchase->cno_reference ?: $stockInNumber;
            $purchase->save();

            DB::commit();

            return redirect()->route('inventory.stock-in.show', $stockIn->nid)
                ->with('success', "Stok Masuk {$stockIn->cno_stock_in} berhasil dibuat dari Pembelian {$purchase->cno_purchase}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal memproses stok masuk: ' . $e->getMessage());
        }
    }

    /**
     * Halaman Detail Stok Masuk (sesuai tampilan Olsera Image 4)
     */
    public function showStockIn(string $id)
    {
        $stockIn = MposStockInH::with([
            'supplier',
            'outlet',
            'user',
            'purchase',
            'details.product',
            'details.ingredient',
        ])->findOrFail($id);

        return view('inventory.stock_in_show', compact('stockIn'));
    }

    /**
     * Cetak dokumen bukti Stok Masuk
     */
    public function printStockIn(string $id)
    {
        $stockIn = MposStockInH::with([
            'supplier',
            'outlet',
            'user',
            'purchase',
            'details.product',
            'details.ingredient',
        ])->findOrFail($id);

        return view('inventory.stock_in_print', compact('stockIn'));
    }
}
