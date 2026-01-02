@extends('layouts.app')

@section('title', 'Dashboard Sales')

@section('content')
    <div class="container-fluid px-0 px-md-3"> {{-- Hapus padding di mobile biar full width --}}

        {{-- HEADER: Di Mobile hanya tampilkan Tanggal & Sapaan --}}
        <div class="d-flex justify-content-between align-items-center mb-3 mb-md-4">
            <div>
                {{-- Judul Besar: HANYA DESKTOP --}}
                <h4 class="fw-bold text-primary d-none d-md-block">Dashboard
                    {{ $isSalesStore ? 'Sales Counter' : 'Sales Lapangan' }}</h4>

                {{-- Sapaan: TAMPIL DI SEMUA (Di mobile jadi teks utama) --}}
                <p class="text-muted mb-0 small">Halo <strong>{{ explode(' ', $user->name)[0] }}</strong>, semangat kejar
                    omset!</p>
            </div>
            <div class="text-end">
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill shadow-sm">
                    <i class="bi bi-calendar-event me-1"></i> <span class="d-none d-sm-inline">{{ date('d M Y') }}</span><span
                        class="d-sm-none">{{ date('d/m') }}</span>
                </span>
            </div>
        </div>

        {{-- 1. INFO PLAFON KREDIT (Mobile Friendly) --}}
        @if ($limitQuota > 0)
            @if ($isCritical)
                <div class="alert alert-danger shadow-sm d-flex align-items-center justify-content-between p-3 mb-3"
                    role="alert">
                    <div class="me-2">
                        <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Limit
                            Tipis!</h6>
                        <p class="mb-0" style="font-size: 0.75rem; line-height: 1.2;">
                            Sisa: <strong>{{ number_format($remaining / 1000, 0) }}k</strong>
                        </p>
                    </div>
                    <button type="button" class="btn btn-light text-danger fw-bold btn-sm shadow-sm text-nowrap"
                        data-bs-toggle="modal" data-bs-target="#requestLimitModal" style="font-size: 0.75rem;">
                        Minta Limit
                    </button>
                </div>
            @else
                <div class="card shadow-sm border-0 border-start border-4 border-success mb-3 mb-md-4">
                    <div class="card-body py-3 d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem;">Sisa Plafon</small>
                            <h4 class="fw-bold text-success mb-0">Rp {{ number_format($remaining, 0, ',', '.') }}</h4>
                        </div>
                        <i class="bi bi-wallet2 fs-1 text-gray-300 opacity-25"></i>
                    </div>
                </div>
            @endif
        @endif

        {{-- 2. WIDGET TARGET / INFO SALES --}}
        @if (!$isSalesStore)
            {{-- SALES FIELD --}}
            <div class="card shadow-sm border-0 mb-3 mb-md-4 bg-primary text-white overflow-hidden">
                <div class="card-body p-3 p-md-4 position-relative">
                    <div class="row align-items-center position-relative z-1">
                        <div class="col-8">
                            <h6 class="fw-bold mb-1 opacity-75" style="font-size: 0.8rem;">Target Visit Hari Ini</h6>
                            <div class="d-flex align-items-end mb-2">
                                <h1 class="display-6 fw-bold mb-0 me-2">{{ $todayVisits }}</h1>
                                <span class="fs-6 mb-2 opacity-75">/ {{ $visitTarget }}</span>
                            </div>
                            <div class="progress" style="height: 6px; background-color: rgba(255,255,255,0.3);">
                                <div class="progress-bar bg-warning" role="progressbar"
                                    style="width: {{ $visitTarget > 0 ? min($visitPercentage, 100) : 0 }}%"></div>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-geo-alt-fill opacity-25" style="font-size: 4rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- SALES STORE --}}
            <div class="card border-0 shadow-sm bg-info bg-opacity-10 border-start border-4 border-info mb-3 mb-md-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem;">Pelanggan Hari Ini</h6>
                            <h3 class="fw-bold mb-0 text-dark">{{ $todayVisits }} <span
                                    class="fs-6 text-muted fw-normal">Orang</span></h3>
                        </div>
                        <a href="{{ route('visits.create') }}" class="btn btn-info text-white btn-sm fw-bold shadow-sm">
                            <i class="bi bi-plus-lg"></i> Laporan
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- 3. RENCANA VISIT & OMSET (Layout: Mobile Stack, Desktop Side-by-Side) --}}
        <div class="row g-3">

            {{-- KOLOM KIRI: RENCANA VISIT (Tampil Pertama di Mobile) --}}
            @if (!$isSalesStore)
                <div class="col-lg-6 mb-3 mb-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold small text-uppercase">Rencana Visit</h6>
                            <a href="{{ route('visits.plan') }}"
                                class="btn btn-sm btn-light text-primary fw-bold rounded-pill" style="font-size: 0.7rem;">+
                                Tambah</a>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($plannedVisits as $plan)
                                {{-- Item Rencana Visit --}}
                                <div
                                    class="list-group-item border-0 border-start border-4 border-{{ $plan->status == 'completed' ? 'success' : ($plan->status == 'in_progress' ? 'warning' : 'primary') }} py-3 px-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="me-2" style="min-width: 0;">
                                            <h6 class="fw-bold mb-0 text-truncate">{{ $plan->customer->name }}</h6>
                                            <small class="text-muted text-truncate d-block" style="font-size: 0.7rem;">
                                                <i
                                                    class="bi bi-geo-alt me-1"></i>{{ Str::limit($plan->customer->address, 25) }}
                                            </small>
                                        </div>
                                        <div class="flex-shrink-0">
                                            @if ($plan->status == 'planned')
                                                <form action="{{ route('visits.checkIn', $plan->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-primary btn-sm rounded-pill px-3 py-1"
                                                        style="font-size: 0.7rem;">Check In</button>
                                                </form>
                                            @elseif($plan->status == 'in_progress')
                                                <a href="{{ route('visits.perform', $plan->id) }}"
                                                    class="btn btn-warning btn-sm rounded-pill px-3 py-1 fw-bold"
                                                    style="font-size: 0.7rem;">Check Out</a>
                                            @else
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted small">Belum ada rencana.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            {{-- KOLOM KANAN: OMSET & CHART (Tampil Kedua di Mobile) --}}
            <div class="{{ $isSalesStore ? 'col-12' : 'col-lg-6' }}">

                {{-- WIDGET OMSET --}}
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="text-muted text-uppercase fw-bold mb-0" style="font-size: 0.65rem;">Omset Bulan
                                    Ini</h6>
                                <h3 class="fw-bold text-dark mb-0 mt-1">Rp {{ number_format($currentOmset, 0, ',', '.') }}
                                </h3>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1"
                                style="font-size: 0.7rem;">
                                {{ round($omsetPercentage) }}%
                            </span>
                        </div>
                        <div class="progress mb-2" style="height: 6px; border-radius: 10px;">
                            <div class="progress-bar bg-success rounded-pill"
                                style="width: {{ min($omsetPercentage, 100) }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.7rem;">
                            <span>Target: {{ number_format($targetOmset / 1000000, 1) }} Jt</span>
                            <span>Kurang: {{ number_format(max(0, $targetOmset - $currentOmset) / 1000, 0) }}k</span>
                        </div>
                    </div>
                </div>

                {{-- CHART --}}
                <div class="card border-0 shadow-sm text-white mb-3"
                    style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border-radius: 12px;">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-3 small"><i class="bi bi-graph-up me-2"></i>Tren Omset</h6>
                        <div style="height: 150px;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>

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
