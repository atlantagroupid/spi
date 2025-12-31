@extends('layouts.app')

@section('title', 'Data Pelanggan')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-gray-800">Data Toko / Pelanggan</h1>
        @if (in_array(Auth::user()->role, ['sales_field', 'sales_store', 'manager_operasional']))
            <a href="{{ route('customers.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-shop me-1"></i> Tambah Toko Baru
            </a>
        @endif
    </div>

    {{-- ======================================================= --}}
    {{-- BAGIAN FILTER & PENCARIAN (TAMPILAN BARU) --}}
    {{-- ======================================================= --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('customers.index') }}" method="GET">
                <div class="row g-3">

                    {{-- 1. SEARCH BAR (Untuk Semua User) --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Cari Data</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0"
                                value="{{ request('search') }}" placeholder="Nama Toko / Pemilik / Alamat...">
                        </div>
                    </div>

                    {{-- 2. FILTER SALES (Hanya muncul jika BUKAN Sales) --}}
                    @if (Auth::user()->role !== 'sales')
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Filter Sales</label>
                            <select name="sales_id" class="form-select">
                                <option value="">- Semua Sales -</option>
                                {{-- Pastikan variabel $salesList dikirim dari Controller --}}
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

                    {{-- 3. FILTER KATEGORI (Baru) --}}
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Kategori Toko</label>
                        <select name="category" class="form-select">
                            <option value="">- Semua Kategori -</option>
                            {{-- Pastikan variabel $categories dikirim dari Controller --}}
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
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill fw-bold" title="Terapkan Filter">
                            <i class="bi bi-funnel-fill"></i> Filter
                        </button>

                        {{-- Tombol Reset muncul jika ada filter aktif --}}
                        @if (request()->anyFilled(['search', 'sales_id', 'category']))
                            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary" title="Reset">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        @endif
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- TABEL DATA --}}
    {{-- ======================================================= --}}
    <div class="card shadow border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-4">Nama Toko</th>
                            <th>Kategori</th> {{-- KOLOM BARU --}}

                            {{-- Tampilkan kolom Sales hanya untuk Admin/Manager --}}
                            @if (Auth::user()->role !== 'sales')
                                <th>Sales (PIC)</th>
                            @endif

                            <th>Kontak</th>
                            <th>Alamat</th>
                            <th width="15%">Lokasi</th>

                            @if (in_array(Auth::user()->role, ['sales', 'manager_operasional', 'manager_bisnis']))
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
                                </td>

                                {{-- 2. Kategori (KOLOM BARU) --}}
                                <td>
                                    @if ($customer->category)
                                        <span class="badge bg-info text-dark rounded-pill px-3">
                                            {{ ucfirst($customer->category) }}
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>

                                {{-- 3. Sales (PIC) - Khusus Admin --}}
                                @if (Auth::user()->role !== 'sales')
                                    <td>
                                        @if ($customer->user)
                                            <div class="d-flex align-items-center">
                                                <div class="badge bg-light text-dark border me-2">
                                                    {{ substr($customer->user->name, 0, 1) }}
                                                </div>
                                                <span class="small">{{ $customer->user->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted fst-italic small">Tanpa Sales</span>
                                        @endif
                                    </td>
                                @endif

                                {{-- 4. Kontak --}}
                                <td>
                                    {{ $customer->contact_person ?? '-' }}<br>
                                    <small class="text-muted">{{ $customer->phone }}</small>
                                </td>

                                {{-- 5. Alamat --}}
                                <td>
                                    <span class="d-inline-block text-truncate" style="max-width: 150px;"
                                        title="{{ $customer->address }}">
                                        {{ $customer->address }}
                                    </span>
                                </td>

                                {{-- 6. maps --}}
                                <td>
                                    {{-- LOGIKA TAMPILKAN TOMBOL MAPS --}}
                                    @if (!empty($customer->latitude) && !empty($customer->longitude))
                                        {{-- Tombol Buka Google Maps --}}
                                        <a href="https://www.google.com/maps/search/?api=1&query={{ $customer->latitude }},{{ $customer->longitude }}"
                                            target="_blank" class="btn btn-sm btn-outline-primary shadow-sm fw-bold mb-1">
                                            <i class="bi bi-map-fill me-1"></i> Lihat Peta
                                        </a>

                                        {{-- Teks Koordinat Kecil --}}
                                        <div class="d-flex align-items-center text-muted small" style="font-size: 0.75rem;">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            {{ Str::limit($customer->latitude, 7) }},
                                            {{ Str::limit($customer->longitude, 7) }}
                                        </div>
                                    @else
                                        {{-- Jika Belum Ada Koordinat --}}
                                        <span class="badge bg-secondary text-white-50">
                                            <i class="bi bi-slash-circle me-1"></i> No Loc
                                        </span>
                                    @endif
                                </td>

                                {{-- 7. Aksi --}}
                                @if (in_array(Auth::user()->role, ['sales', 'manager_operasional', 'manager_bisnis']))
                                    <td class="text-center pe-4">
                                        <div class="btn-group">
                                            <a href="{{ route('customers.edit', $customer->id) }}"
                                                class="btn btn-sm btn-outline-warning" title="Edit">
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
                                @php
                                    // Hitung colspan agar rapi (Name + Category + Sales? + Contact + Address + Finance + Action?)
                                    $baseCols = 6;
                                    if (Auth::user()->role !== 'sales') {
                                        $baseCols++;
                                    } // Tambah 1 kolom Sales
                                    if (
                                        in_array(Auth::user()->role, ['sales', 'manager_operasional', 'manager_bisnis'])
                                    ) {
                                        $baseCols++;
                                    } // Tambah 1 kolom Aksi
                                @endphp
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                    <div>Data tidak ditemukan</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="p-3">
                {{ $customers->links() }}
            </div>
        </div>
    </div>
@endsection
