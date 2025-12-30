@extends('layouts.app')

@section('title', 'Tambah Produk Baru')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Tambah Produk Baru</h5>
                </div>
                <div class="card-body">

                    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf <div class="mb-3">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" placeholder="Contoh: Keramik Roman 40x40">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="category" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}"
                                            {{ old('category', $product->category ?? '') == $cat ? 'selected' : '' }}>
                                            {{ $cat }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stok Awal</label>
                                <input type="number" name="stock"
                                    class="form-control @error('stock') is-invalid @enderror" value="{{ old('stock') }}">
                                @error('stock')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Gudang</label>
                                <select name="lokasi_gudang" id="lokasi_gudang" class="form-select">
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach ($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Gate</label>
                                <select name="gate" id="gate" class="form-select" disabled>
                                    <option value="">-- Pilih Gudang Dulu --</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Block</label>
                                <select name="block" id="block" class="form-select" disabled>
                                    <option value="">-- Pilih Gate Dulu --</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Harga Normal</label>
                                    <input type="number" name="price"
                                        class="form-control @error('price') is-invalid @enderror"
                                        value="{{ old('price') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-danger">Harga Diskon (Opsional)</label>
                                    <input type="number" name="discount_price" class="form-control"
                                        placeholder="Kosongkan jika tidak diskon">
                                    <small class="text-muted">Harga ini yang akan dipakai saat transaksi jika diisi.</small>
                                </div>
                            </div>

                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi (Opsional)</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Foto Produk</label>
                            <input type="file" name="image" class="form-control @error('image') is-invalid @enderror">
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <small class="text-muted me-auto align-self-center">Catatan: Produk baru akan ditinjau oleh Kepala Gudang.</small>
                            <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Produk</button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const gudangSelect = document.getElementById('lokasi_gudang');
        const gateSelect = document.getElementById('gate');
        const blockSelect = document.getElementById('block');

        gudangSelect.addEventListener('change', function () {
            const lokasi_gudang = this.value;
            gateSelect.innerHTML = '<option value="">Loading...</option>';
            gateSelect.disabled = true;
            blockSelect.innerHTML = '<option value="">-- Pilih Gate Dulu --</option>';
            blockSelect.disabled = true;

            if (lokasi_gudang) {
                fetch(`/ajax/gates/${lokasi_gudang}`)
                    .then(response => response.json())
                    .then(data => {
                        gateSelect.innerHTML = '<option value="">-- Pilih Gate --</option>';
                        data.forEach(gate => {
                            gateSelect.innerHTML += `<option value="${gate.id}">${gate.name}</option>`;
                        });
                        gateSelect.disabled = false;
                    });
            } else {
                gateSelect.innerHTML = '<option value="">-- Pilih Gudang Dulu --</option>';
            }
        });

        gateSelect.addEventListener('change', function () {
            const gate = this.value;
            blockSelect.innerHTML = '<option value="">Loading...</option>';
            blockSelect.disabled = true;

            if (gate) {
                fetch(`/ajax/blocks/${gate}`)
                    .then(response => response.json())
                    .then(data => {
                        blockSelect.innerHTML = '<option value="">-- Pilih Block --</option>';
                        data.forEach(block => {
                            blockSelect.innerHTML += `<option value="${block.id}">${block.name}</option>`;
                        });
                        blockSelect.disabled = false;
                    });
            } else {
                blockSelect.innerHTML = '<option value="">-- Pilih Gate Dulu --</option>';
            }
        });
    });
</script>
@endpush
