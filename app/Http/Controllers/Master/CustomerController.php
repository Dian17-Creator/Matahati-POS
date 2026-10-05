<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposCust;
use App\Models\MposCustType;
use App\Models\MposOutlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CustomersImport;

class CustomerController extends Controller
{
    // ==========================================
    // API METHODS
    // ==========================================

    public function apiIndex(Request $request)
    {
        $query = MposCust::with('type');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('cname', 'like', "%{$search}%")
                    ->orWhere('cphone', 'like', "%{$search}%")
                    ->orWhere('cmembership_no', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('nid_outlet') && $request->nid_outlet != '') {
            $query->where('nid_outlet', $request->nid_outlet);
        }

        $customers = $query->orderBy('cname', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $customers
        ], 200);
    }

    public function apiShow(string $id)
    {
        $customer = MposCust::with('type')->find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer
        ], 200);
    }

    public function apiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nid_type' => 'nullable|exists:mpos_cust_type,nid',
            'factive' => 'nullable|boolean',
            'cname' => 'required|string|max:255',
            'cgender' => 'nullable|in:MALE,FEMALE',
            'cmembership_no' => 'nullable|string|max:100',
            'cphone' => 'nullable|string|max:20',
            'dbirth' => 'nullable|date',
            'cnotes' => 'nullable|string|max:500',
            'cemail' => 'nullable|email|max:255',
            'caddress' => 'nullable|string|max:500',
            'cpostal_code' => 'nullable|string|max:10',
            'ccountry' => 'nullable|string|max:100',
            'cprovince' => 'nullable|string|max:100',
            'ccity' => 'nullable|string|max:100',
            'cdistrict' => 'nullable|string|max:100',
            'nid_outlet' => 'nullable|exists:mpos_outlet,nid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (empty($data['nid_type'])) {
            $guestType = MposCustType::where('cname', 'Guest')->first();
            if ($guestType) {
                $data['nid_type'] = $guestType->nid;
            }
        }

        $customer = MposCust::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil ditambahkan',
            'data' => $customer->load('type')
        ], 201);
    }

    public function apiUpdate(Request $request, string $id)
    {
        $customer = MposCust::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nid_type' => 'nullable|exists:mpos_cust_type,nid',
            'cname' => 'required|string|max:255',
            'cgender' => 'nullable|in:MALE,FEMALE',
            'cmembership_no' => 'nullable|string|max:100',
            'cphone' => 'nullable|string|max:20',
            'dbirth' => 'nullable|date',
            'cnotes' => 'nullable|string|max:500',
            'cemail' => 'nullable|email|max:255',
            'caddress' => 'nullable|string|max:500',
            'cpostal_code' => 'nullable|string|max:10',
            'ccountry' => 'nullable|string|max:100',
            'cprovince' => 'nullable|string|max:100',
            'ccity' => 'nullable|string|max:100',
            'cdistrict' => 'nullable|string|max:100',
            'nid_outlet' => 'nullable|exists:mpos_outlet,nid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (empty($data['nid_type'])) {
            $guestType = MposCustType::where('cname', 'Guest')->first();
            if ($guestType) {
                $data['nid_type'] = $guestType->nid;
            }
        }

        $customer->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil diperbarui',
            'data' => $customer->load('type')
        ], 200);
    }

    public function apiDestroy(string $id)
    {
        $customer = MposCust::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }

        if ($customer->sales()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak dapat dihapus karena sudah memiliki riwayat transaksi.'
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil dihapus'
        ], 200);
    }

    public function apiTypes(Request $request)
    {
        $query = MposCustType::orderBy('nid', 'asc');

        if ($request->has('nid_outlet') && $request->nid_outlet != '') {
            $query->where('nid_outlet', $request->nid_outlet);
        }

        $types = $query->get();

        return response()->json([
            'success' => true,
            'data' => $types
        ], 200);
    }

    // ==========================================
    // WEB METHODS (Existing)
    // ==========================================

    public function index(Request $request)
    {
        $outlets = MposOutlet::orderBy('cname')->get();
        $customerTypes = MposCustType::whereIn('nid', function($q) {
            $q->select(DB::raw('MIN(nid)'))
              ->from('mpos_cust_type')
              ->groupBy('cname');
        })->orderBy('cname')->get();
        
        $query = MposCust::query();
        
        if ($request->has('nid_outlet') && $request->nid_outlet != '') {
            $query->where('nid_outlet', $request->nid_outlet);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('cname', 'like', "%{$search}%")
                  ->orWhere('cphone', 'like', "%{$search}%")
                  ->orWhere('cmembership_no', 'like', "%{$search}%")
                  ->orWhere('cemail', 'like', "%{$search}%");
            });
        }
        
        $uniqueCustomerIds = $query->select(DB::raw('MIN(nid) as nid'))
                                   ->groupBy('cname', 'cphone')
                                   ->pluck('nid');
                                   
        $customers = MposCust::with('type')
                             ->withMax('sales', 'dtransaction')
                             ->whereIn('nid', $uniqueCustomerIds)
                             ->orderBy('cname')
                             ->paginate(10)
                             ->withQueryString();
                             
        return view('customers.index', compact('customers', 'customerTypes', 'outlets'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'outlet_id' => 'required|exists:mpos_outlet,nid',
            'file' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        try {
            Excel::import(new CustomersImport($request->outlet_id), $request->file('file'));
            return back()->with('success', 'Data pelanggan berhasil diimport.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan saat import: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'nid_type' => 'nullable|exists:mpos_cust_type,nid',
            'factive' => 'nullable|boolean',
            'cname' => 'required|string|max:255',
            'cgender' => 'nullable|in:MALE,FEMALE',
            'cmembership_no' => 'nullable|string|max:100',
            'cphone' => 'nullable|string|max:20',
            'dbirth' => 'nullable|date',
            'cnotes' => 'nullable|string|max:500',
            'cemail' => 'nullable|email|max:255',
            'caddress' => 'nullable|string|max:500',
            'cpostal_code' => 'nullable|string|max:10',
            'ccountry' => 'nullable|string|max:100',
            'cprovince' => 'nullable|string|max:100',
            'ccity' => 'nullable|string|max:100',
            'cdistrict' => 'nullable|string|max:100',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
        ]);

        $data = $request->except(['outlet_ids', 'modal_id']);
        if (empty($data['nid_type'])) {
            $guestType = MposCustType::where('cname', 'Guest')->first();
            if ($guestType) {
                $data['nid_type'] = $guestType->nid;
            }
        }

        DB::beginTransaction();
        try {
            foreach ($request->outlet_ids as $outletId) {
                $exists = MposCust::where('cphone', $request->cphone)
                            ->where('cname', $request->cname)
                            ->where('nid_outlet', $outletId)
                            ->exists();
                
                if (!$exists) {
                    $insertData = $data;
                    $insertData['nid_outlet'] = $outletId;
                    MposCust::create($insertData);
                }
            }
            DB::commit();
            return redirect()->route('customers.index')
                ->with('success', 'Pelanggan berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nid_type' => 'nullable|exists:mpos_cust_type,nid',
            'factive' => 'nullable|boolean',
            'cname' => 'required|string|max:255',
            'cgender' => 'nullable|in:MALE,FEMALE',
            'cmembership_no' => 'nullable|string|max:100',
            'cphone' => 'nullable|string|max:20',
            'dbirth' => 'nullable|date',
            'cnotes' => 'nullable|string|max:500',
            'cemail' => 'nullable|email|max:255',
            'caddress' => 'nullable|string|max:500',
            'cpostal_code' => 'nullable|string|max:10',
            'ccountry' => 'nullable|string|max:100',
            'cprovince' => 'nullable|string|max:100',
            'ccity' => 'nullable|string|max:100',
            'cdistrict' => 'nullable|string|max:100',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
        ]);

        $customer = MposCust::findOrFail($id);
        $originalCname = $customer->cname;
        $originalCphone = $customer->cphone;

        $data = $request->except(['outlet_ids', 'modal_id']);
        if (empty($data['nid_type'])) {
            $guestType = MposCustType::where('cname', 'Guest')->first();
            if ($guestType) {
                $data['nid_type'] = $guestType->nid;
            }
        }

        DB::beginTransaction();
        try {
            // Update current customer
            $customer->update($data);

            foreach ($request->outlet_ids as $outletId) {
                if ($customer->nid_outlet == $outletId) {
                    continue;
                }

                $existingCustomer = MposCust::where('cname', $originalCname)
                            ->where('cphone', $originalCphone)
                            ->where('nid_outlet', $outletId)
                            ->first();

                if ($existingCustomer) {
                    $existingCustomer->update($data);
                } else {
                    $insertData = $data;
                    $insertData['nid_outlet'] = $outletId;
                    MposCust::create($insertData);
                }
            }

            // Optional: delete instances in outlets not selected
            MposCust::where('cname', $originalCname)
                    ->where('cphone', $originalCphone)
                    ->whereNotIn('nid_outlet', $request->outlet_ids)
                    ->delete();

            DB::commit();
            return redirect()->route('customers.index')
                ->with('success', 'Pelanggan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        $customer = MposCust::findOrFail($id);
        $cname = $customer->cname;
        $cphone = $customer->cphone;
        
        DB::beginTransaction();
        try {
            MposCust::where('cname', $cname)->where('cphone', $cphone)->delete();
            DB::commit();
            return redirect()->route('customers.index')
                ->with('success', 'Pelanggan berhasil dihapus dari semua outlet.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus pelanggan.');
        }
    }
}
