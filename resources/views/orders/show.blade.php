@extends('layouts.app')

{{-- 1. JUDUL HALAMAN --}}
@section('title', 'Detail Order #' . $order->invoice_number)

@section('content')

    <div class="d-flex justify-content-between align-items-center">
        {{-- LOGIKA TOMBOL KEMBALI PINTAR --}}
        @php
            // Default kembali ke Riwayat Order
            $backRoute = route('orders.index');
            $backLabel = 'Kembali ke Riwayat';

            // Jika ada sinyal dari approval, ganti arahnya
            if (request('source') == 'approval') {
                $backRoute = route('approvals.transaksi');
                $backLabel = 'Kembali ke Approval';
            }
        @endphp

        <a href="{{ $backRoute }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> {{ $backLabel }}
        </a>
        {{-- Tombol Cetak (Disembunyikan untuk Sales) --}}
        @if (Auth::user()->role !== 'sales')
            <button onclick="window.print()" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-printer me-1"></i> Cetak Invoice
            </button>
        @endif
    </div>

    </div>
    {{-- B. BANNER STATUS & AKSI (LOGIKA PENGIRIMAN) --}}

    {{-- 1. Jika Status SHIPPED (Sedang Dikirim) -> Munculkan Tombol Konfirmasi --}}
    @if ($order->status == 'shipped' || $order->status == 'processed')
        <div
            class="alert alert-info shadow-sm border-0 d-flex flex-column flex-md-row align-items-center justify-content-between p-4 mb-4">
            <div class="d-flex align-items-center mb-3 mb-md-0">
                <div class="bg-white p-3 rounded-circle text-info me-3 shadow-sm">
                    <i class="bi bi-truck fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Paket Sedang Dikirim</h5>
                    <p class="mb-0 text-muted small">Surat jalan sudah diterbitkan Kasir. Menunggu barang sampai di lokasi.
                    </p>
                </div>
            </div>
            @if (in_array(Auth::user()->role, ['sales_field', 'sales_store', 'manager_operasional']))
                <form action="{{ route('orders.confirmArrival', $order->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm"
                        onclick="return confirm('Apakah Anda yakin barang fisik sudah diterima dengan baik oleh Customer?')">
                        <i class="bi bi-check-circle-fill me-2"></i> Konfirmasi Barang Diterima
                    </button>
                </form>
            @endif
        </div>

        {{-- 2. Jika Status DELIVERED (Diterima) -> Info Menunggu Pelunasan --}}
    @elseif ($order->status == 'delivered')
        <div class="alert alert-success shadow-sm border-0 d-flex align-items-center p-4 mb-4">
            <div class="bg-white p-3 rounded-circle text-success me-3 shadow-sm">
                <i class="bi bi-box-seam-fill fs-3"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1">Barang Sudah Diterima</h5>
                @if ($order->payment_status == 'paid')
                    <p class="mb-0 text-muted small">Transaksi selesai. Terima kasih.</p>
                @else
                    <p class="mb-0 text-dark small">Barang sudah sampai, namun status pembayaran belum
                        <strong>LUNAS</strong>. Order akan selesai otomatis setelah pelunasan.
                    </p>
                @endif
            </div>
        </div>
    @endif


    {{-- C. KARTU INVOICE (AREA PRINT) --}}
    <div class="card shadow border-0" id="printArea">
        <div class="card-body p-4">
            {{-- HEADER ORDER --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <h4 class="text-primary fw-bold mb-1">SALES ORDER</h4>
                    <div class="fs-5 text-dark fw-bold">{{ $order->invoice_number }}</div>
                    <div class="text-muted small mb-2">Tgl: {{ $order->created_at->format('d F Y') }}</div>

                    {{-- STATUS BADGES --}}
                    <div class="mb-3">
                        @if ($order->status == 'pending_approval')
                            <span class="badge bg-warning text-dark">MENUNGGU APPROVAL</span>
                        @elseif($order->status == 'approved')
                            <span class="badge bg-info text-dark">DISETUJUI</span>
                        @elseif($order->status == 'shipped')
                            <span class="badge bg-primary">DIKIRIM</span>
                        @elseif($order->status == 'completed')
                            <span class="badge bg-success">SELESAI</span>
                        @elseif($order->status == 'rejected')
                            <span class="badge bg-danger">DITOLAK</span>
                        @endif

                        @if ($order->payment_status == 'paid')
                            <span class="badge bg-success ms-1">LUNAS</span>
                        @else
                            <span class="badge bg-danger ms-1">BELUM LUNAS</span>
                        @endif
                    </div>

                    {{-- INFO PEMBAYARAN (TOP/CASH) - SUDAH DIBERSIHKAN --}}
                    <div class="alert alert-light border shadow-sm p-3 d-inline-block" style="min-width: 300px;">
                        @if ($order->payment_type == 'kredit' || $order->payment_type == 'top')
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge bg-primary me-2">TOP / KREDIT</span>
                                @if ($order->customer->top_days)
                                    <span class="fw-bold text-dark">{{ $order->customer->top_days }} HARI</span>
                                @endif
                            </div>
                            <div class="small text-muted">
                                Jatuh Tempo: <strong
                                    class="text-danger">{{ \Carbon\Carbon::parse($order->due_date)->format('d F Y') }}</strong>
                            </div>
                        @else
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">CASH / TUNAI</span>
                                <span class="small text-muted">Pembayaran Tunai</span>
                            </div>
                        @endif

                        {{-- NOTE: Bagian Alert "Bukti PO belum diupload" SUDAH DIHAPUS dari sini --}}
                    </div>
                </div>

                {{-- INFO PERUSAHAAN (KANAN) --}}
                <div class="col-md-6 text-md-end">
                    <h5 class="fw-bold text-dark">Bintang Interior & Keramik</h5>
                    <div class="text-muted small">
                        Jl. Teuku Iskandar, Ceurih, Ulee Kareng<br>
                        Kota Banda Aceh, Indonesia<br>
                        Telp: 0812-3456-7890
                    </div>
                </div>
            </div>

            <hr class="my-4">

            {{-- INFO CUSTOMER & SALES --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <small class="text-uppercase text-muted fw-bold ls-1">Kepada Yth:</small>
                    <h5 class="fw-bold mt-1 mb-1">{{ $order->customer->name }}</h5>
                    <div class="text-muted small">
                        {{ $order->customer->address ?? '-' }}<br>
                        Telp: {{ $order->customer->phone ?? '-' }}
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-uppercase text-muted fw-bold ls-1">Sales:</small>
                    <div class="fw-bold mt-1">{{ $order->user->name ?? '-' }}</div>
                    <div class="text-muted small">{{ $order->user->email ?? '-' }}</div>
                </div>
            </div>

            {{-- TABEL ITEM --}}
            <div class="table-responsive">
                <table class="table table-bordered mb-4">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="5%">#</th>
                            <th>Nama Produk</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $index => $item)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    {{ $item->product->name }}
                                    @if ($item->product->category)
                                        <br><small class="text-muted">{{ $item->product->category }}</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">Rp
                                    {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold text-uppercase">Total Tagihan</td>
                            <td class="text-end fw-bold fs-5 bg-light text-primary">
                                Rp {{ number_format($order->total_price, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- CATATAN & FOOTER --}}
            @if ($order->notes)
                <div class="alert alert-light border">
                    <strong>Catatan Order:</strong> {{ $order->notes }}
                </div>
            @endif

            <div class="text-center mt-5 text-muted small">
                <p>Terima kasih telah berbelanja di Bintang Interior & Keramik.</p>
            </div>

        </div>
    </div>

    {{-- CSS UNTUK PRINT --}}
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
                border: none !important;
                box-shadow: none !important;
            }

            .btn,
            .alert,
            .d-print-none {
                display: none !important;
                /* Sembunyikan tombol, alert, dan elemen d-print-none saat print */
            }

            .bg-light {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
@endsection
