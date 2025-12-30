{{-- ============================================================ --}}
{{-- 1. TAMPILAN KHUSUS SALES --}}
{{-- ============================================================ --}}
@if ($isSales)

    {{-- A. WIDGET TARGET KUNJUNGAN --}}
    <div class="card shadow-sm border-0 mb-4 bg-primary text-white overflow-hidden">
        <div class="card-body p-4 position-relative">
            <div class="row align-items-center position-relative z-1">
                <div class="col-md-8">
                    <h4 class="fw-bold mb-1">Target Kunjungan Harian</h4>
                    <p class="mb-3 text-white-50">Ayo kejar targetmu hari ini!</p>
                    <div class="d-flex align-items-end mb-2">
                        <h1 class="display-4 fw-bold mb-0 me-2">{{ $todayVisits ?? 0 }}</h1>
                        <span class="fs-5 mb-2">/ {{ $visitTarget ?? 0 }} Toko</span>
                    </div>

                    @php
                        $vPercent = $visitPercentage ?? 0;
                        $vColor = $vPercent >= 100 ? 'bg-success' : 'bg-warning';
                    @endphp

                    <div class="progress" style="height: 10px; background-color: rgba(255,255,255,0.3);">
                        <div class="progress-bar {{ $vColor }}" role="progressbar"
                            style="width: {{ min($vPercent, 100) }}%"></div>
                    </div>
                    <small class="mt-2 d-block">
                        {{ number_format($vPercent, 0) }}% Tercapai
                    </small>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <i class="bi bi-geo-alt-fill text-white opacity-25"
                        style="font-size: 8rem; position: absolute; right: 20px; top: -20px;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- B. RENCANA KUNJUNGAN --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-check text-primary"></i> Rencana Kunjungan
                        Hari Ini</h6>
                    <a href="{{ route('visits.plan') }}" class="btn btn-sm btn-outline-primary"><i
                            class="bi bi-plus"></i> Tambah</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($plannedVisits ?? [] as $plan)
                        <div class="card mb-3 shadow-sm border-0">
                            @php
                                $borderColor = 'primary'; // Default Biru (Planned)
                                if ($plan->status == 'in_progress') {
                                    $borderColor = 'warning';
                                }
                                if ($plan->status == 'completed') {
                                    $borderColor = 'success';
                                }
                            @endphp

                            <div class="card-body p-3 border-start border-4 border-{{ $borderColor }}">
                                <div class="d-flex justify-content-between align-items-center">

                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            @if ($plan->status == 'planned')
                                                <div class="bg-light text-primary rounded-circle p-2">
                                                    <i class="bi bi-geo-alt-fill fs-5"></i>
                                                </div>
                                            @elseif($plan->status == 'in_progress')
                                                <div
                                                    class="bg-warning bg-opacity-25 text-warning rounded-circle p-2">
                                                    <i class="bi bi-stopwatch fs-5 spin"></i>
                                                </div>
                                            @else
                                                <div
                                                    class="bg-success bg-opacity-25 text-success rounded-circle p-2">
                                                    <i class="bi bi-check-circle-fill fs-5"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark">{{ $plan->customer->name }}</h6>
                                            <small class="text-muted d-block" style="font-size: 0.85rem;">
                                                <i class="bi bi-pin-map me-1"></i>
                                                {{ Str::limit($plan->customer->address, 30) }}
                                            </small>

                                            @if ($plan->status == 'in_progress')
                                                <small class="text-warning fw-bold" style="font-size: 0.75rem;">
                                                    <i class="bi bi-clock"></i> Dimulai:
                                                    {{ \Carbon\Carbon::parse($plan->check_in_time)->format('H:i') }}
                                                </small>
                                            @elseif($plan->status == 'completed')
                                                <small class="text-success" style="font-size: 0.75rem;">
                                                    Selesai jam
                                                    {{ \Carbon\Carbon::parse($plan->check_out_time)->format('H:i') }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>

                                    <div>
                                        {{-- 1. TOMBOL CHECK IN (Biru) --}}
                                        @if ($plan->status == 'planned')
                                            <form action="{{ route('visits.checkIn', $plan->id) }}"
                                                method="POST">
                                                @csrf
                                                <button type="submit"
                                                    class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm">
                                                    <i class="bi bi-play-circle me-1"></i> Check In
                                                </button>
                                            </form>

                                            {{-- 2. TOMBOL CHECK OUT (Kuning & Warning) --}}
                                        @elseif($plan->status == 'in_progress')
                                            <a href="{{ route('visits.perform', $plan->id) }}"
                                                class="btn btn-warning btn-sm px-3 rounded-pill shadow-sm text-dark fw-bold">
                                                <i class="bi bi-box-arrow-right me-1"></i> Check Out
                                            </a>

                                            {{-- 3. LABEL SELESAI (Hijau) --}}
                                        @else
                                            <button class="btn btn-light btn-sm text-success fw-bold border-0"
                                                disabled>
                                                <i class="bi bi-check-circle"></i> Selesai
                                            </button>
                                        @endif
                                    </div>

                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-clipboard-list fs-1 mb-3 text-secondary opacity-25"></i>
                            <p>Belum ada rencana kunjungan hari ini.</p>
                            <a href="{{ route('visits.plan') }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus"></i> Buat Rencana Baru
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- KARTU PENCAPAIAN OMSET (VERSI BIG PERCENTAGE) --}}
        <div class="col-lg-6 mb-4">
            <div class="card bg-success text-white shadow-sm border-0 h-100 position-relative overflow-hidden">

                {{-- Hiasan Background (Piala Samar di Kiri Bawah) --}}
                <div class="position-absolute bottom-0 start-0 opacity-10"
                    style="transform: translate(-10%, 20%)">
                    <i class="bi bi-trophy" style="font-size: 8rem;"></i>
                </div>

                <div class="card-body position-relative p-4">

                    {{-- LOGIKA HITUNG PERSENTASE (Di dalam View) --}}
                    @php
                        $target = $salesUser->sales_target ?? 0;
                        $achieved = $currentOmset ?? 0; // Pastikan variabel ini dikirim dari Controller
                        $percentage = $target > 0 ? round(($achieved / $target) * 100, 1) : 0;
                    @endphp

                    {{-- TOMBOL EDIT (HANYA MUNCUL JIKA MANAGER) --}}
                    @if (in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional']))
                        <button
                            class="btn btn-sm btn-light text-success fw-bold shadow-sm position-absolute top-0 end-0 m-3"
                            style="z-index: 20;" data-bs-toggle="modal" data-bs-target="#modalEditTarget">
                            <i class="bi bi-pencil-square me-1"></i> Atur
                        </button>
                    @endif

                    <div class="row align-items-end">
                        {{-- KOLOM KIRI: Judul & Nominal Uang --}}
                        <div class="col-7">
                            <h6 class="text-uppercase text-white-50 fw-bold mb-1"
                                style="font-size: 0.8rem; letter-spacing: 1px;">
                                Pencapaian Omset
                            </h6>
                            <h3 class="fw-bold mb-0">
                                Rp {{ number_format($achieved, 0, ',', '.') }}
                            </h3>
                            <small class="text-white-50 mt-1 d-block">
                                Target: Rp {{ number_format($target, 0, ',', '.') }}
                            </small>
                        </div>

                        {{-- KOLOM KANAN: Persentase Besar --}}
                        <div class="col-5 text-end">
                            {{-- Spacer agar tidak ketabrak tombol edit (jika ada) --}}
                            <div style="height: 20px;"></div>

                            <div class="fw-bold" style="font-size: 3rem; line-height: 1;">
                                {{ $percentage }}<span style="font-size: 1.5rem;">%</span>
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="progress mt-4" style="height: 8px; background-color: rgba(255,255,255,0.2);">
                        <div class="progress-bar bg-white" role="progressbar"
                            style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}"
                            aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- C. MODAL EDIT TARGET OMSET --}}
    @if (in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional']))
        <div class="modal fade" id="modalEditTarget" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i
                                class="bi bi-bullseye text-primary me-2"></i>Atur Target Omset</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('users.updateTarget') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Pilih Sales</label>
                                <select name="user_id" class="form-select">
                                    @foreach ($allSales ?? [] as $sales)
                                        <option value="{{ $sales->getKey() }}"
                                            {{ ($salesUser->id ?? '') == $sales->id ? 'selected' : '' }}>
                                            {{ $sales->name }} (Target Saat Ini: Rp
                                            {{ number_format($sales->sales_target, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Target Baru (Rupiah)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="target" class="form-control"
                                        placeholder="Contoh: 50000000" required>
                                </div>
                                <div class="form-text">Masukkan angka saja tanpa titik/koma.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Simpan Target</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- D. ORDER TERBARU --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>5 Transaksi Terakhir Anda
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-hover">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Invoice</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders ?? [] as $order)
                        <tr>
                            <td class="ps-4 fw-bold">{{ $order->invoice_number }}</td>
                            <td>{{ $order->customer->name }}</td>
                            <td>
                                @if ($order->status == 'pending_approval')
                                    <span class="badge bg-warning text-dark">Menunggu</span>
                                @elseif($order->status == 'approved')
                                    <span class="badge bg-info text-dark">Disetujui</span>
                                @elseif($order->status == 'processed')
                                    <span class="badge bg-primary">Diantar</span>
                                @elseif($order->status == 'completed')
                                    <span class="badge bg-success">Selesai</span>
                                @else
                                    <span class="badge bg-secondary">{{ $order->status }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">Rp
                                {{ number_format($order->total_price, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Belum ada transaksi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
