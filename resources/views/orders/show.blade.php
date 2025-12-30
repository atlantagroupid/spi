@extends('layouts.app')

@section('title', 'Detail Order #' . $order->invoice_number)

@section('content')
    <div class="container pb-5">

        {{-- ================================================================== --}}
        {{-- 1. HEADER: TOMBOL KEMBALI PINTAR --}}
        {{-- ================================================================== --}}
        <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
            @php
                // Logika Deteksi Asal Halaman
                if (request('source') == 'approval') {
                    // Jika dari menu Approval
                    $backRoute = route('approvals.transaksi');
                    $backLabel = 'Kembali ke Menu Approval';
                } else {
                    // Default dari Riwayat Order
                    $backRoute = route('orders.index');
                    $backLabel = 'Kembali ke Riwayat';
                }
            @endphp

            <a href="{{ $backRoute }}" class="btn btn-secondary shadow-sm">
                <i class="bi bi-arrow-left me-2"></i> {{ $backLabel }}
            </a>

            @if (Auth::user()->role !== 'sales')
                <button onclick="window.print()" class="btn btn-outline-dark">
                    <i class="bi bi-printer me-1"></i> Cetak Invoice
                </button>
            @endif
        </div>
        {{-- ALERT STATUS PENGIRIMAN --}}
        @if ($order->status == 'shipped' || $order->status == 'delivered' || $order->status == 'completed')
            @if ($order->delivery_proof)
                <div
                    class="alert alert-info shadow-sm border-0 d-flex flex-column flex-md-row align-items-center justify-content-between p-4 mb-4 d-print-none">
                    <div class="d-flex align-items-center mb-3 mb-md-0">
                        <div class="bg-white p-3 rounded-circle text-primary me-3 shadow-sm">
                            <i class="bi bi-truck fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-primary">Pesanan Sedang Dikirim / Selesai</h5>
                            <p class="mb-0 text-dark small">
                                Driver: <strong>{{ $order->driver_name ?? '-' }}</strong>.
                                Surat jalan telah diterbitkan.
                            </p>
                        </div>
                    </div>

                    {{-- TOMBOL LIHAT SURAT JALAN --}}
                    <div class="d-flex gap-2">
                        <a href="{{ asset('storage/' . $order->delivery_proof) }}" target="_blank"
                            class="btn btn-light text-primary fw-bold border shadow-sm">
                            <i class="bi bi-file-earmark-text me-2"></i> Lihat Surat Jalan
                        </a>

                        {{-- Tombol Konfirmasi Terima (Khusus Sales/Manager) --}}
                        @if ($order->status == 'shipped' && in_array(Auth::user()->role, ['sales_field', 'sales_store', 'manager_operasional']))
                            <form action="{{ route('orders.confirmArrival', $order->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary fw-bold shadow-sm"
                                    onclick="return confirm('Yakin barang sudah diterima customer?')">
                                    <i class="bi bi-check-circle-fill me-1"></i> Konfirmasi Terima
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        @endif
        {{-- ================================================================== --}}
        {{-- 2. PANEL EKSEKUSI MANAGER (HANYA MUNCUL JIKA STATUS PENDING) --}}
        {{-- ================================================================== --}}
        @if (in_array(Auth::user()->role, ['manager_operasional', 'manager_bisnis']) && $order->status == 'pending_approval')

            @php
                // Ambil tiket approval terkait
                $ticket = $order->latestApproval;
            @endphp

            <div class="card shadow border-0 mb-4 bg-warning bg-opacity-10 d-print-none">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h4 class="fw-bold text-dark mb-1">
                                <i class="bi bi-shield-lock-fill text-warning me-2"></i>Butuh Persetujuan Anda
                            </h4>
                            <p class="mb-0 text-muted">
                                Silakan periksa item di bawah. Jika sesuai, klik setujui agar barang bisa diproses Gudang.
                                @if ($ticket)
                                    <span class="badge bg-secondary ms-2">Ticket #{{ $ticket->id }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-5 text-end mt-3 mt-md-0">
                            <div class="d-flex gap-2 justify-content-end">
                                {{-- TOMBOL TOLAK --}}
                                <button class="btn btn-danger btn-lg fw-bold shadow-sm" data-bs-toggle="modal"
                                    data-bs-target="#modalReject">
                                    <i class="bi bi-x-circle me-1"></i> TOLAK
                                </button>

                                {{-- TOMBOL SETUJUI (Langsung ke ApprovalController) --}}
                                @if ($ticket)
                                    <form action="{{ route('approvals.approve', $ticket->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <button class="btn btn-success btn-lg fw-bold text-white shadow-sm px-4">
                                            <i class="bi bi-check-circle-fill me-1"></i> SETUJUI
                                        </button>
                                    </form>
                                @else
                                    <button class="btn btn-secondary btn-lg" disabled>Ticket Not Found</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- PANEL KASIR --}}
        {{-- Izinkan upload jika Approved ATAU Shipped (agar bisa revisi) --}}
        @if (Auth::user()->role == 'kasir' && in_array($order->status, ['approved', 'processed', 'shipped']))
            <div class="card border-0 shadow-sm mb-4 bg-primary bg-opacity-10 d-print-none">
                <div class="card-body">
                    <h6 class="fw-bold text-primary mb-3">
                        <i class="bi bi-truck me-2"></i>
                        {{ $order->status == 'shipped' ? 'Update Pengiriman' : 'Proses Pengiriman' }}
                    </h6>

                    <form action="{{ route('orders.process', $order->id) }}" method="POST" enctype="multipart/form-data"
                        class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-5">
                            <label class="small fw-bold mb-1">Upload Surat Jalan</label>
                            <input type="file" name="delivery_proof" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold mb-1">Nama Driver</label>
                            {{-- Tambahkan value old --}}
                            <input type="text" name="driver_name" class="form-control form-control-sm"
                                value="{{ old('driver_name', $order->driver_name) }}" placeholder="Nama Supir" required>
                            <input type="hidden" name="is_revision" value="0">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                                <i class="bi bi-upload me-1"></i>
                                {{ $order->status == 'shipped' ? 'Update Data' : 'Proses Jalan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- 3. UNTUK SALES: Notifikasi Jika Ditolak --}}
        @if ($order->status == 'rejected')
            <div class="alert alert-danger shadow-sm border-start border-5 border-danger fade show d-print-none"
                role="alert">
                <h5 class="alert-heading fw-bold"><i class="bi bi-x-circle-fill me-2"></i>Order Ditolak!</h5>
                <p class="mb-0">Alasan Manager:
                    <strong>"{{ $order->rejection_note ?? 'Tidak ada alasan spesifik.' }}"</strong>
                </p>

                {{-- Tombol Edit HANYA muncul untuk Sales pemilik order --}}
                @if (Auth::id() == $order->user_id)
                    <hr>
                    <p class="mb-0 small">Silakan edit order ini untuk memperbaiki dan mengajukan ulang.</p>
                    <a href="{{ route('orders.edit', $order->id) }}"
                        class="btn btn-sm btn-light text-danger fw-bold mt-2 shadow-sm">
                        <i class="bi bi-pencil-square me-1"></i> Edit & Ajukan Ulang
                    </a>
                @endif
            </div>
        @endif

        {{-- ================================================================== --}}
        {{-- 3. AREA INVOICE (TAMPILAN DETAIL) --}}
        {{-- ================================================================== --}}
        <div class="card shadow-lg border-0" id="printArea">
            <div class="card-body p-5">
                {{-- Header Invoice --}}
                <div class="d-flex justify-content-between mb-5">
                    <div>
                        <h3 class="fw-bold text-dark">INVOICE</h3>
                        <p class="text-muted mb-1">#{{ $order->invoice_number }}</p>

                        {{-- Badge Status --}}
                        @php
                            $badgeColor = match ($order->status) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'pending_approval' => 'warning',
                                default => 'primary',
                            };
                        @endphp
                        <span
                            class="badge bg-{{ $badgeColor }} text-uppercase mt-2">{{ str_replace('_', ' ', $order->status) }}</span>
                    </div>
                    {{-- [BARU] INFO PEMBAYARAN (TOP/CASH) --}}
                    {{-- Tambahkan blok ini agar muncul kotak info pembayaran --}}
                    <div class="alert alert-light border shadow-sm p-3 d-inline-block" style="min-width: 300px;">
                        @if ($order->payment_type == 'kredit' || $order->payment_type == 'top')
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge bg-primary me-2">TOP / KREDIT</span>
                                @if ($order->customer->top_days)
                                    <span class="fw-bold text-dark">{{ $order->customer->top_days }} HARI</span>
                                @endif
                            </div>
                            <div class="small text-muted">
                                Jatuh Tempo: <strong class="text-danger">{{ \Carbon\Carbon::parse($order->due_date)->format('d F Y') }}</strong>
                            </div>
                        @else
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">CASH / TUNAI</span>
                                <span class="small text-muted">Pembayaran Tunai</span>
                            </div>
                        @endif
                    </div>
                    <div class="text-end">
                        <h5 class="fw-bold">Bintang Interior & Keramik</h5>
                        <p class="small text-muted mb-0">
                            Jl. Teuku Iskandar, Ceurih<br>
                            Banda Aceh, Indonesia<br>
                            Telp: 0812-3456-7890
                        </p>
                    </div>
                </div>

                {{-- Info Customer --}}
                <div class="row mb-4">
                    <div class="col-6">
                        <h6 class="text-uppercase text-secondary small fw-bold">Tagihan Kepada:</h6>
                        <h5 class="fw-bold">{{ $order->customer->name }}</h5>
                        <p class="text-muted small mb-0">
                            {{ $order->customer->address }}<br>
                            {{ $order->customer->phone }}
                        </p>
                    </div>
                    <div class="col-6 text-end">
                        <h6 class="text-uppercase text-secondary small fw-bold">Detail Pesanan:</h6>
                        <p class="text-muted small mb-0">
                            Tanggal: {{ date('d M Y', strtotime($order->created_at)) }}<br>
                            Jatuh Tempo: {{ date('d M Y', strtotime($order->due_date)) }}<br>
                            Sales: {{ $order->user->name }}
                        </p>
                    </div>
                </div>

                {{-- Tabel Barang --}}
                <div class="table-responsive mb-4">
                    <table class="table table-bordered border-light">
                        <thead class="bg-light">
                            <tr>
                                <th class="py-3 ps-3">Deskripsi Barang</th>
                                <th class="text-center py-3">Qty</th>
                                @if (Auth::user()->role != 'admin_gudang')
                                    <th class="text-end py-3">Harga Satuan</th>
                                    <th class="text-end py-3 pe-3">Subtotal</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="ps-3 py-3">
                                        <span class="fw-bold text-dark">{{ $item->product->name }}</span>
                                        <br><small class="text-muted">{{ $item->product->category }}</small>
                                    </td>
                                    <td class="text-center py-3">{{ $item->quantity }}</td>
                                    @if (Auth::user()->role != 'admin_gudang')
                                        <td class="text-end py-3">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td class="text-end py-3 pe-3 fw-bold">Rp
                                            {{ number_format($item->quantity * $item->price, 0, ',', '.') }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        @if (Auth::user()->role != 'admin_gudang')
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end py-3 fw-bold">TOTAL TAGIHAN</td>
                                    <td class="text-end py-3 pe-3 fw-bold fs-5 text-primary">Rp
                                        {{ number_format($order->total_price, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                {{-- Footer / Notes --}}
                <div class="row">
                    <div class="col-md-6">
                        @if ($order->notes)
                            <div class="p-3 bg-light rounded border border-light">
                                <small class="fw-bold text-secondary d-block mb-1">Catatan:</small>
                                <span class="text-muted small">{{ $order->notes }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="text-muted small fst-italic mt-3">Terima kasih atas kepercayaan Anda.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL REJECT (Wajib Ada di sini) --}}
    @if (isset($ticket))
        <div class="modal fade" id="modalReject" tabindex="-1">
            <div class="modal-dialog">
                <form action="{{ route('approvals.reject', $ticket->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Tolak Order #{{ $order->invoice_number }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Alasan Penolakan</label>
                                <textarea name="reason" class="form-control" rows="3" required
                                    placeholder="Wajib diisi agar Sales tahu apa yang salah..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Tolak Sekarang</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
    {{-- CSS KHUSUS PRINT --}}
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            #printArea,
            #printArea * {
                visibility: visible;
            }

            #printArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none !important;
            }

            .d-print-none {
                display: none !important;
            }
        }
    </style>
@endsection
