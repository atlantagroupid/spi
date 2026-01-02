@extends('layouts.app')

@section('title', 'Riwayat Pesanan')

@section('content')
<div class="container-fluid px-0 px-md-3">

    {{-- HEADER & TOMBOL (RESPONSIF) --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 mb-md-4 gap-3">

        {{-- Judul (Hidden di HP karena sudah ada di Navbar) --}}
        <div class="d-none d-md-block">
            <h4 class="fw-bold text-dark mb-1">Riwayat Transaksi</h4>
            <p class="text-muted small mb-0">Kelola dan pantau semua pesanan yang masuk.</p>
        </div>

        {{-- Tombol Aksi (Full width di HP) --}}
        <div class="d-flex gap-2">
            {{-- Tombol PDF --}}
            <a href="{{ route('orders.export_list_pdf', request()->query()) }}"
               class="btn btn-outline-danger shadow-sm flex-fill flex-md-grow-0">
                <i class="bi bi-file-earmark-pdf me-1"></i> <span class="d-none d-sm-inline">Export PDF</span><span class="d-sm-none">PDF</span>
            </a>

            {{-- Tombol Buat Order --}}
            @if(in_array(Auth::user()->role, ['sales_field', 'sales_store', 'manager_operasional', 'manager_bisnis']))
                <a href="{{ route('orders.create') }}" class="btn btn-primary shadow-sm flex-fill flex-md-grow-0">
                    <i class="bi bi-plus-lg me-1"></i> <span class="d-none d-sm-inline">Buat Order Baru</span><span class="d-sm-none">Order Baru</span>
                </a>
            @endif
        </div>
    </div>

    {{-- CARD FILTER (COLLAPSIBLE) --}}
    <div class="card border-0 shadow-sm mb-3 mb-md-4 rounded-3">
        <div class="card-header bg-white border-bottom-0 py-3" data-bs-toggle="collapse" href="#filterCollapse" role="button" aria-expanded="false" style="cursor: pointer;">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-funnel me-2"></i>Filter Pencarian</h6>
                <i class="bi bi-chevron-down text-muted"></i>
            </div>
        </div>
        <div class="collapse" id="filterCollapse">
            <div class="card-body bg-light border-top">
                <form action="{{ route('orders.index') }}" method="GET">
                    <div class="row g-2 g-md-3">

                        {{-- 1. FILTER NAMA TOKO --}}
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold text-muted">Nama Toko</label>
                            <input type="text" name="store_name" class="form-control form-control-sm"
                                   value="{{ request('store_name') }}" placeholder="Cari nama toko...">
                        </div>

                        {{-- 2. FILTER SALES --}}
                        @if(!in_array(Auth::user()->role, ['sales_field', 'sales_store']))
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold text-muted">Salesman</label>
                            <select name="sales_id" class="form-select form-select-sm">
                                <option value="">-- Semua Sales --</option>
                                @foreach($salesList as $sales)
                                    <option value="{{ $sales->id }}" {{ request('sales_id') == $sales->id ? 'selected' : '' }}>
                                        {{ $sales->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- 3. FILTER TANGGAL --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-bold text-muted">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control form-control-sm"
                                   value="{{ request('start_date') }}">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-bold text-muted">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control form-control-sm"
                                   value="{{ request('end_date') }}">
                        </div>

                        {{-- 4. FILTER STATUS & TOMBOL --}}
                        <div class="col-12 mt-3">
                             <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2">
                                <div class="w-100 w-md-25">
                                    <label class="form-label small fw-bold text-muted">Status Order</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="all">Semua Status</option>
                                        <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>Menunggu Approval</option>
                                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                                        <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Sedang Dikirim</option>
                                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Sampai (Delivered)</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai (Completed)</option>
                                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                                    </select>
                                </div>

                                <div class="d-flex gap-2 w-100 w-md-auto">
                                     <a href="{{ route('orders.index') }}" class="btn btn-sm btn-light border text-danger flex-fill flex-md-grow-0">
                                         <i class="bi bi-arrow-counterclockwise"></i> Reset
                                     </a>
                                     <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold flex-fill flex-md-grow-0">
                                         <i class="bi bi-search me-1"></i> Terapkan
                                     </button>
                                </div>
                             </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- TAMPILAN MOBILE: CARD LIST (D-BLOCK D-MD-NONE) --}}
    {{-- ======================================================= --}}
    <div class="d-block d-md-none">
        @forelse($orders as $order)
            <div class="card border-0 shadow-sm mb-3 rounded-3 position-relative overflow-hidden">
                {{-- Border Kiri Warna Warni berdasarkan Status --}}
                @php
                    $statusColor = match($order->status) {
                        'pending_approval' => 'warning',
                        'approved' => 'info',
                        'shipped', 'delivered' => 'primary',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'secondary'
                    };
                @endphp
                <div class="position-absolute top-0 bottom-0 start-0 bg-{{ $statusColor }}" style="width: 5px;"></div>

                <div class="card-body p-3 ps-4"> {{-- ps-4 memberi jarak dari border warna --}}
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-light text-dark border mb-1">{{ $order->invoice_number }}</span>
                            <h6 class="fw-bold text-dark mb-0">{{ $order->customer->name }}</h6>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block" style="font-size: 0.65rem;">
                                {{ date('d M Y', strtotime($order->created_at)) }}
                            </small>
                            <small class="text-{{ $statusColor }} fw-bold" style="font-size: 0.7rem;">
                                {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                            </small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-end mt-3">
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Total Transaksi</small>
                            <h5 class="fw-bold text-primary mb-0">Rp {{ number_format($order->total_price, 0, ',', '.') }}</h5>

                            {{-- Badge Lunas Kecil --}}
                            @if($order->payment_status == 'paid')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success mt-1" style="font-size: 0.6rem;">LUNAS</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger mt-1" style="font-size: 0.6rem;">BELUM LUNAS</span>
                            @endif
                        </div>
                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                            Detail <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 opacity-25"></i>
                <p class="mt-2">Belum ada data pesanan.</p>
            </div>
        @endforelse

        {{-- Pagination Mobile --}}
        <div class="mt-3">
            {{ $orders->links() }}
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- TAMPILAN DESKTOP: TABEL (D-NONE D-MD-BLOCK) --}}
    {{-- ======================================================= --}}
    <div class="card border-0 shadow-sm d-none d-md-block rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-hover">
                <thead class="bg-light text-secondary">
                    <tr>
                        <th class="ps-4 py-3">Invoice & Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Status Order</th>
                        <th>Pembayaran</th>
                        <th class="text-end pe-4">Total & Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $order->invoice_number }}</div>
                                <small class="text-muted"><i class="bi bi-calendar-event me-1"></i> {{ date('d M Y, H:i', strtotime($order->created_at)) }}</small>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $order->customer->name }}</div>
                                <small class="text-secondary" style="font-size: 0.8rem;">Sales: {{ $order->user->name }}</small>
                            </td>
                            <td>
                                @php
                                    $statusClass = match($order->status) {
                                        'pending_approval' => 'bg-warning text-dark',
                                        'approved' => 'bg-info text-dark',
                                        'shipped' => 'bg-primary',
                                        'delivered' => 'bg-primary',
                                        'completed' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    $statusLabel = match($order->status) {
                                        'pending_approval' => 'Menunggu Approval',
                                        'approved' => 'Disetujui',
                                        'shipped' => 'Sedang Dikirim',
                                        'delivered' => 'Sampai',
                                        'completed' => 'Selesai',
                                        'rejected' => 'Ditolak',
                                        default => $order->status
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }} px-3 py-2 rounded-pill fw-normal">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td>
                                @if($order->payment_status == 'paid')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2">LUNAS</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2">BELUM LUNAS</span>
                                @endif
                                <div class="small text-muted mt-1">{{ strtoupper($order->payment_type) }}</div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="fw-bold text-dark fs-6 mb-2">Rp {{ number_format($order->total_price, 0, ',', '.') }}</div>
                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                                    Detail <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 opacity-25"></i>
                                <p class="mt-2">Belum ada data pesanan sesuai filter ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION DESKTOP --}}
        <div class="card-footer bg-white py-3">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
