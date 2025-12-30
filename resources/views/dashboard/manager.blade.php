@extends('layouts.app')

@section('title', 'Dashboard Manager')

@section('content')
    {{-- Logic Role Helper --}}
    @php
        $userRole = Auth::user()->role;
        // Role Groups
        $isManager = in_array($userRole, ['manager_operasional', 'manager_bisnis']);
        $hasApprovalAccess = in_array($userRole, ['manager_operasional', 'manager_bisnis', 'kepala_gudang']);
        $isWarehouseOrPurchase = in_array($userRole, ['kepala_gudang', 'admin_gudang', 'purchase']);
    @endphp

    <div class="row">
        <div class="col-md-12 mb-4">

            {{-- HEADER DASHBOARD --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark">Dashboard Overview</h4>
                    <p class="text-muted">Halo {{ Auth::user()->name }}, berikut ringkasan operasional hari ini.</p>
                </div>
                {{-- Tanggal Hari Ini --}}
                <div class="d-none d-md-block">
                    <span class="badge bg-white text-dark border px-3 py-2 shadow-sm">
                        <i class="bi bi-calendar-event me-2"></i> {{ \Carbon\Carbon::now()->format('d M Y') }}
                    </span>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- WIDGET UTAMA (STOK & ASET) --}}
            {{-- ============================================================ --}}
            <div class="row g-3 mb-4">

                {{-- 1. Total Stok --}}
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100 bg-primary text-white position-relative overflow-hidden">
                        <div class="card-body d-flex align-items-center justify-content-between position-relative z-1">
                            <div>
                                <h6 class="text-uppercase mb-1 opacity-75 small fw-bold">Total Stok Fisik</h6>
                                <h2 class="mb-0 fw-bold">
                                    {{ number_format($warehouseStats['total_items'] ?? 0, 0, ',', '.') }}
                                </h2>
                                <small class="opacity-75">Item Barang</small>
                            </div>
                            {{-- Ikon Utama --}}
                            <i class="bi bi-box-seam fs-1 bg-white bg-opacity-25 p-3 rounded-circle"></i>
                        </div>
                    </div>
                </div>

                {{-- 2. Total Nilai Aset --}}
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100 bg-success text-white position-relative overflow-hidden">
                        <div class="card-body d-flex align-items-center justify-content-between position-relative z-1">
                            <div>
                                <h6 class="text-uppercase mb-1 opacity-75 small fw-bold">Nilai Aset Gudang</h6>
                                <h2 class="mb-0 fw-bold">Rp
                                    {{ number_format($warehouseStats['total_asset'] ?? 0, 0, ',', '.') }}
                                </h2>
                                <small class="opacity-75">Estimasi Valuasi</small>
                            </div>
                            <i class="bi bi-cash-stack fs-1 bg-white bg-opacity-25 p-3 rounded-circle"></i>
                        </div>
                    </div>
                </div>

                {{-- 3. Widget Kondisional (Approval / Restock) --}}
                <div class="col-md-4">
                    @if ($hasApprovalAccess)
                        {{-- LOGIC: Determine Link Based on Role --}}
                        @php
                            $role = Auth::user()->role;
                            $approvalLink = '#'; // Default fallback

                            if ($role == 'manager_operasional') {
                                $approvalLink = route('approvals.index'); // All Approvals
                            } elseif ($role == 'kepala_gudang') {
                                $approvalLink = route('approvals.products'); // Only Product Approvals
                            } elseif ($role == 'manager_bisnis') {
                                $approvalLink = route('approvals.transaksi'); // Only Transaction Approvals
                            }
                        @endphp

                        {{-- Manager & Kepala Gudang: Lihat Approval --}}
                        <div class="card shadow-sm border-0 border-start border-4 border-danger h-100">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted small fw-bold text-uppercase mb-1">Menunggu Approval</h6>
                                    <h2 class="fw-bold text-danger mb-0">{{ $pendingApprovalCount ?? 0 }}</h2>
                                    <small class="text-muted">Permintaan Pending</small>
                                </div>
                                <div class="position-relative">
                                    <i
                                        class="bi bi-shield-exclamation fs-2 text-danger bg-danger bg-opacity-10 p-3 rounded-circle"></i>
                                    @if (($pendingApprovalCount ?? 0) > 0)
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle"></span>
                                    @endif
                                </div>
                            </div>
                            {{-- USE THE DYNAMIC LINK HERE --}}
                            <a href="{{ $approvalLink }}" class="stretched-link"></a>
                        </div>
                    @else
                        {{-- Admin Gudang & Purchase: Lihat Restock --}}
                        <div class="card shadow-sm border-0 h-100 bg-warning text-dark">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-uppercase mb-1 opacity-75 small fw-bold">Perlu Restock</h6>
                                    <h2 class="mb-0 fw-bold text-danger">{{ $lowStockCount ?? 0 }}</h2>
                                    <small class="opacity-75">Item Stok Menipis</small>
                                </div>
                                <i
                                    class="bi bi-exclamation-triangle-fill fs-2 text-danger bg-white bg-opacity-25 p-3 rounded-circle"></i>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- STATISTIK KEUANGAN (Disembunyikan dari Gudang/Purchase) --}}
            {{-- ============================================================ --}}
            @if (!$isWarehouseOrPurchase)
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <h6 class="text-muted small fw-bold text-uppercase mb-2">Total Penjualan</h6>
                                <h3 class="fw-bold text-dark mb-0">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <h6 class="text-muted small fw-bold text-uppercase mb-2">Uang Diterima (Cash)</h6>
                                <h3 class="fw-bold text-success mb-0">Rp
                                    {{ number_format($cashReceived ?? 0, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <h6 class="text-muted small fw-bold text-uppercase mb-2">Sisa Piutang</h6>
                                <h3 class="fw-bold text-danger mb-0">Rp
                                    {{ number_format($totalReceivable ?? 0, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ============================================================ --}}
            {{-- GRAFIK & LEADERBOARD --}}
            {{-- ============================================================ --}}

            <div class="row">
                {{-- Grafik Penjualan (Kiri - Lebar) --}}
                <div class="col-lg-{{ $isManager ? '7' : '12' }} mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Tren Penjualan
                                Bulanan ({{ date('Y') }})</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" style="max-height: 350px;"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Leaderboard & Efektivitas (Hanya Manager) --}}
                @if ($isManager)
                    <div class="col-lg-5 mb-4">
                        {{-- 1. Chart Leaderboard --}}
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-trophy me-2 text-warning"></i>Top Sales Bulan Ini
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="leaderboardChart" style="max-height: 250px;"></canvas>
                            </div>
                        </div>

                        {{-- 2. Tabel Ringkas --}}
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="mb-0 fw-bold">Detail Performa</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 text-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Nama</th>
                                            <th class="text-center">Visit</th>
                                            <th class="text-end pe-3">Omset</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($topSales ?? [] as $s)
                                            <tr>
                                                <td class="ps-3 fw-bold">{{ $s->name }}</td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-info text-dark rounded-pill">{{ $s->visits_count }}</span>
                                                </td>
                                                <td class="text-end pe-3 fw-bold text-success">
                                                    Rp {{ number_format($s->orders_sum_total_price ?? 0, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-3 text-muted">Belum ada data.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- SCRIPT CHART JS (Hanya diload untuk Manager/Admin) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Setup Format Rupiah untuk Chart
            const formatRupiah = (val) => {
                return 'Rp ' + new Intl.NumberFormat('id-ID', {
                    notation: "compact"
                }).format(val);
            };

            // 1. Chart Penjualan
            const ctx = document.getElementById('salesChart');
            if (ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'line', // Ganti jadi line biar lebih cantik untuk tren
                    data: {
                        labels: @json($chartLabels ?? []),
                        datasets: [{
                            label: 'Total Omset',
                            data: @json($chartData ?? []),
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3 // Garis lengkung
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: (c) => formatRupiah(c.raw)
                                }
                            }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: (val) => formatRupiah(val)
                                }
                            }
                        }
                    }
                });
            }

            // 2. Chart Leaderboard
            const ctxL = document.getElementById('leaderboardChart');
            if (ctxL) {
                new Chart(ctxL.getContext('2d'), {
                    type: 'doughnut', // Ganti jadi donat biar variatif
                    data: {
                        labels: @json($salesNames ?? []),
                        datasets: [{
                            data: @json($salesRevenue ?? []),
                            backgroundColor: [
                                '#198754', '#ffc107', '#0dcaf0', '#d63384', '#6c757d'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'right'
                            },
                            tooltip: {
                                callbacks: {
                                    label: (c) => ' ' + formatRupiah(c.raw)
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endsection
