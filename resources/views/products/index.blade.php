@extends('layouts.app')

@section('title', 'Data Produk')

@section('content')
<div class="container-fluid px-0 px-md-3">

    {{-- HEADER & TOMBOL TAMBAH --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 mb-md-4 gap-3">
        <div>
            <h4 class="fw-bold text-gray-800 mb-1">Data Produk & Stok</h4>
            <div class="small text-muted d-flex gap-3 align-items-center">
                <span>
                    <i class="bi bi-cash-stack me-1"></i>Aset:
                    <span class="fw-bold text-success">Rp {{ number_format($totalAsset ?? 0, 0, ',', '.') }}</span>
                </span>
                <span class="border-start ps-3">
                    <i class="bi bi-box-seam me-1"></i>Stok:
                    <span class="fw-bold text-primary">{{ number_format($totalStock ?? 0, 0, ',', '.') }}</span>
                </span>
            </div>
        </div>

        @if (Auth::user()->role === 'admin_gudang')
            <a href="{{ route('products.create') }}" class="btn btn-primary shadow-sm w-100 w-md-auto">
                <i class="bi bi-plus-lg me-2"></i> Tambah Produk
            </a>
        @endif
    </div>

    {{-- ALERT STOK MENIPIS (ACCORDION) --}}
    @if (in_array(Auth::user()->role, ['purchase', 'manager_operasional', 'kepala_gudang']) && isset($lowStockProducts) && $lowStockProducts->count() > 0)
        <div class="card border-warning mb-4 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-warning bg-opacity-10 fw-bold text-warning-emphasis d-flex justify-content-between align-items-center clickable"
                data-bs-toggle="collapse" data-bs-target="#collapseLowStock" style="cursor: pointer;">
                <span><i class="bi bi-exclamation-triangle-fill me-2"></i> Perlu Restock ({{ $lowStockProducts->total() }})</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse show" id="collapseLowStock"> {{-- Default Show agar terlihat --}}
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach ($lowStockProducts as $item)
                            <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <div class="fw-bold text-dark">{{ $item->name }}</div>
                                    <small class="text-danger fw-bold">Sisa: {{ $item->stock }} Unit</small>
                                    @if ($item->restock_date)
                                        <div class="small text-muted"><i class="bi bi-clock-history me-1"></i>Pesan: {{ date('d/m/y', strtotime($item->restock_date)) }}</div>
                                    @endif
                                </div>
                                @if(Auth::user()->role === 'purchase')
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                    onclick="openRestockModal('{{ $item->id }}', '{{ $item->name }}', '{{ $item->restock_date }}')">
                                    Update
                                </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if ($lowStockProducts->hasPages())
                        <div class="p-2 text-center bg-light">
                            <small class="text-muted">Lihat halaman selanjutnya di bawah...</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- FILTER PENCARIAN (COLLAPSIBLE DI MOBILE) --}}
    <div class="card border-0 shadow-sm mb-4 rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center d-md-none" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-funnel me-2"></i>Filter Produk</h6>
            <i class="bi bi-chevron-down"></i>
        </div>
        <div class="collapse d-md-block" id="filterCollapse">
            <div class="card-body p-3">
                <form action="{{ route('products.index') }}" method="GET">
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari nama produk..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <select name="category" class="form-select" onchange="this.form.submit()">
                                <option value="">- Semua Kategori -</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <select name="is_discount" class="form-select" onchange="this.form.submit()">
                                <option value="">- Harga -</option>
                                <option value="1" {{ request('is_discount') == '1' ? 'selected' : '' }}>🏷️ Diskon</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill fw-bold">Terapkan</button>
                            @if (request()->anyFilled(['search', 'category', 'is_discount']))
                                <a href="{{ route('products.index') }}" class="btn btn-outline-danger" title="Reset"><i class="bi bi-x-lg"></i></a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- LIST PRODUK --}}
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">

        {{-- TAMPILAN DESKTOP (TABLE) --}}
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-4" width="80">Foto</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Harga</th>
                        <th class="text-center">Stok</th>
                        @if (Auth::user()->role === 'admin_gudang')
                            <th class="text-center pe-4">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td class="ps-4">
                                @if ($product->image)
                                    <img src="{{ asset('storage/products/' . $product->image) }}" class="rounded border" width="50" height="50" style="object-fit: cover; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#imgModal{{ $product->id }}">
                                @else
                                    <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 50px; height: 50px;"><i class="bi bi-image"></i></div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $product->name }}</div>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $product->category }}</span></td>
                            <td class="small text-muted">
                                {{ $product->lokasi_gudang ?? '-' }}
                                @if($product->gate) / {{ $product->gate }} @endif
                                @if($product->block) / {{ $product->block }} @endif
                            </td>
                            <td>
                                @if ($product->discount_price > 0)
                                    <div class="text-decoration-line-through text-muted small">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                                    <div class="fw-bold text-danger">Rp {{ number_format($product->discount_price, 0, ',', '.') }}</div>
                                @else
                                    <div class="fw-bold text-primary">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $product->stock <= 10 ? 'bg-warning text-dark' : 'bg-success' }} rounded-pill px-3">{{ $product->stock }}</span>
                            </td>
                            @if (Auth::user()->role === 'admin_gudang')
                                <td class="text-center pe-4">
                                    <div class="btn-group">
                                        <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i></a>
                                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Hapus produk?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted">Produk tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- TAMPILAN MOBILE (CARD LIST) --}}
        <div class="d-md-none bg-light p-2">
            @forelse($products as $product)
                <div class="card mb-2 border-0 shadow-sm rounded-3">
                    <div class="card-body p-3">
                        <div class="d-flex gap-3">
                            {{-- FOTO DI KIRI --}}
                            <div style="flex-shrink: 0;">
                                @if ($product->image)
                                    <img src="{{ asset('storage/products/' . $product->image) }}" class="rounded border" width="70" height="70" style="object-fit: cover;" data-bs-toggle="modal" data-bs-target="#imgModal{{ $product->id }}">
                                @else
                                    <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 70px; height: 70px;"><i class="bi bi-image fs-4"></i></div>
                                @endif
                            </div>

                            {{-- DETAIL DI KANAN --}}
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="fw-bold text-dark mb-1 text-truncate">{{ $product->name }}</h6>
                                    @if (Auth::user()->role === 'admin_gudang')
                                        <div class="dropdown">
                                            <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                                <li><a class="dropdown-item" href="{{ route('products.edit', $product->id) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                                <li>
                                                    <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Hapus?');">
                                                        @csrf @method('DELETE')
                                                        <button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Hapus</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    @endif
                                </div>

                                <div class="mb-1">
                                    <span class="badge bg-light text-secondary border me-1">{{ $product->category }}</span>
                                    <span class="badge {{ $product->stock <= 10 ? 'bg-warning text-dark' : 'bg-success' }}">{{ $product->stock }} Unit</span>
                                </div>

                                <div class="d-flex justify-content-between align-items-end mt-2">
                                    <div>
                                        @if ($product->discount_price > 0)
                                            <small class="text-decoration-line-through text-muted" style="font-size: 0.7rem;">Rp {{ number_format($product->price, 0, ',', '.') }}</small>
                                            <div class="fw-bold text-danger">Rp {{ number_format($product->discount_price, 0, ',', '.') }}</div>
                                        @else
                                            <div class="fw-bold text-primary">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                                        @endif
                                    </div>
                                    <div class="small text-muted text-end" style="font-size: 0.7rem; line-height: 1.2;">
                                        <i class="bi bi-geo-alt-fill text-secondary"></i>
                                        {{ $product->lokasi_gudang ?? '-' }}<br>
                                        {{ $product->gate ?? '' }} {{ $product->block ?? '' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MODAL IMAGE (MOBILE & DESKTOP) --}}
                @if ($product->image)
                    <div class="modal fade" id="imgModal{{ $product->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-transparent border-0 shadow-none">
                                <div class="modal-body text-center p-0">
                                    <img src="{{ asset('storage/products/' . $product->image) }}" class="img-fluid rounded shadow" style="max-height: 80vh;">
                                    <button type="button" class="btn btn-light rounded-circle position-absolute top-0 end-0 m-3 shadow" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-search fs-1 d-block mb-2 opacity-50"></i>
                    <p>Produk tidak ditemukan.</p>
                </div>
            @endforelse
        </div>

        {{-- PAGINATION --}}
        <div class="p-3 bg-white border-top">
            {{ $products->links() }}
        </div>
    </div>

    {{-- MODAL RESTOCK --}}
    <div class="modal fade" id="restockModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="restockForm" method="POST">
                    @csrf @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Update Restock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            <h6 class="text-muted small">PRODUK</h6>
                            <h5 class="fw-bold text-primary" id="modalProductName">...</h5>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Estimasi Barang Masuk</label>
                            <input type="date" name="restock_date" id="modalRestockDate" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary fw-bold">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function openRestockModal(id, name, date) {
                document.getElementById('modalProductName').innerText = name;
                let dateInput = document.getElementById('modalRestockDate');
                dateInput.value = date ? date.split(' ')[0] : '';

                let url = "{{ route('products.updateRestock', ':id') }}";
                document.getElementById('restockForm').action = url.replace(':id', id);

                new bootstrap.Modal(document.getElementById('restockModal')).show();
            }
        </script>
    @endpush
</div>
@endsection
