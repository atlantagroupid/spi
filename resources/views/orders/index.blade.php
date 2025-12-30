@extends('layouts.app')

@section('title', 'Riwayat Transaksi')

@section('content')

    {{-- HEADER & TOMBOL AKSI --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2"></i>Riwayat Order</h5>
        <div>
            {{-- Tombol Export PDF --}}
            {{-- Kita sertakan request()->all() agar jika Anda sedang memfilter tanggal,PDF yang didownload juga ikut terfilter --}}
            <a href="{{ route('orders.exportListPdf', request()->all()) }}" class="btn btn-danger shadow-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export Laporan
            </a>
            {{-- Tombol Buka/Tutup Filter --}}
            <button class="btn btn-outline-secondary btn-sm me-1" type="button" data-bs-toggle="collapse"
                data-bs-target="#filterCollapse">
                <i class="bi bi-funnel"></i> Filter
            </button>
            {{-- Tombol Tambah Order --}}
            @if (in_array(Auth::user()->role, ['sales', 'manager_operasional']))
                <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Baru
                </a>
            @endif
        </div>
    </div>

    {{-- CARD FILTER (DEFAULT TERTUTUP AGAR RAPI) --}}
    <div class="collapse mb-4 {{ request('search') || request('start_date') ? 'show' : '' }}" id="filterCollapse">
        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body p-3">
                <form action="{{ route('orders.index') }}" method="GET">
                    <div class="row g-2">
                        {{-- 1. Cari Invoice/Toko --}}
                        <div class="col-12 col-md-4">
                            <label class="small text-muted fw-bold mb-1">Cari Data</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0"
                                    placeholder="Invoice / Nama Toko..." value="{{ request('search') }}">
                            </div>
                        </div>

                        {{-- 2. Rentang Tanggal (Satu baris di HP biar hemat) --}}
                        <div class="col-6 col-md-3">
                            <label class="small text-muted fw-bold mb-1">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control"
                                value="{{ request('start_date') }}">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="small text-muted fw-bold mb-1">Sampai</label>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>

                        {{-- 3. Status --}}
                        <div class="col-12 col-md-2">
                            <label class="small text-muted fw-bold mb-1">Status</label>
                            <select name="status" class="form-select">
                                <option value="">- Semua -</option>
                                <option value="pending_approval"
                                    {{ request('status') == 'pending_approval' ? 'selected' : '' }}>Menunggu</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui
                                </option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak
                                </option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai
                                </option>
                            </select>
                        </div>

                        {{-- 4. Tombol Submit --}}
                        <div class="col-12 d-flex justify-content-end mt-3 gap-2">
                            <a href="{{ route('orders.index') }}" class="btn btn-light btn-sm text-muted">Reset</a>
                            <button type="submit" class="btn btn-primary btn-sm px-4">Terapkan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 3. TABEL TRANSAKSI --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-4 py-3">No Invoice & Tanggal</th>
                            <th>Pelanggan & Sales</th>
                            <th>Total Transaksi</th>
                            <th class="text-center">Status Order</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                {{-- 1. Invoice --}}
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                                            <i class="bi bi-receipt fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $order->invoice_number }}</div>
                                            <div class="small text-muted">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                {{ $order->created_at->format('d M Y, H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- 2. Pelanggan --}}
                                <td>
                                    <div class="fw-bold text-dark">{{ $order->customer->name }}</div>
                                    <div class="small text-muted">
                                        <i class="bi bi-person-badge me-1"></i> {{ $order->user->name ?? '-' }}
                                    </div>
                                </td>

                                {{-- 3. Total --}}
                                <td>
                                    <div class="fw-bold text-primary fs-6">
                                        Rp {{ number_format($order->total_price, 0, ',', '.') }}
                                    </div>
                                    @if ($order->payment_status == 'paid')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success px-2"
                                            style="font-size: 0.7rem;">LUNAS</span>
                                    @else
                                        <span
                                            class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-2"
                                            style="font-size: 0.7rem;">BELUM LUNAS</span>
                                    @endif
                                </td>

                                {{-- 4. Status (Badge Keren) --}}
                                <td class="text-center">
                                    @if ($order->status == 'pending_approval')
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-hourglass-split me-1"></i> Menunggu
                                        </span>
                                    @elseif($order->status == 'approved')
                                        <span class="badge bg-info text-dark">
                                            <i class="bi bi-check-circle me-1"></i> Disetujui
                                        </span>
                                    @elseif($order->status == 'shipped')
                                        <span class="badge bg-primary">
                                            <i class="bi bi-truck me-1"></i> Diantar
                                        </span>
                                    @elseif($order->status == 'completed')
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-all me-1"></i> Selesai
                                        </span>
                                    @elseif($order->status == 'rejected')
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle me-1"></i> Ditolak
                                        </span>
                                    @elseif($order->status == 'cancelled')
                                        <span class="badge bg-dark">
                                            <i class="bi bi-slash-circle me-1"></i> Batal
                                        </span>
                                    @elseif($order->status == 'delivered')
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-person-check me-1"></i> Diterima
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">

                                        {{-- 1. TOMBOL DETAIL --}}
                                        <a href="{{ route('orders.show', $order->id) }}"
                                            class="btn btn-sm btn-outline-secondary" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @php
                                            // FIX PENTING: Cek kedua kemungkinan nama kolom (agar support data lama & baru)
                                            $buktiSuratJalan = $order->delivery_proof;

                                            // Cek Status Pembayaran
                                            $isTop = $order->payment_type == 'top' || $order->payment_type == 'kredit';
                                            $isCash = !$isTop;
                                            $isPaid = $order->payment_status == 'paid';
                                        @endphp
                                        @if (Auth::user()->role == 'kasir')
                                            {{-- 2. LOGIKA TOMBOL SURAT JALAN --}}
                                            @if ($buktiSuratJalan)
                                                {{-- KONDISI A: FILE ADA (Tampilkan Tombol Lihat & Revisi) --}}

                                                {{-- Tombol Lihat Foto --}}
                                                <a href="{{ asset('storage/' . $buktiSuratJalan) }}" target="_blank"
                                                    class="btn btn-sm btn-info text-white"
                                                    title="Lihat Bukti Surat Jalan">
                                                    <i class="bi bi-file-earmark-image"></i>
                                                </a>

                                                {{-- Tombol Revisi --}}
                                                <button type="button" class="btn btn-sm btn-warning"
                                                    title="Revisi Surat Jalan"
                                                    onclick="openShippingModal({{ $order->id }}, '{{ $order->invoice_number }}', true)">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                            @elseif ($order->status == 'approved')
                                                {{-- KONDISI B: BELUM UPLOAD (Status Approved) --}}

                                                @if ($isTop || ($isCash && $isPaid))
                                                    {{-- Tombol Upload (Truk Biru) --}}
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                        title="Upload Surat Jalan Manual"
                                                        onclick="openShippingModal({{ $order->id }}, '{{ $order->invoice_number }}', false)">
                                                        <i class="bi bi-truck"></i>
                                                    </button>
                                                @else
                                                    {{-- Disabled (Cash Belum Lunas) --}}
                                                    <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip"
                                                        title="Wajib Lunas Dulu (Cash)">
                                                        <button type="button" class="btn btn-sm btn-secondary" disabled>
                                                            <i class="bi bi-truck"></i>
                                                        </button>
                                                    </span>
                                                @endif
                                            @endif

                                            {{-- 3. TOMBOL BAYAR (Muncul jika belum lunas) --}}
                                            @if (!$isPaid && $order->status == 'approved')
                                                <button type="button" class="btn btn-sm btn-success"
                                                    title="Input Pembayaran"
                                                    onclick="openPaymentModal({{ $order->id }}, '{{ $order->invoice_number }}', {{ $order->total_price - $order->paymentLogs->where('status', 'approved')->sum('amount') }})">
                                                    <i class="bi bi-cash-stack"></i>
                                                </button>
                                            @endif
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center text-muted">
                                        <i class="bi bi-inbox fs-1 opacity-25 mb-3"></i>
                                        <h6 class="fw-bold">Belum Ada Transaksi</h6>
                                        <p class="small mb-0">Data transaksi yang dibuat akan muncul di sini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="p-3 border-top">
                {{ $orders->withQueryString()->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL UPLOAD / REVISI SURAT JALAN --}}
    <div class="modal fade" id="shippingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formShipping" method="POST" enctype="multipart/form-data">
                    @csrf
                    {{-- Hidden input untuk menandai ini revisi atau bukan --}}
                    <input type="hidden" name="is_revision" id="isRevisionInput" value="0">

                    <div class="modal-header bg-primary text-white" id="modalHeaderColor">
                        <h5 class="modal-title" id="modalTitle"><i class="bi bi-upload me-2"></i>Upload Bukti Surat Jalan
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        {{-- Alert Dinamis --}}
                        <div class="alert alert-info small border-0" id="modalAlert">
                            <i class="bi bi-info-circle me-1"></i>
                            Silakan upload foto/scan Surat Jalan fisik.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">No. Invoice</label>
                            <input type="text" class="form-control bg-light" id="shippingInvoice" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">File Surat Jalan <span class="text-danger">*</span></label>
                            <input type="file" name="delivery_note_file" class="form-control" required
                                accept="image/*,.pdf">
                            <small class="text-muted">Format: JPG, PNG, atau PDF.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Supir / Kurir</label>
                            <input type="text" name="driver_name" class="form-control"
                                placeholder="Contoh: Pak Budi">
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitShipping">
                            <i class="bi bi-save me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL INPUT PEMBAYARAN --}}
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-wallet2"></i> Input Pembayaran</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="#" method="POST" enctype="multipart/form-data" id="paymentForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        {{-- Info Invoice --}}
                        <div class="alert alert-light border text-center mb-3">
                            <strong id="modalInvoice" class="d-block text-dark"></strong>
                            <small class="text-muted">Sisa Tagihan:</small>
                            <h4 class="text-success fw-bold" id="modalSisaTagihan">Rp 0</h4>
                        </div>

                        {{-- Input Nominal --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nominal Diterima (Rp)</label>
                            <input type="number" name="amount" class="form-control form-control-lg" required
                                placeholder="0">
                        </div>

                        {{-- Metode Bayar --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Metode Pembayaran</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Cash">Tunai (Cash)</option>
                                <option value="Transfer">Transfer Bank</option>
                                <option value="Giro">Giro / Cek</option>
                            </select>
                        </div>

                        {{-- Tgl Bayar --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal Terima</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}"
                                required>
                        </div>

                        {{-- Bukti Foto (Opsional untuk Cash, Wajib untuk Transfer) --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Bukti Foto / Struk</label>
                            <input type="file" name="proof_file" class="form-control">
                            <div class="form-text small">Wajib jika transfer. Opsional jika Tunai.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success fw-bold">
                            <i class="bi bi-save"></i> Simpan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function openShippingModal(id, invoice, isRevision) {
            // 1. Set Invoice
            $('#shippingInvoice').val(invoice);

            // 2. Set URL Form
            // Jika Revisi, mungkin route-nya beda? Atau sama tapi ditangani controller?
            // Kita asumsikan route sama: 'orders.process', tapi controller yang akan memilah logic-nya.
            let url = "{{ route('orders.process', 0) }}".replace('/0', '/' + id);
            $('#formShipping').attr('action', url);

            // 3. Logika Tampilan (Upload Baru vs Revisi)
            if (isRevision) {
                // MODE REVISI
                $('#isRevisionInput').val('1'); // Tandai sebagai revisi
                $('#modalTitle').html('<i class="bi bi-pencil-square me-2"></i> Ajukan Revisi Surat Jalan');
                $('#modalHeaderColor').removeClass('bg-primary').addClass('bg-warning text-dark');
                $('#modalAlert').removeClass('alert-info').addClass('alert-warning text-dark')
                    .html(
                        '<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>PERHATIAN:</strong> Karena surat jalan sudah ada, upload baru ini akan masuk ke <strong>APPROVAL MANAGER</strong> terlebih dahulu.'
                    );
                $('#btnSubmitShipping').removeClass('btn-primary').addClass('btn-warning text-dark').html(
                    '<i class="bi bi-send"></i> Ajukan Approval');
            } else {
                // MODE UPLOAD BARU
                $('#isRevisionInput').val('0'); // Bukan revisi
                $('#modalTitle').html('<i class="bi bi-upload me-2"></i> Upload Bukti Surat Jalan');
                $('#modalHeaderColor').removeClass('bg-warning text-dark').addClass('bg-primary text-white');
                $('#modalAlert').removeClass('alert-warning text-dark').addClass('alert-info')
                    .html(
                        '<i class="bi bi-info-circle me-1"></i> Silakan upload foto/scan Surat Jalan fisik untuk memproses status menjadi DIKIRIM.'
                    );
                $('#btnSubmitShipping').removeClass('btn-warning text-dark').addClass('btn-primary').html(
                    '<i class="bi bi-save"></i> Simpan & Kirim');
            }

            // 4. Buka Modal
            $('#shippingModal').modal('show');
        }

        function openPaymentModal(id, invoice, sisa) {
            // 1. Set Action URL Form
            // Pastikan route 'payment.process' sudah ada di web.php
            let url = "{{ route('receivables.pay', ':id') }}";
            url = url.replace(':id', id);
            document.getElementById('paymentForm').action = url;

            // 2. Set Info Invoice & Sisa
            document.getElementById('modalInvoice').innerText = invoice;

            // Format Rupiah
            let rupiah = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(sisa);
            document.getElementById('modalSisaTagihan').innerText = rupiah;

            // 3. Buka Modal
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }
    </script>
@endsection
