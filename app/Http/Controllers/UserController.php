<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // 1. Tampilkan Daftar User
    public function index()
    {
        // Ambil semua user, urutkan dari yang terbaru
        $users = User::latest()->paginate(10);
        return view('users.index', compact('users'));
    }

    // 2. Form Tambah User Baru
    public function create()
    {
        return view('users.create');
    }

    // 3. Simpan User Baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,manager,sales',
            'daily_visit_target' => 'nullable|integer|min:0', // Target visit opsional
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            // Jika kosong, default 0
            'daily_visit_target' => $request->daily_visit_target ?? 0,
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan!');
    }

    // 4. Form Edit User
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
{
    // 1. Validasi Input
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => ['required', 'email', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)],
        'role' => 'required|string', // Kita izinkan string agar fleksibel dengan role baru
        'sales_target' => 'nullable|numeric|min:0',      // Validasi Target Omset
        'daily_visit_target' => 'nullable|integer|min:0', // Validasi Target Visit
    ]);

    // 2. Siapkan Data
    $data = [
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone, // Pastikan kolom 'phone' ada di $fillable User model
        'role' => $request->role,

        // Simpan Target KPI (Pakai operator ?? 0 untuk default)
        'sales_target' => $request->sales_target ?? 0,
        'daily_visit_target' => $request->daily_visit_target ?? 5,
    ];

    // 3. Cek Password (Hanya update jika diisi)
    if ($request->filled('password')) {
        $data['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
    }

    // 4. Eksekusi Update
    $user->update($data);

    return redirect()->route('users.index')->with('success', 'Data user & Target KPI berhasil diperbarui!');
}

    // 6. Hapus User
    public function destroy(User $user)
    {
        // Cegah admin menghapus dirinya sendiri saat login
        if (Auth::id() == $user->id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri!');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }

    public function updateSalesTarget(Request $request)
    {
        // 1. Validasi: Pastikan yang akses adalah Manager
        if (!in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional'])) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        // 2. Validasi Input
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'target'  => 'required|numeric|min:0'
        ]);

        // 3. Update Target
        $sales = \App\Models\User::findOrFail($request->user_id);
        $sales->update([
            'sales_target' => $request->target
        ]);

        return back()->with('success', 'Target omset untuk ' . $sales->name . ' berhasil diperbarui!');
    }
}
