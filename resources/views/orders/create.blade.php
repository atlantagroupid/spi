@extends('layouts.app')

@section('title', 'Buat Sales Order Baru')

@section('content')
    {{-- CSS Select2 Langsung --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        /* CSS Fix Select2 Mobile */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            padding: 0.375rem 0.75rem;
            border: 1px solid #ced4da;
            background-color: #fff;
        }
        .select2-container { width: 100% !important; }

        /* Tampilan Tabel Keranjang di Mobile */
        @media (max-width: 576px) {
            .table-mobile-responsive thead { display: none; } /* Sembunyikan header tabel */
            .table-mobile-responsive tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
                padding: 1rem;
                background: #fff;
                position: relative;
            }
            .table-mobile-responsive td {
                display: flex;
                justify-content: space-between;
                padding: 0.25rem 0;
                border: none;
            }
            /* Styling khusus per kolom di mobile */
            .td-product { font-weight: bold; font-size: 1rem; margin-bottom: 0.5rem; display: block !important; }
            .td-qty::before { content: "Qty: "; color: #6c757d; }
            .td-price::before { content: "@ "; color: #6c757d; }
            .td-total { border-top: 1px dashed #dee2e6 !important; margin-top: 0.5rem; padding-top: 0.5rem !important; font-weight: bold; color: #0d6efd; }
            .td-action { position: absolute; top: 0.5rem; right: 0.5rem; }
        }
    </style>

    <div class="container-fluid px-0 px-md-3">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Buat Sales Order</h4>
                <p class="text-muted small mb-0 d-none d-md-block">Isi form di bawah untuk transaksi baru.</p>
            </div>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary shadow-sm btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        {{-- ALERT ERROR --}}
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <strong class="d-block mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Menyimpan!</strong>
                <ul class="mb-0 ps-3 small">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('orders.store') }}" method="POST" enctype="multipart/form-data" id="orderForm">
            @csrf

            {{-- [PERBAIKAN] NAMA INPUT DIGANTI JADI 'top_days' AGAR SESUAI CONTROLLER --}}
            <input type="hidden" name="top_days" id="hidden_top_days" value="">

            <div class="row g-4">

                {{-- KOLOM KIRI: INFO PELANGGAN (Urutan Pertama) --}}
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-primary text-white py-3">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2"></i> 1. Info Pelanggan</h6>
                        </div>
                        <div class="card-body p-3 p-md-4">

                            {{-- 1. PILIH CUSTOMER --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Customer</label>
                                <select name="customer_id" id="customer_select" class="form-select select2" onchange="checkPaymentMethod()">
                                    <option value="" data-days="0" data-remaining="0">-- Pilih Customer --</option>
                                    @foreach ($customers as $c)
                                        @php $remaining = $c->credit_limit - ($c->debt ?? 0); @endphp
                                        <option value="{{ $c->id }}" data-days="{{ $c->top_days ?? 0 }}" data-remaining="{{ $remaining }}">
                                            {{ $c->name }} {{ $c->top_days > 0 ? "({$c->top_days} Hari)" : '' }}
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

                            {{-- 3. INFO TOP (Hidden by default) --}}
                            <div id="top_info_section" style="display:none;" class="bg-light p-3 rounded mb-3 border">
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <small class="text-muted text-uppercase d-block" style="font-size: 0.65rem;">Tenor</small>
                                        <span class="fw-bold text-dark" id="display_days">0 Hari</span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted text-uppercase d-block" style="font-size: 0.65rem;">Sisa Limit</small>
                                        <span class="fw-bold text-primary" id="display_limit">Rp 0</span>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="text-center">
                                    <small class="text-muted">Jatuh Tempo:</small>
                                    <h6 class="fw-bold text-danger mb-0" id="display_due_date">-</h6>
                                </div>
                            </div>

                            {{-- 4. CATATAN --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Catatan (Opsional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Catatan pengiriman dll...">{{ old('notes') }}</textarea>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- KOLOM KANAN: KERANJANG BELANJA (Urutan Kedua) --}}
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-cart-check me-2"></i> 2. Produk & Stok</h6>
                        </div>
                        <div class="card-body p-3 p-md-4">

                            {{-- TOOLBAR INPUT PRODUK --}}
                            <div class="bg-light p-3 rounded mb-3 border">
                                <div class="row g-2">
                                    {{-- Filter Kategori --}}
                                    <div class="col-12">
                                        <label class="small fw-bold text-muted">Kategori</label>
                                        <select id="categoryFilter" class="form-select form-select-sm">
                                            <option value="">-- Semua Kategori --</option>
                                            @foreach (\App\Models\Product::select('category')->distinct()->get() as $cat)
                                                <option value="{{ $cat->category }}">{{ $cat->category }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Input Produk (Select2) --}}
                                    <div class="col-12 col-md-6">
                                        <label class="small fw-bold text-muted">Nama Produk</label>
                                        <select id="productSelect" class="form-select">
                                            <option value="">-- Ketik Nama Produk --</option>
                                        </select>
                                    </div>

                                    {{-- Input Qty & Tombol --}}
                                    <div class="col-6 col-md-3">
                                        <label class="small fw-bold text-muted">Qty</label>
                                        <input type="number" id="qtyInput" class="form-control" value="1" min="1">
                                    </div>
                                    <div class="col-6 col-md-3 d-flex align-items-end">
                                        <button type="button" class="btn btn-primary w-100 fw-bold" onclick="addToCart()">
                                            <i class="bi bi-plus-lg"></i> Tambah
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- TABEL KERANJANG (Responsif) --}}
                            <div class="table-mobile-responsive mb-4">
                                <table class="table table-hover align-middle" id="cartTable">
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
                                        <tr id="emptyRow">
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-basket fs-1 d-block mb-2 opacity-25"></i>
                                                Belum ada produk.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- TOTAL & SUBMIT --}}
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center bg-light p-3 rounded border">
                                <div class="mb-3 mb-md-0 text-center text-md-start">
                                    <small class="text-muted fw-bold text-uppercase">Total Estimasi</small>
                                    <h3 class="fw-bold text-primary mb-0" id="grandTotalDisplay">Rp 0</h3>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg px-5 shadow fw-bold w-100 w-md-auto" id="btnSubmit">
                                    <i class="bi bi-check-circle me-2"></i> PROSES ORDER
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- SCRIPT JAVASCRIPT --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        window.cartTotal = 0;

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

                // [PERBAIKAN] Mengisi nilai input 'top_days'
                document.getElementById('hidden_top_days').value = days;

                document.getElementById('display_days').innerText = days + " Hari";
                document.getElementById('display_limit').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(limit);

                var dueDate = new Date();
                dueDate.setDate(dueDate.getDate() + days);
                var options = { day: 'numeric', month: 'long', year: 'numeric' };
                document.getElementById('display_due_date').innerText = dueDate.toLocaleDateString('id-ID', options);
            } else {
                topSection.style.display = 'none';

                // [PERBAIKAN] Kosongkan 'top_days' jika bukan TOP
                document.getElementById('hidden_top_days').value = '';
            }
        }

        $(document).ready(function() {
            // Select2 Customer
            $('#customer_select').select2({ theme: 'bootstrap-5', width: '100%' });

            // Select2 Produk (AJAX)
            $('#productSelect').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Ketik Nama Produk --',
                allowClear: true,
                ajax: {
                    url: "{{ route('orders.ajax_products') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { search: params.term, category: $('#categoryFilter').val() };
                    },
                    processResults: function(data) { return { results: data }; },
                    cache: true
                }
            });

            $('#categoryFilter').change(function() {
                $('#productSelect').val(null).trigger('change');
            });
        });

        function addToCart() {
            let $select = $('#productSelect');
            let data = $select.select2('data')[0];
            let qtyInput = document.getElementById('qtyInput');
            let qty = parseInt(qtyInput.value);

            if (!data || !data.id) { alert('Silakan pilih produk dulu!'); return; }
            if (qty <= 0) { alert('Jumlah minimal 1!'); return; }
            if (qty > data.stock) { alert('Stok tidak cukup! Sisa: ' + data.stock); return; }

            // Hapus row kosong
            let emptyRow = document.getElementById('emptyRow');
            if (emptyRow) emptyRow.remove();

            let price = parseFloat(data.price || 0);
            let subtotal = price * qty;
            window.cartTotal += subtotal;

            let tbody = document.getElementById('cartBody');
            let row = document.createElement('tr');

            // Render HTML (Support Mobile Class)
            row.innerHTML = `
                <td class="td-product">
                    <input type="hidden" name="product_id[]" value="${data.id}">
                    <span class="fw-bold text-dark">${data.text}</span>
                </td>
                <td class="text-center td-qty">
                    <input type="hidden" name="quantity[]" value="${qty}">
                    <span class="badge bg-light text-dark border">${qty}</span>
                </td>
                <td class="text-end text-muted small td-price">
                    Rp ${new Intl.NumberFormat('id-ID').format(price)}
                </td>
                <td class="text-end fw-bold text-dark td-total">
                    Rp ${new Intl.NumberFormat('id-ID').format(subtotal)}
                </td>
                <td class="text-center td-action">
                    <button type="button" class="btn btn-sm btn-light text-danger border-0" onclick="removeRow(this, ${subtotal})">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
            updateGrandTotal();

            // Reset
            $select.val(null).trigger('change');
            qtyInput.value = 1;
        }

        function removeRow(btn, subtotal) {
            btn.closest('tr').remove();
            window.cartTotal -= subtotal;
            updateGrandTotal();
            if (document.getElementById('cartBody').children.length === 0) {
                document.getElementById('cartBody').innerHTML = `<tr id="emptyRow"><td colspan="5" class="text-center py-5 text-muted">Keranjang kosong.</td></tr>`;
            }
        }

        function updateGrandTotal() {
            document.getElementById('grandTotalDisplay').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(window.cartTotal);
        }
    </script>
@endsection
