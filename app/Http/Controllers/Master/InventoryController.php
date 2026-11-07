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
                        if ($recipe->ingredient) {
                            $outletRecProd = MposProduct::with('category')
                                ->where('nid_outlet', $r->nid_outlet)
                                ->whereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim((string) $recipe->ingredient->cname))])
                                ->first() ?: $recipe->ingredient;

                            $recProdId = $outletRecProd->nid;
                            if (!isset($productMovements[$recProdId])) {
                                $productMovements[$recProdId] = [
                                    'type' => MposStockMutation::normalizeItemType($outletRecProd->cproduct_type ?? 'BAHAN_BAKU'),
                                    'id' => $outletRecProd->nid,
                                    'name' => $outletRecProd->cname,
                                    'category_name' => $outletRecProd->category?->cname ?: 'Bahan Baku',
                                    'masuk' => 0.0,
                                    'penjualan' => 0.0,
                                    'pengembalian' => 0.0,
                                    'keluar' => 0.0,
                                    'sisa' => (float) $outletRecProd->nqty,
                                ];
                            }
                            $productMovements[$recProdId]['penjualan'] += ((float) $r->total_sales * (float) $recipe->nqty);
                            $productMovements[$recProdId]['pengembalian'] += ((float) $r->total_refund * (float) $recipe->nqty);
                        } elseif ($recipe->legacyIngredient) {
                            $outletIng = MposIngredients::where('nid_outlet', $r->nid_outlet)
                                ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $recipe->legacyIngredient->cname))])
                                ->first() ?: $recipe->legacyIngredient;

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
                return !empty($row->nid_product);
            })->pluck('nid_product')->filter()->unique()->values();
            $mutProds = MposProduct::with('category')->whereIn('nid', $mutProdIds)->get()->keyBy('nid');

            $mutIngIds = $mutationRows->filter(function ($row) {
                return empty($row->nid_product) && !empty($row->nid_ingredient);
            })->pluck('nid_ingredient')->filter()->unique()->values();
            $mutIngs = $mutIngIds->isNotEmpty()
                ? MposIngredients::whereIn('nid', $mutIngIds)->get()->keyBy('nid')
                : collect();

            foreach ($mutationRows as $r) {
                $qty = (float) $r->total_qty;

                if (!empty($r->nid_product)) {
                    $prod = $mutProds->get($r->nid_product);
                    if (!$prod) {
                        continue;
                    }
                    $normType = MposStockMutation::normalizeItemType($prod->cproduct_type ?? $r->citem_type);
                    $trackStock = $prod->category && isset($prod->category->ftrack_stock)
                        ? (bool) $prod->category->ftrack_stock
                        : true;

                    if ($trackStock || in_array($normType, [MposStockMutation::ITEM_SETENGAH_JADI, MposStockMutation::ITEM_BAHAN_BAKU], true)) {
                        $pId = $prod->nid;
                        if (!isset($productMovements[$pId])) {
                            $productMovements[$pId] = [
                                'type' => $normType,
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
                } elseif (!empty($r->nid_ingredient)) {
                    $ing = $mutIngs->get($r->nid_ingredient);
                    if (!$ing) {
                        continue;
                    }
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
                if (!empty($r->nid_product)) {
                    $prod = MposProduct::with('category')->find($r->nid_product);
                    if (!$prod) {
                        continue;
                    }
                    $normType = MposStockMutation::normalizeItemType($prod->cproduct_type ?? $r->citem_type);
                    $trackStock = $prod->category && isset($prod->category->ftrack_stock) ? (bool) $prod->category->ftrack_stock : true;
                    if ($trackStock || in_array($normType, [MposStockMutation::ITEM_SETENGAH_JADI, MposStockMutation::ITEM_BAHAN_BAKU], true)) {
                        $pId = $prod->nid;
                        if (!isset($productMovements[$pId])) {
                            $productMovements[$pId] = [
                                'type' => $normType,
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
                } elseif (!empty($r->nid_ingredient)) {
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

        // Produk, Setengah Jadi & Bahan Baku dari mpos_product untuk modal tambah stok masuk, keluar & stok opname
        if (in_array($activeTab, ['stok_masuk', 'stok_keluar', 'stok_opname'])) {
            $modalProductsQuery = MposProduct::where('cstatus', 'ACTIVE');
            if (!$isSuperAdmin) {
                $modalProductsQuery->whereIn('nid_outlet', $allowedOutletIds);
            }
            $prodCols = ['nid', 'cname', 'nid_category', 'nid_outlet', 'nprice', 'nprice_buy', 'nqty'];
            if (MposProduct::hasSatuanColumn()) {
                $prodCols[] = 'csatuan';
            }
            if (MposProduct::hasProductTypeColumn()) {
                $prodCols[] = 'cproduct_type';
            }
            $modalProducts = $modalProductsQuery->orderBy('cname')->get($prodCols);
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
     * Pastikan record produk tersedia pada outlet tujuan (cari nama sama di outlet tujuan atau gandakan beserta kategorinya).
     */
    protected function resolveOrCreateOutletProduct(MposProduct $sourceProduct, int $targetOutletId, ?float $fallbackCost = null): MposProduct
    {
        if ((int) $sourceProduct->nid_outlet === $targetOutletId) {
            return $sourceProduct;
        }

        $existingOutletProduct = MposProduct::lockForUpdate()
            ->where('nid_outlet', $targetOutletId)
            ->whereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim((string) $sourceProduct->cname))])
            ->first();

        if ($existingOutletProduct) {
            return $existingOutletProduct;
        }

        $targetCategoryId = $sourceProduct->nid_category;
        if (!empty($sourceProduct->nid_category)) {
            $sourceCategory = MposGrpProduct::find($sourceProduct->nid_category);
            if ($sourceCategory && !empty($sourceCategory->nid_outlet) && (int) $sourceCategory->nid_outlet !== $targetOutletId) {
                $matchedCategory = MposGrpProduct::where('nid_outlet', $targetOutletId)
                    ->whereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim((string) $sourceCategory->cname))])
                    ->first();

                if ($matchedCategory) {
                    $targetCategoryId = $matchedCategory->nid;
                } else {
                    $catPayload = [
                        'cname' => $sourceCategory->cname,
                        'nid_outlet' => $targetOutletId,
                        'ftrack_stock' => $sourceCategory->ftrack_stock ?? true,
                    ];
                    if (MposGrpProduct::hasShowPosColumn()) {
                        $catPayload['fshow_pos'] = $sourceCategory->fshow_pos ?? true;
                    }
                    $newCat = MposGrpProduct::create($catPayload);
                    $targetCategoryId = $newCat->nid;
                }
            }
        }

        $newProduct = $sourceProduct->replicate();
        $newProduct->nid_outlet = $targetOutletId;
        $newProduct->nid_category = $targetCategoryId;
        $newProduct->nqty = 0;
        if ($fallbackCost !== null && $fallbackCost > 0 && empty($newProduct->nprice_buy)) {
            $newProduct->nprice_buy = $fallbackCost;
        }
        $newProduct->save();

        return $newProduct;
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
                $targetProdId = $item->nid_product;
                $targetIngId = $item->nid_ingredient;
                $normalizedType = MposStockMutation::normalizeItemType($item->citem_type);

                if (!$item->isIngredient() && $item->product) {
                    $targetProd = $this->resolveOrCreateOutletProduct(
                        $item->product,
                        (int) $purchase->nid_outlet,
                        (float) $item->ncost
                    );
                    $targetProd->nqty = (float) $targetProd->nqty + (float) $item->nqty;
                    $targetProd->save();
                    $targetProdId = $targetProd->nid;
                    $targetIngId = null;
                } elseif ($item->isIngredient() && $item->ingredient) {
                    $ingredient = $item->ingredient;
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
                            $targetIngId = $outletIngredient->nid;
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
                            $createdIng = MposIngredients::create($newIngPayload);
                            $targetIngId = $createdIng->nid;
                        }
                    }
                }

                MposStockMutation::create([
                    'cno_ref' => $stockInNumber,
                    'dmutation' => Carbon::now(),
                    'ctype' => MposStockMutation::TYPE_IN,
                    'nid_outlet' => $purchase->nid_outlet,
                    'nid_user' => Auth::id() ?? 1,
                    'citem_type' => $normalizedType,
                    'nid_product' => $targetProdId,
                    'nid_ingredient' => $targetIngId,
                    'nqty' => (float) $item->nqty,
                    'ncost' => (float) $item->ncost,
                    'nsubtotal' => (float) $item->nsubtotal,
                    'creason' => 'Pembelian',
                    'cstatus' => MposStockMutation::STATUS_POSTED,
                    'cnote' => 'PO: ' . $purchase->cno_purchase . ($purchase->supplier ? ' (' . $purchase->supplier->cname . ')' : ''),
                    'dcreated' => Carbon::now(),
                ]);
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
            'items.*.citem_type' => 'required|string|in:PRODUK,SETENGAH_JADI,BAHAN_BAKU,PRODUCT,INGREDIENT',
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
            $reason = !empty($validated['creason']) ? $validated['creason'] : 'Penyesuaian Stok Masuk Manual';
            $targetOutletId = (int) $validated['nid_outlet'];

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['nqty'];
                $cost = isset($item['ncost']) ? (float) $item['ncost'] : 0.0;
                $selectedId = (int) $item['nid_item'];

                $sourceProduct = MposProduct::lockForUpdate()->find($selectedId);
                if (!$sourceProduct) {
                    throw new \RuntimeException('Item produk yang dipilih tidak ditemukan.');
                }

                $targetProduct = $this->resolveOrCreateOutletProduct($sourceProduct, $targetOutletId, $cost);
                $normalizedType = MposStockMutation::normalizeItemType($targetProduct->cproduct_type ?? $item['citem_type']);

                $cost = $cost > 0 ? $cost : (float) ($targetProduct->nprice_buy ?: 0);
                $subtotal = $qty * $cost;

                $targetProduct->nqty = (float) $targetProduct->nqty + $qty;
                if ($cost > 0 && empty($targetProduct->nprice_buy)) {
                    $targetProduct->nprice_buy = $cost;
                }
                $targetProduct->save();

                // Catat mutasi IN di mpos_stock_mutations
                MposStockMutation::create([
                    'cno_ref' => $stockInNumber,
                    'dmutation' => $dStockIn,
                    'ctype' => MposStockMutation::TYPE_IN,
                    'nid_outlet' => $targetOutletId,
                    'nid_target_outlet' => null,
                    'nid_user' => Auth::id() ?? 1,
                    'citem_type' => $normalizedType,
                    'nid_product' => $targetProduct->nid,
                    'nid_ingredient' => null,
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
        $items = MposStockMutation::with(['outlet', 'targetOutlet', 'user', 'product.category', 'ingredient'])
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
    /**
     * Simpan Dokumen Stok Keluar baru (Biasa / Transfer Antar Toko) sebagai DRAF
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
            'items.*.citem_type' => 'required|string|in:PRODUK,SETENGAH_JADI,BAHAN_BAKU,PRODUCT,INGREDIENT',
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
            $reason = !empty($validated['creason']) ? $validated['creason'] : ($isTransfer ? 'Transfer Antar Toko' : 'Lain-lain');
            $originOutletId = (int) $validated['nid_outlet'];

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['nqty'];
                $cost = isset($item['ncost']) ? (float) $item['ncost'] : 0.0;
                $selectedId = (int) $item['nid_item'];

                $sourceProduct = MposProduct::find($selectedId);
                if (!$sourceProduct) {
                    throw new \RuntimeException('Item produk yang dipilih tidak ditemukan.');
                }

                $originProduct = $this->resolveOrCreateOutletProduct($sourceProduct, $originOutletId, $cost);
                $normalizedType = MposStockMutation::normalizeItemType($originProduct->cproduct_type ?? $item['citem_type']);

                if ($cost <= 0) {
                    $cost = (float) ($originProduct->nprice_buy ?? 0);
                }
                $subtotal = $qty * $cost;

                // Selalu simpan awal sebagai DRAFT (stok fisik belum terpotong)
                MposStockMutation::create([
                    'cno_ref' => $stockOutNumber,
                    'dmutation' => $dStockOut,
                    'ctype' => MposStockMutation::TYPE_OUT,
                    'nid_outlet' => $originOutletId,
                    'nid_target_outlet' => $isTransfer ? $validated['nid_target_outlet'] : null,
                    'nid_user' => Auth::id() ?? 1,
                    'citem_type' => $normalizedType,
                    'nid_product' => $originProduct->nid,
                    'nid_ingredient' => null,
                    'nqty' => $qty,
                    'ncost' => $cost,
                    'nsubtotal' => $subtotal,
                    'creason' => $reason,
                    'cstatus' => MposStockMutation::STATUS_DRAFT,
                    'cnote' => $validated['cnote'] ?? null,
                    'dexpired' => !empty($item['dexpired']) ? $item['dexpired'] : null,
                    'crack' => $item['crack'] ?? null,
                    'dcreated' => Carbon::now(),
                    'dupdated' => Carbon::now(),
                ]);
            }

            DB::commit();

            return redirect()->route('inventory.stock-out.show', $stockOutNumber)
                ->with('success', "Stok Keluar {$stockOutNumber} berhasil disimpan sebagai Draf. Silakan periksa kembali sebelum diposting.");
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
        $items = MposStockMutation::with(['outlet', 'targetOutlet', 'user', 'product.category', 'ingredient'])
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

        // Attach live current stock for each detail item
        foreach ($items as $item) {
            if ($item->isIngredient()) {
                $item->current_stock = (float) ($item->ingredient?->nstock ?? 0);
            } else {
                $item->current_stock = (float) ($item->product?->nqty ?? 0);
            }
        }

        $availableProducts = MposProduct::with('category')
            ->where(function ($q) use ($first) {
                $q->where('nid_outlet', $first->nid_outlet)
                    ->orWhereNull('nid_outlet');
            })
            ->where('cstatus', 'ACTIVE')
            ->orderBy('cname')
            ->get();

        $availableIngredients = collect();

        $stockOut = (object) [
            'nid' => $first->nid,
            'cno_stock_out' => $first->cno_ref,
            'dstock_out' => $first->dmutation,
            'creason' => $first->creason,
            'cstatus' => $first->cstatus,
            'cnote' => $first->cnote,
            'nid_outlet' => $first->nid_outlet,
            'nid_target_outlet' => $first->nid_target_outlet,
            'outlet' => $first->outlet,
            'targetOutlet' => $first->targetOutlet,
            'user' => $first->user,
            'details' => $items,
            'ntotal_qty' => $items->sum('nqty'),
            'ntotal_cost' => $items->sum('nsubtotal'),
            'isDraft' => (strtoupper((string) $first->cstatus) === MposStockMutation::STATUS_DRAFT),
            'isPosted' => (strtoupper((string) $first->cstatus) === MposStockMutation::STATUS_POSTED),
            'isCancelled' => in_array(strtoupper((string) $first->cstatus), [MposStockMutation::STATUS_CANCELLED, MposStockMutation::STATUS_VOID], true),
        ];

        return view('inventory.stock_out_show', compact('stockOut', 'availableProducts', 'availableIngredients'));
    }

    /**
     * Posting Dokumen Stok Keluar (Potong Stok Fisik & Transfer ke Tujuan jika ada)
     */
    public function postStockOut(Request $request, string $id)
    {
        $items = MposStockMutation::with(['outlet', 'targetOutlet', 'product', 'ingredient'])
            ->where('ctype', MposStockMutation::TYPE_OUT)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)
                    ->orWhere('nid', $id);
            })
            ->get();

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Dokumen Stok Keluar tidak ditemukan.');
        }

        $first = $items->first();
        $stockOutNumber = $first->cno_ref;

        if (strtoupper((string) $first->cstatus) === MposStockMutation::STATUS_POSTED) {
            return redirect()->back()->with('error', 'Dokumen ini sudah pernah diposting.');
        }

        if (in_array(strtoupper((string) $first->cstatus), [MposStockMutation::STATUS_CANCELLED, MposStockMutation::STATUS_VOID], true)) {
            return redirect()->back()->with('error', 'Dokumen yang telah dibatalkan tidak dapat diposting.');
        }

        DB::beginTransaction();
        try {
            $isTransfer = !empty($first->nid_target_outlet);
            $stockInNumber = $isTransfer ? MposStockMutation::generateNumber('IN', $first->dmutation) : null;
            $originOutlet = $first->outlet;

            foreach ($items as $item) {
                $qty = (float) $item->nqty;
                $cost = (float) $item->ncost;
                $subtotal = (float) $item->nsubtotal;

                $targetProdId = null;
                $targetIngId = null;
                $normalizedType = $item->getItemTypeNormalized();

                // 1. Kurangi stok fisik di outlet asal
                if (!$item->isIngredient() && $item->nid_product) {
                    $prod = MposProduct::lockForUpdate()->find($item->nid_product);
                    if ($prod) {
                        $prod->nqty = max(0, (float) $prod->nqty - $qty);
                        $prod->save();

                        // Jika transfer, tambah di outlet tujuan
                        if ($isTransfer) {
                            $targetProd = $this->resolveOrCreateOutletProduct($prod, (int) $first->nid_target_outlet, $cost);
                            $targetProd->nqty = (float) $targetProd->nqty + $qty;
                            $targetProd->save();
                            $targetProdId = $targetProd->nid;
                        }
                    }
                } elseif ($item->isIngredient() && $item->nid_ingredient) {
                    $ing = MposIngredients::lockForUpdate()->find($item->nid_ingredient);
                    if ($ing) {
                        $ing->nstock = max(0, (float) $ing->nstock - $qty);
                        $ing->save();

                        // Jika transfer, tambah di outlet tujuan
                        if ($isTransfer) {
                            $targetIng = MposIngredients::where('nid_outlet', $first->nid_target_outlet)
                                ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $ing->cname))])
                                ->first();

                            if ($targetIng) {
                                $targetIng->nstock = (float) $targetIng->nstock + $qty;
                                $targetIng->save();
                                $targetIngId = $targetIng->nid;
                            } else {
                                $newIngPayload = [
                                    'cname' => $ing->cname,
                                    'nid_outlet' => $first->nid_target_outlet,
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

                // Update status item OUT menjadi POSTED
                $item->cstatus = MposStockMutation::STATUS_POSTED;
                $item->dupdated = Carbon::now();
                $item->save();

                // Jika transfer, buat mutasi IN untuk outlet tujuan
                if ($isTransfer) {
                    $originOutletName = $originOutlet?->cname ?: 'Outlet Asal';
                    $targetNote = 'Transfer dari ' . $originOutletName . ' (' . $stockOutNumber . ')';
                    if (!empty($first->cnote)) {
                        $targetNote .= ' - ' . $first->cnote;
                    }

                    MposStockMutation::create([
                        'cno_ref' => $stockInNumber,
                        'dmutation' => $first->dmutation ?? Carbon::now(),
                        'ctype' => MposStockMutation::TYPE_IN,
                        'nid_outlet' => $first->nid_target_outlet,
                        'nid_target_outlet' => $first->nid_outlet,
                        'nid_user' => Auth::id() ?? 1,
                        'citem_type' => $normalizedType,
                        'nid_product' => $targetProdId ?? $item->nid_product,
                        'nid_ingredient' => $targetIngId ?? $item->nid_ingredient,
                        'nqty' => $qty,
                        'ncost' => $cost,
                        'nsubtotal' => $subtotal,
                        'creason' => 'Transfer Antar Toko',
                        'cstatus' => MposStockMutation::STATUS_POSTED,
                        'cnote' => $targetNote,
                        'dexpired' => $item->dexpired,
                        'crack' => $item->crack,
                        'dcreated' => Carbon::now(),
                        'dupdated' => Carbon::now(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('inventory.stock-out.show', $stockOutNumber)
                ->with('success', "Stok Keluar {$stockOutNumber} berhasil diposting. Stok fisik telah dipotong." . ($isTransfer ? " Dokumen Stok Masuk {$stockInNumber} juga telah otomatis dibuat di outlet tujuan." : ""));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memposting stok keluar: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan Dokumen Stok Keluar (Revert Stok jika sudah POSTED, atau batalkan DRAFT)
     */
    public function cancelStockOut(Request $request, string $id)
    {
        $items = MposStockMutation::where('ctype', MposStockMutation::TYPE_OUT)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)
                    ->orWhere('nid', $id);
            })
            ->get();

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Dokumen Stok Keluar tidak ditemukan.');
        }

        $first = $items->first();
        $stockOutNumber = $first->cno_ref;

        if (in_array(strtoupper((string) $first->cstatus), [MposStockMutation::STATUS_CANCELLED, MposStockMutation::STATUS_VOID], true)) {
            return redirect()->back()->with('error', 'Dokumen ini sudah dibatalkan sebelumnya.');
        }

        DB::beginTransaction();
        try {
            $wasPosted = (strtoupper((string) $first->cstatus) === MposStockMutation::STATUS_POSTED);
            $isTransfer = !empty($first->nid_target_outlet);

            if ($wasPosted) {
                // Kembalikan stok fisik jika sebelumnya sudah terposting
                foreach ($items as $item) {
                    $qty = (float) $item->nqty;

                    // 1. Kembalikan stok ke outlet asal
                    if (!$item->isIngredient() && $item->nid_product) {
                        $prod = MposProduct::lockForUpdate()->find($item->nid_product);
                        if ($prod) {
                            $prod->nqty = (float) $prod->nqty + $qty;
                            $prod->save();
                        }
                    } elseif ($item->isIngredient() && $item->nid_ingredient) {
                        $ing = MposIngredients::lockForUpdate()->find($item->nid_ingredient);
                        if ($ing) {
                            $ing->nstock = (float) $ing->nstock + $qty;
                            $ing->save();
                        }
                    }

                    // 2. Jika transfer, kurangi stok dari outlet tujuan
                    if ($isTransfer) {
                        if (!$item->isIngredient() && $item->nid_product) {
                            $origProd = MposProduct::find($item->nid_product);
                            $targetProd = MposProduct::lockForUpdate()
                                ->where('nid_outlet', $first->nid_target_outlet)
                                ->whereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim((string) ($origProd?->cname ?? '')))])
                                ->first();
                            if ($targetProd) {
                                $targetProd->nqty = max(0, (float) $targetProd->nqty - $qty);
                                $targetProd->save();
                            }
                        } elseif ($item->isIngredient() && $item->nid_ingredient) {
                            $origIng = MposIngredients::find($item->nid_ingredient);
                            $targetIng = MposIngredients::lockForUpdate()
                                ->where('nid_outlet', $first->nid_target_outlet)
                                ->whereRaw('LOWER(cname) = ?', [strtolower(trim((string) ($origIng?->cname ?? '')))])
                                ->first();
                            if ($targetIng) {
                                $targetIng->nstock = max(0, (float) $targetIng->nstock - $qty);
                                $targetIng->save();
                            }
                        }
                    }
                }

                // 3. Batalkan mutasi IN terkait pada outlet tujuan jika transfer
                if ($isTransfer) {
                    MposStockMutation::where('ctype', MposStockMutation::TYPE_IN)
                        ->where('nid_outlet', $first->nid_target_outlet)
                        ->where('cnote', 'LIKE', '%' . $stockOutNumber . '%')
                        ->update([
                            'cstatus' => MposStockMutation::STATUS_CANCELLED,
                            'dupdated' => Carbon::now(),
                        ]);
                }
            }

            // Update semua baris OUT menjadi CANCELLED
            foreach ($items as $item) {
                $item->cstatus = MposStockMutation::STATUS_CANCELLED;
                $item->dupdated = Carbon::now();
                $item->save();
            }

            DB::commit();

            $msg = "Dokumen Stok Keluar {$stockOutNumber} telah dibatalkan.";
            if ($wasPosted) {
                $msg .= " Stok fisik telah berhasil dikembalikan ke outlet asal" . ($isTransfer ? " dan dikoreksi dari outlet tujuan." : ".");
            }

            return redirect()->route('inventory.stock-out.show', $stockOutNumber)->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Update Informasi Dokumen Stok Keluar (Tanggal, Alasan, Catatan)
     */
    public function updateStockOutHeader(Request $request, string $id)
    {
        $items = MposStockMutation::where('ctype', MposStockMutation::TYPE_OUT)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)->orWhere('nid', $id);
            })
            ->get();

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Dokumen Stok Keluar tidak ditemukan.');
        }

        $first = $items->first();
        if (strtoupper((string) $first->cstatus) !== MposStockMutation::STATUS_DRAFT) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat diubah.');
        }

        $validated = $request->validate([
            'dstock_out' => 'required|date',
            'creason' => 'nullable|string|max:100',
            'cnote' => 'nullable|string|max:500',
        ]);

        $dStockOut = Carbon::parse($validated['dstock_out'])->setTime(
            $first->dmutation ? $first->dmutation->hour : Carbon::now()->hour,
            $first->dmutation ? $first->dmutation->minute : Carbon::now()->minute,
            $first->dmutation ? $first->dmutation->second : Carbon::now()->second
        );

        foreach ($items as $item) {
            $item->dmutation = $dStockOut;
            if (isset($validated['creason'])) {
                $item->creason = $validated['creason'];
            }
            $item->cnote = $validated['cnote'] ?? null;
            $item->dupdated = Carbon::now();
            $item->save();
        }

        return redirect()->route('inventory.stock-out.show', $first->cno_ref)
            ->with('success', 'Informasi dokumen stok keluar berhasil diperbarui.');
    }

    /**
     * Tambah Item Barang ke Dokumen Stok Keluar (Draf)
     */
    public function addStockOutItem(Request $request, string $id)
    {
        $items = MposStockMutation::where('ctype', MposStockMutation::TYPE_OUT)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)->orWhere('nid', $id);
            })
            ->get();

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Dokumen Stok Keluar tidak ditemukan.');
        }

        $first = $items->first();
        if (strtoupper((string) $first->cstatus) !== MposStockMutation::STATUS_DRAFT) {
            return redirect()->back()->with('error', 'Item hanya dapat ditambahkan pada dokumen berstatus Draf.');
        }

        $validated = $request->validate([
            'citem_type' => 'required|string|in:PRODUK,SETENGAH_JADI,BAHAN_BAKU,PRODUCT,INGREDIENT',
            'nid_item' => 'required|integer',
            'nqty' => 'required|numeric|min:0.01',
            'ncost' => 'nullable|numeric|min:0',
            'crack' => 'nullable|string|max:100',
            'dexpired' => 'nullable|date',
        ]);

        $sourceProduct = MposProduct::find((int) $validated['nid_item']);
        if (!$sourceProduct) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Item produk tidak ditemukan.'], 422);
            }

            return redirect()->back()->with('error', 'Item produk tidak ditemukan.');
        }

        $originProduct = $this->resolveOrCreateOutletProduct($sourceProduct, (int) $first->nid_outlet);
        $prodId = $originProduct->nid;
        $normalizedType = MposStockMutation::normalizeItemType($originProduct->cproduct_type ?? $validated['citem_type']);

        $cost = isset($validated['ncost']) ? (float) $validated['ncost'] : 0.0;
        if ($cost <= 0) {
            $cost = (float) ($originProduct->nprice_buy ?? 0);
        }

        $qty = (float) $validated['nqty'];
        $subtotal = $qty * $cost;

        // Cek jika item sudah ada di draft ini, update qty-nya
        $existing = $items->first(function ($it) use ($prodId) {
            return $prodId && (int) $it->nid_product === (int) $prodId;
        });

        if ($existing) {
            $existing->nqty = (float) $existing->nqty + $qty;
            $existing->nsubtotal = (float) $existing->nqty * (float) $existing->ncost;
            $existing->citem_type = $normalizedType;
            $existing->dupdated = Carbon::now();
            $existing->save();
        } else {
            MposStockMutation::create([
                'cno_ref' => $first->cno_ref,
                'dmutation' => $first->dmutation ?? Carbon::now(),
                'ctype' => MposStockMutation::TYPE_OUT,
                'nid_outlet' => $first->nid_outlet,
                'nid_target_outlet' => $first->nid_target_outlet,
                'nid_user' => Auth::id() ?? 1,
                'citem_type' => $normalizedType,
                'nid_product' => $prodId,
                'nid_ingredient' => null,
                'nqty' => $qty,
                'ncost' => $cost,
                'nsubtotal' => $subtotal,
                'creason' => $first->creason,
                'cstatus' => MposStockMutation::STATUS_DRAFT,
                'cnote' => $first->cnote,
                'dexpired' => $validated['dexpired'] ?? null,
                'crack' => $validated['crack'] ?? null,
                'dcreated' => Carbon::now(),
                'dupdated' => Carbon::now(),
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Item berhasil ditambahkan ke draf stok keluar.',
            ]);
        }

        return redirect()->route('inventory.stock-out.show', $first->cno_ref)
            ->with('success', 'Item berhasil ditambahkan ke draf stok keluar.');
    }

    /**
     * Update Kuantitas Item pada Dokumen Stok Keluar (Draf)
     */
    public function updateStockOutItem(Request $request, string $id, string $itemId)
    {
        $item = MposStockMutation::where('nid', $itemId)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)->orWhere('nid', $id);
            })
            ->firstOrFail();

        if (strtoupper((string) $item->cstatus) !== MposStockMutation::STATUS_DRAFT) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Hanya item berstatus Draf yang dapat diubah.'], 422);
            }
            return redirect()->back()->with('error', 'Hanya item berstatus Draf yang dapat diubah.');
        }

        $validated = $request->validate([
            'nqty' => 'required|numeric|min:0.01',
        ]);

        $item->nqty = (float) $validated['nqty'];
        $item->nsubtotal = (float) $item->nqty * (float) $item->ncost;
        $item->dupdated = Carbon::now();
        $item->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Qty berhasil diperbarui.',
                'nqty' => $item->nqty,
                'nsubtotal' => $item->nsubtotal,
            ]);
        }

        return redirect()->route('inventory.stock-out.show', $item->cno_ref)
            ->with('success', 'Kuantitas item berhasil diperbarui.');
    }

    /**
     * Hapus Item dari Dokumen Stok Keluar (Draf)
     */
    public function destroyStockOutItem(string $id, string $itemId)
    {
        $item = MposStockMutation::where('nid', $itemId)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)->orWhere('nid', $id);
            })
            ->firstOrFail();

        if (strtoupper((string) $item->cstatus) !== MposStockMutation::STATUS_DRAFT) {
            return redirect()->back()->with('error', 'Hanya item pada draf yang dapat dihapus.');
        }

        $totalCount = MposStockMutation::where('cno_ref', $item->cno_ref)->where('ctype', MposStockMutation::TYPE_OUT)->count();
        if ($totalCount <= 1) {
            return redirect()->back()->with('error', 'Dokumen stok keluar harus memiliki minimal 1 item. Jika tidak diperlukan, silakan batalkan seluruh transaksi.');
        }

        $cnoRef = $item->cno_ref;
        $item->delete();

        return redirect()->route('inventory.stock-out.show', $cnoRef)
            ->with('success', 'Item berhasil dihapus dari draf stok keluar.');
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

                $sourceProduct = MposProduct::find((int) $itemId);
                if (!$sourceProduct) {
                    continue;
                }

                $prod = $this->resolveOrCreateOutletProduct($sourceProduct, (int) $validated['nid_outlet']);
                $prodId = $prod->nid;
                $ingId = null;
                $citemType = MposStockMutation::normalizeItemType($prod->cproduct_type ?? ($row['citem_type'] ?? 'PRODUK'));
                $systemQty = (float) $prod->nqty;
                $cost = (float) ($prod->nprice_buy ?? 0);

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

        // Sinkronkan stok sistem secara real-time jika dokumen masih draf
        if ($opname->isDraft()) {
            $this->syncDraftOpnameSystemStock($opname);
            $opname->load(['details.product.category', 'details.ingredient']);
        }

        // Ambil produk aktif di outlet untuk modal tambah produk
        $availableProducts = MposProduct::with('category')
            ->where(function ($q) use ($opname) {
                $q->where('nid_outlet', $opname->nid_outlet)
                    ->orWhereNull('nid_outlet');
            })
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
                    'citem_type' => MposStockMutation::normalizeItemType($product->cproduct_type ?? 'PRODUK'),
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

        // Cek stok sistem dan biaya beli terkini dari master
        if ($detail->nid_product) {
            $prod = MposProduct::find($detail->nid_product);
            if ($prod) {
                $detail->nqty_system = (float) $prod->nqty;
                if ((float) $detail->ncost <= 0 && (float) $prod->nprice_buy > 0) {
                    $detail->ncost = (float) $prod->nprice_buy;
                }
            }
        } elseif ($detail->nid_ingredient) {
            $ing = MposIngredients::find($detail->nid_ingredient);
            if ($ing) {
                $detail->nqty_system = (float) $ing->nstock;
                if ((float) $detail->ncost <= 0 && (float) $ing->ncost > 0) {
                    $detail->ncost = (float) $ing->ncost;
                }
            }
        }

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
     * Sinkronkan nqty_system pada dokumen Stok Opname draf dengan stok master produk/bahan terkini
     */
    protected function syncDraftOpnameSystemStock(MposStockOpnameH $opname): int
    {
        if (!$opname->isDraft()) {
            return 0;
        }

        $details = $opname->details;
        if ($details->isEmpty()) {
            return 0;
        }

        $productIds = $details->whereNotNull('nid_product')->pluck('nid_product')->unique();
        $products = $productIds->isNotEmpty()
            ? MposProduct::whereIn('nid', $productIds)->get()->keyBy('nid')
            : collect();

        $ingredientIds = $details->whereNotNull('nid_ingredient')->pluck('nid_ingredient')->unique();
        $ingredients = $ingredientIds->isNotEmpty()
            ? MposIngredients::whereIn('nid', $ingredientIds)->get()->keyBy('nid')
            : collect();

        $updatedCount = 0;
        foreach ($details as $d) {
            $currentSystemQty = null;
            $currentCost = (float) $d->ncost;

            if ($d->nid_product && $products->has($d->nid_product)) {
                $prod = $products->get($d->nid_product);
                $currentSystemQty = (float) ($prod->nqty ?? 0);
                if ($currentCost <= 0 && (float) ($prod->nprice_buy ?? 0) > 0) {
                    $currentCost = (float) $prod->nprice_buy;
                }
            } elseif ($d->nid_ingredient && $ingredients->has($d->nid_ingredient)) {
                $ing = $ingredients->get($d->nid_ingredient);
                $currentSystemQty = (float) ($ing->nstock ?? 0);
                if ($currentCost <= 0 && (float) ($ing->ncost ?? 0) > 0) {
                    $currentCost = (float) $ing->ncost;
                }
            }

            if ($currentSystemQty !== null) {
                $actualQty = (float) $d->nqty_actual;
                $diffQty = $actualQty - $currentSystemQty;
                $subtotalDiff = $diffQty * $currentCost;

                if (
                    (float) $d->nqty_system !== $currentSystemQty ||
                    (float) $d->nqty_diff !== $diffQty ||
                    (float) $d->ncost !== $currentCost ||
                    (float) $d->nsubtotal_diff !== $subtotalDiff
                ) {
                    $d->nqty_system = $currentSystemQty;
                    $d->nqty_diff = $diffQty;
                    $d->ncost = $currentCost;
                    $d->nsubtotal_diff = $subtotalDiff;
                    $d->save();
                    $updatedCount++;
                }
            }
        }

        if ($updatedCount > 0) {
            $opname->recalculateTotals();
        }

        return $updatedCount;
    }

    /**
     * Endpoint untuk trigger manual sinkronisasi stok sistem dokumen draf
     */
    public function syncStockOpnameSystemStock(string $id)
    {
        $opname = MposStockOpnameH::with('details')->where('nid', $id)->orWhere('cno_opname', $id)->firstOrFail();

        if (!$opname->isDraft()) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat disinkronkan stok sistemnya.');
        }

        $count = $this->syncDraftOpnameSystemStock($opname);

        return redirect()->back()->with('success', 'Stok sistem berhasil disinkronkan secara real-time dengan stok master terkini' . ($count > 0 ? " ({$count} item diperbarui)." : '. Semua kuantitas sudah mutakhir.'));
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
                $liveSystemQty = (float) $d->nqty_system;
                $cost = (float) $d->ncost;

                // 1. Ambil stok terkini dengan lockForUpdate untuk mencegah race condition
                if ($d->nid_product) {
                    $prod = MposProduct::lockForUpdate()->find($d->nid_product);
                    if ($prod) {
                        $liveSystemQty = (float) $prod->nqty;
                        if ($cost <= 0 && (float) $prod->nprice_buy > 0) {
                            $cost = (float) $prod->nprice_buy;
                        }
                        $prod->nqty = $d->nqty_actual;
                        $prod->save();
                    }
                } elseif ($d->nid_ingredient) {
                    $ing = MposIngredients::lockForUpdate()->find($d->nid_ingredient);
                    if ($ing) {
                        $liveSystemQty = (float) $ing->nstock;
                        if ($cost <= 0 && (float) $ing->ncost > 0) {
                            $cost = (float) $ing->ncost;
                        }
                        $ing->nstock = $d->nqty_actual;
                        $ing->save();
                    }
                }

                // 2. Hitung selisih real-time terhadap stok sistem saat posting
                $actualQty = (float) $d->nqty_actual;
                $diffQty = $actualQty - $liveSystemQty;
                $subtotalDiff = $diffQty * $cost;

                $d->nqty_system = $liveSystemQty;
                $d->nqty_diff = $diffQty;
                $d->ncost = $cost;
                $d->nsubtotal_diff = $subtotalDiff;
                $d->save();

                // 3. Jika ada selisih, catat ke mpos_stock_mutations untuk pergerakan stok
                if ($diffQty != 0) {
                    $isPositive = $diffQty > 0;
                    $mutationType = $isPositive ? MposStockMutation::TYPE_IN : MposStockMutation::TYPE_OUT;
                    $reason = $isPositive
                        ? 'Penyesuaian Stok Opname (Lebih Fisik)'
                        : 'Penyesuaian Stok Opname (Kurang Fisik)';
                    $absQty = abs($diffQty);
                    $subtotal = abs($subtotalDiff);

                    $itemType = $d->isIngredient()
                        ? MposStockMutation::ITEM_INGREDIENT
                        : $d->getItemTypeNormalized();

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
                        'ncost' => $cost,
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

            // 4. Hitung ulang total header setelah semua selisih sinkron
            $opname->recalculateTotals();

            // 5. Update status header opname menjadi POSTED
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

        if ($opname->isDraft()) {
            $this->syncDraftOpnameSystemStock($opname);
            $opname->load(['details.product.category', 'details.ingredient']);
        }

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

        if ($opname->isDraft()) {
            $this->syncDraftOpnameSystemStock($opname);
            $opname->load(['details.product.category', 'details.ingredient']);
        }

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
