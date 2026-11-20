<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Models\MposGrpProduct;
use App\Models\MposOutlet;
use App\Models\MposProduct;
use App\Traits\ResolvesOutletScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfficeProductController extends Controller
{
    use ResolvesOutletScope;

    public function getProducts(Request $request): JsonResponse
    {
        $user = $this->authenticateOfficeUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak terautentikasi.'], 401);
        }

        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $isSuperAdmin = $this->isSuperAdminUser();

        $perPage = (int) $request->input('per_page', 50);

        $query = MposProduct::with(['category', 'outlet']);

        if (!empty($selectedOutletId)) {
            $query->where('nid_outlet', $selectedOutletId);
        } elseif (!$isSuperAdmin) {
            $query->whereIn('nid_outlet', $allowedOutletIds);
        }

        // Filter Kategori
        if ($request->filled('nid_category') && (int) $request->input('nid_category') > 0) {
            $catId = (int) $request->input('nid_category');
            $selectedCategory = MposGrpProduct::find($catId);
            if ($selectedCategory) {
                $matchedCatIds = MposGrpProduct::whereRaw('LOWER(cname) = ?', [strtolower(trim((string) $selectedCategory->cname))])
                    ->pluck('nid')
                    ->all();
                $query->whereIn('nid_category', $matchedCatIds);
            } else {
                $query->where('nid_category', $catId);
            }
        }

        // Filter Search
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('cname', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($cq) use ($search) {
                        $cq->where('cname', 'like', "%{$search}%");
                    });
            });
        }

        // Filter Sembunyikan Stok Kosong
        if ($request->boolean('hide_empty') || $request->input('hide_empty') == '1') {
            $query->where('nqty', '>', 0);
        }

        $paginator = $query->orderBy('cname')->orderBy('nid_outlet')->paginate($perPage);

        $items = collect($paginator->items())->map(function ($row) {
            $nprice = (float) $row->nprice;
            $npriceOnline = (float) $row->nprice_online;
            $npriceBuy = (float) $row->nprice_buy;
            $nqty = (int) $row->nqty;

            $photoUrl = null;
            if (!empty($row->cphotos)) {
                $photoUrl = str_starts_with($row->cphotos, 'http')
                    ? $row->cphotos
                    : asset('storage/' . $row->cphotos);
            }

            return [
                'id' => $row->nid,
                'cname' => $row->cname,
                'photo_url' => $photoUrl,
                'category_name' => $row->category ? strtoupper($row->category->cname) : 'UMUM',
                'outlet_name' => $row->outlet ? $row->outlet->cname : 'Semua Outlet',
                'nprice' => $nprice,
                'nprice_formatted' => 'Rp ' . number_format($nprice, 0, ',', '.'),
                'nprice_online' => $npriceOnline,
                'nprice_online_formatted' => 'Rp ' . number_format($npriceOnline, 0, ',', '.'),
                'nprice_buy' => $npriceBuy,
                'nprice_buy_formatted' => 'Rp ' . number_format($npriceBuy, 0, ',', '.'),
                'nqty' => $nqty,
                'csatuan' => $row->csatuan ?: 'PCS',
                'cstatus' => $row->cstatus ?: 'ACTIVE',
            ];
        });

        // Opsi Kategori untuk Filter Dropdown
        $categoriesQuery = MposGrpProduct::query();
        if (!empty($selectedOutletId)) {
            $categoriesQuery->where('nid_outlet', $selectedOutletId);
        } elseif (!$isSuperAdmin) {
            $categoriesQuery->whereIn('nid_outlet', $allowedOutletIds);
        }

        $categoriesOptions = $categoriesQuery->select(DB::raw('MIN(nid) as nid'), 'cname')
            ->groupBy('cname')
            ->orderBy('cname', 'asc')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->nid,
                    'cname' => $c->cname,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_records' => $paginator->total(),
                'categories_options' => $categoriesOptions,
                'products' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'data' => $items,
                ],
            ],
        ]);
    }

    public function getCategories(Request $request): JsonResponse
    {
        $user = $this->authenticateOfficeUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak terautentikasi.'], 401);
        }

        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $isSuperAdmin = $this->isSuperAdminUser();

        $query = MposGrpProduct::with('outlet');

        if (!empty($selectedOutletId)) {
            $query->where('nid_outlet', $selectedOutletId);
        } elseif (!$isSuperAdmin) {
            $query->whereIn('nid_outlet', $allowedOutletIds);
        }

        $query->whereIn('nid', function ($q) use ($selectedOutletId, $allowedOutletIds, $isSuperAdmin) {
            $q->select(DB::raw('MIN(nid)'))
                ->from('mpos_grp_product')
                ->groupBy('cname');

            if (!empty($selectedOutletId)) {
                $q->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $q->whereIn('nid_outlet', $allowedOutletIds);
            }
        });

        $categories = $query->orderBy('cname', 'asc')->get();

        // Hitung jumlah produk per kategori
        $productCountQuery = DB::table('mpos_product')
            ->join('mpos_grp_product', 'mpos_product.nid_category', '=', 'mpos_grp_product.nid');

        if (!empty($selectedOutletId)) {
            $productCountQuery->where('mpos_product.nid_outlet', $selectedOutletId);
        } elseif (!$isSuperAdmin) {
            $productCountQuery->whereIn('mpos_product.nid_outlet', $allowedOutletIds);
        }

        $productCounts = $productCountQuery
            ->select(
                DB::raw('UPPER(TRIM(mpos_grp_product.cname)) as category_name'),
                DB::raw('COUNT(DISTINCT mpos_product.cname) as product_count')
            )
            ->groupBy(DB::raw('UPPER(TRIM(mpos_grp_product.cname))'))
            ->pluck('product_count', 'category_name');

        $search = trim((string) $request->input('search'));

        $items = $categories->map(function ($cat) use ($productCounts) {
            $catNameUpper = strtoupper(trim($cat->cname));
            $pCount = (int) ($productCounts->get($catNameUpper) ?? 0);
            $ftrackStock = (bool) ($cat->ftrack_stock ?? true);
            $fshowPos = (bool) ($cat->fshow_pos ?? true);

            $badgeLabel = null;
            if (!$ftrackStock) {
                $badgeLabel = 'Tanpa Stok';
            } elseif (!$fshowPos) {
                $badgeLabel = 'Sembunyi di POS';
            }

            return [
                'id' => $cat->nid,
                'cname' => $cat->cname,
                'product_count' => $pCount,
                'ftrack_stock' => $ftrackStock,
                'fshow_pos' => $fshowPos,
                'nid_outlet' => $cat->nid_outlet,
                'outlet_name' => $cat->outlet ? $cat->outlet->cname : 'Semua Outlet',
                'badge_label' => $badgeLabel,
            ];
        });

        if ($search !== '') {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item['cname'], $search) !== false;
            })->values();
        }

        $outlets = $this->getAvailableOutlets()->map(function ($o) {
            return [
                'id' => $o->nid,
                'cname' => $o->cname,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_records' => $items->count(),
                'outlets' => $outlets,
                'categories' => $items->values(),
            ],
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $user = $this->authenticateOfficeUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak terautentikasi.'], 401);
        }

        $request->validate([
            'cname' => 'required|string|max:100',
            'nid_outlet' => 'nullable|integer',
            'ftrack_stock' => 'nullable|boolean',
            'fshow_pos' => 'nullable|boolean',
        ]);

        $cname = trim($request->input('cname'));
        $outletId = $request->input('nid_outlet');
        $ftrackStock = $request->boolean('ftrack_stock', true);
        $fshowPos = $request->boolean('fshow_pos', true);

        DB::beginTransaction();
        try {
            $catPayload = [
                'cname' => $cname,
                'nid_outlet' => $outletId ?: null,
                'ftrack_stock' => $ftrackStock,
            ];
            if (MposGrpProduct::hasShowPosColumn()) {
                $catPayload['fshow_pos'] = $fshowPos;
            }

            $category = MposGrpProduct::create($catPayload);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Kategori '{$cname}' berhasil ditambahkan.",
                'data' => [
                    'id' => $category->nid,
                    'cname' => $category->cname,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan kategori produk: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function authenticateOfficeUser(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            $bearer = $request->bearerToken();
            if ($bearer && str_starts_with($bearer, 'logged_in_office_')) {
                $userId = (int) substr($bearer, strlen('logged_in_office_'));
                $user = \App\Models\Muser::find($userId);
            }
        }

        if ($user) {
            Auth::setUser($user);
        }

        return $user;
    }
}
