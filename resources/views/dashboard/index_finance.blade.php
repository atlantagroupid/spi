@extends('layouts.app')

@section('title', 'Dashboard Finance')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark">Dashboard Keuangan</h4>
            <p class="text-muted mb-0">Halo {{ $user->name }}, berikut ringkasan kas hari ini.</p>
        </div>
        <div class="text-end">
             <span class="badge bg-white border text-dark px-3 py-2 rounded-pill shadow-sm">
                <i class="bi bi-calendar-check me-1"></i> {{ date('d M Y') }}
            </span>
        </div>
    </div>

    {{-- STATISTIK KEUANGAN --}}
    <div class="row g-3 mb-4">
        {{-- 1. Uang Masuk Hari Ini --}}
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100 bg-success text-white">
                <div class="card-body d-flex align-items-center justify-content-between px-4">
                    <div>
                        <h6 class="text-uppercase opacity-75 fw-bold">Uang Masuk (Hari Ini)</h6>
                        <h2 class="fw-bold display-6 mb-0">Rp {{ number_format($cashToday, 0, ',', '.') }}</h2>
                        <small class="opacity-75"><i class="bi bi-graph-up-arrow me-1"></i> Cashflow Harian</small>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3">
                        <i class="bi bi-wallet2 fs-1"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Total Piutang --}}
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100 border-start border-4 border-warning">
                <div class="card-body d-flex align-items-center justify-content-between px-4">
                    <div>
                        <h6 class="text-uppercase text-muted fw-bold">Total Piutang Usaha</h6>
                        <h2 class="fw-bold text-warning display-6 mb-0">Rp {{ number_format($totalReceivable, 0, ',', '.') }}</h2>
                        <a href="{{ route('receivables.index') }}" class="small text-muted text-decoration-none stretched-link">
                            Lihat Daftar Piutang &rarr;
                        </a>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3 text-warning">
                        <i class="bi bi-journal-bookmark-fill fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABEL TRANSAKSI TERBARU --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold"><i class="bi bi-receipt me-2"></i>Pemasukan Terakhir (Verified)</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-hover">
                <thead class="bg-light text-secondary">
                    <tr>
                        <th class="ps-4">Tanggal</th>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Via</th>
                        <th class="text-end pe-4">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $log)
                        <tr>
                            <td class="ps-4">{{ date('d/m/Y H:i', strtotime($log->payment_date)) }}</td>
                            <td class="fw-bold text-primary">{{ $log->order->invoice_number ?? '-' }}</td>
                            <td>{{ $log->order->customer->name ?? 'Guest' }}</td>
                            <td><span class="badge bg-light text-dark border">{{ strtoupper($log->payment_method) }}</span></td>
                            <td class="text-end pe-4 fw-bold text-success">+ Rp {{ number_format($log->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 opacity-25"></i>
                                <p class="mt-2">Belum ada pemasukan hari ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
