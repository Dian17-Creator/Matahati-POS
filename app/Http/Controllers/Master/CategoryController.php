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
        $productCountQuery = DB::table('mpos_grp_product')
            ->leftJoin('mpos_product', 'mpos_product.nid_category', '=', 'mpos_grp_product.nid');

        if (!empty($selectedOutletId)) {
            $productCountQuery->where('mpos_grp_product.nid_outlet', $selectedOutletId);
        } elseif (!$this->isSuperAdminUser()) {
            $productCountQuery->whereIn('mpos_grp_product.nid_outlet', $allowedOutletIds);
        }

        $productCounts = $productCountQuery
            ->select(
                'mpos_grp_product.cname',
                DB::raw('COUNT(DISTINCT mpos_product.cname) as product_count')
            )
            ->groupBy('mpos_grp_product.cname')
            ->pluck('product_count', 'cname');

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

        DB::beginTransaction();
        try {
            foreach ($request->outlet_ids as $outletId) {
                $exists = MposGrpProduct::where('cname', $request->cname)
                            ->where('nid_outlet', $outletId)
                            ->exists();
                
                if (!$exists) {
                    MposGrpProduct::create([
                        'cname' => $request->cname,
                        'nid_outlet' => $outletId,
                        'ftrack_stock' => $trackStock,
                    ]);
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

        DB::beginTransaction();
        try {
            // Update current category
            $category->update([
                'cname' => $request->cname,
                'ftrack_stock' => $trackStock,
            ]);

            // Sync ftrack_stock to all categories that share the same cname
            MposGrpProduct::where('cname', $originalCname)->update([
                'ftrack_stock' => $trackStock,
            ]);

            foreach ($request->outlet_ids as $outletId) {
                if ($category->nid_outlet == $outletId) {
                    continue;
                }

                $existingCategory = MposGrpProduct::where('cname', $originalCname)
                            ->where('nid_outlet', $outletId)
                            ->first();

                if ($existingCategory) {
                    $existingCategory->update([
                        'cname' => $request->cname,
                        'ftrack_stock' => $trackStock,
                    ]);
                } else {
                    $exists = MposGrpProduct::where('cname', $request->cname)
                                ->where('nid_outlet', $outletId)
                                ->exists();

                    if (!$exists) {
                        MposGrpProduct::create([
                            'cname' => $request->cname,
                            'nid_outlet' => $outletId,
                            'ftrack_stock' => $trackStock,
                        ]);
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
