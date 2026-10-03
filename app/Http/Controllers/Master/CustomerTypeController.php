<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposCustType;
use App\Models\MposOutlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = MposCustType::with('outlet');

        if ($request->has('nid_outlet') && $request->nid_outlet != '') {
            $query->where('nid_outlet', $request->nid_outlet);
        }

        $query->whereIn('nid', function($q) use ($request) {
            $q->select(DB::raw('MIN(nid)'))
              ->from('mpos_cust_type')
              ->groupBy('cname');
              
            if ($request->has('nid_outlet') && $request->nid_outlet != '') {
                $q->where('nid_outlet', $request->nid_outlet);
            }
        });

        $customerTypes = $query->paginate(10)->withQueryString();
        $outlets = MposOutlet::orderBy('cname')->get();

        return view('customer-types.index', compact('customerTypes', 'outlets'));
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
                $exists = MposCustType::where('cname', $request->cname)
                            ->where('nid_outlet', $outletId)
                            ->exists();
                
                if (!$exists) {
                    MposCustType::create([
                        'cname' => $request->cname,
                        'nid_outlet' => $outletId,
                    ]);
                }
            }
            DB::commit();
            return redirect()->route('customer-types.index')
                ->with('success', 'Kategori pelanggan berhasil ditambahkan.');
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

        $customerType = MposCustType::findOrFail($id);
        $originalCname = $customerType->cname;

        DB::beginTransaction();
        try {
            // Update current customer type
            $customerType->update(['cname' => $request->cname]);

            foreach ($request->outlet_ids as $outletId) {
                if ($customerType->nid_outlet == $outletId) {
                    continue;
                }

                $existingCustomerType = MposCustType::where('cname', $originalCname)
                            ->where('nid_outlet', $outletId)
                            ->first();

                if ($existingCustomerType) {
                    $existingCustomerType->update(['cname' => $request->cname]);
                } else {
                    $exists = MposCustType::where('cname', $request->cname)
                                ->where('nid_outlet', $outletId)
                                ->exists();

                    if (!$exists) {
                        MposCustType::create([
                            'cname' => $request->cname,
                            'nid_outlet' => $outletId,
                        ]);
                    }
                }
            }
            DB::commit();
            return redirect()->route('customer-types.index')
                ->with('success', 'Kategori pelanggan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        $customerType = MposCustType::findOrFail($id);
        
        // Prevent deleting if there are customers using this type
        if ($customerType->customers()->exists()) {
            return redirect()->route('customer-types.index')
                ->with('error', 'Kategori tidak dapat dihapus karena sudah digunakan oleh pelanggan.');
        }

        $customerType->delete();

        return redirect()->route('customer-types.index')
            ->with('success', 'Kategori pelanggan berhasil dihapus.');
    }
}
