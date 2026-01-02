@extends('layouts.app')

@section('title', 'Detail Order #' . $order->invoice_number)

@section('content')
<div class="container pb-5">

    {{-- HEADER & NAVIGASI --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2 d-print-none">
        @php
            if (request('source') == 'approval') {
                $backRoute = route('approvals.transaksi');
                $backLabel = 'Kembali ke Approval';
            } else {
                $backRoute = route('orders.index');
                $backLabel = 'Kembali';
            }
        @endphp

        <a href="{{ $backRoute }}" class="btn btn-secondary shadow-sm btn-sm-mobile">
            <i class="bi bi-arrow-left me-2"></i> {{ $backLabel }}
        </a>

        @if (Auth::user()->role !== 'sales')
            <button onclick="window.print()" class="btn btn-outline-dark btn-sm-mobile">
                <i class="bi bi-printer me-1"></i> Cetak Invoice
            </button>
        @endif
    </div>

    {{-- ALERT STATUS PENGIRIMAN --}}
    @if (in_array($order->status, ['shipped', 'delivered', 'completed']) && $order->delivery_proof)
        <div class="alert alert-info shadow-sm border-0 p-3 p-md-4 mb-4 d-print-none">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center w-100">
                    <div class="bg-white p-3 rounded-circle text-primary me-3 shadow-sm d-none d-md-block">
                        <i class="bi bi-truck fs-3"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1 text-primary d-flex align-items-center gap-2">
                            <i class="bi bi-truck d-md-none"></i> Pesanan Dikirim
                        </h5>
                        <p class="mb-0 text-dark small">
                            Driver: <strong>{{ $order->driver_name ?? '-' }}</strong>
                        </p>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
                    <a href="{{ asset('storage/' . $order->delivery_proof) }}" target="_blank"
                        class="btn btn-light text-primary fw-bold border shadow-sm w-100">
                        <i class="bi bi-file-earmark-text me-2"></i> Surat Jalan
                    </a>

                    @if ($order->status == 'shipped' && in_array(Auth::user()->role, ['sales_field', 'sales_store', 'manager_operasional']))
                        <form action="{{ route('orders.confirmArrival', $order->id) }}" method="POST" class="w-100">
                            @csrf
                            <button type="submit" class="btn btn-primary fw-bold shadow-sm w-100"
                                onclick="return confirm('Yakin barang sudah diterima customer?')">
                                <i class="bi bi-check-circle-fill me-1"></i> Terima Barang
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- PANEL EKSEKUSI MANAGER --}}
    @if (in_array(Auth::user()->role, ['manager_operasional', 'manager_bisnis']) && $order->status == 'pending_approval')
        @php $ticket = $order->latestApproval; @endphp
        <div class="card shadow border-0 mb-4 bg-warning bg-opacity-10 d-print-none">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <div class="text-center text-md-start">
                        <h5 class="fw-bold text-dark mb-1">
                            <i class="bi bi-shield-lock-fill text-warning me-2"></i>Butuh Approval
                        </h5>
                        <p class="mb-0 text-muted small">Cek data sebelum menyetujui.</p>
                    </div>
                    <div class="d-flex gap-2 w-100 w-md-auto">
                        <button class="btn btn-danger fw-bold shadow-sm w-50 w-md-auto" data-bs-toggle="modal" data-bs-target="#modalReject">
                            <i class="bi bi-x-circle"></i> Tolak
                        </button>
                        @if ($ticket)
                            <form action="{{ route('approvals.approve', $ticket->id) }}" method="POST" class="w-50 w-md-auto">
                                @csrf @method('PUT')
                                <button class="btn btn-success fw-bold text-white shadow-sm w-100">
                                    <i class="bi bi-check-circle-fill"></i> Setujui
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- PANEL KASIR (UPLOAD SURAT JALAN) --}}
    @if (Auth::user()->role == 'kasir' && in_array($order->status, ['approved', 'processed', 'shipped']))
        <div class="card border-0 shadow-sm mb-4 bg-primary bg-opacity-10 d-print-none">
            <div class="card-body p-3 p-md-4">
                <h6 class="fw-bold text-primary mb-3">
                    <i class="bi bi-truck me-2"></i>
                    {{ $order->status == 'shipped' ? 'Update Pengiriman' : 'Proses Pengiriman' }}
                </h6>

                <form action="{{ route('orders.process', $order->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-5">
                            <label class="small fw-bold mb-1">Upload Surat Jalan</label>
                            <input type="file" name="delivery_proof" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="small fw-bold mb-1">Nama Driver</label>
                            <input type="text" name="driver_name" class="form-control form-control-sm"
                                value="{{ old('driver_name', $order->driver_name) }}" placeholder="Nama Supir" required>
                            <input type="hidden" name="is_revision" value="0">
                        </div>
                        <div class="col-12 col-md-3">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold mt-2 mt-md-0">
                                <i class="bi bi-upload me-1"></i>
                                {{ $order->status == 'shipped' ? 'Update Data' : 'Proses Jalan' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- INFO ORDER DITOLAK (SALES ONLY) --}}
    @if ($order->status == 'rejected')
        <div class="alert alert-danger shadow-sm border-start border-5 border-danger fade show d-print-none p-3 p-md-4 mb-4" role="alert">
            <h5 class="alert-heading fw-bold d-flex align-items-center">
                <i class="bi bi-x-circle-fill me-2 fs-4"></i>Order Ditolak!
            </h5>
            <p class="mb-0">Alasan Manager: <strong>"{{ $order->rejection_note ?? 'Tidak ada alasan spesifik.' }}"</strong></p>

            @if (Auth::id() == $order->user_id)
                <hr>
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                    <p class="mb-0 small text-muted">Silakan perbaiki order ini & ajukan ulang.</p>
                    <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-light text-danger fw-bold shadow-sm w-100 w-md-auto">
                        <i class="bi bi-pencil-square me-1"></i> Edit & Ajukan Ulang
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- AREA INVOICE --}}
    <div class="card shadow-lg border-0" id="printArea">
        <div class="card-body p-4 p-md-5">

            {{-- HEADER INVOICE --}}
            <div class="d-flex flex-column flex-md-row justify-content-between mb-4 gap-4">
                <div>
                    <h3 class="fw-bold text-dark">INVOICE</h3>
                    <p class="text-muted mb-2">#{{ $order->invoice_number }}</p>

                    @php
                        $badgeColor = match ($order->status) {
                            'approved', 'completed', 'delivered' => 'success',
                            'rejected' => 'danger',
                            'pending_approval' => 'warning',
                            default => 'primary',
                        };
                    @endphp
                    <span class="badge bg-{{ $badgeColor }} text-uppercase">{{ str_replace('_', ' ', $order->status) }}</span>
                </div>

                {{-- INFO PEMBAYARAN --}}
                <div class="alert alert-light border shadow-sm p-3 w-100 w-md-50">
                    @if ($order->payment_type == 'kredit' || $order->payment_type == 'top')
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge bg-primary me-2">TOP / KREDIT</span>
                            @if ($order->customer->top_days)
                                <span class="fw-bold text-dark small">{{ $order->customer->top_days }} HARI</span>
                            @endif
                        </div>
                        <div class="small text-muted">
                            Jatuh Tempo: <strong class="text-danger">{{ \Carbon\Carbon::parse($order->due_date)->format('d M Y') }}</strong>
                        </div>
                    @else
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success me-2">CASH</span>
                            <span class="small text-muted">Tunai Lunas</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- INFO ALAMAT (RESPONSIVE GRID) --}}
            <div class="row mb-4 g-3">
                <div class="col-12 col-md-6">
                    <h6 class="text-uppercase text-secondary small fw-bold mb-2">Tagihan Kepada:</h6>
                    <div class="bg-light p-3 rounded">
                        <h5 class="fw-bold mb-1">{{ $order->customer->name }}</h5>
                        <p class="text-muted small mb-0">
                            {{ $order->customer->address }}<br>
                            <i class="bi bi-telephone me-1"></i> {{ $order->customer->phone }}
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 text-md-end">
                    <h6 class="text-uppercase text-secondary small fw-bold mb-2">Penerbit:</h6>
                    <h5 class="fw-bold mb-1">Bintang Interior & Keramik</h5>
                    <p class="text-muted small mb-0">
                        Banda Aceh, Indonesia<br>
                        Sales: <strong>{{ $order->user->name }}</strong>
                    </p>
                </div>
            </div>

            {{-- DAFTAR BARANG (RESPONSIVE TABLE vs LIST) --}}

            {{-- TAMPILAN MOBILE (LIST CARD) --}}
            <div class="d-block d-md-none">
                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3">Item Pesanan</h6>
                @foreach ($order->items as $item)
                    <div class="card mb-3 border bg-light">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-bold text-dark">{{ $item->product->name }}</span>
                                <span class="badge bg-secondary">x{{ $item->quantity }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="text-muted">@ {{ number_format($item->price, 0, ',', '.') }}</span>
                                <span class="fw-bold text-primary">Rp {{ number_format($item->quantity * $item->price, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">
                    <span class="fw-bold">TOTAL TAGIHAN</span>
                    <span class="fw-bold fs-5 text-primary">Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- TAMPILAN DESKTOP (TABLE) --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-bordered border-light align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3 ps-3">Deskripsi Barang</th>
                            <th class="text-center py-3" width="100">Qty</th>
                            @if (Auth::user()->role != 'admin_gudang')
                                <th class="text-end py-3" width="150">Harga</th>
                                <th class="text-end py-3 pe-3" width="150">Subtotal</th>
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
                                <td class="text-center">{{ $item->quantity }}</td>
                                @if (Auth::user()->role != 'admin_gudang')
                                    <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td class="text-end pe-3 fw-bold">Rp {{ number_format($item->quantity * $item->price, 0, ',', '.') }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    @if (Auth::user()->role != 'admin_gudang')
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end py-3 fw-bold">TOTAL TAGIHAN</td>
                                <td class="text-end py-3 pe-3 fw-bold fs-5 text-primary">
                                    Rp {{ number_format($order->total_price, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            {{-- CATATAN --}}
            @if ($order->notes)
                <div class="mt-4 p-3 bg-light rounded border border-light">
                    <small class="fw-bold text-secondary d-block mb-1">Catatan:</small>
                    <span class="text-muted small">{{ $order->notes }}</span>
                </div>
            @endif

        </div>
    </div>
</div>

{{-- MODAL REJECT (SALIN DARI SEBELUMNYA) --}}
@if (isset($ticket))
    <div class="modal fade" id="modalReject" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('approvals.reject', $ticket->id) }}" method="POST" class="w-100">
                @csrf @method('PUT')
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Tolak Order</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Alasan Penolakan</label>
                            <textarea name="reason" class="form-control" rows="3" required placeholder="Wajib diisi..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Tolak Sekarang</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- CSS KHUSUS PRINT & MOBILE --}}
<style>
    /* Styling Mobile agar tombol full width di layar kecil */
    @media (max-width: 576px) {
        .btn-sm-mobile { width: 100%; margin-bottom: 5px; }
        .d-flex.gap-2 { flex-direction: column; }
    }

    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0; box-shadow: none !important; }
        .d-print-none { display: none !important; }
        /* Saat print, paksa gunakan tampilan tabel (desktop) agar rapi di kertas */
        .d-md-none { display: none !important; }
        .d-none.d-md-block { display: table !important; width: 100%; }
        .card { border: none !important; }
        .badge { border: 1px solid #000; color: #000 !important; background: none !important; }
    }
</style>
@endsection
