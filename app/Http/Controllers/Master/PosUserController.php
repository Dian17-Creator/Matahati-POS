<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposUser;
use App\Models\MposOutlet;
use App\Models\muser;
use Illuminate\Http\Request;

class PosUserController extends Controller
{
    public function index()
    {
        $posUsers = MposUser::with(['user', 'outlet'])->paginate(10);
        $outlets = MposOutlet::all();
        $users = muser::all();
        
        return view('pos-users.index', compact('posUsers', 'outlets', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nid_user' => 'required|exists:muser,nid',
            'nid_outlet' => 'required|exists:mpos_outlet,nid',
        ]);

        $data = $request->all();
        $data['fowner'] = $request->has('fowner') ? 1 : 0;
        $data['fcashier'] = $request->has('fcashier') ? 1 : 0;
        $data['fcaptain'] = $request->has('fcaptain') ? 1 : 0;

        MposUser::create($data);

        return redirect()->route('pos-users.index')
            ->with('success', 'Pengguna POS berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nid_user' => 'required|exists:muser,nid',
            'nid_outlet' => 'required|exists:mpos_outlet,nid',
        ]);

        $posUser = MposUser::findOrFail($id);
        
        $data = $request->all();
        $data['fowner'] = $request->has('fowner') ? 1 : 0;
        $data['fcashier'] = $request->has('fcashier') ? 1 : 0;
        $data['fcaptain'] = $request->has('fcaptain') ? 1 : 0;

        $posUser->update($data);

        return redirect()->route('pos-users.index')
            ->with('success', 'Pengguna POS berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $posUser = MposUser::findOrFail($id);
        $posUser->delete();

        return redirect()->route('pos-users.index')
            ->with('success', 'Pengguna POS berhasil dihapus.');
    }
}
