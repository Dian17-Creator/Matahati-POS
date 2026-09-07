<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MposUser;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'cemail' => ['required', 'email'],
            'cpassword' => ['required'],
        ]);

        if (Auth::attempt(['cemail' => $request->cemail, 'password' => $request->cpassword])) {
            $user = Auth::user();

            // Check mpos_user
            $mposUser = MposUser::where('nid_user', $user->nid)->first();

            if ($mposUser && ($mposUser->fowner || $mposUser->fcashier || $mposUser->fcaptain)) {
                // Return login success without needing personal_access_tokens table
                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil',
                    'data' => [
                        'token' => 'logged_in_' . $user->nid,
                        'user' => [
                            'id' => $user->nid,
                            'name' => $user->cname,
                            'email' => $user->cemail,
                            'role_owner' => $mposUser->fowner,
                            'role_cashier' => $mposUser->fcashier,
                            'role_captain' => $mposUser->fcashier,
                        ]
                    ]
                ], 200);
            }

            // If not authorized
            Auth::logout();

            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses ke POS (Hanya Owner, Cashier, atau Captain).',
            ], 403);
        }

        return response()->json([
            'success' => false,
            'message' => 'Email atau password salah.',
        ], 401);
    }
}
