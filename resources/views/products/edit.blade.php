@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
<div class="container-fluid px-0 px-md-3">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-gray-800 mb-1">Edit Produk</h4>
            <p class="text-muted small mb-0 d-none d-md-block">{{ $product->name }}</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Batal
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i>Form Edit</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PUT')

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Nama Produk</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase text-muted">Kategori</label>
                                <select name="category" class="form-select" required>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}" {{ old('category', $product->category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase text-muted">Stok</label>
                                <input type="number" name="stock" class="form-control" value="{{ old('stock', $product->stock) }}" min="0">
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded border mb-3">
                            <h6 class="fw-bold text-dark mb-3 small text-uppercase"><i class="bi bi-geo-alt-fill me-1"></i>Update Lokasi</h6>
                            <div class="row g-2">
                                <div class="col-12 col-md-4">
                                    <label class="small text-muted">Gudang</label>
                                    <select name="gudang_id" id="gudang_id" class="form-select form-select-sm">
                                        <option value="">-- Pilih --</option>
                                        @foreach ($gudangs as $gudang)
                                            <option value="{{ $gudang->id }}" {{ $product->gudang_id == $gudang->id ? 'selected' : '' }}>{{ $gudang->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="small text-muted">Gate</label>
                                    <select name="gate_id" id="gate_id" class="form-select form-select-sm" {{ !$product->gudang_id ? 'disabled' : '' }}>
                                        <option value="">-</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="small text-muted">Block</label>
                                    <select name="block_id" id="block_id" class="form-select form-select-sm" {{ !$product->gate_id ? 'disabled' : '' }}>
                                        <option value="">-</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-uppercase text-muted">Harga Normal (Rp)</label>
                                <input type="number" name="price" class="form-control fw-bold text-primary" value="{{ old('price', $product->price) }}" required>
                            </div>
                            @if (in_array(Auth::user()->role, ['purchase', 'manager_operasional']))
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-bold text-uppercase text-danger">Harga Diskon</label>
                                    <input type="number" name="discount_price" class="form-control border-danger text-danger" value="{{ old('discount_price', $product->discount_price) }}">
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Foto Produk</label>
                            <input type="file" name="image" class="form-control mb-2" accept="image/*">
                            @if ($product->image)
                                <div class="bg-light p-2 rounded border d-inline-block">
                                    <img src="{{ asset('storage/products/' . $product->image) }}" height="60" class="rounded">
                                </div>
                            @endif
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning text-dark fw-bold py-2 shadow-sm">
                                <i class="bi bi-pencil-square me-2"></i> UPDATE PRODUK
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SCRIPT AUTO FILL DROPDOWN LAMA --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const gudangSelect = document.getElementById('gudang_id');
        const gateSelect = document.getElementById('gate_id');
        const blockSelect = document.getElementById('block_id');

        const initialGudang = "{{ $product->gudang_id }}";
        const initialGate = "{{ $product->gate_id }}";
        const initialBlock = "{{ $product->block_id }}";

        function loadGates(gudangId, selected = null) {
            if(!gudangId) return;
            fetch(`/ajax/gates/${gudangId}`).then(res => res.json()).then(data => {
                gateSelect.innerHTML = '<option value="">-- Pilih --</option>';
                data.forEach(d => {
                    const sel = (d.id == selected) ? 'selected' : '';
                    gateSelect.innerHTML += `<option value="${d.id}" ${sel}>${d.name}</option>`;
                });
                gateSelect.disabled = false;
            });
        }

        function loadBlocks(gateId, selected = null) {
            if(!gateId) return;
            fetch(`/ajax/blocks/${gateId}`).then(res => res.json()).then(data => {
                blockSelect.innerHTML = '<option value="">-- Pilih --</option>';
                data.forEach(d => {
                    const sel = (d.id == selected) ? 'selected' : '';
                    blockSelect.innerHTML += `<option value="${d.id}" ${sel}>${d.name}</option>`;
                });
                blockSelect.disabled = false;
            });
        }

        gudangSelect.addEventListener('change', function() { loadGates(this.value); });
        gateSelect.addEventListener('change', function() { loadBlocks(this.value); });

        // Load data awal
        if(initialGudang) loadGates(initialGudang, initialGate);
        if(initialGate) loadBlocks(initialGate, initialBlock);
    });
</script>
@endpush
@endsection
