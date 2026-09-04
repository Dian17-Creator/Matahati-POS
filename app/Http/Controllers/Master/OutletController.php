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
        $outlets = MposOutlet::with('department')->paginate(10);
        $departments = Mdepartment::orderBy('cname', 'asc')->get();
        
        return view('outlets.index', compact('outlets', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'nid_dept' => 'nullable|exists:mdepartment,nid',
        ]);

        MposOutlet::create($request->all());

        return redirect()->route('outlets.index')
            ->with('success', 'Outlet berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'nid_dept' => 'nullable|exists:mdepartment,nid',
        ]);

        $outlet = MposOutlet::findOrFail($id);
        $outlet->update($request->all());

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
