@extends('layouts.app')

@section('title', 'Tambah Produk Baru')

@section('content')
<div class="container-fluid px-0 px-md-3">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-gray-800 mb-1">Tambah Produk</h4>
            <p class="text-muted small mb-0 d-none d-md-block">Input data produk baru ke sistem.</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-box-seam me-2"></i>Formulir Produk</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Nama Produk</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Keramik Roman 40x40" required>
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase text-muted">Kategori</label>
                                <select name="category" class="form-select" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase text-muted">Stok Awal</label>
                                <input type="number" name="stock" class="form-control" value="{{ old('stock', 0) }}" min="0" required>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded border mb-3">
                            <h6 class="fw-bold text-dark mb-3 small text-uppercase"><i class="bi bi-geo-alt-fill me-1"></i>Lokasi Penyimpanan</h6>
                            <div class="row g-2">
                                <div class="col-12 col-md-4">
                                    <label class="small text-muted">Gudang</label>
                                    <select name="lokasi_gudang" id="lokasi_gudang" class="form-select form-select-sm">
                                        <option value="">-- Pilih --</option>
                                        @foreach ($gudangs as $gudang)
                                            <option value="{{ $gudang->id }}">{{ $gudang->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="small text-muted">Gate</label>
                                    <select name="gate" id="gate" class="form-select form-select-sm" disabled>
                                        <option value="">-</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="small text-muted">Block</label>
                                    <select name="block" id="block" class="form-select form-select-sm" disabled>
                                        <option value="">-</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase text-muted">Harga Normal (Rp)</label>
                                <input type="number" name="price" class="form-control fw-bold text-primary" value="{{ old('price') }}" required>
                            </div>
                            @if (Auth::user()->role === 'purchase')
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-bold text-uppercase text-danger">Harga Diskon (Opsional)</label>
                                    <input type="number" name="discount_price" class="form-control border-danger text-danger" placeholder="0">
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Foto Produk</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-bold py-2 shadow-sm">
                                <i class="bi bi-save2 me-2"></i> SIMPAN PRODUK
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SCRIPT JAVASCRIPT --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const gudangSelect = document.getElementById('lokasi_gudang');
        const gateSelect = document.getElementById('gate');
        const blockSelect = document.getElementById('block');

        gudangSelect.addEventListener('change', function() {
            const id = this.value;
            gateSelect.innerHTML = '<option value="">Loading...</option>'; gateSelect.disabled = true;
            blockSelect.innerHTML = '<option value="">-</option>'; blockSelect.disabled = true;

            if (id) {
                fetch(`/ajax/gates/${id}`).then(res => res.json()).then(data => {
                    gateSelect.innerHTML = '<option value="">-- Pilih --</option>';
                    data.forEach(d => gateSelect.innerHTML += `<option value="${d.id}">${d.name}</option>`);
                    gateSelect.disabled = false;
                });
            } else {
                gateSelect.innerHTML = '<option value="">-</option>';
            }
        });

        gateSelect.addEventListener('change', function() {
            const id = this.value;
            blockSelect.innerHTML = '<option value="">Loading...</option>'; blockSelect.disabled = true;

            if (id) {
                fetch(`/ajax/blocks/${id}`).then(res => res.json()).then(data => {
                    blockSelect.innerHTML = '<option value="">-- Pilih --</option>';
                    data.forEach(d => blockSelect.innerHTML += `<option value="${d.id}">${d.name}</option>`);
                    blockSelect.disabled = false;
                });
            } else {
                blockSelect.innerHTML = '<option value="">-</option>';
            }
        });
    });
</script>
@endpush
@endsection
