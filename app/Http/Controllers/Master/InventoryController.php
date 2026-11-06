<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposIngredients;
use App\Models\MposGrpProduct;
use App\Models\MposOutlet;
use App\Models\MposProduct;
use App\Models\MposPurchaseD;
use App\Models\MposPurchaseH;
use App\Models\MposSalesH;
use App\Models\MposStockMutation;
use App\Models\MposStockOpnameH;
use App\Models\MposStockOpnameD;
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
        $stockOpnames = null;
        $totalStockOpname = 0;
        $modalProducts = collect();
        $modalIngredients = collect();

        // Auto-healing: konversi mutasi transfer IN yang sebelumnya tersimpan dengan awalan nomor 'OG' menjadi 'IN'
        $legacyTransferIns = MposStockMutation::where('ctype', MposStockMutation::TYPE_IN)
            ->where('cno_ref', 'LIKE', 'OG%')
            ->get();

        if ($legacyTransferIns->isNotEmpty()) {
            foreach ($legacyTransferIns->groupBy('cno_ref') as $oldRef => $records) {
                $firstDate = $records->first()->dmutation ?? Carbon::now();
                $newInRef = MposStockMutation::generateNumber('IN', $firstDate);
                foreach ($records as $rec) {
                    $originOutlet = $rec->nid_target_outlet ? MposOutlet::find($rec->nid_target_outlet) : null;
                    $originName = $originOutlet?->cname ?: 'Outlet Asal';
                    $rec->cno_ref = $newInRef;
                    $rec->creason = 'Transfer Antar Toko';
                    $rec->cnote = 'Transfer dari ' . $originName . ' (' . $oldRef . ')' . ($rec->cnote && !str_contains($rec->cnote, 'Transfer dari') ? ' - ' . $rec->cnote : '');
                    $rec->save();
                }
            }
        }

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

            $outletIds = $stockInRows->pluck('nid_outlet')->merge($stockInRows->pluck('nid_target_outlet'))->unique()->filter()->values();
            $outletsMap = MposOutlet::whereIn('nid', $outletIds)->get()->keyBy('nid');
            $userIds = $stockInRows->pluck('nid_user')->unique()->filter()->values();
            $usersMap = \App\Models\Muser::whereIn('nid', $userIds)->get()->keyBy('nid');

            $stockInDocs = $stockInRows->map(function ($row) use ($outletsMap, $usersMap) {
                $row->outlet = $outletsMap->get($row->nid_outlet);
                $row->targetOutlet = $outletsMap->get($row->nid_target_outlet);
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

            // Stok keluar HANYA muncul di outlet pengirim/asal (nid_outlet), bukan di outlet tujuan!
            if (!empty($selectedOutletId)) {
                $stockOutQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $stockOutQuery->whereIn('nid_outlet', $allowedOutletIds);
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
        } elseif ($activeTab === 'stok_opname') {
            $stockOpnameQuery = MposStockOpnameH::with(['outlet', 'user'])->withCount('details');

            if (!empty($selectedOutletId)) {
                $stockOpnameQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $stockOpnameQuery->whereIn('nid_outlet', $allowedOutletIds);
            }

            if (!empty($startDate) && !empty($endDate)) {
                $stockOpnameQuery->whereBetween('dopname', [$startDate, $endDate]);
            }

            if (!empty($search)) {
                $stockOpnameQuery->where(function ($q) use ($search) {
                    $q->where('cno_opname', 'like', "%{$search}%")
                        ->orWhere('cnote', 'like', "%{$search}%");
                });
            }

            $stockOpnames = $stockOpnameQuery->orderBy('dopname', 'desc')
                ->orderBy('nid', 'desc')
                ->paginate($perPage)
                ->withQueryString();
            $totalStockOpname = $stockOpnames->total();
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

            $mutProdIds = $mutationRows->filter(function ($row) {
                return in_array(strtoupper((string) $row->citem_type), ['PRODUCT', 'PRODUK']) || (!empty($row->nid_product) && empty($row->nid_ingredient));
            })->pluck('nid_product')->filter()->unique()->values();
            $mutProds = MposProduct::with('category')->whereIn('nid', $mutProdIds)->get()->keyBy('nid');

            $mutIngIds = $mutationRows->filter(function ($row) {
                return in_array(strtoupper((string) $row->citem_type), ['INGREDIENT', 'BAHAN_BAKU', 'BAHAN BAKU']) || (!empty($row->nid_ingredient) && empty($row->nid_product));
            })->pluck('nid_ingredient')->filter()->unique()->values();
            $mutIngs = MposIngredients::whereIn('nid', $mutIngIds)->get()->keyBy('nid');

            foreach ($mutationRows as $r) {
                $qty = (float) $r->total_qty;
                $isProduct = in_array(strtoupper((string) $r->citem_type), ['PRODUCT', 'PRODUK']) || (!empty($r->nid_product) && empty($r->nid_ingredient));
                $isIngredient = in_array(strtoupper((string) $r->citem_type), ['INGREDIENT', 'BAHAN_BAKU', 'BAHAN BAKU']) || (!empty($r->nid_ingredient) && empty($r->nid_product));

                if ($isProduct && !empty($r->nid_product)) {
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
                } elseif ($isIngredient && !empty($r->nid_ingredient)) {
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

        // Produk & Bahan Baku untuk modal tambah stok masuk, keluar & stok opname
        if (in_array($activeTab, ['stok_masuk', 'stok_keluar', 'stok_opname'])) {
            $modalProductsQuery = MposProduct::where('cstatus', 'ACTIVE');
            if (!$isSuperAdmin) {
                $modalProductsQuery->whereIn('nid_outlet', $allowedOutletIds);
            }
            $prodCols = ['nid', 'cname', 'nid_outlet', 'nprice', 'nprice_buy', 'nqty'];
            if (MposProduct::hasSatuanColumn()) {
                $prodCols[] = 'csatuan';
            }
            if (MposProduct::hasProductTypeColumn()) {
                $prodCols[] = 'cproduct_type';
            }
            $modalProducts = $modalProductsQuery->orderBy('cname')->get($prodCols);

            $modalIngredientsQuery = MposIngredients::query();
            if (!$isSuperAdmin) {
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
            'stockOpnames',
            'totalStockOpname',
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
     * Simpan transaksi Stok Masuk baru secara manual (tanpa PO)
     */
    public function storeStockIn(Request $request)
    {
        $validated = $request->validate([
            'nid_outlet' => 'required|exists:mpos_outlet,nid',
            'dstock_in' => 'required|date',
            'creason' => 'nullable|string|max:100',
            'cnote' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.citem_type' => 'required|in:PRODUCT,INGREDIENT',
            'items.*.nid_item' => 'required|integer',
            'items.*.nqty' => 'required|numeric|min:0.01',
            'items.*.ncost' => 'nullable|numeric|min:0',
            'items.*.crack' => 'nullable|string|max:100',
            'items.*.dexpired' => 'nullable|date',
            'items.*.cnote' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $dStockIn = Carbon::parse($validated['dstock_in'])->setTime(Carbon::now()->hour, Carbon::now()->minute, Carbon::now()->second);
            $stockInNumber = MposStockMutation::generateNumber('IN', $dStockIn);
            $reason = $validated['creason'] ?: 'Penyesuaian Stok Masuk Manual';

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['nqty'];
                $cost = isset($item['ncost']) ? (float) $item['ncost'] : 0.0;
                $subtotal = $qty * $cost;

                $prodId = $item['citem_type'] === 'PRODUCT' ? (int) $item['nid_item'] : null;
                $ingId = $item['citem_type'] === 'INGREDIENT' ? (int) $item['nid_item'] : null;

                // Tambah stok fisik di outlet
                if ($item['citem_type'] === 'PRODUCT' && $prodId) {
                    $prod = MposProduct::lockForUpdate()->find($prodId);
                    if ($prod) {
                        $cost = $cost ?: (float) ($prod->nprice_buy ?: 0);
                        $subtotal = $qty * $cost;
                        $prod->nqty = (float) $prod->nqty + $qty;
                        if ($cost > 0 && empty($prod->nprice_buy)) {
                            $prod->nprice_buy = $cost;
                        }
                        $prod->save();
                    }
                } elseif ($item['citem_type'] === 'INGREDIENT' && $ingId) {
                    $ing = MposIngredients::lockForUpdate()->find($ingId);
                    if ($ing) {
                        $cost = $cost ?: (float) ($ing->nprice ?? 0);
                        $subtotal = $qty * $cost;
                        $ing->nstock = (float) $ing->nstock + $qty;
                        $ing->save();
                    }
                }

                // Catat mutasi IN di mpos_stock_mutations
                MposStockMutation::create([
                    'cno_ref' => $stockInNumber,
                    'dmutation' => $dStockIn,
                    'ctype' => MposStockMutation::TYPE_IN,
                    'nid_outlet' => $validated['nid_outlet'],
                    'nid_target_outlet' => null,
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
            }

            DB::commit();

            return redirect()->route('inventory.stock-in.show', $stockInNumber)
                ->with('success', "Stok Masuk {$stockInNumber} berhasil disimpan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal memproses stok masuk: ' . $e->getMessage())
                ->withInput();
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
            'targetOutlet' => $first->targetOutlet,
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
            'targetOutlet' => $first->targetOutlet,
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

            $isTransfer = ($validated['ctype'] === 'TRANSFER' && !empty($validated['nid_target_outlet']));
            $stockInNumber = $isTransfer ? MposStockMutation::generateNumber('IN', $dStockOut) : null;

            $originOutlet = MposOutlet::find($validated['nid_outlet']);
            $reason = $validated['creason'] ?: ($isTransfer ? 'Transfer Antar Toko' : 'Lain-lain');

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['nqty'];
                $cost = isset($item['ncost']) ? (float) $item['ncost'] : 0.0;
                $subtotal = $qty * $cost;

                $prodId = $item['citem_type'] === 'PRODUCT' ? (int) $item['nid_item'] : null;
                $ingId = $item['citem_type'] === 'INGREDIENT' ? (int) $item['nid_item'] : null;

                $targetProdId = null;
                $targetIngId = null;

                // Kurangi stok fisik di outlet asal
                if ($item['citem_type'] === 'PRODUCT' && $prodId) {
                    $prod = MposProduct::lockForUpdate()->find($prodId);
                    if ($prod) {
                        $cost = $cost ?: (float) ($prod->nprice_buy ?: 0);
                        $subtotal = $qty * $cost;
                        $prod->nqty = max(0, (float) $prod->nqty - $qty);
                        $prod->save();

                        // Jika transfer, tambah di outlet tujuan
                        if ($isTransfer) {
                            $targetProd = MposProduct::where('nid_outlet', $validated['nid_target_outlet'])
                                ->where('cname', $prod->cname)
                                ->first();

                            if ($targetProd) {
                                $targetProd->nqty = (float) $targetProd->nqty + $qty;
                                $targetProd->save();
                                $targetProdId = $targetProd->nid;
                            } else {
                                $newProduct = $prod->replicate();
                                $newProduct->nid_outlet = $validated['nid_target_outlet'];
                                $newProduct->nqty = $qty;
                                $newProduct->save();
                                $targetProdId = $newProduct->nid;
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
                        if ($isTransfer) {
                            $targetIng = MposIngredients::where('nid_outlet', $validated['nid_target_outlet'])
                                ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $ing->cname))])
                                ->first();

                            if ($targetIng) {
                                $targetIng->nstock = (float) $targetIng->nstock + $qty;
                                $targetIng->save();
                                $targetIngId = $targetIng->nid;
                            } else {
                                $newIngPayload = [
                                    'cname' => $ing->cname,
                                    'nid_outlet' => $validated['nid_target_outlet'],
                                    'csatuan' => $ing->csatuan ?: 'PCS',
                                    'nstock' => $qty,
                                ];
                                if (MposIngredients::hasPriceColumn()) {
                                    $newIngPayload['nprice'] = (float) ($ing->nprice ?: $cost);
                                }
                                $newIng = MposIngredients::create($newIngPayload);
                                $targetIngId = $newIng->nid;
                            }
                        }
                    }
                }

                // Catat mutasi OUT di outlet asal (OG...)
                MposStockMutation::create([
                    'cno_ref' => $stockOutNumber,
                    'dmutation' => $dStockOut,
                    'ctype' => MposStockMutation::TYPE_OUT,
                    'nid_outlet' => $validated['nid_outlet'],
                    'nid_target_outlet' => $isTransfer ? $validated['nid_target_outlet'] : null,
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

                // Jika transfer, catat mutasi IN tersendiri di outlet tujuan (IN...) yang mereferensikan OG...
                if ($isTransfer) {
                    $originOutletName = $originOutlet?->cname ?: 'Outlet Asal';
                    $targetNote = 'Transfer dari ' . $originOutletName . ' (' . $stockOutNumber . ')';
                    if (!empty($validated['cnote'])) {
                        $targetNote .= ' - ' . $validated['cnote'];
                    }

                    MposStockMutation::create([
                        'cno_ref' => $stockInNumber,
                        'dmutation' => $dStockOut,
                        'ctype' => MposStockMutation::TYPE_IN,
                        'nid_outlet' => $validated['nid_target_outlet'],
                        'nid_target_outlet' => $validated['nid_outlet'],
                        'nid_user' => Auth::id() ?? 1,
                        'citem_type' => $item['citem_type'],
                        'nid_product' => $targetProdId ?? $prodId,
                        'nid_ingredient' => $targetIngId ?? $ingId,
                        'nqty' => $qty,
                        'ncost' => $cost,
                        'nsubtotal' => $subtotal,
                        'creason' => 'Transfer Antar Toko',
                        'cstatus' => MposStockMutation::STATUS_POSTED,
                        'cnote' => $targetNote,
                        'dexpired' => !empty($item['dexpired']) ? $item['dexpired'] : null,
                        'crack' => $item['crack'] ?? null,
                        'dcreated' => Carbon::now(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('inventory.stock-out.show', $stockOutNumber)
                ->with('success', "Stok Keluar {$stockOutNumber} berhasil disimpan." . ($isTransfer ? " Dokumen Stok Masuk {$stockInNumber} juga telah otomatis dibuat di outlet tujuan." : ""));
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

    /**
     * Buat dokumen Stok Opname baru sekaligus input item produknya
     */
    public function storeStockOpname(Request $request)
    {
        $validated = $request->validate([
            'dopname' => 'required|date',
            'nid_outlet' => 'required|integer',
            'cnote' => 'nullable|string|max:1000',
            'action_type' => 'nullable|string|in:draft,post',
            'items' => 'nullable|array',
            'items.*.item_id' => 'nullable|integer',
            'items.*.citem_type' => 'nullable|string',
            'items.*.nqty_actual' => 'nullable|numeric|min:0',
            'items.*.crack' => 'nullable|string|max:100',
            'items.*.dexpired' => 'nullable|date',
            'items.*.cnote' => 'nullable|string|max:255',
        ]);

        $allowedOutletIds = $this->getUserAllowedOutletIds();
        if (!$this->isSuperAdminUser() && !in_array((int) $validated['nid_outlet'], $allowedOutletIds)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses ke outlet yang dipilih.')->withInput();
        }

        $dopname = Carbon::parse($validated['dopname']);
        $opnameNo = MposStockOpnameH::generateNumber($dopname);
        $status = MposStockOpnameH::STATUS_DRAFT;
        $isPosting = false;

        DB::beginTransaction();
        try {
            $opname = MposStockOpnameH::create([
                'cno_opname' => $opnameNo,
                'dopname' => $dopname->format('Y-m-d'),
                'nid_outlet' => $validated['nid_outlet'],
                'nid_user' => Auth::id() ?? 1,
                'cstatus' => $status,
                'cnote' => $validated['cnote'] ?? null,
                'ntotal_system_qty' => 0,
                'ntotal_actual_qty' => 0,
                'ntotal_diff_qty' => 0,
                'ntotal_diff_amount' => 0,
                'dposted' => null,
                'dcreated' => Carbon::now(),
            ]);

            $rawItems = $request->input('items', []);
            $itemsCount = 0;
            $mutationDate = $dopname->setTimeFrom(Carbon::now());

            foreach ($rawItems as $row) {
                $itemId = $row['item_id'] ?? ($row['nid_product'] ?? null);
                if (empty($itemId)) {
                    continue;
                }

                $rawType = strtoupper($row['citem_type'] ?? 'PRODUCT');
                $isIngredient = in_array($rawType, ['BAHAN_BAKU', 'INGREDIENT']);
                $citemType = $isIngredient ? MposStockMutation::ITEM_INGREDIENT : MposStockMutation::ITEM_PRODUCT;

                $systemQty = 0.0;
                $cost = 0.0;
                $prodId = null;
                $ingId = null;

                if ($isIngredient) {
                    $ing = MposIngredients::find($itemId);
                    if ($ing) {
                        $ingId = $ing->nid;
                        $systemQty = (float) $ing->nstock;
                        $cost = (float) ($ing->nprice ?? 0);
                    }
                } else {
                    $prod = MposProduct::find($itemId);
                    if ($prod) {
                        $prodId = $prod->nid;
                        $systemQty = (float) $prod->nqty;
                        $cost = (float) ($prod->nprice_buy ?? 0);
                    }
                }

                $actualQty = isset($row['nqty_actual']) && $row['nqty_actual'] !== '' ? (float) $row['nqty_actual'] : $systemQty;
                $diffQty = $actualQty - $systemQty;
                $subtotalDiff = $diffQty * $cost;

                $detail = MposStockOpnameD::create([
                    'nid_opname' => $opname->nid,
                    'citem_type' => $citemType,
                    'nid_product' => $prodId,
                    'nid_ingredient' => $ingId,
                    'nqty_system' => $systemQty,
                    'nqty_actual' => $actualQty,
                    'nqty_diff' => $diffQty,
                    'ncost' => $cost,
                    'nsubtotal_diff' => $subtotalDiff,
                    'dtime' => Carbon::now()->format('H:i:s'),
                    'dexpired' => !empty($row['dexpired']) ? $row['dexpired'] : null,
                    'crack' => $row['crack'] ?? null,
                    'cnote' => $row['cnote'] ?? null,
                    'dcreated' => Carbon::now(),
                ]);

                // Jika langsung diposting sekarang:
                if ($isPosting) {
                    if ($prodId) {
                        $p = MposProduct::lockForUpdate()->find($prodId);
                        if ($p) {
                            $p->nqty = $actualQty;
                            $p->save();
                        }
                    } elseif ($ingId) {
                        $i = MposIngredients::lockForUpdate()->find($ingId);
                        if ($i) {
                            $i->nstock = $actualQty;
                            $i->save();
                        }
                    }

                    if ($diffQty != 0) {
                        $isPositive = $diffQty > 0;
                        MposStockMutation::create([
                            'cno_ref' => $opname->cno_opname,
                            'dmutation' => $mutationDate,
                            'ctype' => $isPositive ? MposStockMutation::TYPE_IN : MposStockMutation::TYPE_OUT,
                            'nid_outlet' => $opname->nid_outlet,
                            'nid_target_outlet' => null,
                            'nid_user' => Auth::id() ?? $opname->nid_user,
                            'citem_type' => $citemType,
                            'nid_product' => $prodId,
                            'nid_ingredient' => $ingId,
                            'nqty' => abs($diffQty),
                            'ncost' => $cost,
                            'nsubtotal' => abs($subtotalDiff),
                            'creason' => $isPositive ? 'Penyesuaian Stok Opname (Lebih Fisik)' : 'Penyesuaian Stok Opname (Kurang Fisik)',
                            'cstatus' => MposStockMutation::STATUS_POSTED,
                            'cnote' => 'Stok Opname: ' . ($opname->cnote ?: $opname->cno_opname),
                            'dexpired' => $detail->dexpired,
                            'crack' => $detail->crack,
                            'dcreated' => Carbon::now(),
                        ]);
                    }
                }

                $itemsCount++;
            }

            $opname->recalculateTotals();
            DB::commit();

            return redirect()->route('inventory.stock-opname.show', $opname->nid)
                ->with('success', "Dokumen Stok Opname {$opname->cno_opname} berhasil disimpan dengan {$itemsCount} item produk. Silakan tinjau kembali sebelum diposting.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal membuat Stok Opname: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Tampilan Detail Stok Opname (sesuai Olsera)
     */
    public function showStockOpname(string $id)
    {
        $opname = MposStockOpnameH::with([
            'outlet',
            'user',
            'details' => function ($q) {
                $q->with(['product.category', 'ingredient']);
            }
        ])->where(function ($q) use ($id) {
            $q->where('nid', $id)
              ->orWhere('cno_opname', $id);
        })->firstOrFail();

        $allowedOutletIds = $this->getUserAllowedOutletIds();
        if (!$this->isSuperAdminUser() && !in_array((int) $opname->nid_outlet, $allowedOutletIds)) {
            abort(403, 'Anda tidak memiliki hak akses ke data outlet ini.');
        }

        // Ambil produk aktif di outlet untuk modal tambah produk
        $availableProducts = MposProduct::with('category')
            ->where('nid_outlet', $opname->nid_outlet)
            ->where('cstatus', 'ACTIVE')
            ->orderBy('cname')
            ->get();

        // Kategori produk untuk filter di modal
        $categories = MposGrpProduct::where(function ($q) use ($opname) {
            $q->where('nid_outlet', $opname->nid_outlet)
              ->orWhereNull('nid_outlet');
        })->orderBy('cname')->get();

        return view('inventory.stock_opname_show', compact('opname', 'availableProducts', 'categories'));
    }

    /**
     * Update Header Dokumen Stok Opname
     */
    public function updateStockOpname(Request $request, string $id)
    {
        $opname = MposStockOpnameH::where('nid', $id)->orWhere('cno_opname', $id)->firstOrFail();

        if (!$opname->isDraft()) {
            return redirect()->back()->with('error', 'Dokumen yang sudah diposting atau dibatalkan tidak dapat diubah.');
        }

        $validated = $request->validate([
            'dopname' => 'required|date',
            'cnote' => 'nullable|string|max:1000',
        ]);

        $opname->dopname = $validated['dopname'];
        $opname->cnote = $validated['cnote'];
        $opname->dupdated = Carbon::now();
        $opname->save();

        return redirect()->back()->with('success', 'Informasi Stok Opname berhasil diperbarui.');
    }

    /**
     * Hapus Dokumen Stok Opname (Hanya Draft/Cancelled)
     */
    public function destroyStockOpname(string $id)
    {
        $opname = MposStockOpnameH::where('nid', $id)->orWhere('cno_opname', $id)->firstOrFail();

        if ($opname->isPosted()) {
            return redirect()->back()->with('error', 'Dokumen yang telah diposting tidak dapat dihapus.');
        }

        $opname->details()->delete();
        $opname->delete();

        return redirect()->route('inventory.index', ['tab' => 'stok_opname'])
            ->with('success', "Dokumen Stok Opname {$opname->cno_opname} berhasil dihapus.");
    }

    /**
     * Tambah Produk ke Dokumen Stok Opname (dari Modal Olsera)
     */
    public function storeStockOpnameItems(Request $request, string $id)
    {
        $opname = MposStockOpnameH::where('nid', $id)->orWhere('cno_opname', $id)->firstOrFail();

        if (!$opname->isDraft()) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat ditambahkan produk.');
        }

        // Filter hanya item yang dicentang (memiliki nid_product valid)
        $rawItems = collect($request->input('items', []))->filter(function ($item) {
            return !empty($item['nid_product']);
        })->values()->all();

        $request->merge(['items' => $rawItems]);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.nid_product' => 'required|integer',
            'items.*.nqty_actual' => 'required|numeric|min:0',
            'items.*.dtime' => 'nullable|string',
            'items.*.dexpired' => 'nullable|date',
            'items.*.crack' => 'nullable|string|max:100',
            'items.*.cnote' => 'nullable|string|max:255',
        ], [
            'items.required' => 'Pilih minimal satu produk untuk ditambahkan ke stok opname.',
            'items.min' => 'Pilih minimal satu produk untuk ditambahkan ke stok opname.',
        ]);

        $prodIds = collect($validated['items'])->pluck('nid_product')->unique();
        $products = MposProduct::whereIn('nid', $prodIds)->get()->keyBy('nid');

        $addedCount = 0;
        foreach ($validated['items'] as $itemData) {
            $product = $products->get($itemData['nid_product']);
            if (!$product) {
                continue;
            }

            $systemQty = (float) $product->nqty;
            $actualQty = (float) $itemData['nqty_actual'];
            $diffQty = $actualQty - $systemQty;
            $cost = (float) ($product->nprice_buy ?: 0);
            $subtotalDiff = $diffQty * $cost;

            MposStockOpnameD::updateOrCreate(
                [
                    'nid_opname' => $opname->nid,
                    'nid_product' => $product->nid,
                ],
                [
                    'citem_type' => MposStockMutation::ITEM_PRODUCT,
                    'nid_ingredient' => null,
                    'nqty_system' => $systemQty,
                    'nqty_actual' => $actualQty,
                    'nqty_diff' => $diffQty,
                    'ncost' => $cost,
                    'nsubtotal_diff' => $subtotalDiff,
                    'dtime' => !empty($itemData['dtime']) ? $itemData['dtime'] : Carbon::now()->format('H:i:s'),
                    'dexpired' => !empty($itemData['dexpired']) ? $itemData['dexpired'] : null,
                    'crack' => $itemData['crack'] ?? null,
                    'cnote' => $itemData['cnote'] ?? null,
                    'dcreated' => Carbon::now(),
                ]
            );
            $addedCount++;
        }

        $opname->recalculateTotals();

        return redirect()->route('inventory.stock-opname.show', $opname->nid)
            ->with('success', "{$addedCount} produk berhasil ditambahkan ke Stok Opname.");
    }

    /**
     * Update 1 baris item produk dalam Stok Opname
     */
    public function updateStockOpnameItem(Request $request, string $id, string $itemId)
    {
        $opname = MposStockOpnameH::where('nid', $id)->orWhere('cno_opname', $id)->firstOrFail();

        if (!$opname->isDraft()) {
            return response()->json(['status' => 'error', 'message' => 'Dokumen sudah tidak berstatus Draf.'], 422);
        }

        $detail = MposStockOpnameD::where('nid', $itemId)->where('nid_opname', $opname->nid)->firstOrFail();

        $validated = $request->validate([
            'nqty_actual' => 'required|numeric|min:0',
            'dexpired' => 'nullable|date',
            'crack' => 'nullable|string|max:100',
            'cnote' => 'nullable|string|max:255',
            'dtime' => 'nullable|string',
        ]);

        $actualQty = (float) $validated['nqty_actual'];
        $systemQty = (float) $detail->nqty_system;
        $diffQty = $actualQty - $systemQty;
        $cost = (float) $detail->ncost;
        $subtotalDiff = $diffQty * $cost;

        $detail->nqty_actual = $actualQty;
        $detail->nqty_diff = $diffQty;
        $detail->nsubtotal_diff = $subtotalDiff;
        if (array_key_exists('dexpired', $validated)) {
            $detail->dexpired = $validated['dexpired'];
        }
        if (array_key_exists('crack', $validated)) {
            $detail->crack = $validated['crack'];
        }
        if (array_key_exists('cnote', $validated)) {
            $detail->cnote = $validated['cnote'];
        }
        if (array_key_exists('dtime', $validated)) {
            $detail->dtime = $validated['dtime'];
        }
        $detail->save();

        $opname->recalculateTotals();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Item berhasil diperbarui.',
                'item' => $detail,
            ]);
        }

        return redirect()->back()->with('success', 'Item berhasil diperbarui.');
    }

    /**
     * Hapus item dari dokumen Stok Opname
     */
    public function destroyStockOpnameItem(string $id, string $itemId)
    {
        $opname = MposStockOpnameH::where('nid', $id)->orWhere('cno_opname', $id)->firstOrFail();

        if (!$opname->isDraft()) {
            return redirect()->back()->with('error', 'Dokumen yang telah diposting tidak dapat diubah.');
        }

        MposStockOpnameD::where('nid', $itemId)->where('nid_opname', $opname->nid)->delete();
        $opname->recalculateTotals();

        return redirect()->back()->with('success', 'Produk berhasil dihapus dari Stok Opname.');
    }

    /**
     * Posting Dokumen Stok Opname (Finalisasi, Update Stok Produk & Catat Pergerakan Stok)
     */
    public function postStockOpname(Request $request, string $id)
    {
        $opname = MposStockOpnameH::with('details')->where('nid', $id)->orWhere('cno_opname', $id)->firstOrFail();

        if (!$opname->isDraft()) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat diposting.');
        }

        if ($opname->details->isEmpty()) {
            return redirect()->back()->with('error', 'Dokumen Stok Opname tidak memiliki item produk. Silakan tambahkan produk terlebih dahulu.');
        }

        DB::beginTransaction();
        try {
            $mutationDate = Carbon::parse($opname->dopname)->setTimeFrom(Carbon::now());

            foreach ($opname->details as $d) {
                // 1. Update Stok di Master Produk
                if ($d->nid_product) {
                    $prod = MposProduct::lockForUpdate()->find($d->nid_product);
                    if ($prod) {
                        $prod->nqty = $d->nqty_actual;
                        $prod->save();
                    }
                } elseif ($d->nid_ingredient) {
                    $ing = MposIngredients::lockForUpdate()->find($d->nid_ingredient);
                    if ($ing) {
                        $ing->nstock = $d->nqty_actual;
                        $ing->save();
                    }
                }

                // 2. Jika ada selisih, catat ke mpos_stock_mutations untuk pergerakan stok
                $diffQty = (float) $d->nqty_diff;
                if ($diffQty != 0) {
                    $isPositive = $diffQty > 0;
                    $mutationType = $isPositive ? MposStockMutation::TYPE_IN : MposStockMutation::TYPE_OUT;
                    $reason = $isPositive
                        ? 'Penyesuaian Stok Opname (Lebih Fisik)'
                        : 'Penyesuaian Stok Opname (Kurang Fisik)';
                    $absQty = abs($diffQty);
                    $subtotal = abs((float) $d->nsubtotal_diff);

                    $itemType = (!empty($d->nid_ingredient) || in_array(strtoupper((string) $d->citem_type), ['INGREDIENT', 'BAHAN_BAKU', 'BAHAN BAKU']))
                        ? MposStockMutation::ITEM_INGREDIENT
                        : MposStockMutation::ITEM_PRODUCT;

                    MposStockMutation::create([
                        'cno_ref' => $opname->cno_opname,
                        'dmutation' => $mutationDate,
                        'ctype' => $mutationType,
                        'nid_outlet' => $opname->nid_outlet,
                        'nid_target_outlet' => null,
                        'nid_user' => Auth::id() ?? $opname->nid_user,
                        'citem_type' => $itemType,
                        'nid_product' => $d->nid_product,
                        'nid_ingredient' => $d->nid_ingredient,
                        'nqty' => $absQty,
                        'ncost' => (float) $d->ncost,
                        'nsubtotal' => $subtotal,
                        'creason' => $reason,
                        'cstatus' => MposStockMutation::STATUS_POSTED,
                        'cnote' => 'Stok Opname: ' . ($opname->cnote ?: $opname->cno_opname) . ($d->cnote ? ' - ' . $d->cnote : ''),
                        'dexpired' => $d->dexpired,
                        'crack' => $d->crack,
                        'dcreated' => Carbon::now(),
                    ]);
                }
            }

            // 3. Update status header opname menjadi POSTED
            $opname->cstatus = MposStockOpnameH::STATUS_POSTED;
            $opname->dposted = Carbon::now();
            $opname->dupdated = Carbon::now();
            $opname->save();

            DB::commit();

            return redirect()->route('inventory.stock-opname.show', $opname->nid)
                ->with('success', "Stok Opname {$opname->cno_opname} berhasil diposting. Stok master telah disesuaikan dan selisih tercatat di Pergerakan Stok.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal memposting Stok Opname: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan Dokumen Stok Opname
     */
    public function cancelStockOpname(string $id)
    {
        $opname = MposStockOpnameH::where('nid', $id)->orWhere('cno_opname', $id)->firstOrFail();

        if ($opname->isPosted()) {
            return redirect()->back()->with('error', 'Dokumen yang telah diposting tidak dapat dibatalkan.');
        }

        $opname->cstatus = MposStockOpnameH::STATUS_CANCELLED;
        $opname->dupdated = Carbon::now();
        $opname->save();

        return redirect()->route('inventory.stock-opname.show', $opname->nid)
            ->with('success', "Stok Opname {$opname->cno_opname} telah dibatalkan.");
    }

    /**
     * Cetak Lembar Stok Opname
     */
    public function printStockOpname(string $id)
    {
        $opname = MposStockOpnameH::with([
            'outlet',
            'user',
            'details' => function ($q) {
                $q->with(['product.category', 'ingredient']);
            }
        ])->where('nid', $id)->orWhere('cno_opname', $id)->firstOrFail();

        return view('inventory.stock_opname_print', compact('opname'));
    }

    /**
     * Export Dokumen Stok Opname ke format Excel / CSV
     */
    public function exportStockOpnameExcel(string $id)
    {
        $opname = MposStockOpnameH::with([
            'outlet',
            'user',
            'details' => function ($q) {
                $q->with(['product.category', 'ingredient']);
            }
        ])->where('nid', $id)->orWhere('cno_opname', $id)->firstOrFail();

        $filename = "Stok_Opname_{$opname->cno_opname}_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($opname) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM agar rapi di Microsoft Excel
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['DOKUMEN STOK OPNAME']);
            fputcsv($handle, ['No. Opname', $opname->cno_opname]);
            fputcsv($handle, ['Tanggal', Carbon::parse($opname->dopname)->format('d-M-Y')]);
            fputcsv($handle, ['Outlet', $opname->outlet?->cname ?: '-']);
            fputcsv($handle, ['Petugas', $opname->user?->email ?: ($opname->user?->cname ?: '-')]);
            fputcsv($handle, ['Status', $opname->cstatus]);
            fputcsv($handle, ['Catatan', $opname->cnote ?: '-']);
            fputcsv($handle, []);

            fputcsv($handle, [
                'No',
                'Produk / Item',
                'Waktu',
                'Qty Sistem',
                'Qty Aktual',
                'Qty Selisih',
                'Harga Pokok (Rp)',
                'Selisih Nilai (Rp)',
                'Tgl. Kadaluwarsa',
                'Rack / Lokasi',
                'Catatan',
            ]);

            $no = 1;
            foreach ($opname->details as $d) {
                fputcsv($handle, [
                    $no++,
                    $d->item_name,
                    $d->dtime ?: '-',
                    $d->nqty_system,
                    $d->nqty_actual,
                    $d->nqty_diff,
                    $d->ncost,
                    $d->nsubtotal_diff,
                    $d->dexpired ? Carbon::parse($d->dexpired)->format('d-M-Y') : '-',
                    $d->crack ?: '-',
                    $d->cnote ?: '-',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTAL',
                '',
                '',
                $opname->ntotal_system_qty,
                $opname->ntotal_actual_qty,
                $opname->ntotal_diff_qty,
                '',
                $opname->ntotal_diff_amount,
                '',
                '',
                '',
            ]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
