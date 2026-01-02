@extends('layouts.app')

@section('title', 'Arsip Bon Lunas')

@section('content')
<div class="container-fluid px-0 px-md-3">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-gray-800 mb-1">Arsip Lunas</h4>
            <p class="text-muted small mb-0 d-none d-md-block">Riwayat invoice yang sudah selesai dibayar.</p>
        </div>
        <a href="{{ route('receivables.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="card shadow-sm border-0 border-start border-4 border-success rounded-3 overflow-hidden">

        {{-- DESKTOP TABLE --}}
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small">
                    <tr>
                        <th class="ps-4">Invoice</th>
                        <th>Customer</th>
                        <th>Total Transaksi</th>
                        <th>Tanggal Lunas</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $inv)
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold text-success">{{ $inv->invoice_number }}</span><br>
                            <small class="text-muted">{{ $inv->created_at->format('d M Y') }}</small>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $inv->customer->name }}</div>
                            <small class="text-muted">Sales: {{ $inv->user->name }}</small>
                        </td>
                        <td><span class="fw-bold">Rp {{ number_format($inv->total_price, 0, ',', '.') }}</span></td>
                        <td>{{ $inv->updated_at->format('d M Y H:i') }}</td>
                        <td class="text-center"><span class="badge bg-success">LUNAS</span></td>
                        <td class="text-end pe-4">
                            <a href="{{ route('orders.show', $inv->id) }}" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i> Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- MOBILE CARD LIST --}}
        <div class="d-md-none">
            @forelse ($invoices as $inv)
                <div class="p-3 border-bottom position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success mb-1">{{ $inv->invoice_number }}</span>
                            <h6 class="fw-bold text-dark mb-0">{{ $inv->customer->name }}</h6>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success rounded-pill" style="font-size: 0.65rem;">LUNAS</span>
                            <small class="d-block text-muted mt-1" style="font-size: 0.65rem;">{{ $inv->updated_at->format('d/m/y') }}</small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <h5 class="fw-bold text-dark mb-0">Rp {{ number_format($inv->total_price, 0, ',', '.') }}</h5>
                        <a href="{{ route('orders.show', $inv->id) }}" class="btn btn-sm btn-light border rounded-pill px-3">Detail</a>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">Belum ada data.</div>
            @endforelse
        </div>

        <div class="p-3">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection
