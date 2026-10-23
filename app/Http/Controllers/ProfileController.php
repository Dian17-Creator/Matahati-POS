<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\MposUser;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $posUser = MposUser::with('outlet')->where('nid_user', $user->nid)->first();
        $outletName = $posUser?->outlet?->cname ?? 'Matahati POS';

        return view('profile.index', compact('user', 'posUser', 'outletName'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'cname' => 'required|string|max:100',
            'cfullname' => 'nullable|string|max:150',
            'cphone' => 'nullable|string|max:30',
            'cemail' => 'required|email|max:100|unique:muser,cemail,' . $user->nid . ',nid',
        ], [
            'cname.required' => 'Nama wajib diisi.',
            'cemail.required' => 'Email wajib diisi.',
            'cemail.email' => 'Format email tidak valid.',
            'cemail.unique' => 'Email sudah digunakan oleh akun lain.',
        ]);

        $user->cname = $request->cname;
        if ($request->filled('cfullname')) {
            $user->cfullname = $request->cfullname;
        } else {
            $user->cfullname = $request->cname;
        }
        $user->cphone = $request->cphone;
        $user->cemail = $request->cemail;
        $user->save();

        return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'new_password.required' => 'Kata sandi baru wajib diisi.',
            'new_password.min' => 'Kata sandi baru minimal 6 karakter.',
            'new_password.confirmed' => 'Konfirmasi kata sandi baru tidak sesuai.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->cpassword)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak cocok.']);
        }

        $user->cpassword = Hash::make($request->new_password);
        $user->save();

        return redirect()->route('profile')->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
