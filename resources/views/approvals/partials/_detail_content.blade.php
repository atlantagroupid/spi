{{-- FILE: resources/views/approvals/partials/_detail_content.blade.php --}}
@if ($approval)
    <div class="p-3">
        {{-- HEADER INFO --}}
        <div class="alert alert-light border shadow-sm mb-3">
            <div class="d-flex justify-content-between small">
                <div><strong>ID Ticket:</strong> #{{ $approval->id }}</div>
                <div><strong>Oleh:</strong> <span
                        class="text-primary fw-bold">{{ $approval->requester->name ?? 'System' }}</span></div>
                <div><strong>Tanggal:</strong> {{ $approval->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <hr>

        {{-- LOGIKA TAMPILAN --}}

        {{-- 1. JIKA ORDER (TRANSAKSI) --}}
        @if (str_contains($approval->model_type, 'Order'))
            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-cart-fill me-2"></i>Detail Pesanan</h5>
            <table class="table table-bordered table-sm mb-3">
                <tr>
                    <td width="30%" class="bg-light fw-bold">Customer</td>
                    <td>{{ $data->customer->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="bg-light fw-bold">Invoice</td>
                    <td>{{ $data->invoice_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="bg-light fw-bold">Tipe Bayar</td>
                    <td>{!! $data->payment_type == 'top'
                        ? '<span class="badge bg-warning text-dark">TOP</span>'
                        : '<span class="badge bg-success">CASH</span>' !!}</td>
                </tr>
            </table>

            <h6 class="fw-bold">Item Barang:</h6>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-sm">
                    <thead class="bg-secondary text-white">
                        <tr>
                            <th>Produk</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data->items ?? [] as $item)
                            <tr>
                                <td>{{ $item->product->name ?? 'Hapus' }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">{{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="3" class="text-end fw-bold">TOTAL TAGIHAN</td>
                            <td class="text-end fw-bold bg-warning text-dark">Rp
                                {{ number_format($data->total_price ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- 2. JIKA PAYMENT (PIUTANG) --}}
        @elseif (str_contains($approval->model_type, 'PaymentLog'))
            <h5 class="fw-bold mb-3 text-success"><i class="bi bi-cash-stack me-2"></i>Detail Pembayaran</h5>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered table-sm">
                        <tr>
                            <td class="bg-light fw-bold">Nominal</td>
                            <td class="fw-bold text-success fs-5">Rp
                                {{ number_format($approval->new_data['amount'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="bg-light fw-bold">Metode</td>
                            <td>{{ $approval->new_data['payment_method'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="bg-light fw-bold">Tgl Bayar</td>
                            <td>{{ $approval->new_data['payment_date'] ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6 text-center">
                    <label class="fw-bold small text-muted mb-2">Bukti Transfer:</label><br>
                    @if (!empty($approval->new_data['proof_file']))
                        <a href="{{ asset('storage/payment_proofs/' . $approval->new_data['proof_file']) }}"
                            target="_blank">
                            <img src="{{ asset('storage/payment_proofs/' . $approval->new_data['proof_file']) }}"
                                class="img-fluid rounded" style="max-height: 150px;">
                        </a>
                    @else
                        <span class="text-muted fst-italic">Tidak ada bukti foto</span>
                    @endif
                </div>
            </div>

            {{-- 3. JIKA PRODUCT (DATA BARANG) --}}
        @elseif (str_contains($approval->model_type, 'Product'))
            <h5 class="fw-bold mb-3 {{ $approval->action == 'delete' ? 'text-danger' : 'text-warning' }}">
                <i class="bi bi-box-seam me-2"></i>Detail Produk
            </h5>

            <div
                class="alert {{ $approval->action == 'delete' ? 'alert-danger' : 'alert-warning text-dark' }} border-0 small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Status Request:</strong>
                @if ($approval->action == 'create')
                    <span class="badge bg-success">Produk Baru</span>
                @elseif($approval->action == 'delete')
                    <span class="badge bg-danger">HAPUS PRODUK</span>
                    <div class="mt-1">Peringatan: Menyetujui ini akan menghapus produk dari database secara permanen.
                    </div>
                @else
                    <span class="badge bg-info text-dark">Update Stok/Data</span>
                @endif
            </div>

            {{-- TENTUKAN SUMBER DATA (Jika Hapus, ambil data lama. Jika Baru/Edit, ambil data baru) --}}
            @php
                // Jika action delete, new_data biasanya kosong, jadi kita tampilkan data aslinya ($data)
                $displayData = $approval->action == 'delete' ? ($data ? $data->toArray() : []) : $approval->new_data;
                // Mapping field manual jika $data object
                $name = $displayData['name'] ?? ($data->name ?? '-');
                $category = $displayData['category'] ?? ($data->category ?? 'Umum');
                $price = $displayData['price'] ?? ($data->price ?? 0);
                $stock = $displayData['stock'] ?? ($data->stock ?? 0);
                $desc = $displayData['description'] ?? ($data->description ?? '-');
            @endphp

            <table class="table table-bordered table-striped shadow-sm">
                <thead class="{{ $approval->action == 'delete' ? 'bg-danger text-white' : 'bg-warning text-dark' }}">
                    <tr>
                        <th width="35%">Atribut</th>
                        <th>Detail Data {{ $approval->action == 'delete' ? '(Akan Dihapus)' : '' }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-bold bg-light">Nama Produk</td>
                        <td class="fw-bold fs-5">{{ $name }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold bg-light">Kategori</td>
                        <td><span class="badge bg-secondary">{{ $category }}</span></td>
                    </tr>
                    <tr>
                        <td class="fw-bold bg-light">Harga Jual</td>
                        <td class="fw-bold text-primary">Rp {{ number_format((float) $price, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold bg-light">Stok Saat Ini</td>
                        <td class="fw-bold">{{ $stock }} Unit</td>
                    </tr>
                    @if (!empty($desc))
                        <tr>
                            <td class="fw-bold bg-light">Deskripsi</td>
                            <td class="small text-muted">{{ $desc }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            {{-- 4. DEFAULT (JSON MENTAH) --}}
        @else
            <div class="alert alert-secondary">Data Mentah:</div>
            <pre class="bg-light p-2 border rounded">{{ json_encode($approval->new_data, JSON_PRETTY_PRINT) }}</pre>
        @endif
    </div>
@else
    <div class="alert alert-danger m-3">Gagal memuat detail approval.</div>
@endif
