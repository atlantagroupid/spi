@extends('layouts.app')

@section('title', 'Buat Sales Order Baru')

@section('content')
    {{-- ================================================================= --}}
    {{-- FIX: CSS DILOAD LANGSUNG DI SINI AGAR TIDAK GAGAL TAMPIL --}}
    {{-- ================================================================= --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    {{-- Style Tambahan agar tidak menumpuk --}}
    <style>
        /* Memastikan container Select2 menyesuaikan lebar bootstrap */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
            min-height: 38px;
        }

        /* Hilangkan bullet point jika CSS gagal load partial */
        .select2-results__options {
            list-style: none;
            padding: 0;
            margin: 0;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-gray-800">Buat Sales Order (SO)</h1>
            <p class="text-muted small mb-0">Isi form di bawah untuk membuat transaksi baru.</p>
        </div>
        <a href="{{ route('orders.index') }}" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <div>
                    <strong class="d-block mb-1">Gagal Menyimpan Order!</strong>
                    <ul class="mb-0 ps-3 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('orders.store') }}" method="POST" enctype="multipart/form-data" id="orderForm">
        @csrf

        <div class="row g-4">

            {{-- KOLOM KIRI: INFO PELANGGAN --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-primary text-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2"></i> 1. Informasi Pelanggan</h6>
                    </div>
                    <div class="card-body">

                        {{-- 1. PILIH CUSTOMER --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Customer</label>
                            <select name="customer_id" id="customer_select" class="form-select"
                                onchange="checkPaymentMethod()">
                                <option value="" data-days="0" data-remaining="0">-- Pilih Customer --</option>
                                @foreach ($customers as $c)
                                    @php
                                        $currentDebt = $c->debt ?? 0;
                                        $remaining = $c->credit_limit - $currentDebt;
                                    @endphp
                                    <option class="text-dark paddings" value="{{ $c->id }}"
                                        data-days="{{ $c->top_days ?? 0 }}" data-remaining="{{ $remaining }}">
                                        {{ $c->name }}
                                        @if ($c->top_days > 0)
                                            ({{ $c->top_days }} Hari)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 2. METODE PEMBAYARAN --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Metode Pembayaran</label>
                            <select name="payment_type" id="payment_method" class="form-select" onchange="toggleTopInfo()">
                                <option value="cash">Cash / Tunai</option>
                                <option value="top" id="option_top" style="display:none;">TOP (Tempo)</option>
                            </select>
                        </div>

                        {{-- 3. INFO TOP --}}
                        <div id="top_info_section" style="display:none;">
                            <label class="form-label fw-bold small text-dark mb-2">Info TOP</label>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <div class="border rounded p-2 text-center bg-light h-100">
                                        <small class="text-muted d-block text-uppercase"
                                            style="font-size: 0.7rem;">Durasi</small>
                                        <span class="fw-bold fs-5 text-dark" id="display_days">0 Hari</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-2 text-center bg-light h-100">
                                        <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Sisa
                                            Kredit</small>
                                        <span class="fw-bold fs-5 text-primary" id="display_limit">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Estimasi Jatuh Tempo</label>
                                <div class="border border-warning rounded p-3 text-center bg-warning bg-opacity-10">
                                    <h6 class="fw-bold text-danger mb-0 fs-5" id="display_due_date">-</h6>
                                </div>
                            </div>
                        </div>

                        {{-- 4. CATATAN --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Catatan (Opsional)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Barang dikirim siang hari...">{{ old('notes') }}</textarea>
                        </div>

                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: KERANJANG BELANJA --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-cart-check me-2"></i> 2. Pilih Produk & Stok</h6>
                    </div>
                    <div class="card-body">

                        {{-- TOOLBAR TAMBAH PRODUK --}}
                        <div class="bg-light p-3 rounded mb-3 border">

                            {{-- A. FILTER KATEGORI --}}
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label class="small fw-bold text-muted">Filter Kategori</label>
                                    <select id="categoryFilter" class="form-select form-select-sm">
                                        <option value="">-- Semua Kategori --</option>
                                        @foreach (\App\Models\Product::select('category')->distinct()->get() as $cat)
                                            <option value="{{ $cat->category }}">{{ $cat->category }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2 align-items-end">
                                {{-- B. DROPDOWN PRODUK (SELECT2 AJAX) --}}
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold text-muted">Cari Produk</label>
                                    {{-- Pastikan class form-select ada --}}
                                    <select id="productSelect" class="form-select" style="width: 100%;">
                                        <option value="">-- Ketik Nama Produk --</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Qty</label>
                                    <input type="number" id="qtyInput" class="form-control" value="1"
                                        min="1">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-primary w-100" onclick="addToCart()">
                                        <i class="bi bi-plus-lg"></i> Tambah
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- TABEL KERANJANG --}}
                        <div class="table-responsive mb-3">
                            <table class="table table-striped table-hover align-middle border" id="cartTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th width="100" class="text-center">Qty</th>
                                        <th width="150" class="text-end">Harga</th>
                                        <th width="150" class="text-end">Total</th>
                                        <th width="50" class="text-center">#</th>
                                    </tr>
                                </thead>
                                <tbody id="cartBody">
                                    {{-- KOSONG --}}
                                    <tr id="emptyRow">
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-basket fs-1 d-block mb-2 opacity-25"></i>
                                            Belum ada produk di keranjang.
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="border-top-2">
                                    <tr class="bg-light fw-bold" style="font-size: 1.1rem;">
                                        <td colspan="3" class="text-end text-muted">ESTIMASI TOTAL:</td>
                                        <td class="text-end text-primary" id="grandTotalDisplay">Rp 0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- TOMBOL SUBMIT --}}
                        <div class="text-end">
                            <button type="submit" class="btn btn-success btn-lg px-5 shadow fw-bold" id="btnSubmit">
                                <i class="bi bi-check-circle me-2"></i> SIMPAN ORDER
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- ================================================================= --}}
    {{-- SCRIPT JAVASCRIPT (LANGSUNG DI FOOTER CONTENT) --}}
    {{-- ================================================================= --}}
    {{-- Load jQuery (Jika belum ada di layout, kalau error double load hapus baris ini) --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{-- Load Select2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // --- 1. INISIALISASI VARIABEL GLOBAL ---
        window.cartTotal = 0;

        // --- LOGIC CUSTOMER & PAYMENT (DARI KODE LAMA) ---
        function checkPaymentMethod() {
            var customerSelect = document.getElementById('customer_select');
            var selectedOption = customerSelect.options[customerSelect.selectedIndex];

            var days = parseInt(selectedOption.getAttribute('data-days')) || 0;
            var remaining = parseFloat(selectedOption.getAttribute('data-remaining')) || 0;

            var paymentSelect = document.getElementById('payment_method');
            var optionTop = document.getElementById('option_top');

            if (days > 0 || remaining > 0) {
                optionTop.style.display = 'block';
                paymentSelect.setAttribute('data-current-days', days);
                paymentSelect.setAttribute('data-current-limit', remaining);
            } else {
                optionTop.style.display = 'none';
                paymentSelect.value = 'cash';
            }
            toggleTopInfo();
        }

        function toggleTopInfo() {
            var paymentSelect = document.getElementById('payment_method');
            var topSection = document.getElementById('top_info_section');

            if (paymentSelect.value === 'top') {
                topSection.style.display = 'block';
                var days = parseInt(paymentSelect.getAttribute('data-current-days')) || 0;
                var limit = parseFloat(paymentSelect.getAttribute('data-current-limit')) || 0;

                document.getElementById('display_days').innerText = days + " Hari";
                document.getElementById('display_limit').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(
                    limit);

                var dueDate = new Date();
                dueDate.setDate(dueDate.getDate() + days);
                var options = {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                };
                document.getElementById('display_due_date').innerText = dueDate.toLocaleDateString('id-ID', options);
            } else {
                topSection.style.display = 'none';
            }
        }

        // --- INISIALISASI SELECT2 AJAX SAAT HALAMAN READY ---
        $(document).ready(function() {

            // Setup Select2 untuk Produk
            $('#productSelect').select2({
                theme: 'bootstrap-5', // Tema Bootstrap
                width: '100%', // Paksa lebar 100%
                placeholder: '-- Ketik Nama Produk --',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: "{{ route('orders.ajax_products') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term, // keyword pencarian
                            category: $('#categoryFilter').val() // kirim nilai filter kategori
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            });

            // Event saat Filter Kategori Berubah -> Reset Select2
            $('#categoryFilter').change(function() {
                $('#productSelect').val(null).trigger('change');
                $('#productSelect').select2('open');
            });
        });

        // --- FUNCTION TAMBAH BARANG (UPDATED UNTUK SELECT2 AJAX) ---
        function addToCart() {
            // Ambil elemen Select2 pakai jQuery
            let $select = $('#productSelect');
            let data = $select.select2('data')[0]; // Ambil object data dari Select2

            let qtyInput = document.getElementById('qtyInput');
            let qty = parseInt(qtyInput.value);

            // Validasi Awal
            if (!data || !data.id) {
                alert('Silakan pilih produk terlebih dahulu!');
                return;
            }

            // Ambil Data dari Object Select2 (Bukan Attribute HTML lagi)
            let id = data.id;
            let name = data.text; // Nama Produk
            let price = parseFloat(data.price || 0);
            let stock = parseInt(data.stock || 0);

            // Validasi Stok
            if (qty <= 0) {
                alert('Jumlah (Qty) minimal 1!');
                return;
            }
            if (qty > stock) {
                alert('Stok tidak cukup! Sisa: ' + stock);
                return;
            }

            // Hapus Baris "Keranjang Kosong"
            let emptyRow = document.getElementById('emptyRow');
            if (emptyRow) emptyRow.remove();

            // Hitung Subtotal
            let subtotal = price * qty;
            window.cartTotal += subtotal;

            // Render Baris Baru
            let tbody = document.getElementById('cartBody');
            let row = document.createElement('tr');

            row.innerHTML = `
            <td>
                <input type="hidden" name="product_id[]" value="${id}">
                <span class="fw-bold text-dark">${name}</span>
            </td>
            <td class="text-center">
                <input type="hidden" name="quantity[]" value="${qty}">
                <span class="badge bg-light text-dark border">${qty}</span>
            </td>
            <td class="text-end text-muted small">
                Rp ${new Intl.NumberFormat('id-ID').format(price)}
            </td>
            <td class="text-end fw-bold text-dark">
                Rp ${new Intl.NumberFormat('id-ID').format(subtotal)}
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeRow(this, ${subtotal})">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </td>
        `;

            tbody.appendChild(row);
            updateGrandTotal();

            // Reset Input
            $select.val(null).trigger('change');
            qtyInput.value = 1;
        }

        // --- FUNCTION HAPUS BARIS ---
        function removeRow(btn, subtotal) {
            let row = btn.closest('tr');
            row.remove();

            window.cartTotal -= subtotal;
            updateGrandTotal();

            // Cek Kosong
            let tbody = document.getElementById('cartBody');
            if (tbody.children.length === 0) {
                tbody.innerHTML =
                    `<tr id="emptyRow"><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-basket fs-1 d-block mb-2 opacity-25"></i>Belum ada produk di keranjang.</td></tr>`;
            }
        }

        function updateGrandTotal() {
            let display = document.getElementById('grandTotalDisplay');
            if (display) {
                display.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(window.cartTotal);
            }
        }
    </script>
@endsection
