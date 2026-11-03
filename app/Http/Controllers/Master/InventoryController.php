<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposIngredients;
use App\Models\MposOutlet;
use App\Models\MposProduct;
use App\Models\MposPurchaseD;
use App\Models\MposPurchaseH;
use App\Models\MposSalesH;
use App\Models\MposStockMutation;
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
        $stockOuts = null;
        $totalStockOut = 0;
        $modalProducts = collect();
        $modalIngredients = collect();

        if ($activeTab === 'stok_masuk') {
            $stockInQuery = MposStockMutation::where('ctype', MposStockMutation::TYPE_IN);

            if (!empty($selectedOutletId)) {
                $stockInQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $stockInQuery->whereIn('nid_outlet', $allowedOutletIds);
            }

            if (!empty($startDate) && !empty($endDate)) {
                $stockInQuery->whereBetween('dmutation', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59',
                ]);
            }

            if (!empty($search)) {
                $stockInQuery->where(function ($q) use ($search) {
                    $q->where('cno_ref', 'like', "%{$search}%")
                        ->orWhere('cnote', 'like', "%{$search}%")
                        ->orWhere('creason', 'like', "%{$search}%");
                });
            }

            $stockInRows = (clone $stockInQuery)->select(
                'cno_ref',
                'dmutation',
                'nid_outlet',
                'nid_target_outlet',
                'nid_user',
                'creason',
                'cstatus',
                'cnote',
                DB::raw('SUM(nqty) as ntotal_qty'),
                DB::raw('SUM(nsubtotal) as ntotal_cost'),
                DB::raw('COUNT(nid) as total_items')
            )->groupBy('cno_ref', 'dmutation', 'nid_outlet', 'nid_target_outlet', 'nid_user', 'creason', 'cstatus', 'cnote')
             ->orderBy('dmutation', 'desc')
             ->get();

            $outletIds = $stockInRows->pluck('nid_outlet')->unique()->filter()->values();
            $outletsMap = MposOutlet::whereIn('nid', $outletIds)->get()->keyBy('nid');
            $userIds = $stockInRows->pluck('nid_user')->unique()->filter()->values();
            $usersMap = \App\Models\Muser::whereIn('nid', $userIds)->get()->keyBy('nid');

            $stockInDocs = $stockInRows->map(function ($row) use ($outletsMap, $usersMap) {
                $row->outlet = $outletsMap->get($row->nid_outlet);
                $row->user = $usersMap->get($row->nid_user);
                $row->cno_stock_in = $row->cno_ref;
                $row->dstock_in = $row->dmutation;
                $row->creceived_by = $row->user?->email ?: ($row->user?->cname ?: 'Admin');
                $row->supplier = null;
                return $row;
            });

            $currentPage = Paginator::resolveCurrentPage() ?: 1;
            $currentPageItems = $stockInDocs->slice(($currentPage - 1) * $perPage, $perPage)->values();
            $stockIns = new LengthAwarePaginator(
                $currentPageItems,
                $stockInDocs->count(),
                $perPage,
                $currentPage,
                ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
            );
            $totalStockIn = $stockInDocs->count();

            // Ambil daftar Pembelian (PO) dengan status PENDING untuk modal "Terima dari Pembelian"
            $pendingPurchasesQuery = MposPurchaseH::with(['supplier', 'outlet', 'details'])
                ->where('cstatus', MposPurchaseH::STATUS_PENDING);

            if (!empty($selectedOutletId)) {
                $pendingPurchasesQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $pendingPurchasesQuery->whereIn('nid_outlet', $allowedOutletIds);
            }

            $pendingPurchases = $pendingPurchasesQuery->orderBy('dpurchase', 'desc')->orderBy('nid', 'desc')->get();
        } elseif ($activeTab === 'stok_keluar') {
            $stockOutQuery = MposStockMutation::where('ctype', MposStockMutation::TYPE_OUT);

            if (!empty($selectedOutletId)) {
                $stockOutQuery->where(function ($q) use ($selectedOutletId) {
                    $q->where('nid_outlet', $selectedOutletId)
                        ->orWhere('nid_target_outlet', $selectedOutletId);
                });
            } elseif (!$isSuperAdmin) {
                $stockOutQuery->where(function ($q) use ($allowedOutletIds) {
                    $q->whereIn('nid_outlet', $allowedOutletIds)
                        ->orWhereIn('nid_target_outlet', $allowedOutletIds);
                });
            }

            if (!empty($startDate) && !empty($endDate)) {
                $stockOutQuery->whereBetween('dmutation', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59',
                ]);
            }

            if (!empty($search)) {
                $stockOutQuery->where(function ($q) use ($search) {
                    $q->where('cno_ref', 'like', "%{$search}%")
                        ->orWhere('cnote', 'like', "%{$search}%")
                        ->orWhere('creason', 'like', "%{$search}%");
                });
            }

            $stockOutRows = (clone $stockOutQuery)->select(
                'cno_ref',
                'dmutation',
                'nid_outlet',
                'nid_target_outlet',
                'nid_user',
                'creason',
                'cstatus',
                'cnote',
                DB::raw('SUM(nqty) as ntotal_qty'),
                DB::raw('SUM(nsubtotal) as ntotal_cost'),
                DB::raw('COUNT(nid) as total_items')
            )->groupBy('cno_ref', 'dmutation', 'nid_outlet', 'nid_target_outlet', 'nid_user', 'creason', 'cstatus', 'cnote')
             ->orderBy('dmutation', 'desc')
             ->get();

            $outletIds = $stockOutRows->pluck('nid_outlet')->merge($stockOutRows->pluck('nid_target_outlet'))->unique()->filter()->values();
            $outletsMap = MposOutlet::whereIn('nid', $outletIds)->get()->keyBy('nid');
            $userIds = $stockOutRows->pluck('nid_user')->unique()->filter()->values();
            $usersMap = \App\Models\Muser::whereIn('nid', $userIds)->get()->keyBy('nid');

            $stockOutDocs = $stockOutRows->map(function ($row) use ($outletsMap, $usersMap) {
                $row->outlet = $outletsMap->get($row->nid_outlet);
                $row->targetOutlet = $row->nid_target_outlet ? $outletsMap->get($row->nid_target_outlet) : null;
                $row->user = $usersMap->get($row->nid_user);
                $row->cno_stock_out = $row->cno_ref;
                $row->dstock_out = $row->dmutation;
                return $row;
            });

            $currentPage = Paginator::resolveCurrentPage() ?: 1;
            $currentPageItems = $stockOutDocs->slice(($currentPage - 1) * $perPage, $perPage)->values();
            $stockOuts = new LengthAwarePaginator(
                $currentPageItems,
                $stockOutDocs->count(),
                $perPage,
                $currentPage,
                ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
            );
            $totalStockOut = $stockOutDocs->count();

            // Produk & Bahan Baku untuk modal tambah stok keluar
            $modalProductsQuery = MposProduct::where('cstatus', 'ACTIVE');
            if (!empty($selectedOutletId)) {
                $modalProductsQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $modalProductsQuery->whereIn('nid_outlet', $allowedOutletIds);
            }
            $modalProducts = $modalProductsQuery->orderBy('cname')->get(['nid', 'cname', 'nid_outlet', 'nprice', 'nprice_buy', 'nqty']);

            $modalIngredientsQuery = MposIngredients::query();
            if (!empty($selectedOutletId)) {
                $modalIngredientsQuery->where(function ($iq) use ($selectedOutletId) {
                    $iq->where('nid_outlet', $selectedOutletId)
                        ->orWhereNull('nid_outlet');
                });
            } elseif (!$isSuperAdmin) {
                $modalIngredientsQuery->where(function ($iq) use ($allowedOutletIds) {
                    $iq->whereIn('nid_outlet', $allowedOutletIds)
                        ->orWhereNull('nid_outlet');
                });
            }
            $selectCols = ['nid', 'cname', 'nid_outlet', 'csatuan', 'nstock'];
            if (MposIngredients::hasPriceColumn()) {
                $selectCols[] = 'nprice as ncost';
            }
            $modalIngredients = $modalIngredientsQuery->orderBy('cname')->get($selectCols);
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

            // 2. Ambil seluruh data Mutasi Stok (IN dan OUT) di periode tersebut dari mpos_stock_mutations
            $mutationQuery = DB::table('mpos_stock_mutations')
                ->whereBetween('dmutation', [$startDateTime, $endDateTime])
                ->where('cstatus', MposStockMutation::STATUS_POSTED);

            if (!empty($selectedOutletId)) {
                $mutationQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $mutationQuery->whereIn('nid_outlet', $allowedOutletIds);
            }

            $mutationRows = $mutationQuery->select(
                'ctype',
                'citem_type',
                'nid_product',
                'nid_ingredient',
                DB::raw('SUM(nqty) as total_qty')
            )->groupBy('ctype', 'citem_type', 'nid_product', 'nid_ingredient')->get();

            $mutProdIds = $mutationRows->where('citem_type', 'PRODUCT')->pluck('nid_product')->filter()->unique()->values();
            $mutProds = MposProduct::with('category')->whereIn('nid', $mutProdIds)->get()->keyBy('nid');

            $mutIngIds = $mutationRows->where('citem_type', 'INGREDIENT')->pluck('nid_ingredient')->filter()->unique()->values();
            $mutIngs = MposIngredients::whereIn('nid', $mutIngIds)->get()->keyBy('nid');

            foreach ($mutationRows as $r) {
                $qty = (float) $r->total_qty;
                if ($r->citem_type === 'PRODUCT' && !empty($r->nid_product)) {
                    $prod = $mutProds->get($r->nid_product);
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
                        if ($r->ctype === 'IN') {
                            $productMovements[$pId]['masuk'] += $qty;
                        } else {
                            $productMovements[$pId]['keluar'] += $qty;
                        }
                    }
                } elseif ($r->citem_type === 'INGREDIENT' && !empty($r->nid_ingredient)) {
                    $ing = $mutIngs->get($r->nid_ingredient);
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
                    if ($r->ctype === 'IN') {
                        $ingredientMovements[$ingId]['masuk'] += $qty;
                    } else {
                        $ingredientMovements[$ingId]['keluar'] += $qty;
                    }
                }
            }

            // 3. Dukungan untuk Legacy PO yang belum tercatat di mpos_stock_mutations
            $legacyPoQuery = DB::table('mpos_purchase_d')
                ->join('mpos_purchase_h', 'mpos_purchase_d.nid_purchase', '=', 'mpos_purchase_h.nid')
                ->whereBetween('mpos_purchase_h.dreceived', [$startDateTime, $endDateTime])
                ->where('mpos_purchase_h.cstatus', MposPurchaseH::STATUS_RECEIVED)
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('mpos_stock_mutations')
                        ->where('ctype', 'IN')
                        ->whereColumn('mpos_stock_mutations.cnote', 'like', DB::raw("CONCAT('%', mpos_purchase_h.cno_purchase, '%')"));
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

            // 4. Gabungkan seluruh item yang memiliki pergerakan (masuk > 0, penjualan > 0, pengembalian > 0, keluar > 0)
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
            'products',
            'stockOuts',
            'totalStockOut',
            'modalProducts',
            'modalIngredients'
        ));
    }

    /**
     * Terima barang dari Pesanan Pembelian (PO), catat ke mpos_stock_mutations,
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
            $stockInNumber = MposStockMutation::generateNumber('IN');

            foreach ($purchase->details as $item) {
                MposStockMutation::create([
                    'cno_ref' => $stockInNumber,
                    'dmutation' => Carbon::now(),
                    'ctype' => MposStockMutation::TYPE_IN,
                    'nid_outlet' => $purchase->nid_outlet,
                    'nid_user' => Auth::id() ?? 1,
                    'citem_type' => $item->citem_type,
                    'nid_product' => $item->nid_product,
                    'nid_ingredient' => $item->nid_ingredient,
                    'nqty' => (float) $item->nqty,
                    'ncost' => (float) $item->ncost,
                    'nsubtotal' => (float) $item->nsubtotal,
                    'creason' => 'Pembelian',
                    'cstatus' => MposStockMutation::STATUS_POSTED,
                    'cnote' => 'PO: ' . $purchase->cno_purchase . ($purchase->supplier ? ' (' . $purchase->supplier->cname . ')' : ''),
                    'dcreated' => Carbon::now(),
                ]);

                // Update stok fisik di outlet tujuan
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
                                MposIngredients::create($newIngPayload);
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
                            $outletProduct = MposProduct::where('nid_outlet', $purchase->nid_outlet)
                                ->where('cname', $product->cname)
                                ->first();

                            if ($outletProduct) {
                                $outletProduct->nqty = (float) $outletProduct->nqty + (float) $item->nqty;
                                $outletProduct->save();
                            } else {
                                $newProduct = $product->replicate();
                                $newProduct->nid_outlet = $purchase->nid_outlet;
                                $newProduct->nqty = (float) $item->nqty;
                                $newProduct->save();
                            }
                        }
                    }
                }
            }

            // Update status Pesanan Pembelian menjadi RECEIVED
            $purchase->cstatus = MposPurchaseH::STATUS_RECEIVED;
            $purchase->dreceived = Carbon::now();
            $purchase->creceived_by = $receivedBy;
            $purchase->cno_reference = $purchase->cno_reference ?: $stockInNumber;
            $purchase->save();

            DB::commit();

            return redirect()->route('inventory.stock-in.show', $stockInNumber)
                ->with('success', "Stok Masuk {$stockInNumber} berhasil dibuat dari Pembelian {$purchase->cno_purchase}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal memproses stok masuk: ' . $e->getMessage());
        }
    }

    /**
     * Halaman Detail Stok Masuk (sesuai tampilan Olsera)
     */
    public function showStockIn(string $id)
    {
        $items = MposStockMutation::with(['outlet', 'targetOutlet', 'user', 'product', 'ingredient'])
            ->where('ctype', MposStockMutation::TYPE_IN)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)
                  ->orWhere('nid', $id);
            })
            ->get();

        if ($items->isEmpty()) {
            abort(404, 'Dokumen Stok Masuk tidak ditemukan.');
        }

        $first = $items->first();
        $stockIn = (object) [
            'nid' => $first->nid,
            'cno_stock_in' => $first->cno_ref,
            'dstock_in' => $first->dmutation,
            'creason' => $first->creason,
            'cstatus' => $first->cstatus,
            'cnote' => $first->cnote,
            'outlet' => $first->outlet,
            'user' => $first->user,
            'details' => $items,
            'ntotal_qty' => $items->sum('nqty'),
            'ntotal_cost' => $items->sum('nsubtotal'),
            'supplier' => null,
            'creceived_by' => $first->user?->email ?: ($first->user?->cname ?: 'Admin'),
        ];

        return view('inventory.stock_in_show', compact('stockIn'));
    }

    /**
     * Cetak dokumen bukti Stok Masuk
     */
    public function printStockIn(string $id)
    {
        $items = MposStockMutation::with(['outlet', 'targetOutlet', 'user', 'product', 'ingredient'])
            ->where('ctype', MposStockMutation::TYPE_IN)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)
                  ->orWhere('nid', $id);
            })
            ->get();

        if ($items->isEmpty()) {
            abort(404, 'Dokumen Stok Masuk tidak ditemukan.');
        }

        $first = $items->first();
        $stockIn = (object) [
            'nid' => $first->nid,
            'cno_stock_in' => $first->cno_ref,
            'dstock_in' => $first->dmutation,
            'creason' => $first->creason,
            'cstatus' => $first->cstatus,
            'cnote' => $first->cnote,
            'outlet' => $first->outlet,
            'user' => $first->user,
            'details' => $items,
            'ntotal_qty' => $items->sum('nqty'),
            'ntotal_cost' => $items->sum('nsubtotal'),
            'supplier' => null,
            'creceived_by' => $first->user?->email ?: ($first->user?->cname ?: 'Admin'),
        ];

        return view('inventory.stock_in_print', compact('stockIn'));
    }

    /**
     * Simpan transaksi Stok Keluar baru (manual atau transfer ke toko)
     */
    public function storeStockOut(Request $request)
    {
        $validated = $request->validate([
            'nid_outlet' => 'required|exists:mpos_outlet,nid',
            'ctype' => 'required|in:OUT,TRANSFER',
            'nid_target_outlet' => 'nullable|required_if:ctype,TRANSFER|different:nid_outlet|exists:mpos_outlet,nid',
            'dstock_out' => 'required|date',
            'creason' => 'nullable|string|max:100',
            'cnote' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.citem_type' => 'required|in:PRODUCT,INGREDIENT',
            'items.*.nid_item' => 'required|integer',
            'items.*.nqty' => 'required|numeric|min:0.01',
            'items.*.ncost' => 'nullable|numeric|min:0',
            'items.*.crack' => 'nullable|string|max:100',
            'items.*.cstock_from' => 'nullable|string|max:100',
            'items.*.dexpired' => 'nullable|date',
            'items.*.cnote' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $dStockOut = Carbon::parse($validated['dstock_out'])->setTime(Carbon::now()->hour, Carbon::now()->minute, Carbon::now()->second);
            $stockOutNumber = MposStockMutation::generateNumber('OUT', $dStockOut);
            $reason = $validated['creason'] ?: ($validated['ctype'] === 'TRANSFER' ? 'Transfer Antar Toko' : 'Lain-lain');

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['nqty'];
                $cost = isset($item['ncost']) ? (float) $item['ncost'] : 0.0;
                $subtotal = $qty * $cost;

                $prodId = $item['citem_type'] === 'PRODUCT' ? (int) $item['nid_item'] : null;
                $ingId = $item['citem_type'] === 'INGREDIENT' ? (int) $item['nid_item'] : null;

                // Kurangi stok fisik di outlet asal
                if ($item['citem_type'] === 'PRODUCT' && $prodId) {
                    $prod = MposProduct::lockForUpdate()->find($prodId);
                    if ($prod) {
                        $cost = $cost ?: (float) ($prod->nprice_buy ?: 0);
                        $subtotal = $qty * $cost;
                        $prod->nqty = max(0, (float) $prod->nqty - $qty);
                        $prod->save();

                        // Jika transfer, tambah di outlet tujuan
                        if ($validated['ctype'] === 'TRANSFER' && !empty($validated['nid_target_outlet'])) {
                            $targetProd = MposProduct::where('nid_outlet', $validated['nid_target_outlet'])
                                ->where(function ($tq) use ($prod) {
                                    $tq->where('csku', $prod->csku)->whereNotNull('csku')
                                        ->orWhere('cname', $prod->cname);
                                })->first();

                            if ($targetProd) {
                                $targetProd->nqty = (float) $targetProd->nqty + $qty;
                                $targetProd->save();
                            }
                        }
                    }
                } elseif ($item['citem_type'] === 'INGREDIENT' && $ingId) {
                    $ing = MposIngredients::lockForUpdate()->find($ingId);
                    if ($ing) {
                        $cost = $cost ?: (float) ($ing->nprice ?? 0);
                        $subtotal = $qty * $cost;
                        $ing->nstock = max(0, (float) $ing->nstock - $qty);
                        $ing->save();

                        // Jika transfer, tambah di outlet tujuan
                        if ($validated['ctype'] === 'TRANSFER' && !empty($validated['nid_target_outlet'])) {
                            $targetIng = MposIngredients::where('nid_outlet', $validated['nid_target_outlet'])
                                ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $ing->cname))])
                                ->first();

                            if ($targetIng) {
                                $targetIng->nstock = (float) $targetIng->nstock + $qty;
                                $targetIng->save();
                            }
                        }
                    }
                }

                // Catat mutasi OUT
                MposStockMutation::create([
                    'cno_ref' => $stockOutNumber,
                    'dmutation' => $dStockOut,
                    'ctype' => MposStockMutation::TYPE_OUT,
                    'nid_outlet' => $validated['nid_outlet'],
                    'nid_target_outlet' => $validated['ctype'] === 'TRANSFER' ? $validated['nid_target_outlet'] : null,
                    'nid_user' => Auth::id() ?? 1,
                    'citem_type' => $item['citem_type'],
                    'nid_product' => $prodId,
                    'nid_ingredient' => $ingId,
                    'nqty' => $qty,
                    'ncost' => $cost,
                    'nsubtotal' => $subtotal,
                    'creason' => $reason,
                    'cstatus' => MposStockMutation::STATUS_POSTED,
                    'cnote' => $validated['cnote'] ?? null,
                    'dexpired' => !empty($item['dexpired']) ? $item['dexpired'] : null,
                    'crack' => $item['crack'] ?? null,
                    'dcreated' => Carbon::now(),
                ]);

                // Jika transfer, catat mutasi pasangannya di outlet tujuan sebagai IN
                if ($validated['ctype'] === 'TRANSFER' && !empty($validated['nid_target_outlet'])) {
                    MposStockMutation::create([
                        'cno_ref' => $stockOutNumber,
                        'dmutation' => $dStockOut,
                        'ctype' => MposStockMutation::TYPE_IN,
                        'nid_outlet' => $validated['nid_target_outlet'],
                        'nid_target_outlet' => $validated['nid_outlet'],
                        'nid_user' => Auth::id() ?? 1,
                        'citem_type' => $item['citem_type'],
                        'nid_product' => $prodId,
                        'nid_ingredient' => $ingId,
                        'nqty' => $qty,
                        'ncost' => $cost,
                        'nsubtotal' => $subtotal,
                        'creason' => 'Transfer Masuk dari Toko Lain',
                        'cstatus' => MposStockMutation::STATUS_POSTED,
                        'cnote' => 'Transfer dari ' . (MposOutlet::find($validated['nid_outlet'])?->cname ?: 'Outlet Lain'),
                        'dexpired' => !empty($item['dexpired']) ? $item['dexpired'] : null,
                        'crack' => $item['crack'] ?? null,
                        'dcreated' => Carbon::now(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('inventory.stock-out.show', $stockOutNumber)
                ->with('success', "Stok Keluar {$stockOutNumber} berhasil disimpan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal memproses stok keluar: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Halaman Detail Stok Keluar (sesuai tampilan Olsera)
     */
    public function showStockOut(string $id)
    {
        $items = MposStockMutation::with(['outlet', 'targetOutlet', 'user', 'product', 'ingredient'])
            ->where('ctype', MposStockMutation::TYPE_OUT)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)
                  ->orWhere('nid', $id);
            })
            ->get();

        if ($items->isEmpty()) {
            abort(404, 'Dokumen Stok Keluar tidak ditemukan.');
        }

        $first = $items->first();
        $stockOut = (object) [
            'nid' => $first->nid,
            'cno_stock_out' => $first->cno_ref,
            'dstock_out' => $first->dmutation,
            'creason' => $first->creason,
            'cstatus' => $first->cstatus,
            'cnote' => $first->cnote,
            'outlet' => $first->outlet,
            'targetOutlet' => $first->targetOutlet,
            'user' => $first->user,
            'details' => $items,
            'ntotal_qty' => $items->sum('nqty'),
            'ntotal_cost' => $items->sum('nsubtotal'),
        ];

        return view('inventory.stock_out_show', compact('stockOut'));
    }

    /**
     * Cetak dokumen bukti Stok Keluar
     */
    public function printStockOut(string $id)
    {
        $items = MposStockMutation::with(['outlet', 'targetOutlet', 'user', 'product', 'ingredient'])
            ->where('ctype', MposStockMutation::TYPE_OUT)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)
                  ->orWhere('nid', $id);
            })
            ->get();

        if ($items->isEmpty()) {
            abort(404, 'Dokumen Stok Keluar tidak ditemukan.');
        }

        $first = $items->first();
        $stockOut = (object) [
            'nid' => $first->nid,
            'cno_stock_out' => $first->cno_ref,
            'dstock_out' => $first->dmutation,
            'creason' => $first->creason,
            'cstatus' => $first->cstatus,
            'cnote' => $first->cnote,
            'outlet' => $first->outlet,
            'targetOutlet' => $first->targetOutlet,
            'user' => $first->user,
            'details' => $items,
            'ntotal_qty' => $items->sum('nqty'),
            'ntotal_cost' => $items->sum('nsubtotal'),
        ];

        return view('inventory.stock_out_print', compact('stockOut'));
    }
}
