<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MposOutlet;
use App\Models\MposUser;
use App\Models\MposUserSpv;
use App\Models\Muser;
use App\Traits\ResolvesOutletScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ResolvesOutletScope;

    public function login(Request $request)
    {
        $request->validate([
            'cemail' => ['required', 'email'],
            'cpassword' => ['required'],
        ]);

        if (Auth::attempt(['cemail' => $request->cemail, 'password' => $request->cpassword])) {
            $user = Auth::user();

            // Check mpos_user or mpos_user_spv
            $mposUser = MposUser::where('nid_user', $user->nid)->first();
            $hasSpvAccess = MposUserSpv::where('nid_user', $user->nid)->exists();

            if (($mposUser && ($mposUser->fowner || $mposUser->fspv || $mposUser->fcashier || $mposUser->fcaptain)) || $hasSpvAccess) {
                $isSpv = ($mposUser && $mposUser->fspv) || $hasSpvAccess;

                // Ambil semua outlet yang berhak diakses oleh user ini
                $allowedOutletIds = $this->getUserAllowedOutletIds();
                $outlets = MposOutlet::whereIn('nid', $allowedOutletIds)
                    ->get(['nid as id', 'cname as name'])
                    ->values();

                // Tentukan outlet default (prioritaskan nid_outlet yang ada di mposUser jika ada dalam daftar)
                $defaultOutlet = $outlets->firstWhere('id', $mposUser->nid_outlet ?? null) ?? $outlets->first();
                $defaultOutletId = $defaultOutlet ? (int) $defaultOutlet->id : ($mposUser->nid_outlet ?? null);
                $defaultOutletName = $defaultOutlet ? $defaultOutlet->name : ($mposUser->outlet->cname ?? null);

                // Return login success with list of allowed outlets
                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil',
                    'data' => [
                        'token' => 'logged_in_' . $user->nid,
                        'user' => [
                            'id' => $user->nid,
                            'name' => $user->cname,
                            'email' => $user->cemail,
                            'nid_outlet' => $defaultOutletId,
                            'outlet_name' => $defaultOutletName,
                            'role_owner' => (bool) ($mposUser->fowner ?? false),
                            'role_spv' => (bool) $isSpv,
                            'role_cashier' => (bool) ($mposUser->fcashier ?? false),
                            'role_captain' => (bool) ($mposUser->fcaptain ?? false),
                            'outlets' => $outlets,
                        ]
                    ]
                ], 200);
            }

            // If not authorized
            Auth::logout();

            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses ke POS (Hanya Owner, SPV, Cashier, atau Captain).',
            ], 403);
        }

        return response()->json([
            'success' => false,
            'message' => 'Email atau password salah.',
        ], 401);
    }

    /**
     * Endpoint untuk memuat ulang daftar outlet yang dapat diakses pengguna saat ini.
     */
    public function outlets(Request $request)
    {
        $user = $request->user() ?? Auth::user();

        if (!$user) {
            $bearer = $request->bearerToken();
            if ($bearer && str_starts_with($bearer, 'logged_in_')) {
                $userId = (int) substr($bearer, strlen('logged_in_'));
                $user = Muser::find($userId);
            }
        }

        if (!$user && $request->filled('user_id')) {
            $user = Muser::find($request->input('user_id'));
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        Auth::setUser($user);

        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $outlets = MposOutlet::whereIn('nid', $allowedOutletIds)
            ->get(['nid as id', 'cname as name'])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $outlets,
        ], 200);
    }
}
