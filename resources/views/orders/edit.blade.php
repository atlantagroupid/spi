@extends('layouts.app')
@section('title', 'Edit Order #' . $order->invoice_number)

@section('content')
    <div class="card shadow border-0">
        <div class="card-header bg-warning text-dark">
            <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-square"></i> Revisi Order</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('orders.update', $order->id) }}" method="POST">
                @csrf
                @method('PUT') {{-- Wajib untuk Update --}}

                {{-- Info Customer --}}
                <div class="mb-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select" required>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" {{ $order->customer_id == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Area Produk (Dynamic Input) --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Daftar Produk</label>
                    <div id="product-container">
                        {{-- LOOPING ITEM YANG SUDAH ADA --}}
                        @foreach ($order->items as $index => $item)
                            <div class="row g-2 mb-2 product-row">
                                <div class="col-7">
                                    <select name="products[]" class="form-select form-select-sm" required>
                                        <option value="">Pilih Produk</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}"
                                                {{ $item->product_id == $p->id ? 'selected' : '' }}>
                                                {{ $p->name }} (Stok: {{ $p->stock }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-3">
                                    <input type="number" name="quantities[]" class="form-control form-control-sm"
                                        placeholder="Qty" value="{{ $item->quantity }}" min="1" required>
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-danger btn-sm w-100 remove-row">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-product-btn">
                        <i class="bi bi-plus-circle"></i> Tambah Produk Lain
                    </button>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan Revisi</label>
                    <textarea name="notes" class="form-control" rows="2">{{ $order->notes }}</textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Simpan & Ajukan Ulang</button>
                    <a href="{{ route('orders.index') }}" class="btn btn-light">Batal</a>
                </div>
            </form>
        </div>
    </div>

    {{-- SCRIPT DUPLIKASI BARIS PRODUK (Sama seperti di Create) --}}
    {{-- JAVASCRIPT --}}
    <script>
        // 1. LOGIKA PELANGGAN & TOP
        document.getElementById('customerSelect').addEventListener('change', function() {
            let option = this.options[this.selectedIndex];
            let top = option.getAttribute('data-top');

            if (top) {
                // Hitung Tanggal Jatuh Tempo
                let today = new Date();
                today.setDate(today.getDate() + parseInt(top));
                let dateString = today.toISOString().split('T')[0];

                document.getElementById('dueDateInput').value = dateString;

                // Info TOP
                let msg = top > 0 ?
                    `<span class="text-danger fw-bold"><i class="bi bi-clock-history"></i> Kredit (TOP ${top} Hari). Wajib Upload Bukti.</span>` :
                    `<span class="text-success fw-bold"><i class="bi bi-cash-coin"></i> Cash / Tunai.</span>`;

                document.getElementById('topInfo').innerHTML = msg;

                // Warning Bukti Bayar
                let warning = document.getElementById('proofWarning');
                if (top > 0) warning.classList.remove('d-none');
                else warning.classList.add('d-none');

            } else {
                document.getElementById('topInfo').innerText = "Pilih customer untuk cek TOP.";
            }
        });

        // 2. LOGIKA KERANJANG BELANJA
        let cartTotal = 0;

        function addToCart() {
            let productSelect = document.getElementById('productSelect');
            let qtyInput = document.getElementById('qtyInput');

            let id = productSelect.value;
            let name = productSelect.options[productSelect.selectedIndex].getAttribute('data-name');
            let price = parseFloat(productSelect.options[productSelect.selectedIndex].getAttribute('data-price'));
            let stock = parseInt(productSelect.options[productSelect.selectedIndex].getAttribute('data-stock'));
            let qty = parseInt(qtyInput.value);

            // Validasi Sederhana
            if (!id) {
                alert('Pilih produk dulu bos!');
                return;
            }
            if (qty > stock) {
                alert('Stok tidak cukup! Sisa cuma: ' + stock);
                return;
            }

            // Hapus Baris Kosong
            let emptyRow = document.getElementById('emptyRow');
            if (emptyRow) emptyRow.remove();

            // Hitung Subtotal
            let subtotal = price * qty;
            cartTotal += subtotal;

            // Tambah Baris ke Tabel
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

            // Update Total
            updateGrandTotal();

            // Reset Input
            productSelect.value = "";
            qtyInput.value = 1;
        }

        function removeRow(btn, subtotal) {
            let row = btn.closest('tr');
            row.remove();
            cartTotal -= subtotal;
            updateGrandTotal();

            // Cek kalau kosong, balikin baris kosong
            let tbody = document.getElementById('cartBody');
            if (tbody.children.length === 0) {
                tbody.innerHTML =
                    `<tr id="emptyRow"><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-basket fs-1 d-block mb-2 opacity-25"></i>Keranjang Kosong</td></tr>`;
            }
        }

        function updateGrandTotal() {
            document.getElementById('grandTotalDisplay').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(
                cartTotal);
        }
    </script>
@endsection
