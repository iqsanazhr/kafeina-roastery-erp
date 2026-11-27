<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show the login screen.
     */
    public function showLogin()
    {
        $users = User::with(['role', 'branch'])->where('is_active', true)->get();

        return view('auth.login', compact('users'));
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            session(['active_user_id' => Auth::id()]);

            return redirect()->intended(route('dashboard.index'));
        }

        return back()->withErrors([
            'email' => 'Kredensial yang diberikan tidak cocok dengan catatan kami.',
        ])->onlyInput('email');
    }

    /**
     * Fast switch account for Super Admin multi-role supervision.
     */
    public function switchAccount(Request $request)
    {
        $current = Auth::user();
        if (! $current || (! $current->isSuperAdmin() && ! session()->has('impersonator_id'))) {
            abort(403, 'Akses ditolak: Alih akun hanya diizinkan untuk Super Administrator.');
        }

        $userId = $request->input('user_id');
        $user = User::with(['role', 'branch'])->findOrFail($userId);

        if ($current->isSuperAdmin() && ! session()->has('impersonator_id')) {
            session(['impersonator_id' => $current->id]);
        } elseif ((int) session('impersonator_id') === $user->id) {
            session()->forget('impersonator_id');
        }

        Auth::login($user);
        session(['active_user_id' => $user->id]);

        return back()->with('success', 'Berhasil beralih akun ke '.$user->name.' ('.($user->role->display_name ?? 'User').')');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem KAFEINA ERP.');
    }

    /**
     * Update current authenticated user's profile and credentials.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:120', "unique:users,email,{$user->id}"],
            'pin_code' => ['nullable', 'digits:6'],
            'current_password' => ['nullable', 'required_with:new_password', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap pengguna wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email ini sudah terdaftar oleh pengguna lain.',
            'pin_code.digits' => 'PIN kasir POS wajib berupa 6 digit angka.',
            'current_password.required_with' => 'Password saat ini wajib diisi jika ingin membuat password baru.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        // Validasi password saat ini jika user ingin mengubah password
        if (! empty($validated['new_password'])) {
            if (! Hash::check($validated['current_password'], $user->password)) {
                return back()
                    ->withErrors(['current_password' => 'Password saat ini yang Anda masukkan salah.'])
                    ->with('open_user_account_modal', true)
                    ->withInput();
            }
            $user->password = Hash::make($validated['new_password']);
        }

        // Update PIN kasir jika diisi
        if (! empty($validated['pin_code'])) {
            $user->pin_code = Hash::make($validated['pin_code']);
            $user->pin_failed_attempts = 0;
            $user->pin_locked_until = null;
        }

        $user->name = $validated['name'];
        $user->email = strtolower(trim($validated['email']));
        $user->save();

        return back()->with('success', "Profil dan pengaturan akun {$user->name} berhasil diperbarui.");
    }
}
