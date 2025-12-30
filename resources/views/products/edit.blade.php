@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Edit Produk: {{ $product->name }}</h5>
                </div>
                <div class="card-body">

                    <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $product->name) }}">
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
                                <label class="form-label">Stok</label>
                                <input type="number" name="stock" class="form-control"
                                    value="{{ old('stock', $product->stock) }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Gudang</label>
                                <select name="gudang_id" id="gudang_id" class="form-select">
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach ($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}" @selected(old('gudang_id', $product->gudang_id) == $gudang->id)>
                                            {{ $gudang->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Gate</label>
                                <select name="gate_id" id="gate_id" class="form-select" disabled>
                                    <option value="">-- Pilih Gudang Dulu --</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Block</label>
                                <select name="block_id" id="block_id" class="form-select" disabled>
                                    <option value="">-- Pilih Gate Dulu --</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Harga Normal</label>
                                    <input type="number" name="price" class="form-control"
                                        value="{{ old('price', $product->price) }}">
                                </div>
                                @if (in_array(Auth::user()->role, ['purchase', 'manager_operasional']))
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold text-danger">Harga Diskon (Opsional)</label>
                                        <input type="number" name="discount_price" class="form-control"
                                            placeholder="Kosongkan jika tidak diskon"
                                            value="{{ old('discount_price', $product->discount_price) }}">
                                        <small class="text-muted">Harga ini yang akan dipakai saat transaksi jika
                                            diisi.</small>
                                    </div>
                                @endif
                            </div>

                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Foto Produk (Biarkan kosong jika tidak diganti)</label>
                            <input type="file" name="image" class="form-control mb-2">
                            @if ($product->image)
                                <small class="text-muted">Gambar saat ini:</small><br>
                                <img src="{{ asset('storage/products/' . $product->image) }}" width="100"
                                    class="rounded border p-1">
                            @endif
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Update Produk</button>
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
        const gudangSelect = document.getElementById('gudang_id');
        const gateSelect = document.getElementById('gate_id');
        const blockSelect = document.getElementById('block_id');

        const initialGudangId = '{{ old('gudang_id', $product->gudang_id) }}';
        const initialGateId = '{{ old('gate_id', $product->gate_id) }}';
        const initialBlockId = '{{ old('block_id', $product->block_id) }}';

        function fetchGates(gudangId, selectedGateId = null) {
            if (!gudangId) {
                gateSelect.innerHTML = '<option value="">-- Pilih Gudang Dulu --</option>';
                gateSelect.disabled = true;
                return;
            }

            fetch(`/ajax/gates/${gudangId}`)
                .then(response => response.json())
                .then(data => {
                    gateSelect.innerHTML = '<option value="">-- Pilih Gate --</option>';
                    data.forEach(gate => {
                        const selected = selectedGateId == gate.id ? 'selected' : '';
                        gateSelect.innerHTML += `<option value="${gate.id}" ${selected}>${gate.name}</option>`;
                    });
                    gateSelect.disabled = false;
                    // Trigger change if a gate was pre-selected
                    if(selectedGateId) {
                        gateSelect.dispatchEvent(new Event('change'));
                    }
                });
        }

        function fetchBlocks(gateId, selectedBlockId = null) {
             if (!gateId) {
                blockSelect.innerHTML = '<option value="">-- Pilih Gate Dulu --</option>';
                blockSelect.disabled = true;
                return;
            }
            fetch(`/ajax/blocks/${gateId}`)
                .then(response => response.json())
                .then(data => {
                    blockSelect.innerHTML = '<option value="">-- Pilih Block --</option>';
                    data.forEach(block => {
                        const selected = selectedBlockId == block.id ? 'selected' : '';
                        blockSelect.innerHTML += `<option value="${block.id}" ${selected}>${block.name}</option>`;
                    });
                    blockSelect.disabled = false;
                });
        }

        gudangSelect.addEventListener('change', function () {
            fetchGates(this.value);
            blockSelect.innerHTML = '<option value="">-- Pilih Gate Dulu --</option>';
            blockSelect.disabled = true;
        });

        gateSelect.addEventListener('change', function () {
            fetchBlocks(this.value);
        });

        // Initial load
        if (initialGudangId) {
            fetchGates(initialGudangId, initialGateId);
        }
        if(initialGateId){
            fetchBlocks(initialGateId, initialBlockId);
        }
    });
</script>
@endpush