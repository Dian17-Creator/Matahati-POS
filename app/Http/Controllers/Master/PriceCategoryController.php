<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposPriceCategory;
use App\Models\MposOutlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = MposPriceCategory::with('outlet');

        if ($request->has('nid_outlet') && $request->nid_outlet != '') {
            $query->where('nid_outlet', $request->nid_outlet);
        }

        $query->whereIn('nid', function($q) use ($request) {
            $q->select(DB::raw('MIN(nid)'))
              ->from('mpos_price_category')
              ->groupBy('cname');
              
            if ($request->has('nid_outlet') && $request->nid_outlet != '') {
                $q->where('nid_outlet', $request->nid_outlet);
            }
        });

        $priceCategories = $query->paginate(10)->withQueryString();
        $outlets = MposOutlet::orderBy('cname')->get();

        return view('price-categories.index', compact('priceCategories', 'outlets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cname' => 'required|string|max:100',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->outlet_ids as $outletId) {
                $exists = MposPriceCategory::where('cname', $request->cname)
                            ->where('nid_outlet', $outletId)
                            ->exists();
                
                if (!$exists) {
                    MposPriceCategory::create([
                        'cname' => $request->cname,
                        'nid_outlet' => $outletId,
                    ]);
                }
            }
            DB::commit();
            return redirect()->route('price-categories.index')
                ->with('success', 'Kategori harga berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'cname' => 'required|string|max:100',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
        ]);

        $priceCategory = MposPriceCategory::findOrFail($id);
        $originalCname = $priceCategory->cname;

        DB::beginTransaction();
        try {
            $priceCategory->update(['cname' => $request->cname]);

            foreach ($request->outlet_ids as $outletId) {
                if ($priceCategory->nid_outlet == $outletId) {
                    continue;
                }

                $existingPriceCategory = MposPriceCategory::where('cname', $originalCname)
                            ->where('nid_outlet', $outletId)
                            ->first();

                if ($existingPriceCategory) {
                    $existingPriceCategory->update(['cname' => $request->cname]);
                } else {
                    $exists = MposPriceCategory::where('cname', $request->cname)
                                ->where('nid_outlet', $outletId)
                                ->exists();

                    if (!$exists) {
                        MposPriceCategory::create([
                            'cname' => $request->cname,
                            'nid_outlet' => $outletId,
                        ]);
                    }
                }
            }
            DB::commit();
            return redirect()->route('price-categories.index')
                ->with('success', 'Kategori harga berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        $priceCategory = MposPriceCategory::findOrFail($id);
        
        // Prevent deleting if used in customer types or product prices
        if ($priceCategory->customerTypes()->exists() || $priceCategory->productPrices()->exists()) {
            return redirect()->route('price-categories.index')
                ->with('error', 'Kategori harga tidak dapat dihapus karena sudah digunakan.');
        }

        $priceCategory->delete();

        return redirect()->route('price-categories.index')
            ->with('success', 'Kategori harga berhasil dihapus.');
    }
}
