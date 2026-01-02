@extends('layouts.app')

@section('title', 'Revisi Order #' . $order->invoice_number)

@section('content')
    {{-- CSS Select2 Langsung --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        /* 1. CSS Select2 Mobile Friendly */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            padding: 0.375rem 0.75rem;
            border: 1px solid #ced4da;
            background-color: #fff;
        }
        .select2-container { width: 100% !important; }

        /* 2. Tampilan Baris Produk di Mobile (Stacking) */
        @media (max-width: 576px) {
            .product-row {
                background-color: #fff;
                border: 1px solid #e3e6f0;
                border-radius: 0.5rem;
                padding: 1rem;
                margin-bottom: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .product-row .col-md-7,
            .product-row .col-md-3,
            .product-row .col-md-2 {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            /* Tombol hapus full width di HP */
            .btn-remove-row { width: 100%; margin-top: 0.5rem; }

            /* Sembunyikan label di desktop, munculkan di mobile agar jelas */
            .mobile-label { display: block; font-weight: bold; font-size: 0.8rem; color: #6c757d; margin-bottom: 4px; }
        }

        @media (min-width: 577px) {
            .mobile-label { display: none; }
        }
    </style>

    <div class="container-fluid px-0 px-md-3">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-primary mb-1">Revisi Order</h4>
                <p class="text-muted small mb-0 d-none d-md-block">Perbaiki pesanan yang ditolak dan ajukan ulang.</p>
            </div>
            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Batal
            </a>
        </div>

        {{-- ALERT ERROR --}}
        @if ($errors->any())
            <div class="alert alert-danger shadow-sm border-start border-5 border-danger mb-4">
                <strong class="d-block mb-1"><i class="bi bi-exclamation-circle me-1"></i> Periksa Inputan:</strong>
                <ul class="mb-0 small ps-3">
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
            @method('PUT')

            <div class="card border-0 shadow-sm mb-4 rounded-3">
                <div class="card-body p-3 p-md-4">

                    {{-- INFO CUSTOMER (Readonly) --}}
                    <div class="bg-light p-3 rounded mb-4 border">
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Customer</small>
                                <div class="fw-bold text-dark">{{ $order->customer->name }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">No. Invoice</small>
                                <div class="fw-bold text-primary">{{ $order->invoice_number }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- FORM ITEM BARANG --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam me-2"></i>Item Barang</h6>
                    </div>

                    <div id="product-list">
                        {{-- LOOP ITEM LAMA --}}
                        @foreach($order->items as $index => $item)
                            <div class="row g-2 mb-2 product-row align-items-end">
                                {{-- Produk --}}
                                <div class="col-md-7">
                                    <label class="mobile-label">Nama Produk</label>
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
                                {{-- Qty --}}
                                <div class="col-md-3">
                                    <label class="mobile-label">Jumlah (Qty)</label>
                                    <input type="number" name="quantity[]" class="form-control" min="1" value="{{ $item->quantity }}" required placeholder="Qty">
                                </div>
                                {{-- Hapus --}}
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger w-100 btn-remove-row">
                                        <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Hapus Item</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- TOMBOL TAMBAH --}}
                    <button type="button" class="btn btn-light border text-primary fw-bold btn-sm mt-2 w-100 py-2 shadow-sm" id="add-product-btn">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Item Lain
                    </button>

                    <hr class="my-4">

                    {{-- CATATAN --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Catatan Revisi (Untuk Manager)</label>
                        <textarea name="notes" class="form-control bg-light" rows="3" placeholder="Jelaskan perubahan yang dilakukan...">{{ $order->notes }}</textarea>
                    </div>

                    {{-- SUBMIT --}}
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary fw-bold py-3 shadow-sm rounded-pill">
                            <i class="bi bi-send-check me-2"></i> SIMPAN & AJUKAN ULANG
                        </button>
                    </div>

                </div>
            </div>
        </form>
    </div>

    {{-- SCRIPT --}}
    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // 1. Init Select2 Awal
            $('.select2-product').select2({ theme: 'bootstrap-5', width: '100%' });

            // 2. Fungsi Tambah Baris
            $('#add-product-btn').click(function() {
                let row = `
                    <div class="row g-2 mb-2 product-row align-items-end">
                        <div class="col-md-7">
                            <label class="mobile-label">Nama Produk</label>
                            <select name="product_id[]" class="form-select select2-new" required>
                                <option value="" disabled selected>-- Pilih Produk --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }} (Stok: {{ $product->stock }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="mobile-label">Jumlah (Qty)</label>
                            <input type="number" name="quantity[]" class="form-control" min="1" value="1" required placeholder="Qty">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-danger w-100 btn-remove-row">
                                <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Hapus Item</span>
                            </button>
                        </div>
                    </div>
                `;
                $('#product-list').append(row);

                // Init Select2 hanya untuk element baru
                $('.select2-new').last().select2({ theme: 'bootstrap-5', width: '100%' })
                    .removeClass('select2-new').addClass('select2-product');
            });

            // 3. Fungsi Hapus Baris
            $(document).on('click', '.btn-remove-row', function() {
                if ($('.product-row').length > 1) {
                    $(this).closest('.product-row').remove();
                } else {
                    alert('Minimal harus ada 1 barang dalam pesanan!');
                }
            });
        });
    </script>
    @endpush
@endsection
