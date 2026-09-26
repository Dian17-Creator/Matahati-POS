<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposCust;
use App\Models\MposCustType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        $customers = $query->get();

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

    public function apiTypes()
    {
        $types = MposCustType::orderBy('nid', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $types
        ], 200);
    }

    // ==========================================
    // WEB METHODS (Existing)
    // ==========================================

    public function index()
    {
        $customers = MposCust::paginate(10);
        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'cphone' => 'nullable|string|max:20',
            'cemail' => 'nullable|email|max:255',
            'caddress' => 'nullable|string',
        ]);

        MposCust::create($request->all());

        return redirect()->route('customers.index')
            ->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'cphone' => 'nullable|string|max:20',
            'cemail' => 'nullable|email|max:255',
            'caddress' => 'nullable|string',
        ]);

        $customer = MposCust::findOrFail($id);
        $customer->update($request->all());

        return redirect()->route('customers.index')
            ->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $customer = MposCust::findOrFail($id);
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Pelanggan berhasil dihapus.');
    }
}
