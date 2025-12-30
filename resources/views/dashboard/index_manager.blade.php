@extends('layouts.app')

@section('title', 'Dashboard Manager')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark">Dashboard Manager</h4>
            <p class="text-muted mb-0">Overview kinerja perusahaan hari ini.</p>
        </div>
    </div>

    {{-- 1. STATISTIK UTAMA --}}
    <div class="row g-3 mb-4">
        {{-- Total Stok --}}
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75 small fw-bold">Stok Fisik</h6>
                    <h3 class="fw-bold">{{ number_format($warehouseStats['total_items'] ?? 0, 0, ',', '.') }}</h3>
                    <small><i class="bi bi-box-seam me-1"></i> Total Item</small>
                </div>
            </div>
        </div>
        {{-- Nilai Aset --}}
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 bg-success text-white">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75 small fw-bold">Nilai Aset</h6>
                    <h4 class="fw-bold">Rp {{ number_format($warehouseAsset, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        {{-- Pending Approval --}}
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 border-start border-4 border-danger">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small fw-bold">Perlu Approval</h6>
                    <h3 class="fw-bold text-danger">{{ $pendingApprovalCount }}</h3>

                    {{-- LOGIKA LINK DINAMIS --}}
                    @php
                        $role = Auth::user()->role;
                        $targetRoute = '#';

                        if ($role == 'manager_bisnis') {
                            // Manager Bisnis fokus utamanya Transaksi (Order/Piutang)
                            $targetRoute = route('approvals.transaksi');
                        } elseif ($role == 'manager_operasional') {
                            // Manager Ops
                            $targetRoute = route('approvals.index');
                        }
                    @endphp

                    <a href="{{ $targetRoute }}" class="stretched-link small text-danger fw-bold">Lihat Detail &rarr;</a>
                </div>
            </div>
        </div>
         {{-- Total Piutang --}}
         <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small fw-bold">Total Piutang</h6>
                    <h4 class="fw-bold text-danger">Rp {{ number_format($totalReceivable, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- 2. GRAFIK OMSET TAHUNAN --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Tren Penjualan ({{ date('Y') }})</h5>
                </div>
                <div class="card-body">
                    <canvas id="managerChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>

        {{-- 3. LEADERBOARD SALES --}}
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Top Sales Bulan Ini</h5>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($topSales as $index => $sales)
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-light text-dark rounded-circle me-3 border" style="width: 30px; height: 30px; display:flex; align-items:center; justify-content:center;">{{ $index + 1 }}</span>
                                <div>
                                    <h6 class="fw-bold mb-0">{{ $sales->name }}</h6>
                                    <small class="text-muted">Target: {{ $sales->sales_target ? round(($sales->orders_sum_total_price / $sales->sales_target)*100) : 0 }}%</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="d-block fw-bold text-success">Rp {{ number_format($sales->orders_sum_total_price, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted">Belum ada data penjualan.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('managerChart');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
                datasets: [{
                    label: 'Total Omset',
                    data: @json($chartData),
                    backgroundColor: '#4e73df',
                    borderRadius: 5
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    });
</script>
@endsection
