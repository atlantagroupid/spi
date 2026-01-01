@extends('layouts.app')

@section('title', 'Riwayat Pesanan')

@section('content')
<div class="container-fluid">

    {{-- HEADER & TOMBOL --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">Riwayat Transaksi</h4>
            <p class="text-muted small mb-0">Kelola dan pantau semua pesanan yang masuk.</p>
        </div>

        <div class="d-flex gap-2">
            {{-- TOMBOL PRINT PDF --}}
            {{-- Mengirim semua query string (filter) saat ini ke route export --}}
            <a href="{{ route('orders.export_list_pdf', request()->query()) }}" class="btn btn-outline-danger shadow-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </a>

            @if(in_array(Auth::user()->role, ['sales_field', 'sales_store', 'manager_operasional', 'manager_bisnis']))
                <a href="{{ route('orders.create') }}" class="btn btn-primary shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Buat Order Baru
                </a>
            @endif
        </div>
    </div>

    {{-- CARD FILTER (COLLAPSIBLE) --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom-0 py-3" data-bs-toggle="collapse" href="#filterCollapse" role="button" aria-expanded="false" style="cursor: pointer;">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-funnel me-2"></i>Filter Pencarian</h6>
                <i class="bi bi-chevron-down text-muted"></i>
            </div>
        </div>
        <div class="collapse" id="filterCollapse">
            <div class="card-body bg-light border-top">
                <form action="{{ route('orders.index') }}" method="GET">
                    <div class="row g-3">

                        {{-- 1. FILTER NAMA TOKO --}}
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Nama Toko</label>
                            <input type="text" name="store_name" class="form-control form-control-sm"
                                   value="{{ request('store_name') }}" placeholder="Cari nama toko...">
                        </div>

                        {{-- 2. FILTER SALES (HANYA MUNCUL UNTUK MANAGER/ADMIN) --}}
                        @if(!in_array(Auth::user()->role, ['sales_field', 'sales_store']))
                        <div class="col-md-3">
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
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control form-control-sm"
                                   value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control form-control-sm"
                                   value="{{ request('end_date') }}">
                        </div>

                        {{-- 4. FILTER STATUS --}}
                        <div class="col-md-12 d-flex justify-content-between align-items-end mt-3">
                             <div class="w-25">
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

                             <div class="d-flex gap-2">
                                 <a href="{{ route('orders.index') }}" class="btn btn-sm btn-light border text-danger">
                                     <i class="bi bi-arrow-counterclockwise"></i> Reset
                                 </a>
                                 <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold">
                                     <i class="bi bi-search me-1"></i> Terapkan Filter
                                 </button>
                             </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- TABEL DATA --}}
    <div class="card border-0 shadow-sm">
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

        {{-- PAGINATION --}}
        <div class="card-footer bg-white py-3">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
