<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposGrpProduct;
use App\Models\MposOutlet;
use App\Traits\ResolvesOutletScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    use ResolvesOutletScope;

    public function apiIndex(Request $request)
    {
        $query = MposGrpProduct::query();
        if ($request->has('nid_outlet') && $request->nid_outlet != '') {
            $query->where('nid_outlet', $request->nid_outlet);
        }
        if (MposGrpProduct::hasShowPosColumn()) {
            $query->where('fshow_pos', 1);
        }
        $categories = $query->get();
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function index(Request $request)
    {
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $isCashierOnly = $this->isCashierOnlyUser();
        $isSuperAdmin = $this->isSuperAdminUser();
        $isOutletLocked = $this->isOutletLocked();
        $outlets = $this->getAvailableOutlets();
        $allowedOutletIds = $this->getUserAllowedOutletIds();

        $query = MposGrpProduct::with('outlet');

        if (!empty($selectedOutletId)) {
            $query->where('nid_outlet', $selectedOutletId);
        } elseif (!$this->isSuperAdminUser()) {
            $query->whereIn('nid_outlet', $allowedOutletIds);
        }

        $query->whereIn('nid', function($q) use ($selectedOutletId, $allowedOutletIds) {
            $q->select(DB::raw('MIN(nid)'))
              ->from('mpos_grp_product')
              ->groupBy('cname');
              
            if (!empty($selectedOutletId)) {
                $q->where('nid_outlet', $selectedOutletId);
            } elseif (!$this->isSuperAdminUser()) {
                $q->whereIn('nid_outlet', $allowedOutletIds);
            }
        });

        $categories = $query->orderBy('cname', 'asc')->get();

        // Hitung jumlah produk unik (distinct cname) per nama kategori sesuai scope outlet
        // Join mencocokkan nama kategori (case-insensitive) agar produk multi-outlet tetap terhitung
        $productCountQuery = DB::table('mpos_product')
            ->join('mpos_grp_product', 'mpos_product.nid_category', '=', 'mpos_grp_product.nid');

        if (!empty($selectedOutletId)) {
            $productCountQuery->where('mpos_product.nid_outlet', $selectedOutletId);
        } elseif (!$this->isSuperAdminUser()) {
            $productCountQuery->whereIn('mpos_product.nid_outlet', $allowedOutletIds);
        }

        $productCounts = $productCountQuery
            ->select(
                DB::raw('UPPER(TRIM(mpos_grp_product.cname)) as category_name'),
                DB::raw('COUNT(DISTINCT mpos_product.cname) as product_count')
            )
            ->groupBy(DB::raw('UPPER(TRIM(mpos_grp_product.cname))'))
            ->pluck('product_count', 'category_name');

        // Preload pemetaan outlet untuk setiap nama kategori agar bebas N+1 query di modal detail
        $categoryOutletsMap = MposGrpProduct::with('outlet')
            ->get()
            ->groupBy('cname');

        return view('categories.index', compact(
            'categories',
            'productCounts',
            'categoryOutletsMap',
            'outlets',
            'selectedOutletId',
            'isCashierOnly',
            'isSuperAdmin',
            'isOutletLocked',
            'allowedOutletIds'
        ));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
        ]);

        $trackStock = $request->has('ftrack_stock') ? 1 : 0;
        $showPos = $request->has('fshow_pos') ? 1 : 0;
        $hasShowPos = MposGrpProduct::hasShowPosColumn();

        DB::beginTransaction();
        try {
            foreach ($request->outlet_ids as $outletId) {
                $exists = MposGrpProduct::where('cname', $request->cname)
                            ->where('nid_outlet', $outletId)
                            ->exists();
                
                if (!$exists) {
                    $payload = [
                        'cname' => $request->cname,
                        'nid_outlet' => $outletId,
                        'ftrack_stock' => $trackStock,
                    ];
                    if ($hasShowPos) {
                        $payload['fshow_pos'] = $showPos;
                    }
                    MposGrpProduct::create($payload);
                }
            }
            DB::commit();
            return redirect()->route('categories.index')
                ->with('success', 'Kategori berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(string $id)
    {
        $category = MposGrpProduct::findOrFail($id);
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
        ]);

        $category = MposGrpProduct::findOrFail($id);
        $originalCname = $category->cname;
        $trackStock = $request->has('ftrack_stock') ? 1 : 0;
        $showPos = $request->has('fshow_pos') ? 1 : 0;
        $hasShowPos = MposGrpProduct::hasShowPosColumn();

        DB::beginTransaction();
        try {
            // Update current category
            $updatePayload = [
                'cname' => $request->cname,
                'ftrack_stock' => $trackStock,
            ];
            if ($hasShowPos) {
                $updatePayload['fshow_pos'] = $showPos;
            }
            $category->update($updatePayload);

            // Sync ftrack_stock & fshow_pos to all categories that share the same cname
            $syncPayload = [
                'ftrack_stock' => $trackStock,
            ];
            if ($hasShowPos) {
                $syncPayload['fshow_pos'] = $showPos;
            }
            MposGrpProduct::where('cname', $originalCname)->update($syncPayload);

            foreach ($request->outlet_ids as $outletId) {
                if ($category->nid_outlet == $outletId) {
                    continue;
                }

                $existingCategory = MposGrpProduct::where('cname', $originalCname)
                            ->where('nid_outlet', $outletId)
                            ->first();

                if ($existingCategory) {
                    $existingCategory->update($updatePayload);
                } else {
                    $exists = MposGrpProduct::where('cname', $request->cname)
                                ->where('nid_outlet', $outletId)
                                ->exists();

                    if (!$exists) {
                        $createPayload = [
                            'cname' => $request->cname,
                            'nid_outlet' => $outletId,
                            'ftrack_stock' => $trackStock,
                        ];
                        if ($hasShowPos) {
                            $createPayload['fshow_pos'] = $showPos;
                        }
                        MposGrpProduct::create($createPayload);
                    }
                }
            }
            DB::commit();
            return redirect()->route('categories.index')
                ->with('success', 'Kategori berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            $category = MposGrpProduct::findOrFail($id);
            $category->delete();

            return redirect()->route('categories.index')
                ->with('success', 'Kategori berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('categories.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.');
        }
    }
}
