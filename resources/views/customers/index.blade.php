@extends('layouts.app')

@section('title', 'Data Pelanggan')

@section('content')
    <div class="container-fluid px-0 px-md-3">

        {{-- HEADER: Judul Halaman (Hidden di Mobile) --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-none d-md-block">
                <h3 class="fw-bold text-primary">Data Toko / Pelanggan</h3>
                <p class="text-muted small mb-0">Kelola data pelanggan dan toko di sini.</p>
            </div>

            {{-- Tombol Tambah (Tetap muncul di Mobile tapi kecil) --}}
            @if (in_array(Auth::user()->role, ['sales_field', 'sales_store', 'manager_operasional']))
                <a href="{{ route('customers.create') }}" class="btn btn-primary shadow-sm ms-auto ms-md-0">
                    <i class="bi bi-shop me-1"></i> <span class="d-none d-sm-inline">Tambah Toko Baru</span><span class="d-sm-none">Baru</span>
                </a>
            @endif
        </div>

        {{-- ======================================================= --}}
        {{-- BAGIAN FILTER & PENCARIAN --}}
        {{-- ======================================================= --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form action="{{ route('customers.index') }}" method="GET">
                    <div class="row g-2 align-items-end">

                        {{-- 1. SEARCH BAR (Lebar menyesuaikan) --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small text-muted">Cari Data</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0"
                                    value="{{ request('search') }}" placeholder="Nama Toko / Pemilik / Alamat...">
                            </div>
                        </div>

                        {{-- 2. FILTER SALES (HANYA MANAGER OPS & BISNIS) --}}
                        @if (in_array(Auth::user()->role, ['manager_operasional', 'manager_bisnis']))
                            <div class="col-6 col-md-3">
                                <label class="form-label fw-bold small text-muted">Filter Sales</label>
                                <select name="sales_id" class="form-select">
                                    <option value="">- Semua Sales -</option>
                                    @if (isset($salesList))
                                        @foreach ($salesList as $sales)
                                            <option value="{{ $sales->id }}"
                                                {{ request('sales_id') == $sales->id ? 'selected' : '' }}>
                                                {{ $sales->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        @endif

                        {{-- 3. FILTER KATEGORI (Semua User) --}}
                        {{-- Jika bukan manager, kolom ini jadi lebih lebar di mobile --}}
                        <div class="{{ in_array(Auth::user()->role, ['manager_operasional', 'manager_bisnis']) ? 'col-6 col-md-3' : 'col-12 col-md-4' }}">
                            <label class="form-label fw-bold small text-muted">Kategori</label>
                            <select name="category" class="form-select">
                                <option value="">- Semua Kategori -</option>
                                @if (isset($categories))
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                                        {{ ucfirst($cat) }}
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        {{-- 4. TOMBOL AKSI --}}
                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary fw-bold flex-fill shadow-sm">
                                <i class="bi bi-funnel-fill me-1"></i> Filter
                            </button>

                            @if (request()->anyFilled(['search', 'sales_id', 'category']))
                                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary shadow-sm" title="Reset">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            @endif
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- TABEL DATA (RESPONSIVE) --}}
        {{-- ======================================================= --}}
        <div class="card shadow border-0 rounded-3 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3">Nama Toko</th>
                                <th class="d-none d-md-table-cell">Kategori</th>

                                {{-- Manager Only --}}
                                @if (in_array(Auth::user()->role, ['manager_operasional', 'manager_bisnis', 'admin_gudang']))
                                    <th class="d-none d-md-table-cell">Sales (PIC)</th>
                                @endif

                                <th class="d-none d-md-table-cell">Kontak</th>
                                <th>Alamat</th>
                                <th width="10%">Lokasi</th>

                                @if (in_array(Auth::user()->role, ['sales_field', 'sales_store', 'manager_operasional', 'manager_bisnis']))
                                    <th class="text-center pe-4">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $customer)
                                <tr>
                                    {{-- 1. Nama Toko & Owner --}}
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $customer->name }}</div>
                                        <div class="small text-muted">{{ $customer->owner_name ?? '' }}</div>
                                        {{-- Tampilkan Kategori & Kontak kecil di Mobile --}}
                                        <div class="d-md-none mt-1">
                                            <span class="badge bg-light text-dark border">{{ ucfirst($customer->category ?? 'Umum') }}</span>
                                            <div class="small text-muted mt-1"><i class="bi bi-telephone me-1"></i> {{ $customer->phone }}</div>
                                        </div>
                                    </td>

                                    {{-- 2. Kategori --}}
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info rounded-pill px-3">
                                            {{ ucfirst($customer->category ?? 'Umum') }}
                                        </span>
                                    </td>

                                    {{-- 3. Sales (PIC) - Manager Only --}}
                                    @if (in_array(Auth::user()->role, ['manager_operasional', 'manager_bisnis', 'admin_gudang']))
                                        <td class="d-none d-md-table-cell">
                                            @if ($customer->user)
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-light rounded-circle border d-flex justify-content-center align-items-center me-2" style="width:28px; height:28px; font-size: 0.7rem;">
                                                        {{ substr($customer->user->name, 0, 1) }}
                                                    </div>
                                                    <span class="small fw-bold">{{ explode(' ', $customer->user->name)[0] }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted fst-italic small">-</span>
                                            @endif
                                        </td>
                                    @endif

                                    {{-- 4. Kontak --}}
                                    <td class="d-none d-md-table-cell">
                                        <div class="small fw-bold">{{ $customer->contact_person ?? '-' }}</div>
                                        <small class="text-muted">{{ $customer->phone }}</small>
                                    </td>

                                    {{-- 5. Alamat --}}
                                    <td>
                                        <span class="d-inline-block text-truncate small" style="max-width: 150px;"
                                            title="{{ $customer->address }}">
                                            {{ $customer->address }}
                                        </span>
                                    </td>

                                    {{-- 6. Lokasi --}}
                                    <td>
                                        @if (!empty($customer->latitude) && !empty($customer->longitude))
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ $customer->latitude }},{{ $customer->longitude }}"
                                                target="_blank" class="btn btn-sm btn-outline-danger shadow-sm py-0 px-2" style="font-size: 0.75rem;">
                                                <i class="bi bi-geo-alt-fill me-1"></i> Peta
                                            </a>
                                        @else
                                            <span class="badge bg-secondary text-white-50" style="font-size: 0.65rem;">No Loc</span>
                                        @endif
                                    </td>

                                    {{-- 7. Aksi --}}
                                    @if (in_array(Auth::user()->role, ['sales_field', 'sales_store', 'manager_operasional', 'manager_bisnis']))
                                        <td class="text-center pe-4">
                                            <div class="btn-group">
                                                <a href="{{ route('customers.edit', $customer->id) }}"
                                                    class="btn btn-sm btn-light border text-primary" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <form action="{{ route('customers.destroy', $customer->id) }}" method="POST"
                                                class="d-inline" onsubmit="return confirm('Hapus toko ini?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"
                                                    onclick="confirmSubmit(event, 'Hapus Data?', 'Data yang dihapus tidak bisa dikembalikan!')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-shop-window fs-1 d-block mb-2 opacity-25"></i>
                                        <div>Data tidak ditemukan.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="p-3 border-top">
                    {{ $customers->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
