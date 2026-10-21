<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposSupplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = MposSupplier::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('cname', 'like', "%{$search}%")
                  ->orWhere('ccontact_person', 'like', "%{$search}%")
                  ->orWhere('cphone', 'like', "%{$search}%")
                  ->orWhere('cemail', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('factive', $request->status === 'active' ? 1 : 0);
        }

        $suppliers = $query->withCount('purchases')
            ->orderBy('cname', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cname' => 'required|string|max:255',
            'ccontact_person' => 'nullable|string|max:255',
            'cphone' => 'nullable|string|max:50',
            'cemail' => 'nullable|email|max:255',
            'caddress' => 'nullable|string',
            'cnote' => 'nullable|string',
            'clogo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'cname.required' => 'Nama Supplier wajib diisi.',
            'clogo.image' => 'File logo harus berupa gambar (jpg, png, webp).',
            'clogo.max' => 'Ukuran logo maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error_modal', 'create');
        }

        $data = $validator->validated();
        $data['factive'] = $request->has('factive') ? 1 : 1;
        $data['dcreated'] = now();

        if ($request->hasFile('clogo')) {
            $file = $request->file('clogo');
            $filename = 'supplier_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $targetDir = public_path('uploads/supplier');
            if (!file_exists($targetDir)) {
                @mkdir($targetDir, 0775, true);
            }
            $file->move($targetDir, $filename);
            $data['clogo'] = 'uploads/supplier/' . $filename;
        }

        MposSupplier::create($data);

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        $supplier = MposSupplier::with(['purchases' => function ($q) {
            $q->orderBy('dpurchase', 'desc');
        }])->findOrFail($id);

        return view('suppliers.show', compact('supplier'));
    }

    public function update(Request $request, string $id)
    {
        $supplier = MposSupplier::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'cname' => 'required|string|max:255',
            'ccontact_person' => 'nullable|string|max:255',
            'cphone' => 'nullable|string|max:50',
            'cemail' => 'nullable|email|max:255',
            'caddress' => 'nullable|string',
            'cnote' => 'nullable|string',
            'factive' => 'nullable|boolean',
            'clogo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'cname.required' => 'Nama Supplier wajib diisi.',
            'clogo.image' => 'File logo harus berupa gambar (jpg, png, webp).',
            'clogo.max' => 'Ukuran logo maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error_modal', 'edit_' . $id);
        }

        $data = $validator->validated();
        $data['factive'] = $request->has('factive') ? 1 : 0;

        if ($request->hasFile('clogo')) {
            if ($supplier->clogo) {
                if (file_exists(public_path($supplier->clogo))) {
                    @unlink(public_path($supplier->clogo));
                } elseif (Storage::disk('public')->exists($supplier->clogo)) {
                    Storage::disk('public')->delete($supplier->clogo);
                }
            }

            $file = $request->file('clogo');
            $filename = 'supplier_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $targetDir = public_path('uploads/supplier');
            if (!file_exists($targetDir)) {
                @mkdir($targetDir, 0775, true);
            }
            $file->move($targetDir, $filename);
            $data['clogo'] = 'uploads/supplier/' . $filename;
        }

        $supplier->update($data);

        return redirect()->back()
            ->with('success', 'Data supplier berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $supplier = MposSupplier::findOrFail($id);

        if ($supplier->purchases()->exists()) {
            return redirect()->back()
                ->with('error', 'Supplier tidak dapat dihapus karena sudah memiliki riwayat transaksi pembelian.');
        }

        if ($supplier->clogo) {
            if (file_exists(public_path($supplier->clogo))) {
                @unlink(public_path($supplier->clogo));
            } elseif (Storage::disk('public')->exists($supplier->clogo)) {
                Storage::disk('public')->delete($supplier->clogo);
            }
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier berhasil dihapus.');
    }
}
