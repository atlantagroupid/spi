@extends('layouts.app')

@section('title', 'Data Produk')

@section('content')
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Data Produk & Stok</h1>
            {{-- INFO TOTAL ASET --}}
            <div class="mt-1 small text-muted">
                <span class="me-3">
                    <i class="bi bi-cash-stack me-1"></i> Total Aset:
                    <span class="fw-bold text-success">
                        Rp {{ number_format($totalAsset ?? 0, 0, ',', '.') }}
                    </span>
                </span>
                <span>
                    <i class="bi bi-box-seam me-1"></i> Total Stok:
                    <span class="fw-bold text-primary">
                        {{ number_format($totalStock ?? 0, 0, ',', '.') }} Unit
                    </span>
                </span>
            </div>
        </div>

        {{-- TOMBOL TAMBAH PRODUK (Role Tertentu) --}}
        @if (in_array(Auth::user()->role, ['manager_operasional', 'kepala_gudang', 'admin_gudang']))
            <a href="{{ route('products.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Tambah Produk
            </a>
        @endif
    </div>

    {{-- 1. TABEL ALERT STOK MENIPIS (ACCORDION) --}}
    @if (in_array(Auth::user()->role, ['purchase', 'manager_operasional', 'kepala_gudang']))
        @if (isset($lowStockProducts) && $lowStockProducts->count() > 0)
            <div class="card border-warning mb-4 shadow-sm">
                {{-- HEADER (KLIK UNTUK BUKA/TUTUP) --}}
                <div class="card-header bg-warning bg-opacity-10 fw-bold text-warning-emphasis d-flex justify-content-between align-items-center"
                    style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseLowStock"
                    aria-expanded="true" aria-controls="collapseLowStock">
                    <span>
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Perlu Restock (Stok Menipis)
                    </span>
                    <i class="bi bi-chevron-down"></i>
                </div>

                {{-- BODY (DEFAULT TERBUKA) --}}
                <div class="collapse show" id="collapseLowStock">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3">Produk</th>
                                        <th class="text-center">Sisa Stok</th>
                                        <th>Status Pemesanan</th>
                                        <th class="text-end pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lowStockProducts as $item)
                                        <tr>
                                            <td class="ps-3 fw-bold">{{ $item->name }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-danger">{{ $item->stock }} Unit</span>
                                            </td>
                                            <td>
                                                @if ($item->restock_date)
                                                    <span class="badge bg-info text-dark border border-info">
                                                        <i class="bi bi-calendar-check me-1"></i>
                                                        Dipesan: {{ date('d/m/Y', strtotime($item->restock_date)) }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary opacity-50">Belum Pesan</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-3">
                                                {{-- Tombol Update Info Restock --}}
                                                <button class="btn btn-sm btn-outline-primary"
                                                    onclick="event.stopPropagation(); openRestockModal('{{ $item->id }}', '{{ $item->name }}', '{{ $item->restock_date }}')">
                                                    <i class="bi bi-pencil-square"></i> Update Info
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- FOOTER PAGINATION --}}
                    @if ($lowStockProducts->hasPages())
                        <div class="card-footer bg-white py-2">
                            <div class="d-flex justify-content-center">
                                {{-- Gunakan pagination simple Bootstrap --}}
                                {{ $lowStockProducts->appends(request()->query())->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    {{-- 2. FILTER PENCARIAN --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('products.index') }}" method="GET" class="row g-2 align-items-center">
                {{-- Input Cari --}}
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama produk..."
                        value="{{ request('search') }}">
                </div>
                {{-- Dropdown Kategori --}}
                <div class="col-md-3">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">- Semua Kategori -</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                                {{ $cat }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Dropdown Harga --}}
                <div class="col-md-2">
                    <select name="is_discount" class="form-select" onchange="this.form.submit()">
                        <option value="">- Harga -</option>
                        <option value="1" {{ request('is_discount') == '1' ? 'selected' : '' }}>🏷️ Diskon</option>
                    </select>
                </div>
                {{-- Tombol Action (Cari & Reset) --}}
                <div class="col-md-3 col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>

                    @if (request('search') || request('category') || request('is_discount'))
                        <a href="{{ route('products.index') }}" class="btn btn-danger" title="Reset Filter">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @else
                        <a href="{{ route('products.index') }}" class="btn btn-light border" title="Refresh Data">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- 3. TABEL UTAMA DATA PRODUK --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3" width="80">Gambar</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>

                            @if (Auth::user()->role === 'purchase')
                                <th>Harga Normal</th>
                                <th style="width: 200px;" class="text-danger bg-danger bg-opacity-10">Set Diskon</th>
                            @else
                                <th>Harga Satuan</th>
                            @endif

                            <th class="text-center">Stok</th>

                            @if (in_array(Auth::user()->role, ['manager_operasional', 'kepala_gudang', 'admin_gudang']))
                                <th class="text-center pe-4" width="100">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            {{-- KOLOM GAMBAR (OPTIMIZED) --}}
                            <td class="text-center" style="width: 80px;">
                                @if ($product->image)
                                    {{-- JIKA ADA GAMBAR --}}
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#imgModal{{ $product->id }}">
                                        <img src="{{ asset('storage/products/' . $product->image) }}"
                                            alt="{{ $product->name }}" class="rounded border shadow-sm" width="50"
                                            height="50" loading="lazy" {{-- FITUR LAZY LOAD --}}
                                            style="object-fit: cover; cursor: pointer;">
                                    </a>

                                    {{-- MODAL PREVIEW --}}
                                    <div class="modal fade" id="imgModal{{ $product->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold text-truncate" style="max-width: 90%;">
                                                        {{ $product->name }}
                                                    </h6>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-center p-0 bg-light">
                                                    <img src="{{ asset('storage/products/' . $product->image) }}"
                                                        class="img-fluid" loading="lazy" {{-- LAZY JUGA DI MODAL --}}
                                                        style="max-height: 500px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    {{-- JIKA TIDAK ADA GAMBAR (FALLBACK ICON) --}}
                                    <div class="d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 rounded border"
                                        style="width: 50px; height: 50px; margin: 0 auto;" title="Tidak ada gambar">
                                        <i class="bi bi-card-image text-secondary fs-5"></i>
                                    </div>
                                @endif
                            </td>

                            {{-- NAMA --}}
                            <td>
                                <div class="fw-bold text-dark">{{ $product->name }}</div>
                            </td>

                            {{-- KATEGORI --}}
                            <td><span class="badge bg-light text-dark border">{{ $product->category }}</span></td>

                            {{-- LOKASI --}}
                            <td>
                                <div class="small lh-sm">
                                    <div class="fw-bold">{{ $product->lokasi_gudang ?? 'N/A' }}</div>
                                    <div class="text-muted">
                                        @if ($product->gate)
                                            Gate: {{ $product->gate }}
                                        @endif
                                        @if ($product->gate && $product->block)
                                            |
                                        @endif
                                        @if ($product->block)
                                            Block: {{ $product->block }}
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- HARGA (LOGIKA ROLE) --}}
                            @if (Auth::user()->role === 'purchase')
                                <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                <td class="bg-danger bg-opacity-10">
                                    <form action="{{ route('products.updateDiscount', $product->id) }}" method="POST"
                                        class="d-flex gap-1">
                                        @csrf
                                        <input type="number" name="discount_price"
                                            class="form-control form-control-sm border-danger text-danger fw-bold"
                                            value="{{ $product->discount_price == 0 ? '' : $product->discount_price }}"
                                            placeholder="No Disc">
                                        <button type="submit" class="btn btn-sm btn-danger shadow-sm">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                </td>
                            @else
                                <td>
                                    @if ($product->discount_price && $product->discount_price > 0)
                                        <div class="text-decoration-line-through text-muted small">
                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </div>
                                        <div class="fw-bold text-danger">
                                            Rp {{ number_format($product->discount_price, 0, ',', '.') }}
                                        </div>
                                    @else
                                        <div class="fw-bold text-primary">
                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </div>
                                    @endif
                                </td>
                            @endif

                            {{-- STOK --}}
                            <td class="text-center">
                                <span class="badge {{ $product->stock <= 10 ? 'bg-warning text-dark' : 'bg-success' }}">
                                    {{ $product->stock }}
                                </span>
                            </td>

                            {{-- AKSI (EDIT/DELETE) --}}
                            @if (in_array(Auth::user()->role, ['manager_operasional', 'kepala_gudang', 'admin_gudang']))
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('products.edit', $product->id) }}"
                                            class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('products.destroy', $product->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Hapus data produk ini? Data tidak bisa dikembalikan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger shadow-sm"
                                                title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Tidak ada data produk ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL UPDATE RESTOCK (FIXED METHOD) --}}
    <div class="modal fade" id="restockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="restockForm" method="POST">
                    @csrf
                    {{-- PERBAIKAN: Tambahkan @method('PATCH') karena Route::patch --}}
                    @method('PATCH')

                    <div class="modal-header bg-light">
                        <h5 class="modal-title">
                            <i class="bi bi-box-seam me-2"></i>Update Restock
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            <h6 class="text-muted small uppercase">Nama Produk</h6>
                            <h4 class="fw-bold text-primary" id="modalProductName">...</h4>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Tanggal Estimasi Barang Masuk</label>
                            <input type="date" name="restock_date" id="modalRestockDate" class="form-control"
                                required>
                            <div class="form-text text-muted small">
                                Pilih tanggal kapan stok baru diperkirakan tiba di gudang.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">
                            <i class="bi bi-save me-1"></i> Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 5. SCRIPT PENDUKUNG & MODAL --}}
    @push('scripts')
        <script>
            function openRestockModal(id, name, date) {
                // 1. Isi Nama Produk di Header Modal
                document.getElementById('modalProductName').innerText = name;

                // 2. Isi Tanggal
                let dateInput = document.getElementById('modalRestockDate');
                if (date) {
                    dateInput.value = date.split(' ')[0];
                } else {
                    dateInput.value = '';
                }

                // 3. Update Action URL pada Form
                let url = "{{ route('products.updateRestock', ':id') }}";
                url = url.replace(':id', id);
                document.getElementById('restockForm').action = url;

                // 4. Tampilkan Modal
                let myModal = new bootstrap.Modal(document.getElementById('restockModal'));
                myModal.show();
            }
        </script>
    @endpush
@endsection
