@extends('layouts.app')

@section('title', 'Pengaturan Situs')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-gray-800">Pengaturan Situs</h1>
    </div>

    <div class="card shadow border-0">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="settingTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold" id="general-tab" data-bs-toggle="tab" data-bs-target="#general"
                        type="button">
                        <i class="bi bi-shop me-2"></i> Identitas Toko
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="category-tab" data-bs-toggle="tab" data-bs-target="#category"
                        type="button">
                        <i class="bi bi-tags me-2"></i> Kategori Produk
                    </button>
                </li>
                {{-- 🔥 TAB BARU: KATEGORI CUSTOMER 🔥 --}}
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="cust-cat-tab" data-bs-toggle="tab" data-bs-target="#cust-cat"
                        type="button">
                        <i class="bi bi-people me-2"></i> Kategori Customer
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="settingTabsContent">

                {{-- TAB 1: IDENTITAS TOKO --}}
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <form action="{{ route('settings.updateGeneral') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Aplikasi (Di Header)</label>
                            <input type="text" name="app_name" class="form-control"
                                value="{{ $settings['app_name'] ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Perusahaan (Di Laporan/Invoice)</label>
                            <input type="text" name="company_name" class="form-control"
                                value="{{ $settings['company_name'] ?? '' }}">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Alamat Toko</label>
                                <textarea name="company_address" class="form-control" rows="3">{{ $settings['company_address'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nomor Telepon</label>
                                <input type="text" name="company_phone" class="form-control"
                                    value="{{ $settings['company_phone'] ?? '' }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Identitas</button>
                    </form>
                </div>

                {{-- TAB 2: KATEGORI PRODUK --}}
                <div class="tab-pane fade" id="category" role="tabpanel">
                    <div class="row">
                        <div class="col-md-5 mb-4">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3">Tambah Kategori Baru</h6>
                                    <form action="{{ route('settings.storeCategory') }}" method="POST">
                                        @csrf
                                        <div class="input-group mb-3">
                                            <input type="text" name="name" class="form-control"
                                                placeholder="Contoh: Vinyl / Wallpaper" required>
                                            <button class="btn btn-success" type="submit">Tambah</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <h6 class="fw-bold mb-3">Daftar Kategori Aktif</h6>
                            <table class="table table-bordered table-hover bg-white">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Kategori</th>
                                        <th class="text-center" width="100">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($categories as $cat)
                                        <tr>
                                            <td>{{ $cat->name }}</td>
                                            <td class="text-center">
                                                <form action="{{ route('settings.destroyCategory', $cat->id) }}"
                                                    method="POST" onsubmit="return confirm('Yakin hapus kategori ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"><i
                                                            class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {{-- ISI TAB BARU: KATEGORI CUSTOMER --}}
                <div class="tab-pane fade" id="cust-cat" role="tabpanel">
                    <div class="row">
                        {{-- Form Tambah --}}
                        <div class="col-md-5 mb-4">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-primary">Tambah Kategori Customer</h6>
                                    <form action="{{ route('settings.storeCustomerCategory') }}" method="POST">
                                        @csrf
                                        <div class="input-group mb-3">
                                            <input type="text" name="name" class="form-control"
                                                placeholder="Contoh: Toko Bangunan / Kontraktor" required>
                                            <button class="btn btn-primary" type="submit">Tambah</button>
                                        </div>
                                        <small class="text-muted">Digunakan untuk mengelompokkan jenis pelanggan.</small>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Tabel List --}}
                        <div class="col-md-7">
                            <h6 class="fw-bold mb-3">Daftar Kategori Customer</h6>
                            <table class="table table-bordered table-hover bg-white">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Kategori</th>
                                        <th class="text-center" width="100">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customerCategories as $custCat)
                                        <tr>
                                            <td>{{ $custCat->name }}</td>
                                            <td class="text-center">
                                                <form
                                                    action="{{ route('settings.destroyCustomerCategory', $custCat->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Hapus kategori customer ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"><i
                                                            class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-3">Belum ada data kategori
                                                customer.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
