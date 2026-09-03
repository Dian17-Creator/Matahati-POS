<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MposUser;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

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
                $request->session()->regenerate();
                return redirect()->intended('dashboard');
            }

            // If not authorized
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'cemail' => 'Anda tidak memiliki hak akses ke POS (Hanya Owner, Cashier, atau Captain).',
            ]);
        }

        return back()->withErrors([
            'cemail' => 'Email atau password salah.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
