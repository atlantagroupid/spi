@extends('layouts.app')

@section('title', 'Dashboard Sales')

@section('content')
    <div class="container-fluid">

        {{-- HEADER (Sama seperti sebelumnya) --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-primary">Dashboard {{ $isSalesStore ? 'Sales Counter' : 'Sales Lapangan' }}</h4>
                <p class="text-muted mb-0">Halo {{ $user->name }}, semangat kejar omset hari ini!</p>
            </div>
            <div class="text-end">
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                    <i class="bi bi-calendar-event me-1"></i> {{ date('d M Y') }}
                </span>
            </div>
        </div>

        {{-- 1. INFO PLAFON KREDIT (Sama, tetap tampilkan untuk keduanya) --}}
        @if ($limitQuota > 0)
            {{-- ... (Kode Plafon Kredit biarkan sama) ... --}}
            @if ($isCritical)
                <div class="alert alert-danger shadow-sm d-flex align-items-center justify-content-between" role="alert">
                    <div>
                        <h5 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Limit Menipis!
                        </h5>
                        <p class="mb-0 small">
                            Sisa limit: <strong>Rp {{ number_format($remaining, 0, ',', '.') }}</strong>
                            (Terpakai: Rp {{ number_format($usedCredit, 0, ',', '.') }}).
                        </p>
                    </div>
                    <button type="button" class="btn btn-light text-danger fw-bold btn-sm" data-bs-toggle="modal"
                        data-bs-target="#requestLimitModal">
                        <i class="bi bi-arrow-up-circle me-1"></i> Minta Limit
                    </button>
                </div>
            @else
                <div class="card shadow-sm border-0 border-start border-4 border-success mb-4">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-uppercase text-muted fw-bold">Sisa Plafon Kredit</small>
                                <h4 class="fw-bold text-success mb-0">Rp {{ number_format($remaining, 0, ',', '.') }}</h4>
                            </div>
                            <i class="bi bi-wallet2 fs-1 text-gray-300 opacity-25"></i>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        {{-- FITUR KHUSUS BERDASARKAN TIPE SALES --}}

        @if (!$isSalesStore)
            {{-- ================================================= --}}
            {{-- TAMPILAN UNTUK SALES FIELD (LAPANGAN) --}}
            {{-- ================================================= --}}

            {{-- 2. WIDGET TARGET KUNJUNGAN (HARIAN) --}}
            <div class="card shadow-sm border-0 mb-4 bg-primary text-white overflow-hidden">
                <div class="card-body p-4 position-relative">
                    <div class="row align-items-center position-relative z-1">
                        <div class="col-8">
                            <h5 class="fw-bold mb-1">Target Kunjungan Hari Ini</h5>
                            <div class="d-flex align-items-end mb-2">
                                <h1 class="display-4 fw-bold mb-0 me-2">{{ $todayVisits }}</h1>
                                <span class="fs-5 mb-2 opacity-75">/ {{ $visitTarget }} Toko</span>
                            </div>

                            <div class="progress" style="height: 8px; background-color: rgba(255,255,255,0.3);">
                                <div class="progress-bar bg-warning" role="progressbar"
                                    style="width: {{ $visitTarget > 0 ? min($visitPercentage, 100) : 0 }}%"></div>
                            </div>
                            <small class="mt-2 d-block">{{ round($visitPercentage) }}% Tercapai</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-geo-alt-fill opacity-25" style="font-size: 5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- ================================================= --}}
            {{-- TAMPILAN UNTUK SALES STORE (TOKO) --}}
            {{-- ================================================= --}}

            {{-- Ganti Widget Visit dengan Info Simple saja --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm bg-info bg-opacity-10 border-start border-4 border-info">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-white p-3 rounded-circle me-3 text-info shadow-sm">
                                    <i class="bi bi-people-fill fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted fw-bold mb-1">Pelanggan Dilayani Hari Ini</h6>
                                    <h3 class="fw-bold mb-0 text-dark">{{ $todayVisits }} <span
                                            class="fs-6 text-muted fw-normal">Orang</span></h3>
                                </div>
                                <div class="ms-auto text-end">
                                    {{-- Tombol Cepat Buat Visit/Order untuk Sales Toko --}}
                                    <a href="{{ route('visits.create') }}"
                                        class="btn btn-info text-white fw-bold shadow-sm">
                                        <i class="bi bi-pencil-square me-1"></i> Input Laporan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            {{-- 3. RENCANA KUNJUNGAN (HANYA UNTUK SALES FIELD) --}}
            @if (!$isSalesStore)
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-check text-primary me-2"></i>Rencana Visit
                            </h6>
                            <a href="{{ route('visits.plan') }}" class="btn btn-sm btn-outline-primary">+ Tambah</a>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($plannedVisits as $plan)
                                {{-- ... (Looping Rencana Visit BIARKAN SAMA) ... --}}
                                @php
                                    $borderColor = 'primary';
                                    if ($plan->status == 'in_progress') {
                                        $borderColor = 'warning';
                                    }
                                    if ($plan->status == 'completed') {
                                        $borderColor = 'success';
                                    }
                                @endphp
                                <div
                                    class="list-group-item border-0 border-start border-4 border-{{ $borderColor }} py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="fw-bold mb-1">{{ $plan->customer->name }}</h6>
                                            <small class="text-muted"><i
                                                    class="bi bi-geo-alt me-1"></i>{{ Str::limit($plan->customer->address, 30) }}</small>
                                        </div>
                                        <div>
                                            @if ($plan->status == 'planned')
                                                <form action="{{ route('visits.checkIn', $plan->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-primary btn-sm rounded-pill px-3">Check In</button>
                                                </form>
                                            @elseif($plan->status == 'in_progress')
                                                <a href="{{ route('visits.perform', $plan->id) }}"
                                                    class="btn btn-warning btn-sm rounded-pill px-3 fw-bold">Check Out</a>
                                            @else
                                                <span class="badge bg-success"><i class="bi bi-check-circle"></i>
                                                    Selesai</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard-x fs-1 opacity-25"></i>
                                    <p class="mt-2">Belum ada rencana kunjungan.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            {{-- Jika Sales Store, kolom omset jadi full width atau tetap col-6 tergantung preferensi --}}
            <div class="{{ $isSalesStore ? 'col-lg-12' : 'col-lg-6' }}">

                {{-- 4. PENCAPAIAN OMSET (DINAMIS - SAMA UNTUK KEDUANYA) --}}
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                    {{-- ... (Widget Omset BIARKAN SAMA) ... --}}
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center">
                                <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle me-3 flex-shrink-0"
                                    style="width: 40px; height: 40px;">
                                    <i class="bi bi-trophy-fill fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted text-uppercase fw-bold mb-0"
                                        style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                        Omset Bulan Ini
                                    </h6>
                                    <div class="small text-success fw-bold" style="font-size: 0.75rem;">
                                        <i class="bi bi-graph-up-arrow me-1"></i>
                                        {{ $omsetPercentage >= 100 ? 'Target Tercapai!' : 'On Progress' }}
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">
                                {{ round($omsetPercentage) }}%
                            </span>
                        </div>

                        <h3 class="fw-bold text-dark mb-3 mt-1" style="font-size: 1.5rem;">
                            Rp {{ number_format($currentOmset, 0, ',', '.') }}
                        </h3>

                        <div>
                            <div class="progress mb-2" style="height: 8px; background-color: #f1f5f9; border-radius: 10px;">
                                <div class="progress-bar bg-success rounded-pill" role="progressbar"
                                    style="width: {{ $omsetPercentage > 100 ? 100 : $omsetPercentage }}%"
                                    aria-valuenow="{{ $omsetPercentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>

                            {{-- PERHITUNGAN OTOMATIS --}}
                            <div class="d-flex justify-content-between align-items-center text-muted"
                                style="font-size: 0.7rem;">
                                <span>Target: <strong>Rp {{ number_format($targetOmset, 0, ',', '.') }}</strong></span>
                                <span>Kurang: Rp
                                    {{ number_format(max(0, $targetOmset - $currentOmset), 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. GRAFIK KINERJA --}}
                <div class="card border-0 shadow-sm text-white"
                    style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border-radius: 16px;">
                    {{-- ... (Widget Grafik BIARKAN SAMA) ... --}}
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-line me-2"></i>Tren Omset Pribadi</h6>
                            <i class="bi bi-graph-up fs-4 opacity-50"></i>
                        </div>
                        {{-- KANVAS CHART --}}
                        <div style="height: 180px;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL REQUEST LIMIT --}}
        @include('dashboard.partials.modal_request_limit')

    </div>

    {{-- SCRIPT CHART --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('salesChart');
            if (ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt',
                            'Nov', 'Des'
                        ],
                        datasets: [{
                            label: 'Omset',
                            data: @json($chartData), // Data dari Controller
                            borderColor: '#fff', // Garis Putih
                            backgroundColor: 'rgba(255,255,255,0.2)', // Fill Transparan Putih
                            tension: 0.4, // Garis melengkung halus
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointRadius: 3
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                display: true, // Tampilkan Label Bulan
                                ticks: {
                                    color: 'rgba(255,255,255,0.7)',
                                    font: {
                                        size: 10
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                display: false // Sembunyikan angka Y biar bersih
                            }
                        },
                        maintainAspectRatio: false,
                        responsive: true
                    }
                });
            }
        });
    </script>
@endsection
