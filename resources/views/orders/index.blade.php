@extends('layouts.app')

@section('title', 'Riwayat Pesanan')

@section('content')
<div class="container-fluid">

    {{-- HEADER & TOMBOL BUAT --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Riwayat Transaksi</h4>
            <p class="text-muted small mb-0">Kelola semua pesanan yang masuk di sini.</p>
        </div>
        @if(in_array(Auth::user()->role, ['sales', 'sales_field', 'sales_store', 'manager_operasional']))
            <a href="{{ route('orders.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Buat Order Baru
            </a>
        @endif
    </div>

    {{-- FILTER TABS (STATUS) --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-2">
            <ul class="nav nav-pills nav-fill gap-2">
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == '' ? 'active bg-dark' : 'text-muted' }}" href="{{ route('orders.index') }}">
                        Semua
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'pending_approval' ? 'active bg-warning text-dark fw-bold' : 'text-muted' }}"
                       href="{{ route('orders.index', ['status' => 'pending_approval']) }}">
                       <i class="bi bi-hourglass-split me-1"></i> Menunggu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'approved' ? 'active bg-info text-dark fw-bold' : 'text-muted' }}"
                       href="{{ route('orders.index', ['status' => 'approved']) }}">
                       <i class="bi bi-check-circle me-1"></i> Disetujui
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'shipped' ? 'active bg-primary' : 'text-muted' }}"
                       href="{{ route('orders.index', ['status' => 'shipped']) }}">
                       <i class="bi bi-truck me-1"></i> Dikirim
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'completed' ? 'active bg-success' : 'text-muted' }}"
                       href="{{ route('orders.index', ['status' => 'completed']) }}">
                       <i class="bi bi-check-all me-1"></i> Selesai
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- TABEL MODERN --}}
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
                                <p class="mt-2">Belum ada data pesanan.</p>
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
