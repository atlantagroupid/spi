{{-- ============================================================ --}}
{{-- 2. TAMPILAN NON-SALES (MANAGER, GUDANG, ADMIN) --}}
{{-- ============================================================ --}}
{{-- WIDGET ATAS (STOK / APPROVAL) --}}
<div class="row g-3 mb-4">
    {{-- Total Stok --}}
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100 bg-primary text-white">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase mb-1 opacity-75 small fw-bold">Total Stok Fisik</h6>
                    <h2 class="mb-0 fw-bold">
                        {{ number_format($warehouseStats['total_items'] ?? 0, 0, ',', '.') }}</h2>
                </div>
                <i class="bi bi-box-seam fs-2 bg-white bg-opacity-25 p-3 rounded-circle"></i>
            </div>
        </div>
    </div>

    {{-- Total Aset --}}
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100 bg-success text-white">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase mb-1 opacity-75 small fw-bold">Nilai Aset</h6>
                    <h2 class="mb-0 fw-bold">Rp
                        {{ number_format($warehouseStats['total_asset'] ?? 0, 0, ',', '.') }}</h2>
                </div>
                <i class="bi bi-cash-stack fs-2 bg-white bg-opacity-25 p-3 rounded-circle"></i>
            </div>
        </div>
    </div>

    {{-- Approval / Low Stock (Logic Kondisional) --}}
    <div class="col-md-4">
        @if ($hasApprovalAccess)
            {{-- Manager & Kepala Gudang: Lihat Approval --}}
            <div class="card shadow-sm border-0 border-start border-4 border-danger h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted small fw-bold text-uppercase mb-1">Menunggu Approval</h6>
                        <h2 class="fw-bold text-danger mb-0">{{ $pendingApprovalCount ?? 0 }}</h2>
                        <small class="text-muted">Permintaan Data</small>
                    </div>
                    <i
                        class="bi bi-shield-exclamation fs-2 text-danger bg-danger bg-opacity-10 p-3 rounded-circle"></i>
                </div>
                <a href="{{ route('approvals.index') }}" class="stretched-link"></a>
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

{{-- STATISTIK KEUANGAN (Disembunyikan dari Gudang/Purchase) --}}
@if (!$isWarehouseOrPurchase)
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold text-uppercase">Total Penjualan</h6>
                    <h3 class="fw-bold text-dark">Rp
                        {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold text-uppercase">Uang Diterima</h6>
                    <h3 class="fw-bold text-success">Rp
                        {{ number_format($cashReceived ?? 0, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold text-uppercase">Sisa Piutang</h6>
                    <h3 class="fw-bold text-danger">Rp
                        {{ number_format($totalReceivable ?? 0, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- GRAFIK PENJUALAN BULANAN --}}
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Grafik Penjualan Bulanan ({{ date('Y') }})</h5>
        </div>
        <div class="card-body"><canvas id="salesChart" style="max-height: 400px;"></canvas></div>
    </div>

    {{-- LEADERBOARD KHUSUS MANAGER --}}
    @if ($isManager)
        <div class="row mt-4">
            <div class="col-lg-7 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Top Sales Bulan Ini</h5>
                    </div>
                    <div class="card-body"><canvas id="leaderboardChart"
                            style="max-height: 300px;"></canvas></div>
                </div>
            </div>
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Efektivitas Sales</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
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
                                            <td class="text-center"><span
                                                    class="badge bg-info text-dark">{{ $s->visits_count }}</span>
                                            </td>
                                            <td class="text-end pe-3 text-success fw-bold">Rp
                                                {{ number_format($s->orders_sum_total_price ?? 0, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">
                                                Belum
                                                ada data.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    {{-- SCRIPT CHART UNTUK ADMIN/MANAGER --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Chart Penjualan
            const ctx = document.getElementById('salesChart');
            if (ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($chartLabels ?? []),
                        datasets: [{
                            label: 'Omset',
                            data: @json($chartData ?? []),
                            backgroundColor: 'rgba(54, 162, 235, 0.6)'
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(val) {
                                        return 'Rp ' + new Intl.NumberFormat('id-ID', {
                                            notation: "compact"
                                        }).format(val);
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // 2. Chart Leaderboard (Manager Only)
            const ctxL = document.getElementById('leaderboardChart');
            if (ctxL) {
                new Chart(ctxL.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($salesNames ?? []),
                        datasets: [{
                            label: 'Omset',
                            data: @json($salesRevenue ?? []),
                            backgroundColor: ['rgba(255, 206, 86, 0.7)',
                                'rgba(192, 192, 192, 0.7)',
                                'rgba(205, 127, 50, 0.7)', 'rgba(54, 162, 235, 0.5)'
                            ]
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    callback: function(val) {
                                        return 'Rp ' + new Intl.NumberFormat('id-ID', {
                                            notation: "compact"
                                        }).format(val);
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endif

{{-- TAMPILAN KHUSUS KEPALA GUDANG (Barang Masuk/Keluar) --}}
@if (Auth::user()->role == 'kepala_gudang')
    @include('dashboard._kepala_gudang')
@endif
