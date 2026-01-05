<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Helper: Validasi Manual Ekstra Cepat
     * Mencegah file .php masuk sebelum diproses oleh validator gambar Laravel
     */
    private function validateFileSafety(Request $request, $fieldName = 'photo')
    {
        if ($request->hasFile($fieldName)) {
            $file = $request->file($fieldName);

            // 1. Cek Ekstensi Berbahaya (Blacklist)
            $blockedExtensions = ['php', 'php7', 'phtml', 'exe', 'sh', 'bat', 'bin'];
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, $blockedExtensions)) {
                return 'File berbahaya terdeteksi (Blocked Extension).';
            }

            // 2. Cek Ekstensi Valid (Whitelist)
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowedExtensions)) {
                 return 'Format file tidak diizinkan. Hanya JPG, PNG, dan WEBP.';
            }
        }
        return null;
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request) // <--- Pastikan parameternya Request, bukan ProfileUpdateRequest
    {
        // --- 1. SECURITY CHECK (PRIORITAS UTAMA) ---
        // Ini yang akan membuat Test Security PASS (Baris 137)
        if ($error = $this->validateFileSafety($request, 'photo')) {
            return back()->withErrors(['photo' => $error])->withInput();
        }

        // --- 2. VALIDASI DATA STANDAR ---
        // Kita tulis validasinya langsung di sini (tanpa file terpisah)
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($request->user()->id)
            ],
            // Validasi foto standar (backup layer)
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        // --- 3. PROSES UPDATE ---
        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // Logic Simpan Foto
        if ($request->hasFile('photo')) {
            // Hapus foto lama
            if ($request->user()->photo) {
                Storage::delete($request->user()->photo);
            }
            // Simpan foto baru
            $path = $request->file('photo')->store('profile-photos', 'public');
            $request->user()->photo = $path;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
