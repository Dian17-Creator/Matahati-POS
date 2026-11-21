<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Models\MposIngredients;
use App\Models\MposOutlet;
use App\Models\MposProduct;
use App\Models\MposProductionH;
use App\Models\MposPurchaseD;
use App\Models\MposPurchaseH;
use App\Models\MposSalesH;
use App\Models\MposStockMutation;
use App\Models\MposStockOpnameH;
use App\Models\MposSupplier;
use App\Traits\ResolvesOutletScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfficeInventoryController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            $bearer = $request->bearerToken();
            if ($bearer && str_starts_with($bearer, 'logged_in_office_')) {
                $userId = (int) substr($bearer, strlen('logged_in_office_'));
                $user = \App\Models\Muser::find($userId);
            }
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        Auth::setUser($user);

        $tab = strtolower((string) $request->input('tab', 'stok_masuk'));
        $validTabs = ['stok_masuk', 'stok_keluar', 'produksi_stok', 'stok_opname', 'pergerakan_stok'];
        if (!in_array($tab, $validTabs, true)) {
            $tab = 'stok_masuk';
        }

        $startDate = (string) $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = (string) $request->input('end_date', Carbon::now()->format('Y-m-d'));

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $isSuperAdmin = $this->isSuperAdminUser();

        return match ($tab) {
            'stok_keluar' => $this->getStockOutList($request, $startDate, $endDate, $selectedOutletId, $allowedOutletIds, $isSuperAdmin),
            'produksi_stok' => $this->getProductionList($request, $startDate, $endDate, $selectedOutletId, $allowedOutletIds, $isSuperAdmin),
            'stok_opname' => $this->getStockOpnameList($request, $startDate, $endDate, $selectedOutletId, $allowedOutletIds, $isSuperAdmin),
            'pergerakan_stok' => $this->getStockMovementList($request, $startDate, $endDate, $selectedOutletId, $allowedOutletIds, $isSuperAdmin),
            default => $this->getStockInList($request, $startDate, $endDate, $selectedOutletId, $allowedOutletIds, $isSuperAdmin),
        };
    }

    private function getStockInList(Request $request, string $startDate, string $endDate, ?int $outletId, array $allowedOutletIds, bool $isSuperAdmin): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 50);

        $query = MposStockMutation::where('mpos_stock_mutations.ctype', MposStockMutation::TYPE_IN);

        if (!empty($outletId)) {
            $query->where('mpos_stock_mutations.nid_outlet', $outletId);
        } elseif (!$isSuperAdmin) {
            $query->whereIn('mpos_stock_mutations.nid_outlet', $allowedOutletIds);
        }

        $query->whereBetween('mpos_stock_mutations.dmutation', [
            $startDate . ' 00:00:00',
            $endDate . ' 23:59:59',
        ]);

        $rows = (clone $query)->select(
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

        $outletIds = $rows->pluck('nid_outlet')->merge($rows->pluck('nid_target_outlet'))->unique()->filter()->values();
        $outletsMap = MposOutlet::whereIn('nid', $outletIds)->get()->keyBy('nid');
        $userIds = $rows->pluck('nid_user')->unique()->filter()->values();
        $usersMap = \App\Models\Muser::whereIn('nid', $userIds)->get()->keyBy('nid');

        $docs = $rows->map(function ($row) use ($outletsMap, $usersMap) {
            $row->outlet = $outletsMap->get($row->nid_outlet);
            $row->targetOutlet = $outletsMap->get($row->nid_target_outlet);
            $row->user = $usersMap->get($row->nid_user);
            $dmutation = $row->dmutation ? Carbon::parse($row->dmutation) : null;

            $diterimaDari = !empty($row->targetOutlet) ? $row->targetOutlet->cname : ($row->outlet?->cname ?? '-');
            $supplierName = str_starts_with($row->creason ?? '', 'Penerimaan Supplier: ')
                ? str_replace('Penerimaan Supplier: ', '', $row->creason)
                : ($row->creason ?: '-');
            $receivedBy = $row->user?->cemail ?: ($row->user?->email ?: ($row->user?->cname ?: 'Admin'));

            $statusLabel = match (strtoupper((string) $row->cstatus)) {
                'POSTED' => 'Posted',
                'DRAFT' => 'Draf',
                'CANCELLED', 'VOID' => 'Batal',
                default => $row->cstatus ?: 'Draf',
            };

            return [
                'no' => $row->cno_ref ?: '-',
                'diterima_dari' => $diterimaDari,
                'supplier' => $supplierName,
                'tanggal' => $dmutation ? $dmutation->format('d M Y') : '-',
                'catatan' => $row->cnote ?: '-',
                'status' => $row->cstatus ?: 'DRAFT',
                'status_label' => $statusLabel,
                'diterima_oleh' => $receivedBy,
            ];
        });

        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $currentPageItems = $docs->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $currentPageItems,
            $docs->count(),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'tab' => 'stok_masuk',
                'total_records' => $docs->count(),
                'inventory' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'data' => $paginator->items(),
                ],
            ],
        ]);
    }

    private function getStockOutList(Request $request, string $startDate, string $endDate, ?int $outletId, array $allowedOutletIds, bool $isSuperAdmin): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 50);

        $query = MposStockMutation::where('mpos_stock_mutations.ctype', MposStockMutation::TYPE_OUT);

        if (!empty($outletId)) {
            $query->where('mpos_stock_mutations.nid_outlet', $outletId);
        } elseif (!$isSuperAdmin) {
            $query->whereIn('mpos_stock_mutations.nid_outlet', $allowedOutletIds);
        }

        $query->whereBetween('mpos_stock_mutations.dmutation', [
            $startDate . ' 00:00:00',
            $endDate . ' 23:59:59',
        ]);

        $rows = (clone $query)->select(
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

        $outletIds = $rows->pluck('nid_outlet')->merge($rows->pluck('nid_target_outlet'))->unique()->filter()->values();
        $outletsMap = MposOutlet::whereIn('nid', $outletIds)->get()->keyBy('nid');
        $userIds = $rows->pluck('nid_user')->unique()->filter()->values();
        $usersMap = \App\Models\Muser::whereIn('nid', $userIds)->get()->keyBy('nid');

        $docs = $rows->map(function ($row) use ($outletsMap, $usersMap) {
            $row->targetOutlet = $row->nid_target_outlet ? $outletsMap->get($row->nid_target_outlet) : null;
            $row->user = $usersMap->get($row->nid_user);
            $dmutation = $row->dmutation ? Carbon::parse($row->dmutation) : null;

            $transferKe = $row->targetOutlet?->cname ?? '-';
            $createdBy = $row->user?->cemail ?: ($row->user?->email ?: ($row->user?->cname ?: 'admin'));

            $statusLabel = match (strtoupper((string) $row->cstatus)) {
                'POSTED' => 'Posted',
                'DRAFT' => 'Draf',
                'CANCELLED', 'VOID' => 'Batal',
                default => $row->cstatus ?: 'Draf',
            };

            return [
                'no' => $row->cno_ref ?: '-',
                'transfer_ke' => $transferKe,
                'tanggal' => $dmutation ? $dmutation->format('d M Y') : '-',
                'catatan' => $row->cnote ?: '-',
                'alasan' => $row->creason ?: '-',
                'status' => $row->cstatus ?: 'DRAFT',
                'status_label' => $statusLabel,
                'dibuat_oleh' => $createdBy,
            ];
        });

        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $currentPageItems = $docs->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $currentPageItems,
            $docs->count(),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'tab' => 'stok_keluar',
                'total_records' => $docs->count(),
                'inventory' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'data' => $paginator->items(),
                ],
            ],
        ]);
    }

    private function getProductionList(Request $request, string $startDate, string $endDate, ?int $outletId, array $allowedOutletIds, bool $isSuperAdmin): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 50);

        $query = MposProductionH::with(['user']);

        if (!empty($outletId)) {
            $query->where('nid_outlet', $outletId);
        } elseif (!$isSuperAdmin) {
            $query->whereIn('nid_outlet', $allowedOutletIds);
        }

        $query->whereBetween('dproduction', [$startDate, $endDate]);

        $paginator = $query->orderBy('dproduction', 'desc')->orderBy('nid', 'desc')->paginate($perPage);

        $items = collect($paginator->items())->map(function ($row) {
            $dprod = $row->dproduction ? Carbon::parse($row->dproduction) : null;
            $createdBy = $row->user ? ($row->user->cname ?: 'Admin') : 'Admin';

            $statusLabel = match (strtoupper((string) $row->cstatus)) {
                'POSTED' => 'Posted',
                'DRAFT' => 'Draf',
                'CANCELLED', 'VOID' => 'Batal',
                default => $row->cstatus ?: 'Draf',
            };

            return [
                'no' => $row->cno_production ?: '-',
                'tanggal' => $dprod ? $dprod->format('d M Y') : '-',
                'catatan' => $row->cnote ?: '-',
                'status' => $row->cstatus ?: 'DRAFT',
                'status_label' => $statusLabel,
                'diterima_dibuat_oleh' => $createdBy,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'tab' => 'produksi_stok',
                'total_records' => $paginator->total(),
                'inventory' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'data' => $items,
                ],
            ],
        ]);
    }

    private function getStockOpnameList(Request $request, string $startDate, string $endDate, ?int $outletId, array $allowedOutletIds, bool $isSuperAdmin): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 50);

        $query = MposStockOpnameH::with(['user']);

        if (!empty($outletId)) {
            $query->where('nid_outlet', $outletId);
        } elseif (!$isSuperAdmin) {
            $query->whereIn('nid_outlet', $allowedOutletIds);
        }

        $query->whereBetween('dopname', [$startDate, $endDate]);

        $paginator = $query->orderBy('dopname', 'desc')->orderBy('nid', 'desc')->paginate($perPage);

        $items = collect($paginator->items())->map(function ($row) {
            $dop = $row->dopname ? Carbon::parse($row->dopname) : null;
            $receivedBy = $row->user ? ($row->user->cname ?: 'Admin') : 'Admin';

            $statusLabel = match (strtoupper((string) $row->cstatus)) {
                'POSTED' => 'Posted',
                'DRAFT' => 'Draf',
                'CANCELLED', 'VOID' => 'Batal',
                default => $row->cstatus ?: 'Draf',
            };

            return [
                'no' => $row->cno_opname ?: '-',
                'tanggal' => $dop ? $dop->format('d M Y') : '-',
                'catatan' => $row->cnote ?: '-',
                'status' => $row->cstatus ?: 'DRAFT',
                'status_label' => $statusLabel,
                'diterima_oleh' => $receivedBy,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'tab' => 'stok_opname',
                'total_records' => $paginator->total(),
                'inventory' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'data' => $items,
                ],
            ],
        ]);
    }

    private function getStockMovementList(Request $request, string $startDate, string $endDate, ?int $outletId, array $allowedOutletIds, bool $isSuperAdmin): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 50);

        // Rekonstruksi data pergerakan stok dari MposStockMutation & Sales/PO
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';
        $isHistoric = ($endDateTime < Carbon::now()->format('Y-m-d H:i:s'));

        // Query Penjualan
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

        if (!empty($outletId)) {
            $salesQuery->where('mpos_sales_h.nid_outlet', $outletId);
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
        $soldProductIds = $salesRows->pluck('nid_product')->filter()->unique()->values();
        $soldProducts = MposProduct::with(['category'])->whereIn('nid', $soldProductIds)->get()->keyBy('nid');

        foreach ($salesRows as $r) {
            $prod = $soldProducts->get($r->nid_product);
            if (!$prod) continue;
            $pId = $prod->nid;
            if (!isset($productMovements[$pId])) {
                $productMovements[$pId] = [
                    'category_name' => $prod->category?->cname ?: 'Produk',
                    'cname' => $prod->cname,
                    'qty_awal' => 0.0,
                    'qty_masuk' => 0.0,
                    'qty_pengembalian' => 0.0,
                    'qty_penjualan' => 0.0,
                    'qty_keluar' => 0.0,
                    'qty_sisa' => (float) $prod->nqty,
                ];
            }
            $productMovements[$pId]['qty_penjualan'] += (float) $r->total_sales;
            $productMovements[$pId]['qty_pengembalian'] += (float) $r->total_refund;
        }

        // Query Mutasi Stok
        $mutationQuery = DB::table('mpos_stock_mutations')
            ->whereBetween('dmutation', [$startDateTime, $endDateTime])
            ->where('cstatus', MposStockMutation::STATUS_POSTED);

        if (!empty($outletId)) {
            $mutationQuery->where('nid_outlet', $outletId);
        } elseif (!$isSuperAdmin) {
            $mutationQuery->whereIn('nid_outlet', $allowedOutletIds);
        }

        $mutationRows = $mutationQuery->select(
            'ctype',
            'nid_product',
            DB::raw('SUM(nqty) as total_qty')
        )->groupBy('ctype', 'nid_product')->get();

        $mutProdIds = $mutationRows->pluck('nid_product')->filter()->unique()->values();
        $mutProds = MposProduct::with('category')->whereIn('nid', $mutProdIds)->get()->keyBy('nid');

        foreach ($mutationRows as $r) {
            if (empty($r->nid_product)) continue;
            $prod = $mutProds->get($r->nid_product);
            if (!$prod) continue;
            $pId = $prod->nid;
            if (!isset($productMovements[$pId])) {
                $productMovements[$pId] = [
                    'category_name' => $prod->category?->cname ?: 'Produk',
                    'cname' => $prod->cname,
                    'qty_awal' => 0.0,
                    'qty_masuk' => 0.0,
                    'qty_pengembalian' => 0.0,
                    'qty_penjualan' => 0.0,
                    'qty_keluar' => 0.0,
                    'qty_sisa' => (float) $prod->nqty,
                ];
            }
            if ($r->ctype === 'IN') {
                $productMovements[$pId]['qty_masuk'] += (float) $r->total_qty;
            } else {
                $productMovements[$pId]['qty_keluar'] += (float) $r->total_qty;
            }
        }

        $allItems = collect(array_values($productMovements))->map(function ($item) {
            $awal = $item['qty_sisa'] - $item['qty_masuk'] - $item['qty_pengembalian'] + $item['qty_penjualan'] + $item['qty_keluar'];
            if ($awal < 0) $awal = 0.0;
            return [
                'category_name' => $item['category_name'],
                'cname' => $item['cname'],
                'qty_awal' => (int) $awal,
                'qty_masuk' => (int) $item['qty_masuk'],
                'qty_pengembalian' => (int) $item['qty_pengembalian'],
                'qty_penjualan' => (int) $item['qty_penjualan'],
                'qty_keluar' => (int) $item['qty_keluar'],
                'qty_sisa' => (int) $item['qty_sisa'],
            ];
        })->sortBy('cname')->values();

        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $currentPageItems = $allItems->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $currentPageItems,
            $allItems->count(),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'tab' => 'pergerakan_stok',
                'total_records' => $allItems->count(),
                'inventory' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'data' => $paginator->items(),
                ],
            ],
        ]);
    }

    public function showDetail(Request $request): JsonResponse
    {
        $user = $this->authenticateOfficeUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak terautentikasi.'], 401);
        }

        $type = strtolower((string) $request->input('type', 'stock_in'));
        $no = trim((string) $request->input('no'));

        if (empty($no)) {
            return response()->json(['success' => false, 'message' => 'Nomor dokumen wajib diisi.'], 422);
        }

        if (in_array($type, ['produksi_stok', 'production'], true)) {
            return $this->getProductionDetail($no);
        }

        if (in_array($type, ['stok_opname', 'opname'], true)) {
            return $this->getOpnameDetail($no);
        }

        $ctype = ($type === 'stock_out' || $type === 'stok_keluar') ? MposStockMutation::TYPE_OUT : MposStockMutation::TYPE_IN;

        $items = MposStockMutation::with(['outlet', 'targetOutlet', 'user', 'product.category', 'ingredient'])
            ->where('ctype', $ctype)
            ->where(function ($q) use ($no) {
                $q->where('cno_ref', $no)
                    ->orWhere('nid', $no);
            })
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Dokumen transaksi tidak ditemukan.'], 404);
        }

        $first = $items->first();
        $dmutation = $first->dmutation ? Carbon::parse($first->dmutation) : null;

        $details = $items->map(function ($d, $idx) {
            $isIng = $d->isIngredient();
            $itemType = $isIng ? 'Bahan Baku' : ($d->getItemTypeLabel() ?: 'Produk');
            $itemName = $d->product?->cname ?? ($isIng ? ($d->ingredient?->cname ?? 'Bahan Baku') : 'Produk');
            $itemCategory = $d->product?->category?->cname ?: ($isIng ? 'BAHAN BAKU' : 'PRODUK');
            $currentStock = (int) ($isIng ? ($d->ingredient?->nstock ?? 0) : ($d->product?->nqty ?? 0));
            $qty = (int) $d->nqty;
            $cost = (float) $d->ncost;
            $subtotal = (float) $d->nsubtotal;

            return [
                'no' => $idx + 1,
                'tipe' => $itemType,
                'item_name' => $itemName,
                'category_name' => strtoupper($itemCategory),
                'current_stock' => $currentStock,
                'qty' => $qty,
                'cost' => $cost,
                'cost_formatted' => 'Rp ' . number_format($cost, 0, ',', '.'),
                'subtotal' => $subtotal,
                'subtotal_formatted' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
            ];
        });

        $postedBy = $first->user?->cemail ?: ($first->user?->email ?: ($first->user?->cname ?: 'Admin'));
        $diterimaDari = !empty($first->targetOutlet) ? $first->targetOutlet->cname : ($first->outlet?->cname ?? 'Input Stok Masuk Manual');
        $transferKe = $first->targetOutlet?->cname ?? '-';

        $statusLabel = match (strtoupper((string) $first->cstatus)) {
            'POSTED' => 'Posted',
            'DRAFT' => 'Draf',
            'CANCELLED', 'VOID' => 'Batal',
            default => $first->cstatus ?: 'Draf',
        };

        return response()->json([
            'success' => true,
            'data' => [
                'cno_ref' => $first->cno_ref,
                'ctype' => $ctype,
                'dmutation' => $dmutation ? $dmutation->format('d-M-Y') : '-',
                'dmutation_full' => $dmutation ? $dmutation->format('d/m/Y H:i') : '-',
                'diterima_dari' => $diterimaDari,
                'transfer_ke' => $transferKe,
                'outlet_name' => $first->outlet?->cname ?: 'Matahati POS',
                'creason' => $first->creason ?: ($ctype === MposStockMutation::TYPE_IN ? 'Pembelian' : 'Lain-lain'),
                'cnote' => $first->cnote ?: '-',
                'cstatus' => $first->cstatus ?: 'DRAFT',
                'status_label' => $statusLabel,
                'posted_by' => $postedBy,
                'total_qty' => (int) $items->sum('nqty'),
                'total_cost' => (float) $items->sum('nsubtotal'),
                'total_cost_formatted' => 'Rp ' . number_format($items->sum('nsubtotal'), 0, ',', '.'),
                'details' => $details,
            ],
        ]);
    }

    private function getProductionDetail(string $no): JsonResponse
    {
        $prodH = MposProductionH::with([
            'outlet',
            'user',
            'products.product.category',
            'materials.product',
            'materials.ingredient'
        ])
        ->where('cno_production', $no)
        ->orWhere('nid', $no)
        ->first();

        if (!$prodH) {
            return response()->json(['success' => false, 'message' => 'Dokumen Produksi Stok tidak ditemukan.'], 404);
        }

        $dprod = $prodH->dproduction ? Carbon::parse($prodH->dproduction) : null;
        $createdBy = $prodH->user?->cemail ?: ($prodH->user?->email ?: ($prodH->user?->cname ?: 'Admin'));

        $statusLabel = match (strtoupper((string) $prodH->cstatus)) {
            'POSTED' => 'Posted',
            'DRAFT' => 'Draf',
            'CANCELLED', 'VOID' => 'Batal',
            default => $prodH->cstatus ?: 'Draf',
        };

        $materials = $prodH->materials->map(function ($m, $idx) {
            $isIng = $m->isIngredient();
            $name = $m->product?->cname ?? ($isIng ? ($m->ingredient?->cname ?? 'Bahan Baku') : 'Item');
            $unit = $m->getItemUnitAttribute();
            $qty = (float) $m->nqty;
            $cost = (float) $m->ncost;
            $subtotal = (float) $m->nsubtotal;

            return [
                'no' => $idx + 1,
                'item_name' => $name,
                'tipe' => $isIng ? 'BAHAN BAKU' : ($m->product ? 'PRODUK' : 'BAHAN BAKU'),
                'cost' => $cost,
                'cost_formatted' => 'Rp ' . number_format($cost, 0, ',', '.'),
                'qty_deducted' => -$qty,
                'qty_deducted_formatted' => '-' . (fmod($qty, 1) === 0.0 ? (int)$qty : number_format($qty, 2, ',', '.')),
                'unit' => strtoupper($unit),
                'subtotal' => $subtotal,
                'subtotal_formatted' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
            ];
        });

        $products = $prodH->products->map(function ($p, $idx) use ($materials) {
            $name = $p->product?->cname ?? 'Produk Hasil';
            $unit = $p->product?->csatuan ?: 'GRAM';
            $qty = (float) $p->nqty;

            return [
                'no' => $idx + 1,
                'product_name' => $name,
                'qty' => $qty,
                'qty_formatted' => number_format($qty, 2, ',', '.'),
                'unit' => strtoupper($unit),
                'materials_count' => count($materials),
                'materials' => $materials,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'type' => 'produksi_stok',
                'cno_ref' => $prodH->cno_production,
                'dmutation' => $dprod ? $dprod->format('d-M-Y') : '-',
                'dmutation_full' => $dprod ? $dprod->format('d M Y') : '-',
                'outlet_name' => $prodH->outlet?->cname ?: 'Matahati POS',
                'user_email' => $createdBy,
                'cnote' => $prodH->cnote ?: '-',
                'cstatus' => $prodH->cstatus ?: 'DRAFT',
                'status_label' => $statusLabel,
                'total_product_qty' => (float) $prodH->ntotal_product_qty,
                'total_product_qty_formatted' => number_format((float) $prodH->ntotal_product_qty, 2, ',', '.'),
                'products' => $products,
                'materials' => $materials,
            ],
        ]);
    }

    private function getOpnameDetail(string $no): JsonResponse
    {
        $opH = MposStockOpnameH::with([
            'outlet',
            'user',
            'details.product.category',
            'details.ingredient'
        ])
        ->where('cno_opname', $no)
        ->orWhere('nid', $no)
        ->first();

        if (!$opH) {
            return response()->json(['success' => false, 'message' => 'Dokumen Stok Opname tidak ditemukan.'], 404);
        }

        $dop = $opH->dopname ? Carbon::parse($opH->dopname) : null;
        $createdBy = $opH->user?->cemail ?: ($opH->user?->email ?: ($opH->user?->cname ?: 'Admin'));

        $statusLabel = match (strtoupper((string) $opH->cstatus)) {
            'POSTED' => 'Posted',
            'DRAFT' => 'Draf',
            'CANCELLED', 'VOID' => 'Batal',
            default => $opH->cstatus ?: 'Draf',
        };

        $details = $opH->details->map(function ($d, $idx) {
            $isIng = $d->isIngredient();
            $name = $d->product?->cname ?? ($isIng ? ($d->ingredient?->cname ?? 'Bahan Baku') : 'Item');
            $unit = $d->product?->csatuan ?? ($isIng ? ($d->ingredient?->csatuan ?? 'PCS') : 'PCS');
            $typeLabel = $d->getItemTypeLabel();

            $qtySystem = (float) $d->nqty_system;
            $qtyActual = (float) $d->nqty_actual;
            $qtyDiff = (float) $d->nqty_diff;

            return [
                'no' => $idx + 1,
                'product_name' => $name,
                'item_type' => $typeLabel,
                'unit' => strtoupper($unit),
                'category_unit_line' => 'STOK • ' . strtoupper($unit),
                'qty_system' => (int) $qtySystem,
                'qty_actual' => (int) $qtyActual,
                'qty_diff' => (int) $qtyDiff,
                'qty_diff_formatted' => ($qtyDiff > 0 ? '+' : '') . (int) $qtyDiff,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'type' => 'stok_opname',
                'cno_ref' => $opH->cno_opname,
                'dmutation' => $dop ? $dop->format('d-M-Y') : '-',
                'dmutation_full' => $dop ? $dop->format('d M Y') : '-',
                'outlet_name' => $opH->outlet?->cname ?: 'Matahati POS',
                'user_email' => $createdBy,
                'cnote' => $opH->cnote ?: 'Tidak ada catatan',
                'cstatus' => $opH->cstatus ?: 'DRAFT',
                'status_label' => $statusLabel,
                'total_items_count' => count($details),
                'total_system_qty' => (int) $opH->details->sum('nqty_system'),
                'total_actual_qty' => (int) $opH->details->sum('nqty_actual'),
                'total_diff_qty' => (int) $opH->details->sum('nqty_diff'),
                'details' => $details,
            ],
        ]);
    }

    public function printPdf(Request $request)
    {
        $user = $this->authenticateOfficeUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak terautentikasi.'], 401);
        }

        $type = strtolower((string) $request->input('type', 'stock_in'));
        $no = trim((string) $request->input('no'));

        if (empty($no)) {
            return response()->json(['success' => false, 'message' => 'Nomor dokumen wajib diisi.'], 422);
        }

        $ctype = ($type === 'stock_out' || $type === 'stok_keluar') ? MposStockMutation::TYPE_OUT : MposStockMutation::TYPE_IN;

        $items = MposStockMutation::with(['outlet', 'targetOutlet', 'user', 'product', 'ingredient'])
            ->where('ctype', $ctype)
            ->where(function ($q) use ($no) {
                $q->where('cno_ref', $no)
                    ->orWhere('nid', $no);
            })
            ->get();

        if ($items->isEmpty()) {
            abort(404, 'Dokumen transaksi tidak ditemukan.');
        }

        $first = $items->first();

        if ($ctype === MposStockMutation::TYPE_IN) {
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

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inventory.stock_in_print', compact('stockIn'));
            $pdf->setPaper('a4', 'portrait');
            return $pdf->stream("Stok_Masuk_{$stockIn->cno_stock_in}.pdf");
        } else {
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

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inventory.stock_out_print', compact('stockOut'));
            $pdf->setPaper('a4', 'portrait');
            return $pdf->stream("Stok_Keluar_{$stockOut->cno_stock_out}.pdf");
        }
    }

    private function authenticateOfficeUser(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            $bearer = $request->bearerToken() ?: $request->input('token');
            if ($bearer) {
                if (str_starts_with($bearer, 'Bearer ')) {
                    $bearer = substr($bearer, 7);
                }
                if (str_starts_with($bearer, 'logged_in_office_')) {
                    $userId = (int) substr($bearer, strlen('logged_in_office_'));
                    $user = \App\Models\Muser::find($userId);
                }
            }
        }

        if ($user) {
            Auth::setUser($user);
        }

        return $user;
    }
}
