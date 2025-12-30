@extends('layouts.app')

@section('title', 'Dashboard Sales')

@section('content')
    <div class="container-fluid px-0"> {{-- px-0 agar full width di HP --}}

        {{-- 1. HEADER SIMPLE --}}
        <div class="mb-4 px-2">
            <h4 class="fw-bold text-dark mb-0">Halo, {{ Auth::user()->name }}! 👋</h4>
            <small class="text-muted">Semangat kejar target hari ini.</small>
        </div>

        <div class="row g-3"> {{-- G-3 Memberi jarak antar kartu --}}

            {{-- 2. TARGET KUNJUNGAN (CARD BIRU) --}}
            <div class="col-12 col-md-6">
                <div class="card shadow-sm border-0 bg-primary text-white overflow-hidden h-100">
                    <div class="card-body p-3 position-relative">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h6 class="text-uppercase text-white-50 fw-bold mb-1" style="font-size: 0.75rem;">Kunjungan
                                    Hari Ini</h6>
                                <div class="d-flex align-items-baseline">
                                    <h1 class="fw-bold mb-0 me-2" style="font-size: 2.5rem;">{{ $todayVisits ?? 0 }}</h1>
                                    <span class="fs-6 opacity-75">/ {{ $visitTarget ?? 0 }} Toko</span>
                                </div>
                            </div>
                            {{-- Ikon Besar (Hanya muncul di Tablet/Desktop) --}}
                            <div class="col-4 text-end d-none d-sm-block">
                                <i class="bi bi-geo-alt-fill opacity-25" style="font-size: 4rem;"></i>
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        @php $vPercent = $visitPercentage ?? 0; @endphp
                        <div class="mt-3">
                            <div class="progress" style="height: 6px; background-color: rgba(255,255,255,0.3);">
                                <div class="progress-bar bg-white" style="width: {{ min($vPercent, 100) }}%"></div>
                            </div>
                            <small class="mt-2 d-block text-white-50">{{ number_format($vPercent, 0) }}% Tercapai</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. TARGET OMSET (CARD HIJAU - MODIFIKASI MOBILE) --}}
            <div class="col-12 col-md-6">
                <div class="card bg-success text-white shadow-sm border-0 overflow-hidden h-100">
                    <div class="card-body p-3 position-relative">
                        {{-- Ikon Trophy (Hidden di HP kecil biar gak sumpek) --}}
                        <div class="position-absolute bottom-0 start-0 opacity-10 d-none d-sm-block"
                            style="transform: translate(-10%, 20%)">
                            <i class="fas fa-trophy" style="font-size: 6rem;"></i>
                        </div>

                        @php
                            $target = $salesUser->sales_target ?? 0;
                            $achieved = $currentOmset ?? 0;
                            $percentage = $target > 0 ? round(($achieved / $target) * 100, 1) : 0;
                        @endphp

                        <div class="row align-items-end">
                            <div class="col-7">
                                <h6 class="text-uppercase text-white-50 fw-bold mb-1" style="font-size: 0.75rem;">Pencapaian
                                    Omset</h6>
                                <h3 class="fw-bold mb-0" style="font-size: 1.5rem;">
                                    Rp {{ number_format($achieved, 0, ',', '.') }}
                                </h3>
                                <small class="text-white-50 mt-1 d-block" style="font-size: 0.7rem;">
                                    Target: Rp {{ number_format($target, 0, ',', '.') }}
                                </small>
                            </div>

                            {{-- Persentase Besar --}}
                            <div class="col-5 text-end">
                                <div class="fw-bold" style="font-size: 2.2rem; line-height: 1;">
                                    {{ $percentage }}<span style="font-size: 1rem;">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="progress mt-3" style="height: 6px; background-color: rgba(255,255,255,0.2);">
                            <div class="progress-bar bg-white" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. RENCANA KUNJUNGAN (LIST GROUP STYLE) --}}
        <div class="row mt-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3 px-2">
                    <h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-calendar-check me-2"></i>Rencana Visit</h6>
                    <a href="{{ route('visits.plan') }}" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </a>
                </div>

                @forelse($plannedVisits ?? [] as $plan)
                    {{-- CARD VISIT MOBILE FRIENDLY --}}
                    <div class="card mb-3 shadow-sm border-0">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between">
                                {{-- Info Toko --}}
                                <div class="d-flex align-items-start overflow-hidden">
                                    <div class="me-3 mt-1">
                                        @if ($plan->status == 'planned')
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </div>
                                        @elseif($plan->status == 'in_progress')
                                            {{-- >>> TAMBAHAN: LIVE TIMER <<< --}}
                                            <div class="d-flex align-items-center bg-light rounded px-2 py-1 mb-2 border"
                                                style="width: fit-content;">
                                                <i class="bi bi-stopwatch text-danger me-2"></i>
                                                <span class="fw-bold text-danger live-timer"
                                                    data-start="{{ $plan->check_in_time }}">
                                                    Memuat...
                                                </span>
                                            </div>
                                        @else
                                            <div class="bg-success bg-opacity-10 text-success rounded-circle p-2">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div style="min-width: 0;"> {{-- Trik agar text-truncate berfungsi --}}
                                        <h6 class="fw-bold text-dark mb-1 text-truncate">{{ $plan->customer->name }}</h6>
                                        <small class="text-muted d-block text-truncate">
                                            {{ $plan->customer->address }}
                                        </small>

                                        {{-- Status Waktu --}}
                                        @if ($plan->status == 'in_progress')
                                            <span class="badge bg-warning text-dark mt-1">Sedang Visit</span>
                                        @elseif($plan->status == 'completed')
                                            <span class="badge bg-success mt-1">Selesai
                                                {{ \Carbon\Carbon::parse($plan->check_out_time)->format('H:i') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Tombol Aksi (Full Width di HP) --}}
                            <div class="mt-3 d-grid gap-2">
                                @if ($plan->status == 'planned')
                                    <form action="{{ route('visits.checkIn', $plan->id) }}" method="POST" class="d-grid">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm py-2">
                                            Check In
                                        </button>
                                    </form>
                                @elseif($plan->status == 'in_progress')
                                    <a href="{{ route('visits.perform', $plan->id) }}"
                                        class="btn btn-warning text-dark fw-bold btn-sm py-2">
                                        Check Out
                                    </a>
                                @else
                                    <button class="btn btn-light text-muted btn-sm py-2" disabled>Kunjungan Selesai</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <img src="https://img.freepik.com/free-vector/no-data-concept-illustration_114360-536.jpg"
                            alt="Empty" style="width: 150px; opacity: 0.5;">
                        <p class="text-muted mt-3 small">Belum ada rencana kunjungan.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- 5. TRANSAKSI TERAKHIR (Simple List) --}}
        <div class="row mt-2 mb-5">
            <div class="col-12">
                <h6 class="fw-bold mb-3 px-2 text-secondary"><i class="bi bi-receipt me-2"></i>Transaksi Terakhir</h6>

                <div class="card border-0 shadow-sm">
                    <div class="list-group list-group-flush">
                        @forelse($recentOrders ?? [] as $order)
                            <div class="list-group-item p-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold small">{{ $order->invoice_number }}</span>
                                    <span class="badge bg-light text-dark border">{{ $order->status }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small text-truncate"
                                        style="max-width: 150px;">{{ $order->customer->name }}</span>
                                    <span class="fw-bold text-success small">Rp
                                        {{ number_format($order->total_price, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted small">Belum ada transaksi.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        {{-- 6. GRAFIK KINERJA BULANAN --}}
        <div class="row mt-4 mb-5">
            <div class="col-12">
                <h6 class="fw-bold mb-3 px-2 text-secondary">
                    <i class="bi bi-graph-up-arrow me-2"></i>Tren Penjualan Saya ({{ date('Y') }})
                </h6>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        {{-- Canvas Chart --}}
                        <canvas id="mySalesChart" style="max-height: 250px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- SCRIPT CHART JS --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById('mySalesChart');

                if (ctx) {
                    // Fungsi format Rupiah untuk tooltip
                    const formatRupiah = (val) => {
                        return 'Rp ' + new Intl.NumberFormat('id-ID', {
                            notation: "compact"
                        }).format(val);
                    };

                    new Chart(ctx.getContext('2d'), {
                        type: 'line', // Pakai Line chart biar kelihatan tren naik/turun
                        data: {
                            labels: @json($chartLabels),
                            datasets: [{
                                label: 'Omset',
                                data: @json($chartData),
                                borderColor: '#0d6efd', // Warna Biru Sales
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                borderWidth: 3,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#0d6efd',
                                pointRadius: 5,
                                fill: true,
                                tension: 0.4 // Garis melengkung halus
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }, // Sembunyikan legenda biar bersih
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return ' ' + formatRupiah(context.raw);
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        borderDash: [5, 5]
                                    }, // Garis putus-putus
                                    ticks: {
                                        callback: function(value) {
                                            return formatRupiah(value);
                                        },
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }, // Hilangkan grid vertikal biar bersih
                                    ticks: {
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });
        </script>
        {{-- 7. SCRIPT LIVE TIMER UNTUK KUNJUNGAN YANG SEDANG BERLANGSUNG --}}
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Fungsi untuk mengupdate timer
                function updateTimers() {
                    const timers = document.querySelectorAll('.live-timer');

                    timers.forEach(timer => {
                        const startTimeStr = timer.getAttribute('data-start');
                        // Konversi string Laravel ke Date Object
                        const startTime = new Date(startTimeStr).getTime();
                        const now = new Date().getTime();

                        // Hitung selisih waktu (dalam milidetik)
                        const distance = now - startTime;

                        if (distance > 0) {
                            // Hitung jam, menit, detik
                            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                            // Format supaya jadi 00:00:00
                            const hDisplay = hours < 10 ? "0" + hours : hours;
                            const mDisplay = minutes < 10 ? "0" + minutes : minutes;
                            const sDisplay = seconds < 10 ? "0" + seconds : seconds;

                            timer.innerHTML = `${hDisplay}:${mDisplay}:${sDisplay}`;
                        } else {
                            timer.innerHTML = "00:00:00";
                        }
                    });
                }

                // Jalankan fungsi setiap 1 detik
                setInterval(updateTimers, 1000);

                // Jalankan sekali saat load agar tidak menunggu 1 detik
                updateTimers();
            });
        </script>
    </div>
@endsection
