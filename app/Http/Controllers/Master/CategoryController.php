<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposGrpProduct;
use App\Models\MposOutlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
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
        $query = MposGrpProduct::with('outlet');

        if ($request->has('nid_outlet') && $request->nid_outlet != '') {
            $query->where('nid_outlet', $request->nid_outlet);
        }

        $query->whereIn('nid', function($q) use ($request) {
            $q->select(DB::raw('MIN(nid)'))
              ->from('mpos_grp_product')
              ->groupBy('cname');
              
            if ($request->has('nid_outlet') && $request->nid_outlet != '') {
                $q->where('nid_outlet', $request->nid_outlet);
            }
        });

        $categories = $query->paginate(10)->withQueryString();
        $outlets = MposOutlet::orderBy('cname')->get();

        return view('categories.index', compact('categories', 'outlets'));
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

        DB::beginTransaction();
        try {
            // Update current category
            $category->update(['cname' => $request->cname]);

            foreach ($request->outlet_ids as $outletId) {
                if ($category->nid_outlet == $outletId) {
                    continue;
                }

                $existingCategory = MposGrpProduct::where('cname', $originalCname)
                            ->where('nid_outlet', $outletId)
                            ->first();

                if ($existingCategory) {
                    $existingCategory->update(['cname' => $request->cname]);
                } else {
                    $exists = MposGrpProduct::where('cname', $request->cname)
                                ->where('nid_outlet', $outletId)
                                ->exists();

                    if (!$exists) {
                        MposGrpProduct::create([
                            'cname' => $request->cname,
                            'nid_outlet' => $outletId,
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
        $category = MposGrpProduct::findOrFail($id);
        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
