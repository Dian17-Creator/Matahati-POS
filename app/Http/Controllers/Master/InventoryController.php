<?php

namespace App\Http\Controllers\Master;

use App\Exports\StockMovementExport;
use App\Exports\StockOpnameDetailExport;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
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
use App\Models\MposProductionH;
use App\Models\MposProductionProduct;
use App\Models\MposProductionMaterial;
use App\Models\MposRecipe;
use App\Traits\ResolvesOutletScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
        $productions = null;
        $totalProduction = 0;
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
                $row->user = $usersMap->get($row->nid_user);
                $row->cno_stock_in = $row->cno_ref;
                $row->dstock_in = $row->dmutation;
                $row->creceived_by = $row->user?->cemail ?: ($row->user?->email ?: ($row->user?->cname ?: 'Admin'));
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
        } elseif ($activeTab === 'produksi_stok') {
            $productionQuery = MposProductionH::with(['outlet', 'user'])->withCount(['products', 'materials']);

            if (!empty($selectedOutletId)) {
                $productionQuery->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $productionQuery->whereIn('nid_outlet', $allowedOutletIds);
            }

            if (!empty($startDate) && !empty($endDate)) {
                $productionQuery->whereBetween('dproduction', [$startDate, $endDate]);
            }

            if (!empty($search)) {
                $productionQuery->where(function ($q) use ($search) {
                    $q->where('cno_production', 'like', "%{$search}%")
                        ->orWhere('cnote', 'like', "%{$search}%");
                });
            }

            $productions = $productionQuery->orderBy('dproduction', 'desc')
                ->orderBy('nid', 'desc')
                ->paginate($perPage)
                ->withQueryString();
            $totalProduction = $productions->total();
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
            $allItems = $this->calculateStockMovementData(
                $startDate,
                $endDate,
                $selectedOutletId,
                $search,
                $isSuperAdmin,
                $allowedOutletIds
            );

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

        // Produk, Setengah Jadi & Bahan Baku dari mpos_product untuk modal tambah stok masuk, keluar, stok opname & produksi stok
        $modalRecipes = collect();
        if (in_array($activeTab, ['stok_masuk', 'stok_keluar', 'stok_opname', 'produksi_stok'])) {
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

            if ($activeTab === 'produksi_stok') {
                $modalRecipes = MposRecipe::with(['ingredient' => function ($q) {
                    $cols = ['nid', 'cname', 'nprice_buy', 'nqty'];
                    if (MposProduct::hasSatuanColumn()) {
                        $cols[] = 'csatuan';
                    }
                    if (MposProduct::hasProductTypeColumn()) {
                        $cols[] = 'cproduct_type';
                    }
                    $q->select($cols);
                }])
                ->get()
                ->groupBy('nid_product');
            }
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
            'productions',
            'totalProduction',
            'modalProducts',
            'modalIngredients',
            'modalRecipes'
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
     * Simpan Dokumen Stok Masuk baru secara manual (tanpa PO) sebagai DRAF
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
            $reason = !empty($validated['creason']) ? $validated['creason'] : 'Koreksi Selisih Stok (Adjustment)';
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

                // Simpan awal sebagai DRAFT (stok fisik belum bertambah sampai diposting di halaman detail)
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
                    'cstatus' => MposStockMutation::STATUS_DRAFT,
                    'cnote' => $validated['cnote'] ?? null,
                    'dexpired' => !empty($item['dexpired']) ? $item['dexpired'] : null,
                    'crack' => $item['crack'] ?? null,
                    'dcreated' => Carbon::now(),
                    'dupdated' => Carbon::now(),
                ]);
            }

            DB::commit();

            return redirect()->route('inventory.stock-in.show', $stockInNumber)
                ->with('success', "Stok Masuk {$stockInNumber} berhasil disimpan sebagai Draf. Silakan periksa kembali sebelum diposting.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal memproses stok masuk: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Halaman Detail Stok Masuk (sesuai tampilan Olsera & seragam dengan Detail Stok Keluar)
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

        $stockIn = (object) [
            'nid' => $first->nid,
            'cno_stock_in' => $first->cno_ref,
            'dstock_in' => $first->dmutation,
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
            'supplier' => null,
            'creceived_by' => $first->user?->cemail ?: ($first->user?->email ?: ($first->user?->cname ?: 'Admin')),
            'isDraft' => (strtoupper((string) $first->cstatus) === MposStockMutation::STATUS_DRAFT),
            'isPosted' => (strtoupper((string) $first->cstatus) === MposStockMutation::STATUS_POSTED),
            'isCancelled' => in_array(strtoupper((string) $first->cstatus), [MposStockMutation::STATUS_CANCELLED, MposStockMutation::STATUS_VOID], true),
        ];

        return view('inventory.stock_in_show', compact('stockIn', 'availableProducts'));
    }

    /**
     * Posting Dokumen Stok Masuk (Tambah Stok Fisik ke Master Produk)
     */
    public function postStockIn(Request $request, string $id): RedirectResponse
    {
        $items = MposStockMutation::with(['outlet', 'product', 'ingredient'])
            ->where('ctype', MposStockMutation::TYPE_IN)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)
                    ->orWhere('nid', $id);
            })
            ->get();

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Dokumen Stok Masuk tidak ditemukan.');
        }

        $first = $items->first();
        $stockInNumber = $first->cno_ref;

        if (strtoupper((string) $first->cstatus) === MposStockMutation::STATUS_POSTED) {
            return redirect()->back()->with('error', 'Dokumen ini sudah berstatus Posted.');
        }

        if (in_array(strtoupper((string) $first->cstatus), [MposStockMutation::STATUS_CANCELLED, MposStockMutation::STATUS_VOID], true)) {
            return redirect()->back()->with('error', 'Dokumen yang telah dibatalkan tidak dapat diposting.');
        }

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $qty = (float) $item->nqty;
                $cost = (float) $item->ncost;

                if (!$item->isIngredient() && $item->nid_product) {
                    $prod = MposProduct::lockForUpdate()->find($item->nid_product);
                    if ($prod) {
                        $prod->nqty = (float) $prod->nqty + $qty;
                        if ($cost > 0 && empty($prod->nprice_buy)) {
                            $prod->nprice_buy = $cost;
                        }
                        $prod->save();
                    }
                } elseif ($item->isIngredient() && $item->nid_ingredient) {
                    $ing = MposIngredients::lockForUpdate()->find($item->nid_ingredient);
                    if ($ing) {
                        $ing->nstock = (float) $ing->nstock + $qty;
                        $ing->save();
                    }
                }

                $item->cstatus = MposStockMutation::STATUS_POSTED;
                $item->dupdated = Carbon::now();
                $item->save();
            }

            DB::commit();

            return redirect()->route('inventory.stock-in.show', $stockInNumber)
                ->with('success', "Dokumen Stok Masuk {$stockInNumber} berhasil diposting! Stok fisik barang di outlet penerima telah ditambahkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memposting stok masuk: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan Dokumen Stok Masuk (Draf)
     */
    public function cancelStockIn(string $id): RedirectResponse
    {
        $items = MposStockMutation::where('ctype', MposStockMutation::TYPE_IN)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)
                    ->orWhere('nid', $id);
            })
            ->get();

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Dokumen Stok Masuk tidak ditemukan.');
        }

        $first = $items->first();
        $stockInNumber = $first->cno_ref;
        $currentStatus = strtoupper((string) $first->cstatus);

        if (in_array($currentStatus, [MposStockMutation::STATUS_CANCELLED, MposStockMutation::STATUS_VOID], true)) {
            return redirect()->back()->with('error', 'Dokumen ini sudah dibatalkan sebelumnya.');
        }

        if ($currentStatus !== MposStockMutation::STATUS_DRAFT) {
            return redirect()->back()->with('error', 'Hanya dokumen Stok Masuk berstatus Draf yang dapat dibatalkan.');
        }

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $item->cstatus = MposStockMutation::STATUS_CANCELLED;
                $item->dupdated = Carbon::now();
                $item->save();
            }

            DB::commit();

            return redirect()->route('inventory.stock-in.show', $stockInNumber)
                ->with('success', "Dokumen Stok Masuk {$stockInNumber} telah dibatalkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Update Informasi Dokumen Stok Masuk (Tanggal, Alasan, Catatan) saat Draf
     */
    public function updateStockInHeader(Request $request, string $id): RedirectResponse
    {
        $items = MposStockMutation::where('ctype', MposStockMutation::TYPE_IN)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)->orWhere('nid', $id);
            })
            ->get();

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Dokumen Stok Masuk tidak ditemukan.');
        }

        $first = $items->first();
        if (strtoupper((string) $first->cstatus) !== MposStockMutation::STATUS_DRAFT) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat diubah.');
        }

        $validated = $request->validate([
            'dstock_in' => 'required|date',
            'creason' => 'nullable|string|max:100',
            'cnote' => 'nullable|string|max:500',
        ]);

        $dStockIn = Carbon::parse($validated['dstock_in'])->setTime(
            $first->dmutation ? $first->dmutation->hour : Carbon::now()->hour,
            $first->dmutation ? $first->dmutation->minute : Carbon::now()->minute,
            $first->dmutation ? $first->dmutation->second : Carbon::now()->second
        );

        foreach ($items as $item) {
            $item->dmutation = $dStockIn;
            if (isset($validated['creason'])) {
                $item->creason = $validated['creason'];
            }
            $item->cnote = $validated['cnote'] ?? null;
            $item->dupdated = Carbon::now();
            $item->save();
        }

        return redirect()->route('inventory.stock-in.show', $first->cno_ref)
            ->with('success', 'Informasi dokumen stok masuk berhasil diperbarui.');
    }

    /**
     * Tambah Item Barang ke Dokumen Stok Masuk (Draf)
     */
    public function addStockInItem(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $items = MposStockMutation::where('ctype', MposStockMutation::TYPE_IN)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)->orWhere('nid', $id);
            })
            ->get();

        if ($items->isEmpty()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Dokumen Stok Masuk tidak ditemukan.'], 404);
            }
            return redirect()->back()->with('error', 'Dokumen Stok Masuk tidak ditemukan.');
        }

        $first = $items->first();
        if (strtoupper((string) $first->cstatus) !== MposStockMutation::STATUS_DRAFT) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Item hanya dapat ditambahkan pada dokumen berstatus Draf.'], 422);
            }
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

        $qty = (float) $validated['nqty'];
        $cost = isset($validated['ncost']) ? (float) $validated['ncost'] : 0.0;
        $selectedId = (int) $validated['nid_item'];

        DB::beginTransaction();
        try {
            $sourceProduct = MposProduct::lockForUpdate()->find($selectedId);
            if (!$sourceProduct) {
                DB::rollBack();
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Item produk yang dipilih tidak ditemukan.'], 422);
                }
                return redirect()->back()->with('error', 'Item produk yang dipilih tidak ditemukan.');
            }

            $targetProduct = $this->resolveOrCreateOutletProduct($sourceProduct, (int) $first->nid_outlet, $cost);
            $prodId = $targetProduct->nid;
            $normalizedType = MposStockMutation::normalizeItemType($targetProduct->cproduct_type ?? $validated['citem_type']);

            if ($cost <= 0) {
                $cost = (float) ($targetProduct->nprice_buy ?? 0);
            }
            $subtotal = $qty * $cost;

            $existing = MposStockMutation::where('cno_ref', $first->cno_ref)
                ->where('ctype', MposStockMutation::TYPE_IN)
                ->where('nid_product', $prodId)
                ->first();

            if ($existing) {
                $existing->nqty = (float) $existing->nqty + $qty;
                $existing->ncost = $cost > 0 ? $cost : $existing->ncost;
                $existing->nsubtotal = (float) $existing->nqty * (float) $existing->ncost;
                $existing->citem_type = $normalizedType;
                $existing->dupdated = Carbon::now();
                $existing->save();
            } else {
                MposStockMutation::create([
                    'cno_ref' => $first->cno_ref,
                    'dmutation' => $first->dmutation ?? Carbon::now(),
                    'ctype' => MposStockMutation::TYPE_IN,
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

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal menambahkan item: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Gagal menambahkan item: ' . $e->getMessage());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Item berhasil ditambahkan ke draf stok masuk.',
            ]);
        }

        return redirect()->route('inventory.stock-in.show', $first->cno_ref)
            ->with('success', 'Item berhasil ditambahkan ke draf stok masuk.');
    }

    /**
     * Update Kuantitas / Harga Beli Item pada Dokumen Stok Masuk (Draf)
     */
    public function updateStockInItem(Request $request, string $id, string $itemId): RedirectResponse|JsonResponse
    {
        $item = MposStockMutation::where('nid', $itemId)
            ->where('ctype', MposStockMutation::TYPE_IN)
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
            'ncost' => 'nullable|numeric|min:0',
        ]);

        $item->nqty = (float) $validated['nqty'];
        if (array_key_exists('ncost', $validated) && $validated['ncost'] !== null) {
            $item->ncost = (float) $validated['ncost'];
        }
        $item->nsubtotal = (float) $item->nqty * (float) $item->ncost;
        $item->dupdated = Carbon::now();
        $item->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Item berhasil diperbarui.',
                'nqty' => $item->nqty,
                'ncost' => $item->ncost,
                'nsubtotal' => $item->nsubtotal,
            ]);
        }

        return redirect()->route('inventory.stock-in.show', $item->cno_ref)
            ->with('success', 'Rincian item berhasil diperbarui.');
    }

    /**
     * Hapus Item dari Dokumen Stok Masuk (Draf)
     */
    public function destroyStockInItem(string $id, string $itemId): RedirectResponse
    {
        $item = MposStockMutation::where('nid', $itemId)
            ->where('ctype', MposStockMutation::TYPE_IN)
            ->where(function ($q) use ($id) {
                $q->where('cno_ref', $id)->orWhere('nid', $id);
            })
            ->firstOrFail();

        if (strtoupper((string) $item->cstatus) !== MposStockMutation::STATUS_DRAFT) {
            return redirect()->back()->with('error', 'Hanya item pada draf yang dapat dihapus.');
        }

        $totalCount = MposStockMutation::where('cno_ref', $item->cno_ref)
            ->where('ctype', MposStockMutation::TYPE_IN)
            ->count();

        if ($totalCount <= 1) {
            return redirect()->back()->with('error', 'Dokumen stok masuk harus memiliki minimal 1 item. Jika tidak diperlukan, silakan batalkan seluruh transaksi.');
        }

        $cnoRef = $item->cno_ref;
        $item->delete();

        return redirect()->route('inventory.stock-in.show', $cnoRef)
            ->with('success', 'Item berhasil dihapus dari draf stok masuk.');
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
            'creceived_by' => $first->user?->cemail ?: ($first->user?->email ?: ($first->user?->cname ?: 'Admin')),
        ];

        $pdf = Pdf::loadView('inventory.stock_in_print', compact('stockIn'));
        $pdf->setPaper('a4', 'portrait');
        $filename = "Stok_Masuk_{$stockIn->cno_stock_in}.pdf";

        return $pdf->stream($filename);
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

        $pdf = Pdf::loadView('inventory.stock_out_print', compact('stockOut'));
        $pdf->setPaper('a4', 'portrait');
        $filename = "Stok_Keluar_{$stockOut->cno_stock_out}.pdf";

        return $pdf->stream($filename);
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
     * Export Dokumen Stok Opname ke format Excel (.xlsx)
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

        $filename = "Stok_Opname_{$opname->cno_opname}_" . date('Ymd_His') . ".xlsx";

        return Excel::download(new StockOpnameDetailExport($opname), $filename);
    }

    /**
     * Export data Pergerakan Stok ke Excel (.xlsx).
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportStockMovementExcel(Request $request)
    {
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $isSuperAdmin = $this->isSuperAdminUser();

        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $search = $request->input('search');

        $outletName = 'Semua Outlet';
        if (!empty($selectedOutletId)) {
            $outlet = MposOutlet::find($selectedOutletId);
            if ($outlet) {
                $outletName = $outlet->cname;
            }
        }

        $movements = $this->calculateStockMovementData(
            $startDate,
            $endDate,
            $selectedOutletId,
            $search,
            $isSuperAdmin,
            $allowedOutletIds
        );

        $dateSuffix = $startDate === $endDate
            ? Carbon::parse($startDate)->format('Ymd')
            : Carbon::parse($startDate)->format('Ymd') . '_' . Carbon::parse($endDate)->format('Ymd');
        $filename = "Pergerakan_Stok_{$dateSuffix}.xlsx";

        return Excel::download(new StockMovementExport($movements, $startDate, $endDate, $outletName), $filename);
    }

    /**
     * Menghitung data pergerakan stok (gabungan penjualan, mutasi stok, dan PO).
     *
     * @param string $startDate
     * @param string $endDate
     * @param string|null $selectedOutletId
     * @param string|null $search
     * @param bool|null $isSuperAdmin
     * @param array|null $allowedOutletIds
     * @return Collection
     */
    protected function calculateStockMovementData(
        string $startDate,
        string $endDate,
        ?string $selectedOutletId = null,
        ?string $search = null,
        ?bool $isSuperAdmin = null,
        ?array $allowedOutletIds = null
    ): Collection {
        if ($isSuperAdmin === null) {
            $isSuperAdmin = $this->isSuperAdminUser();
        }
        if ($allowedOutletIds === null) {
            $allowedOutletIds = $this->getUserAllowedOutletIds();
        }

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

        // 1b. Ambil transaksi penjualan terakhir per produk untuk created_by & created_time
        $latestSalesQuery = DB::table('mpos_sales_d')
            ->join('mpos_sales_h', 'mpos_sales_d.nid_transaction', '=', 'mpos_sales_h.nid')
            ->leftJoin('mpos_user', 'mpos_sales_h.nid_user', '=', 'mpos_user.nid')
            ->leftJoin('muser as u_pos', 'mpos_user.nid_user', '=', 'u_pos.nid')
            ->leftJoin('muser as u_direct', 'mpos_sales_h.nid_user', '=', 'u_direct.nid')
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
            $latestSalesQuery->where('mpos_sales_h.nid_outlet', $selectedOutletId);
        } elseif (!$isSuperAdmin) {
            $latestSalesQuery->whereIn('mpos_sales_h.nid_outlet', $allowedOutletIds);
        }

        $latestSales = $latestSalesQuery->select(
            'mpos_sales_d.nid_product',
            'mpos_sales_h.dtransaction',
            DB::raw("COALESCE(u_pos.cname, u_direct.cname, 'System') as user_name")
        )
        ->orderBy('mpos_sales_h.dtransaction', 'desc')
        ->get()
        ->groupBy('nid_product')
        ->map(fn($g) => $g->first());

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

        // 2b. Ambil mutasi terakhir per produk & ingredient untuk created_by & created_time
        $latestMutationQuery = DB::table('mpos_stock_mutations')
            ->leftJoin('muser', 'mpos_stock_mutations.nid_user', '=', 'muser.nid')
            ->whereBetween('dmutation', [$startDateTime, $endDateTime])
            ->where('cstatus', MposStockMutation::STATUS_POSTED);

        if (!empty($selectedOutletId)) {
            $latestMutationQuery->where('nid_outlet', $selectedOutletId);
        } elseif (!$isSuperAdmin) {
            $latestMutationQuery->whereIn('nid_outlet', $allowedOutletIds);
        }

        $latestMutations = $latestMutationQuery->select(
            'nid_product',
            'nid_ingredient',
            'dmutation',
            DB::raw("COALESCE(muser.cname, 'System') as user_name")
        )
        ->orderBy('dmutation', 'desc')
        ->get();

        $latestMutationByProd = $latestMutations->whereNotNull('nid_product')->groupBy('nid_product')->map(fn($g) => $g->first());
        $latestMutationByIng = $latestMutations->whereNull('nid_product')->whereNotNull('nid_ingredient')->groupBy('nid_ingredient')->map(fn($g) => $g->first());

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

        // 3b. Ambil PO terakhir per produk & ingredient untuk created_by & created_time
        $latestPoQuery = DB::table('mpos_purchase_d')
            ->join('mpos_purchase_h', 'mpos_purchase_d.nid_purchase', '=', 'mpos_purchase_h.nid')
            ->leftJoin('muser', 'mpos_purchase_h.nid_user', '=', 'muser.nid')
            ->whereBetween('mpos_purchase_h.dreceived', [$startDateTime, $endDateTime])
            ->where('mpos_purchase_h.cstatus', MposPurchaseH::STATUS_RECEIVED)
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('mpos_stock_mutations')
                    ->where('ctype', 'IN')
                    ->whereColumn('mpos_stock_mutations.cnote', 'like', DB::raw("CONCAT('%', mpos_purchase_h.cno_purchase, '%')"));
            });

        if (!empty($selectedOutletId)) {
            $latestPoQuery->where('mpos_purchase_h.nid_outlet', $selectedOutletId);
        } elseif (!$isSuperAdmin) {
            $latestPoQuery->whereIn('mpos_purchase_h.nid_outlet', $allowedOutletIds);
        }

        $latestPos = $latestPoQuery->select(
            'mpos_purchase_d.nid_product',
            'mpos_purchase_d.nid_ingredient',
            'mpos_purchase_h.dreceived',
            DB::raw("COALESCE(muser.cname, 'System') as user_name")
        )
        ->orderBy('mpos_purchase_h.dreceived', 'desc')
        ->get();

        $latestPoByProd = $latestPos->whereNotNull('nid_product')->groupBy('nid_product')->map(fn($g) => $g->first());
        $latestPoByIng = $latestPos->whereNull('nid_product')->whereNotNull('nid_ingredient')->groupBy('nid_ingredient')->map(fn($g) => $g->first());

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

        // Hitung nilai Awal & tentukan created_by serta created_time dari aktivitas terbaru
        $defaultUserName = Auth::user()?->cname ?? 'System';
        $defaultTime = Carbon::now()->format('Y-m-d H:i:s');

        $allItems = $allItems->map(function ($item) use ($latestSales, $latestMutationByProd, $latestMutationByIng, $latestPoByProd, $latestPoByIng, $defaultUserName, $defaultTime) {
            $awal = $item['sisa'] - $item['masuk'] - $item['pengembalian'] + $item['penjualan'] + $item['keluar'];
            if ($awal < 0) {
                $awal = 0.0;
            }

            $isIng = ($item['type'] === 'INGREDIENT');
            $latestSale = !$isIng ? $latestSales->get($item['id']) : null;
            $latestMut = $isIng ? $latestMutationByIng->get($item['id']) : $latestMutationByProd->get($item['id']);
            $latestPo = $isIng ? $latestPoByIng->get($item['id']) : $latestPoByProd->get($item['id']);

            $candidates = [];
            if ($latestSale && !empty($latestSale->dtransaction)) {
                $candidates[] = [
                    'time' => Carbon::parse($latestSale->dtransaction)->format('Y-m-d H:i:s'),
                    'user' => $latestSale->user_name,
                ];
            }
            if ($latestMut && !empty($latestMut->dmutation)) {
                $candidates[] = [
                    'time' => Carbon::parse($latestMut->dmutation)->format('Y-m-d H:i:s'),
                    'user' => $latestMut->user_name,
                ];
            }
            if ($latestPo && !empty($latestPo->dreceived)) {
                $candidates[] = [
                    'time' => Carbon::parse($latestPo->dreceived)->format('Y-m-d H:i:s'),
                    'user' => $latestPo->user_name,
                ];
            }

            if (!empty($candidates)) {
                usort($candidates, function ($a, $b) {
                    return strcmp($b['time'], $a['time']);
                });
                $createdBy = $candidates[0]['user'] ?: $defaultUserName;
                $createdTime = $candidates[0]['time'];
            } else {
                $createdBy = $defaultUserName;
                $createdTime = $defaultTime;
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
                'created_by' => $createdBy,
                'created_time' => $createdTime,
            ];
        })->sortBy('cname')->values();

        return $allItems;
    }

    // =========================================================================
    // PRODUKSI STOK METHODS
    // =========================================================================

    /**
     * Simpan dokumen draft Produksi Stok baru
     */
    public function storeProduction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dproduction' => 'required|date',
            'nid_outlet' => 'required|integer',
            'cnote' => 'nullable|string|max:1000',
            'products' => 'required|array|min:1',
            'products.*.temp_id' => 'nullable|string',
            'products.*.nid_product' => 'required|integer',
            'products.*.nqty' => 'required|numeric|min:0.01',
            'products.*.ncost_unit' => 'nullable|numeric|min:0',
            'products.*.dexpired' => 'nullable|date',
            'products.*.crack' => 'nullable|string|max:100',
            'materials' => 'nullable|array',
            'materials.*.product_temp_id' => 'nullable|string',
            'materials.*.citem_type' => 'nullable|string|max:50',
            'materials.*.nid_product' => 'nullable|integer',
            'materials.*.nid_ingredient' => 'nullable|integer',
            'materials.*.nqty' => 'required|numeric|min:0.001',
            'materials.*.ncost' => 'nullable|numeric|min:0',
            'materials.*.cnote' => 'nullable|string|max:255',
        ]);

        $allowedOutletIds = $this->getUserAllowedOutletIds();
        if (!$this->isSuperAdminUser() && !in_array((int) $validated['nid_outlet'], $allowedOutletIds)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses ke outlet yang dipilih.')->withInput();
        }

        $dproduction = Carbon::parse($validated['dproduction']);
        $prodNo = MposProductionH::generateNumber($dproduction);

        DB::beginTransaction();
        try {
            $production = MposProductionH::create([
                'cno_production' => $prodNo,
                'dproduction' => $dproduction->format('Y-m-d'),
                'nid_outlet' => $validated['nid_outlet'],
                'nid_user' => Auth::id() ?? 1,
                'cstatus' => MposProductionH::STATUS_DRAFT,
                'cnote' => $validated['cnote'] ?? null,
                'ntotal_product_qty' => 0,
                'ntotal_material_cost' => 0,
                'nadditional_cost' => 0,
                'ntotal_production_cost' => 0,
                'dcreated' => Carbon::now(),
            ]);

            // Simpan produk hasil produksi
            $productMap = []; // temp_id => MposProductionProduct instance
            $firstProductRecord = null;

            foreach ($validated['products'] as $pData) {
                $pQty = (float) $pData['nqty'];
                $pModel = MposProduct::find($pData['nid_product']);
                $costUnit = isset($pData['ncost_unit']) && $pData['ncost_unit'] !== '' 
                    ? (float) $pData['ncost_unit'] 
                    : (float) ($pModel?->nprice_buy ?? 0);

                $prodRecord = MposProductionProduct::create([
                    'nid_production' => $production->nid,
                    'nid_product' => $pData['nid_product'],
                    'nqty' => $pQty,
                    'ncost_unit' => $costUnit,
                    'nsubtotal' => $pQty * $costUnit,
                    'dexpired' => $pData['dexpired'] ?? null,
                    'crack' => $pData['crack'] ?? null,
                    'dcreated' => Carbon::now(),
                ]);

                if (!$firstProductRecord) {
                    $firstProductRecord = $prodRecord;
                }

                $tempId = $pData['temp_id'] ?? null;
                if ($tempId) {
                    $productMap[$tempId] = $prodRecord;
                }
            }

            // Simpan bahan baku jika diinputkan
            if (!empty($validated['materials'])) {
                foreach ($validated['materials'] as $mData) {
                    $targetProdRecord = null;
                    if (!empty($mData['product_temp_id']) && isset($productMap[$mData['product_temp_id']])) {
                        $targetProdRecord = $productMap[$mData['product_temp_id']];
                    } else {
                        $targetProdRecord = $firstProductRecord;
                    }

                    $matCost = 0;
                    if (isset($mData['ncost']) && $mData['ncost'] !== '') {
                        $matCost = (float) $mData['ncost'];
                    } elseif (!empty($mData['nid_product'])) {
                        $matProduct = MposProduct::find($mData['nid_product']);
                        $matCost = (float) ($matProduct?->nprice_buy ?? 0);
                    }

                    $matQty = (float) $mData['nqty'];
                    $matSubtotal = $matQty * $matCost;

                    MposProductionMaterial::create([
                        'nid_production' => $production->nid,
                        'nid_production_product' => $targetProdRecord?->nid,
                        'citem_type' => $mData['citem_type'] ?? 'PRODUK',
                        'nid_product' => $mData['nid_product'] ?? null,
                        'nid_ingredient' => $mData['nid_ingredient'] ?? null,
                        'nqty' => $matQty,
                        'ncost' => $matCost,
                        'nsubtotal' => $matSubtotal,
                        'cnote' => $mData['cnote'] ?? null,
                        'dcreated' => Carbon::now(),
                    ]);
                }
            }

            $production->recalculateTotals();

            DB::commit();

            return redirect()->route('inventory.production.show', $production->nid)
                ->with('success', "Dokumen Produksi {$prodNo} berhasil disimpan sebagai Draf. Silakan periksa kembali sebelum diposting.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membuat dokumen produksi: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Tampilkan detail dokumen Produksi Stok
     */
    public function showProduction(string $id)
    {
        $production = MposProductionH::with([
            'outlet',
            'user',
            'products.product',
            'materials.product',
            'materials.ingredient',
        ])
        ->where('nid', $id)
        ->orWhere('cno_production', $id)
        ->firstOrFail();

        // Ambil daftar produk dan bahan baku untuk autocomplete
        $outletId = $production->nid_outlet;
        $prodCols = ['nid', 'cname', 'nid_category', 'nid_outlet', 'nprice', 'nprice_buy', 'nqty'];
        if (MposProduct::hasSatuanColumn()) {
            $prodCols[] = 'csatuan';
        }
        if (MposProduct::hasProductTypeColumn()) {
            $prodCols[] = 'cproduct_type';
        }

        $allProducts = MposProduct::where(function ($q) {
            $q->where('cstatus', 'ACTIVE')->orWhere('cstatus', '1')->orWhereNull('cstatus');
        })
        ->orderBy('cname', 'asc')
        ->get($prodCols);

        // Grouping per nama produk agar unik dan menampilkan stok pada outlet produksi ini
        $byName = [];
        foreach ($allProducts as $p) {
            $key = strtoupper(trim((string) $p->cname));
            if (!$key) {
                continue;
            }
            $byName[$key][] = $p;
        }

        $availableProducts = collect();
        foreach ($byName as $prods) {
            $match = null;
            if ($outletId) {
                foreach ($prods as $p) {
                    if ((string) $p->nid_outlet === (string) $outletId) {
                        $match = $p;
                        break;
                    }
                }
            }
            if (!$match) {
                $tmpl = $prods[0];
                $match = clone $tmpl;
                $match->nqty = 0;
            }
            $availableProducts->push($match);
        }

        $availableProducts = $availableProducts->sortBy('cname')->values();

        $availableIngredients = MposIngredients::where(function ($q) use ($outletId) {
            $q->where('nid_outlet', $outletId)->orWhereNull('nid_outlet');
        })
        ->orderBy('cname', 'asc')
        ->get();

        return view('inventory.production_show', compact(
            'production',
            'availableProducts',
            'availableIngredients'
        ));
    }

    /**
     * Update header (tanggal & catatan)
     */
    public function updateProductionHeader(Request $request, string $id): RedirectResponse
    {
        $production = MposProductionH::where('nid', $id)->orWhere('cno_production', $id)->firstOrFail();

        if (!$production->isDraft()) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat diubah.');
        }

        $validated = $request->validate([
            'dproduction' => 'required|date',
            'cnote' => 'nullable|string|max:1000',
        ]);

        $production->update([
            'dproduction' => $validated['dproduction'],
            'cnote' => $validated['cnote'] ?? null,
            'dupdated' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Informasi dokumen produksi berhasil diperbarui.');
    }

    /**
     * Tambah Produk Jadi (Hasil Produksi / PLUS)
     */
    public function addProductionProduct(Request $request, string $id): RedirectResponse
    {
        $production = MposProductionH::where('nid', $id)->orWhere('cno_production', $id)->firstOrFail();

        if (!$production->isDraft()) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat ditambahkan produk.');
        }

        $validated = $request->validate([
            'nid_product' => 'required|integer',
            'nqty' => 'required|numeric|min:0.01',
            'ncost_unit' => 'nullable|numeric|min:0',
            'dexpired' => 'nullable|date',
            'crack' => 'nullable|string|max:100',
            'cnote' => 'nullable|string|max:255',
        ]);

        $product = MposProduct::findOrFail($validated['nid_product']);
        $product = $this->resolveOrCreateOutletProduct($product, (int) $production->nid_outlet, (float) ($product->nprice_buy ?? 0));
        $qty = (float) $validated['nqty'];
        $costUnit = isset($validated['ncost_unit']) && $validated['ncost_unit'] !== '' ? (float) $validated['ncost_unit'] : (float) ($product->nprice_buy ?? 0);
        $subtotal = $qty * $costUnit;

        DB::beginTransaction();
        try {
            // Cek apakah produk sudah ada di daftar hasil produksi dokumen ini
            $existing = MposProductionProduct::where('nid_production', $production->nid)
                ->where('nid_product', $product->nid)
                ->first();

            if ($existing) {
                $newQty = (float) $existing->nqty + $qty;
                $existing->update([
                    'nqty' => $newQty,
                    'nsubtotal' => $newQty * (float) $existing->ncost_unit,
                    'dexpired' => $validated['dexpired'] ?? $existing->dexpired,
                    'crack' => $validated['crack'] ?? $existing->crack,
                    'cnote' => $validated['cnote'] ?? $existing->cnote,
                    'dupdated' => Carbon::now(),
                ]);
                $prodRecord = $existing;
            } else {
                $prodRecord = MposProductionProduct::create([
                    'nid_production' => $production->nid,
                    'nid_product' => $product->nid,
                    'nqty' => $qty,
                    'ncost_unit' => $costUnit,
                    'nsubtotal' => $subtotal,
                    'dexpired' => $validated['dexpired'] ?? null,
                    'crack' => $validated['crack'] ?? null,
                    'cnote' => $validated['cnote'] ?? null,
                    'dcreated' => Carbon::now(),
                ]);
            }

            // AUTO-FILL RESEP DARI mpos_recipe (jika tersedia)
            $recipes = MposRecipe::where('nid_product', $product->nid)->get();
            if ($recipes->isNotEmpty()) {
                foreach ($recipes as $rc) {
                    $neededQty = (float) $rc->nqty * $qty;
                    $matProduct = MposProduct::find($rc->nid_ingredient);
                    $matCost = $matProduct ? (float) ($matProduct->nprice_buy ?? 0) : 0;

                    // Cek apakah bahan untuk produk ini sudah ada
                    $existingMat = MposProductionMaterial::where('nid_production', $production->nid)
                        ->where('nid_production_product', $prodRecord->nid)
                        ->where('nid_product', $rc->nid_ingredient)
                        ->first();

                    if ($existingMat) {
                        $updQty = (float) $existingMat->nqty + $neededQty;
                        $existingMat->update([
                            'nqty' => $updQty,
                            'nsubtotal' => $updQty * (float) $existingMat->ncost,
                            'dupdated' => Carbon::now(),
                        ]);
                    } else {
                        MposProductionMaterial::create([
                            'nid_production' => $production->nid,
                            'nid_production_product' => $prodRecord->nid,
                            'citem_type' => $matProduct?->cproduct_type ?: 'PRODUK',
                            'nid_product' => $rc->nid_ingredient,
                            'nid_ingredient' => null,
                            'nqty' => $neededQty,
                            'ncost' => $matCost,
                            'nsubtotal' => $neededQty * $matCost,
                            'cnote' => 'Resep: ' . $product->cname,
                            'dcreated' => Carbon::now(),
                        ]);
                    }
                }
            }

            $production->recalculateTotals();
            DB::commit();

            return redirect()->back()->with('success', "Produk '{$product->cname}' berhasil ditambahkan ke daftar produksi.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menambahkan produk: ' . $e->getMessage());
        }
    }

    /**
     * Update item produk jadi
     */
    public function updateProductionProduct(Request $request, string $id, string $productId): RedirectResponse|JsonResponse
    {
        $production = MposProductionH::where('nid', $id)->orWhere('cno_production', $id)->firstOrFail();

        if (!$production->isDraft()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Hanya dokumen berstatus Draf yang dapat diubah.'], 422);
            }
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat diubah.');
        }

        $prodItem = MposProductionProduct::where('nid_production', $production->nid)
            ->where('nid', $productId)
            ->firstOrFail();

        $validated = $request->validate([
            'nqty' => 'required|numeric|min:0.01',
            'ncost_unit' => 'nullable|numeric|min:0',
            'dexpired' => 'nullable|date',
            'crack' => 'nullable|string|max:100',
        ]);

        $qty = (float) $validated['nqty'];
        $cost = isset($validated['ncost_unit']) ? (float) $validated['ncost_unit'] : (float) $prodItem->ncost_unit;

        $prodItem->update([
            'nqty' => $qty,
            'ncost_unit' => $cost,
            'nsubtotal' => $qty * $cost,
            'dexpired' => $validated['dexpired'] ?? $prodItem->dexpired,
            'crack' => $validated['crack'] ?? $prodItem->crack,
            'dupdated' => Carbon::now(),
        ]);

        $production->recalculateTotals();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Qty produk berhasil diperbarui.',
                'product' => [
                    'nid' => $prodItem->nid,
                    'nqty' => $qty,
                    'formatted_qty' => number_format($qty, 2, ',', '.'),
                    'nsubtotal' => (float) $prodItem->nsubtotal,
                    'formatted_subtotal' => 'Rp ' . number_format($prodItem->nsubtotal, 0, ',', '.'),
                ],
                'total_product_qty' => (float) $production->ntotal_product_qty,
                'formatted_total_product_qty' => number_format($production->ntotal_product_qty, 2, ',', '.'),
                'total_production_cost' => (float) $production->ntotal_production_cost,
                'formatted_total_production_cost' => 'Rp ' . number_format($production->ntotal_production_cost, 0, ',', '.'),
            ]);
        }

        return redirect()->back()->with('success', 'Qty dan informasi produk berhasil diperbarui.');
    }

    /**
     * Hapus item produk jadi
     */
    public function destroyProductionProduct(string $id, string $productId): RedirectResponse
    {
        $production = MposProductionH::where('nid', $id)->orWhere('cno_production', $id)->firstOrFail();

        if (!$production->isDraft()) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat diubah.');
        }

        $prodItem = MposProductionProduct::where('nid_production', $production->nid)
            ->where('nid', $productId)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            // Hapus bahan yang terikat dengan produk ini
            MposProductionMaterial::where('nid_production_product', $prodItem->nid)->delete();
            $prodItem->delete();

            $production->recalculateTotals();
            DB::commit();

            return redirect()->back()->with('success', 'Produk dan bahan terkait berhasil dihapus dari daftar produksi.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus produk: ' . $e->getMessage());
        }
    }

    /**
     * Tambah Bahan Baku (Material / MINUS)
     */
    public function addProductionMaterial(Request $request, string $id): RedirectResponse
    {
        $production = MposProductionH::where('nid', $id)->orWhere('cno_production', $id)->firstOrFail();

        if (!$production->isDraft()) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat ditambahkan bahan.');
        }

        $validated = $request->validate([
            'nid_production_product' => 'nullable|integer',
            'citem_type' => 'nullable|string|in:PRODUK,BAHAN_BAKU,SETENGAH_JADI,INGREDIENT',
            'nid_product' => 'nullable|integer',
            'nid_ingredient' => 'nullable|integer',
            'nqty' => 'required|numeric|min:0.001',
            'ncost' => 'nullable|numeric|min:0',
            'cnote' => 'nullable|string|max:255',
        ]);

        if (empty($validated['nid_product']) && empty($validated['nid_ingredient'])) {
            return redirect()->back()->with('error', 'Pilih salah satu item produk atau bahan baku.');
        }

        $itemType = $validated['citem_type'] ?? 'PRODUK';
        $cost = 0;
        if (isset($validated['ncost']) && $validated['ncost'] !== '') {
            $cost = (float) $validated['ncost'];
        } elseif (!empty($validated['nid_product'])) {
            $p = MposProduct::find($validated['nid_product']);
            $cost = (float) ($p->nprice_buy ?? 0);
        }

        $totalQty = (float) $validated['nqty'];
        $subtotal = $totalQty * $cost;

        MposProductionMaterial::create([
            'nid_production' => $production->nid,
            'nid_production_product' => $validated['nid_production_product'] ?? null,
            'citem_type' => $itemType,
            'nid_product' => $validated['nid_product'] ?? null,
            'nid_ingredient' => $validated['nid_ingredient'] ?? null,
            'nqty' => $totalQty,
            'ncost' => $cost,
            'nsubtotal' => $subtotal,
            'cnote' => $validated['cnote'] ?? null,
            'dcreated' => Carbon::now(),
        ]);

        $production->recalculateTotals();

        return redirect()->back()->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    /**
     * Update Bahan Baku
     */
    public function updateProductionMaterial(Request $request, string $id, string $materialId): RedirectResponse|JsonResponse
    {
        $production = MposProductionH::where('nid', $id)->orWhere('cno_production', $id)->firstOrFail();

        if (!$production->isDraft()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Hanya dokumen berstatus Draf yang dapat diubah.'], 422);
            }
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat diubah.');
        }

        $mat = MposProductionMaterial::where('nid_production', $production->nid)
            ->where('nid', $materialId)
            ->firstOrFail();

        $validated = $request->validate([
            'nqty' => 'required|numeric|min:0.001',
            'ncost' => 'nullable|numeric|min:0',
            'cnote' => 'nullable|string|max:255',
        ]);

        $qty = (float) $validated['nqty'];
        $cost = isset($validated['ncost']) ? (float) $validated['ncost'] : (float) $mat->ncost;

        $mat->update([
            'nqty' => $qty,
            'ncost' => $cost,
            'nsubtotal' => $qty * $cost,
            'cnote' => $validated['cnote'] ?? $mat->cnote,
            'dupdated' => Carbon::now(),
        ]);

        $production->recalculateTotals();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Bahan baku berhasil diperbarui.',
                'material' => [
                    'nid' => $mat->nid,
                    'nqty' => $qty,
                    'formatted_qty' => '-' . number_format($qty, 2, ',', '.'),
                    'nsubtotal' => (float) $mat->nsubtotal,
                    'formatted_subtotal' => 'Rp ' . number_format($mat->nsubtotal, 0, ',', '.'),
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Bahan baku berhasil diperbarui.');
    }

    /**
     * Hapus Bahan Baku
     */
    public function destroyProductionMaterial(string $id, string $materialId): RedirectResponse
    {
        $production = MposProductionH::where('nid', $id)->orWhere('cno_production', $id)->firstOrFail();

        if (!$production->isDraft()) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat diubah.');
        }

        $mat = MposProductionMaterial::where('nid_production', $production->nid)
            ->where('nid', $materialId)
            ->firstOrFail();

        $mat->delete();
        $production->recalculateTotals();

        return redirect()->back()->with('success', 'Bahan baku berhasil dihapus.');
    }

    /**
     * Update Biaya Tambahan Produksi (Operasional / Tenaga Kerja / Listrik)
     */
    public function updateAdditionalCost(Request $request, string $id): RedirectResponse
    {
        $production = MposProductionH::where('nid', $id)->orWhere('cno_production', $id)->firstOrFail();

        if (!$production->isDraft()) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat diubah.');
        }

        $validated = $request->validate([
            'nadditional_cost' => 'required|numeric|min:0',
        ]);

        $production->nadditional_cost = (float) $validated['nadditional_cost'];
        $production->save();
        $production->recalculateTotals();

        return redirect()->back()->with('success', 'Biaya produksi berhasil diperbarui.');
    }

    /**
     * Posting Dokumen Produksi Sekarang
     * - Memotong stok bahan baku (mpos_stock_mutations TYPE_OUT)
     * - Menambah stok produk jadi (mpos_stock_mutations TYPE_IN)
     * - Mengubah status dokumen menjadi POSTED
     */
    public function postProduction(string $id): RedirectResponse
    {
        $production = MposProductionH::with(['products.product', 'materials.product', 'materials.ingredient'])
            ->where('nid', $id)
            ->orWhere('cno_production', $id)
            ->firstOrFail();

        if (!$production->isDraft()) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draf yang dapat diposting.');
        }

        if ($production->products->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak dapat memposting: Dokumen belum memiliki produk hasil produksi.');
        }

        DB::beginTransaction();
        try {
            $mutationTime = Carbon::now();
            $dmutation = Carbon::parse($production->dproduction)->setTime($mutationTime->hour, $mutationTime->minute, $mutationTime->second);

            // 1. Catat mutasi MASUK untuk setiap produk hasil produksi & tambah stok fisik
            foreach ($production->products as $prod) {
                $prodItemType = ($prod->product && !empty($prod->product->cproduct_type))
                    ? MposStockMutation::normalizeItemType($prod->product->cproduct_type)
                    : MposStockMutation::ITEM_PRODUK;

                MposStockMutation::create([
                    'cno_ref' => $production->cno_production,
                    'dmutation' => $dmutation,
                    'ctype' => MposStockMutation::TYPE_IN,
                    'nid_outlet' => $production->nid_outlet,
                    'nid_target_outlet' => null,
                    'nid_user' => Auth::id() ?? $production->nid_user,
                    'citem_type' => $prodItemType,
                    'nid_product' => $prod->nid_product,
                    'nid_ingredient' => null,
                    'nqty' => $prod->nqty,
                    'ncost' => $prod->ncost_unit,
                    'nsubtotal' => $prod->nsubtotal,
                    'creason' => 'Hasil Produksi',
                    'cstatus' => MposStockMutation::STATUS_POSTED,
                    'cnote' => 'Hasil Produksi #' . $production->cno_production,
                    'dexpired' => $prod->dexpired,
                    'crack' => $prod->crack,
                    'dcreated' => Carbon::now(),
                ]);

                // Update stok fisik master produk jadi
                if ($prod->nid_product) {
                    $product = MposProduct::lockForUpdate()->find($prod->nid_product);
                    if ($product) {
                        $product->nqty = (float) $product->nqty + (float) $prod->nqty;
                        $product->save();
                    }
                }
            }

            // 2. Catat mutasi KELUAR untuk setiap bahan baku yang digunakan & kurangi stok fisik
            foreach ($production->materials as $mat) {
                MposStockMutation::create([
                    'cno_ref' => $production->cno_production,
                    'dmutation' => $dmutation,
                    'ctype' => MposStockMutation::TYPE_OUT,
                    'nid_outlet' => $production->nid_outlet,
                    'nid_target_outlet' => null,
                    'nid_user' => Auth::id() ?? $production->nid_user,
                    'citem_type' => $mat->getItemTypeNormalized(),
                    'nid_product' => $mat->nid_product,
                    'nid_ingredient' => $mat->nid_ingredient,
                    'nqty' => $mat->nqty,
                    'ncost' => $mat->ncost,
                    'nsubtotal' => $mat->nsubtotal,
                    'creason' => 'Bahan Produksi',
                    'cstatus' => MposStockMutation::STATUS_POSTED,
                    'cnote' => 'Bahan Produksi #' . $production->cno_production,
                    'dcreated' => Carbon::now(),
                ]);

                // Update stok fisik master produk / bahan baku
                if (!$mat->isIngredient() && $mat->nid_product) {
                    $matProd = MposProduct::lockForUpdate()->find($mat->nid_product);
                    if ($matProd) {
                        $matProd->nqty = max(0, (float) $matProd->nqty - (float) $mat->nqty);
                        $matProd->save();
                    }
                } elseif ($mat->isIngredient() && $mat->nid_ingredient) {
                    $ing = MposIngredients::lockForUpdate()->find($mat->nid_ingredient);
                    if ($ing) {
                        $ing->nstock = max(0, (float) $ing->nstock - (float) $mat->nqty);
                        $ing->save();
                    }
                }
            }

            // 3. Update status dokumen produksi menjadi POSTED
            $production->update([
                'cstatus' => MposProductionH::STATUS_POSTED,
                'dposted' => Carbon::now(),
                'dupdated' => Carbon::now(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Dokumen Produksi berhasil diposting. Stok bahan baku telah dipotong dan stok produk jadi telah ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memposting dokumen produksi: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan Dokumen Produksi
     */
    public function cancelProduction(string $id): RedirectResponse
    {
        $production = MposProductionH::where('nid', $id)->orWhere('cno_production', $id)->firstOrFail();

        if ($production->isPosted()) {
            return redirect()->back()->with('error', 'Dokumen yang sudah diposting tidak dapat dibatalkan.');
        }

        $production->update([
            'cstatus' => MposProductionH::STATUS_CANCELLED,
            'dupdated' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Dokumen Produksi berhasil dibatalkan.');
    }

    /**
     * Sinkronkan Stok Fisik Dokumen Produksi yang Sudah Diposting
     */
    public function syncProductionStock(string $id): RedirectResponse
    {
        $production = MposProductionH::with(['products', 'materials'])
            ->where('nid', $id)
            ->orWhere('cno_production', $id)
            ->firstOrFail();

        if (!$production->isPosted()) {
            return redirect()->back()->with('error', 'Hanya dokumen yang berstatus POSTED yang dapat disinkronkan stoknya.');
        }

        DB::beginTransaction();
        try {
            // Update stok produk hasil produksi
            foreach ($production->products as $prod) {
                if ($prod->nid_product) {
                    $product = MposProduct::lockForUpdate()->find($prod->nid_product);
                    if ($product) {
                        $product->nqty = (float) $product->nqty + (float) $prod->nqty;
                        $product->save();
                    }
                }
            }

            // Update stok bahan baku
            foreach ($production->materials as $mat) {
                if (!$mat->isIngredient() && $mat->nid_product) {
                    $matProd = MposProduct::lockForUpdate()->find($mat->nid_product);
                    if ($matProd) {
                        $matProd->nqty = max(0, (float) $matProd->nqty - (float) $mat->nqty);
                        $matProd->save();
                    }
                } elseif ($mat->isIngredient() && $mat->nid_ingredient) {
                    $ing = MposIngredients::lockForUpdate()->find($mat->nid_ingredient);
                    if ($ing) {
                        $ing->nstock = max(0, (float) $ing->nstock - (float) $mat->nqty);
                        $ing->save();
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Stok master produk berhasil disinkronkan! Stok produk jadi bertambah dan stok bahan baku berkurang.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyinkronkan stok: ' . $e->getMessage());
        }
    }

    /**
     * Hapus Dokumen Produksi Draft / Batal
     */
    public function destroyProduction(string $id): RedirectResponse
    {
        $production = MposProductionH::where('nid', $id)->orWhere('cno_production', $id)->firstOrFail();

        if ($production->isPosted()) {
            return redirect()->back()->with('error', 'Dokumen yang sudah diposting tidak dapat dihapus.');
        }

        $production->delete();

        return redirect()->route('inventory.index', ['tab' => 'produksi_stok'])
            ->with('success', 'Dokumen Produksi berhasil dihapus.');
    }

    /**
     * Cetak PDF Dokumen Produksi
     */
    public function printProduction(string $id)
    {
        $production = MposProductionH::with([
            'outlet',
            'user',
            'products.product',
            'materials.product',
            'materials.ingredient',
        ])
        ->where('nid', $id)
        ->orWhere('cno_production', $id)
        ->firstOrFail();

        $pdf = Pdf::loadView('inventory.production_print', compact('production'))
            ->setPaper('a4', 'portrait');

        $filename = "Produksi_Stok_{$production->cno_production}.pdf";

        return $pdf->stream($filename);
    }
}
