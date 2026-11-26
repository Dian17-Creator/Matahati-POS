<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposUser;
use App\Models\MposUserSpv;
use App\Models\MposOutlet;
use App\Models\Muser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosUserController extends Controller
{
    public function index(): \Illuminate\Contracts\View\View
    {
        $posUsers = MposUser::with(['user', 'outlet', 'spvOutlets.outlet'])
            ->orderBy('nid')
            ->get();
        $outlets = MposOutlet::orderBy('cname', 'asc')->get();
        $users = Muser::where('factive', 1)->get();

        $roleGroups = [
            [
                'key' => 'fowner',
                'title' => 'OWNER',
                'description' => 'Pemilik dapat mengakses dan mengelola seluruh data, outlet, laporan, serta pengaturan sistem POS.',
                'items' => $posUsers->filter(fn (MposUser $pu): bool => (bool) $pu->fowner)->values(),
            ],
            [
                'key' => 'fspv',
                'title' => 'SUPERVISOR',
                'description' => 'Dapat mengakses dan mengelola seluruh data operasional, laporan, serta persetujuan POS pada outlet yang ditugaskan.',
                'items' => $posUsers->filter(fn (MposUser $pu): bool => (bool) $pu->fspv)->values(),
            ],
            [
                'key' => 'fcaptain',
                'title' => 'CAPTAIN',
                'description' => 'Mengelola operasional outlet yang ditugaskan, transaksi POS, shift, dan pengawasan kasir.',
                'items' => $posUsers->filter(fn (MposUser $pu): bool => (bool) $pu->fcaptain)->values(),
            ],
            [
                'key' => 'fcashier',
                'title' => 'CASHIER',
                'description' => 'Dapat mengakses aplikasi POS untuk transaksi penjualan, buka/tutup shift, dan melihat riwayat transaksi.',
                'items' => $posUsers->filter(fn (MposUser $pu): bool => (bool) $pu->fcashier)->values(),
            ],
        ];

        return view('pos-users.index', compact('posUsers', 'outlets', 'users', 'roleGroups'));
    }

    public function apiIndex(Request $request)
    {
        $outletId = $request->input('outlet_id') ?? $request->input('nid_outlet');
        $query = MposUser::with('user');

        if ($outletId) {
            $query->where(function ($q) use ($outletId) {
                $q->where('nid_outlet', $outletId)
                  ->orWhereHas('spvOutlets', function ($spvQ) use ($outletId) {
                      $spvQ->where('nid_outlet', $outletId);
                  });
            });
        }

        $posUsers = $query->get()->map(function ($posUser) {
            return [
                'id' => $posUser->nid,
                'nid_user' => $posUser->nid_user,
                'nid_outlet' => $posUser->nid_outlet,
                'name' => $posUser->user->cname ?? $posUser->user->name ?? 'Unknown',
                'username' => $posUser->user->cusername ?? $posUser->user->username ?? '',
                'role_owner' => (bool) $posUser->fowner,
                'role_spv' => (bool) $posUser->fspv,
                'role_cashier' => (bool) $posUser->fcashier,
                'role_captain' => (bool) $posUser->fcaptain,
                'role' => $posUser->fowner ? 'Owner' : ($posUser->fspv ? 'Supervisor' : ($posUser->fcaptain ? 'Captain' : ($posUser->fcashier ? 'Cashier' : 'Cashier'))),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $posUsers,
        ], 200);
    }

    public function store(Request $request)
    {
        $isSpv = $request->has('fspv');
        $isCashier = $request->has('fcashier');
        $isMultiOutlet = $isSpv || $isCashier;

        if ($isMultiOutlet) {
            $request->validate([
                'nid_user' => 'required|exists:muser,nid',
                'outlet_ids' => 'required|array|min:1',
                'outlet_ids.*' => 'exists:mpos_outlet,nid',
            ], [
                'outlet_ids.required' => 'Pilih minimal satu outlet.',
                'outlet_ids.min' => 'Pilih minimal satu outlet.',
            ]);

            $existingMultiUser = MposUser::where('nid_user', $request->nid_user)
                ->where(function ($q) use ($isSpv, $isCashier) {
                    if ($isSpv) {
                        $q->orWhere('fspv', 1);
                    }
                    if ($isCashier) {
                        $q->orWhere('fcashier', 1);
                    }
                })
                ->first();
            if ($existingMultiUser) {
                return redirect()->back()
                    ->withErrors(['nid_user' => 'Pengguna ini sudah terdaftar sebagai SPV/Kasir. Silakan edit data tersebut.'])
                    ->withInput();
            }

            $primaryOutletId = $request->outlet_ids[0];
        } else {
            $request->validate([
                'nid_user' => 'required|exists:muser,nid',
                'nid_outlet' => 'required|exists:mpos_outlet,nid',
            ]);

            $primaryOutletId = $request->nid_outlet;
        }

        $exists = MposUser::where('nid_user', $request->nid_user)
            ->where('nid_outlet', $primaryOutletId)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['nid_user' => 'Pengguna ini sudah terdaftar di outlet tersebut.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $isSpv, $isCashier, $isMultiOutlet, $primaryOutletId) {
            $data = [
                'nid_user' => $request->nid_user,
                'nid_outlet' => $primaryOutletId,
                'fowner' => $request->has('fowner') ? 1 : 0,
                'fspv' => $isSpv ? 1 : 0,
                'fcashier' => $isCashier ? 1 : 0,
                'fcaptain' => $request->has('fcaptain') ? 1 : 0,
            ];

            $posUser = MposUser::create($data);

            if ($isMultiOutlet && is_array($request->outlet_ids)) {
                foreach ($request->outlet_ids as $outletId) {
                    MposUserSpv::firstOrCreate([
                        'nid_user' => $posUser->nid_user,
                        'nid_outlet' => $outletId,
                    ]);
                }
            }
        });

        return redirect()->route('pos-users.index')
            ->with('success', 'Pengguna POS berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $posUser = MposUser::findOrFail($id);
        $isSpv = $request->has('fspv');
        $isCashier = $request->has('fcashier');
        $isMultiOutlet = $isSpv || $isCashier;

        if ($isMultiOutlet) {
            $request->validate([
                'nid_user' => 'required|exists:muser,nid',
                'outlet_ids' => 'required|array|min:1',
                'outlet_ids.*' => 'exists:mpos_outlet,nid',
            ], [
                'outlet_ids.required' => 'Pilih minimal satu outlet.',
                'outlet_ids.min' => 'Pilih minimal satu outlet.',
            ]);

            $existingMultiUser = MposUser::where('nid_user', $request->nid_user)
                ->where(function ($q) use ($isSpv, $isCashier) {
                    if ($isSpv) {
                        $q->orWhere('fspv', 1);
                    }
                    if ($isCashier) {
                        $q->orWhere('fcashier', 1);
                    }
                })
                ->where('nid', '!=', $posUser->nid)
                ->first();
            if ($existingMultiUser) {
                return redirect()->back()
                    ->withErrors(['nid_user' => 'Pengguna ini sudah terdaftar sebagai SPV/Kasir di baris lain.'])
                    ->withInput();
            }

            $primaryOutletId = $request->outlet_ids[0];
        } else {
            $request->validate([
                'nid_user' => 'required|exists:muser,nid',
                'nid_outlet' => 'required|exists:mpos_outlet,nid',
            ]);

            $primaryOutletId = $request->nid_outlet;
        }

        $duplicate = MposUser::where('nid_user', $request->nid_user)
            ->where('nid_outlet', $primaryOutletId)
            ->where('nid', '!=', $posUser->nid)
            ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->withErrors(['nid_user' => 'Pengguna ini sudah terdaftar di outlet tersebut.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $posUser, $isSpv, $isCashier, $isMultiOutlet, $primaryOutletId) {
            $oldNidUser = $posUser->nid_user;

            $data = [
                'nid_user' => $request->nid_user,
                'nid_outlet' => $primaryOutletId,
                'fowner' => $request->has('fowner') ? 1 : 0,
                'fspv' => $isSpv ? 1 : 0,
                'fcashier' => $isCashier ? 1 : 0,
                'fcaptain' => $request->has('fcaptain') ? 1 : 0,
            ];

            $posUser->update($data);

            // Clean previous multi-outlet assignments for this user
            MposUserSpv::where('nid_user', $oldNidUser)->delete();
            if ($oldNidUser != $posUser->nid_user) {
                MposUserSpv::where('nid_user', $posUser->nid_user)->delete();
            }

            if ($isMultiOutlet && is_array($request->outlet_ids)) {
                foreach ($request->outlet_ids as $outletId) {
                    MposUserSpv::firstOrCreate([
                        'nid_user' => $posUser->nid_user,
                        'nid_outlet' => $outletId,
                    ]);
                }
            }
        });

        return redirect()->route('pos-users.index')
            ->with('success', 'Pengguna POS berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $posUser = MposUser::findOrFail($id);

        DB::transaction(function () use ($posUser) {
            MposUserSpv::where('nid_user', $posUser->nid_user)->delete();
            $posUser->delete();
        });

        return redirect()->route('pos-users.index')
            ->with('success', 'Pengguna POS berhasil dihapus.');
    }
}
