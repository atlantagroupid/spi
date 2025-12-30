@extends('layouts.app')

@section('title', 'Edit Order #' . $order->invoice_number)

@section('content')
<div class="container pb-5">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-primary mb-1">Revisi Order</h4>
            <p class="text-muted small mb-0">Perbaiki pesanan yang ditolak dan ajukan ulang.</p>
        </div>
        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Batal & Kembali
        </a>
    </div>

    {{-- ALERT ERROR (PENTING AGAR TAHU KENAPA MENTAL) --}}
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm border-start border-5 border-danger">
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger shadow-sm">{{ session('error') }}</div>
    @endif

    <form action="{{ route('orders.update', $order->id) }}" method="POST">
        @csrf
        @method('PUT') {{-- WAJIB ADA UNTUK UPDATE --}}

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">

                {{-- INFO CUSTOMER (READONLY) --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Customer</label>
                        <input type="text" class="form-control bg-light" value="{{ $order->customer->name }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Invoice</label>
                        <input type="text" class="form-control bg-light" value="{{ $order->invoice_number }}" readonly>
                    </div>
                </div>

                <hr class="my-4">

                {{-- FORM ITEM BARANG --}}
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-box-seam me-2"></i>Daftar Item Barang</h6>

                <div id="product-list">
                    {{-- LOOP BARANG LAMA --}}
                    @foreach($order->items as $index => $item)
                        <div class="row g-2 mb-2 product-row align-items-end">
                            <div class="col-md-7">
                                <label class="small text-muted mb-1">Pilih Produk</label>
                                <select name="product_id[]" class="form-select select2-product" required>
                                    <option value="" disabled>-- Pilih Produk --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}"
                                            data-price="{{ $product->price }}"
                                            {{ $product->id == $item->product_id ? 'selected' : '' }}>
                                            {{ $product->name }} (Stok: {{ $product->stock + ($product->id == $item->product_id ? $item->quantity : 0) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted mb-1">Qty</label>
                                <input type="number" name="quantity[]" class="form-control" min="1" value="{{ $item->quantity }}" required placeholder="Jml">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger w-100 btn-remove-row">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- TOMBOL TAMBAH BARIS --}}
                <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-product-btn">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Baris Barang
                </button>

                <hr class="my-4">

                {{-- CATATAN --}}
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Catatan Revisi</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Tulis catatan tambahan untuk Manager...">{{ $order->notes }}</textarea>
                </div>

                {{-- TOMBOL SUBMIT --}}
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary fw-bold py-2">
                        <i class="bi bi-save me-1"></i> SIMPAN PERUBAHAN & AJUKAN ULANG
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- SCRIPT DYNAMIC FORM --}}
@push('scripts')
<script>
    $(document).ready(function() {

        // 1. Fungsi Tambah Baris
        $('#add-product-btn').click(function() {
            let row = `
                <div class="row g-2 mb-2 product-row align-items-end">
                    <div class="col-md-7">
                        <select name="product_id[]" class="form-select" required>
                            <option value="" selected disabled>-- Pilih Produk --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }} (Stok: {{ $product->stock }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="quantity[]" class="form-control" min="1" value="1" required placeholder="Jml">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger w-100 btn-remove-row">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            $('#product-list').append(row);
        });

        // 2. Fungsi Hapus Baris
        $(document).on('click', '.btn-remove-row', function() {
            // Cek sisa baris, jangan sampai kosong semua
            if ($('.product-row').length > 1) {
                $(this).closest('.product-row').remove();
            } else {
                alert('Minimal harus ada 1 barang!');
            }
        });

    });
</script>
@endpush
@endsection
