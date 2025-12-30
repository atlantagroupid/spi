@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-gray-800 mb-0">Manajemen User & Sales</h1>
            <p class="text-muted small">Kelola akun, hak akses, dan target kinerja pegawai.</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah User Baru
        </a>
    </div>

    <div class="card shadow border-0">
        <div class="card-body">

            {{-- Pesan Sukses/Error (Opsional jika sudah ada di layout utama) --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Nama & Kontak</th>
                            <th>Role / Jabatan</th>
                            <th>Terdaftar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ $user->name }}</div>
                                    <small class="text-muted d-block">{{ $user->email }}</small>
                                    @if ($user->phone)
                                        <small class="text-success fw-bold" style="font-size: 0.75rem;">
                                            <i class="bi bi-whatsapp me-1"></i>{{ $user->phone }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    {{-- BADGE ROLE UPDATED --}}
                                    @switch($user->role)
                                        @case('manager_operasional')
                                            <span class="badge bg-dark">MANAGER OPERASIONAL</span>
                                        @break

                                        @case('manager_bisnis')
                                            <span class="badge bg-secondary">MANAGER BISNIS</span>
                                        @break

                                        @case('kepala_gudang')
                                            <span class="badge bg-warning text-dark">KEPALA GUDANG</span>
                                        @break

                                        @case('admin_gudang')
                                            <span class="badge bg-light text-warning border border-warning">ADMIN GUDANG</span>
                                        @break

                                        @case('sales_field')
                                            <span class="badge bg-success">SALES LAPANGAN</span>
                                        @break

                                        @case('sales_store')
                                            <span class="badge bg-info text-dark">SALES TOKO</span>
                                        @break

                                        @case('sales')
                                            <span class="badge bg-success">SALES (LAMA)</span>
                                        @break

                                        @case('finance')
                                            <span class="badge bg-primary">FINANCE</span>
                                        @break

                                        @case('purchase')
                                            <span class="badge bg-danger">PURCHASE</span>
                                        @break

                                        @case('kasir')
                                            <span class="badge bg-light text-success border border-success">KASIR</span>
                                        @break

                                        @default
                                            <span
                                                class="badge bg-light text-secondary border">{{ strtoupper(str_replace('_', ' ', $user->role)) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <div class="small text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $user->created_at->format('d M Y') }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('users.edit', $user->id) }}"
                                            class="btn btn-sm btn-outline-primary" title="Edit User">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        @if (auth()->id() != $user->id)
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('PERINGATAN: Menghapus user ini akan menghapus semua riwayat kunjungan dan order yang terkait. Lanjutkan?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Hapus User">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
                                        Belum ada data user yang terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 px-3">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    @endsection
