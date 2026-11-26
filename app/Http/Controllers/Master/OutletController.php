<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposOutlet;
use App\Models\Mdepartment;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    public function index()
    {
        $outlets = MposOutlet::with('department')->orderBy('cname', 'asc')->paginate(10);
        $departments = Mdepartment::orderBy('cname', 'asc')->get();
        
        return view('outlets.index', compact('outlets', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cname' => 'required|string|max:255',
            'nid_dept' => 'nullable|exists:mdepartment,nid',
            'ctransaction_type' => 'required|string|in:ONLINE,OFFLINE',
        ]);

        MposOutlet::create([
            'cname' => $validated['cname'],
            'nid_dept' => $validated['nid_dept'] ?? null,
            'ctransaction_type' => strtoupper($validated['ctransaction_type']),
        ]);

        return redirect()->route('outlets.index')
            ->with('success', 'Outlet berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'cname' => 'required|string|max:255',
            'nid_dept' => 'nullable|exists:mdepartment,nid',
            'ctransaction_type' => 'required|string|in:ONLINE,OFFLINE',
        ]);

        $outlet = MposOutlet::findOrFail($id);
        $outlet->update([
            'cname' => $validated['cname'],
            'nid_dept' => $validated['nid_dept'] ?? null,
            'ctransaction_type' => strtoupper($validated['ctransaction_type']),
        ]);

        return redirect()->route('outlets.index')
            ->with('success', 'Outlet berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $outlet = MposOutlet::findOrFail($id);
        $outlet->delete();

        return redirect()->route('outlets.index')
            ->with('success', 'Outlet berhasil dihapus.');
    }
}
