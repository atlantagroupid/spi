@extends('layouts.app')

@section('title', 'Monitoring Visit')

@section('content')
    {{-- LOGIC PHP UNTUK MENENTUKAN TAB & VISIBILITY --}}
    @php
        $user = Auth::user();
        $isManager = in_array($user->role, ['manager_operasional', 'manager_bisnis']);
        $isField   = $user->role == 'sales_field';
        $isStore   = $user->role == 'sales_store';

        // Tentukan Tab mana yang aktif duluan
        $activeTab = ($isStore && !$isManager) ? 'store' : 'field';
    @endphp

    <div class="container-fluid px-0 px-md-3">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-3 mb-md-4 gap-3">

            {{-- JUDUL HALAMAN (HANYA DESKTOP) --}}
            {{-- Di Mobile disembunyikan agar tidak double header dengan Navbar --}}
            <div class="d-none d-md-block">
                <h3 class="fw-bold text-primary mb-1">
                    <i class="bi bi-bar-chart-line-fill me-2"></i>
                    {{ $isManager ? 'Monitoring Sales' : 'Riwayat Kunjungan' }}
                </h3>
                <p class="text-muted small mb-0">
                    {{ $isManager ? 'Pantau pergerakan Sales Lapangan & aktivitas Sales Toko secara realtime.' : 'Daftar riwayat aktivitas dan kunjungan Anda.' }}
                </p>
            </div>

            {{-- FORM FILTER (RESPONSIVE) --}}
            <div class="bg-white p-2 rounded shadow-sm border w-100 w-md-auto">
                <form action="{{ route('visits.index') }}" method="GET" class="d-flex flex-wrap flex-md-nowrap align-items-center gap-2">

                    {{-- FILTER SALESMAN (HANYA MUNCUL BAGI MANAGER) --}}
                    @if($isManager && isset($salesList) && count($salesList) > 0)
                    <div class="flex-grow-1 flex-md-grow-0" style="min-width: 130px;">
                        <select name="sales_id" class="form-select form-select-sm border-primary fw-bold text-primary">
                            <option value="">Semua Sales</option>
                            @foreach($salesList as $s)
                                <option value="{{ $s->id }}" {{ request('sales_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- INPUT TANGGAL (GROUP) --}}
                    <div class="d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 text-primary px-2">
                                <i class="bi bi-calendar-event"></i>
                            </span>
                            <input type="date" name="start_date"
                                class="form-control border-start-0 ps-0 fw-bold text-secondary"
                                value="{{ request('start_date', date('Y-m-d')) }}" style="max-width: 110px;">
                        </div>

                        <span class="text-muted fw-bold">-</span>

                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 text-primary px-2">
                                <i class="bi bi-calendar-check"></i>
                            </span>
                            <input type="date" name="end_date"
                                class="form-control border-start-0 ps-0 fw-bold text-secondary"
                                value="{{ request('end_date', date('Y-m-d')) }}" style="max-width: 110px;">
                        </div>
                    </div>

                    {{-- TOMBOL AKSI --}}
                    <div class="d-flex gap-1 flex-grow-1 flex-md-grow-0">
                        <button type="submit" class="btn btn-sm btn-primary px-3 fw-bold shadow-sm rounded-pill flex-grow-1 flex-md-grow-0">
                            <i class="bi bi-funnel-fill me-1"></i> Filter
                        </button>

                        <a href="{{ route('visits.index') }}"
                            class="btn btn-sm btn-light text-danger border rounded-circle shadow-sm" data-bs-toggle="tooltip"
                            title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>

                </form>
            </div>
        </div>

        {{-- SUMMARY CARDS --}}
        <div class="row mb-3 mb-md-4 g-2 g-md-3">
            <div class="col-4 col-md-4">
                <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
                    <div class="card-body py-2 py-md-3 px-2 px-md-3 text-center text-md-start">
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.65rem;">Total Aktivitas</small>
                        <h3 class="fw-bold mb-0 text-primary">{{ $summary['total_all'] }}</h3>
                    </div>
                </div>
            </div>

            {{-- KARTU LAPANGAN --}}
            @if($isManager || $isField)
            <div class="col-4 col-md-4">
                <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
                    <div class="card-body py-2 py-md-3 px-2 px-md-3 text-center text-md-start">
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.65rem;">Lapangan</small>
                        <h3 class="fw-bold mb-0 text-success">{{ $summary['total_field'] }}</h3>
                    </div>
                </div>
            </div>
            @endif

            {{-- KARTU TOKO --}}
            @if($isManager || $isStore)
            <div class="col-4 col-md-4">
                <div class="card shadow-sm border-0 border-start border-4 border-info h-100">
                    <div class="card-body py-2 py-md-3 px-2 px-md-3 text-center text-md-start">
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.65rem;">Layanan Toko</small>
                        <h3 class="fw-bold mb-0 text-info">{{ $summary['total_store'] }}</h3>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- TABEL PENCAPAIAN BULANAN (MANAGER ONLY) --}}
        @if (isset($monthlyRecap) && count($monthlyRecap) > 0)
            <div class="card shadow border-0 mb-4 d-none d-md-block"> {{-- Sembunyikan tabel besar ini di HP agar tidak penuh --}}
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold text-primary mb-0"><i class="bi bi-trophy-fill text-warning me-2"></i>Pencapaian
                        Sales Bulan Ini</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nama Sales</th>
                                    <th class="text-center">Target Visit</th>
                                    <th class="text-center">Realisasi</th>
                                    <th class="text-center">Target Omset</th>
                                    <th class="text-center">Realisasi Omset</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($monthlyRecap as $recap)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold">{{ $recap['name'] }}</div>
                                            @if ($recap['role'] == 'sales_store')
                                                <span class="badge bg-info text-dark" style="font-size: 0.65rem;">Sales Toko</span>
                                            @else
                                                <span class="badge bg-success" style="font-size: 0.65rem;">Sales Lapangan</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $recap['target_visit'] }}</td>
                                        <td class="text-center">
                                            <span class="fw-bold {{ $recap['visit_pct'] >= 100 ? 'text-success' : 'text-primary' }}">
                                                {{ $recap['actual_visit'] }}
                                            </span>
                                            <small class="text-muted ms-1">({{ $recap['visit_pct'] }}%)</small>
                                        </td>
                                        <td class="text-center">Rp {{ number_format($recap['target_omset'], 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            <span class="fw-bold {{ $recap['omset_pct'] >= 100 ? 'text-success' : 'text-warning' }}">
                                                Rp {{ number_format($recap['current_omset'], 0, ',', '.') }}
                                            </span>
                                            <small class="text-muted ms-1">({{ $recap['omset_pct'] }}%)</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- MAIN CONTENT TABS --}}
        <div class="card shadow border-0 rounded-3 overflow-hidden">
            <div class="card-header bg-white border-bottom pt-3 pb-0 px-0 px-md-3">
                <ul class="nav nav-tabs card-header-tabs flex-nowrap overflow-auto px-3 px-md-0" id="visitTabs" role="tablist" style="scrollbar-width: none;">
                    {{-- TAB LAPANGAN --}}
                    @if($isManager || $isField)
                    <li class="nav-item">
                        <button class="nav-link {{ $activeTab == 'field' ? 'active' : '' }} fw-bold text-nowrap" id="field-tab" data-bs-toggle="tab"
                            data-bs-target="#field" type="button">
                            <i class="bi bi-geo-alt-fill text-success me-1"></i> <span class="d-none d-sm-inline">Sales</span> Lapangan
                        </button>
                    </li>
                    @endif

                    {{-- TAB TOKO --}}
                    @if($isManager || $isStore)
                    <li class="nav-item">
                        <button class="nav-link {{ $activeTab == 'store' ? 'active' : '' }} fw-bold text-nowrap" id="store-tab" data-bs-toggle="tab" data-bs-target="#store"
                            type="button">
                            <i class="bi bi-shop text-info me-1"></i> <span class="d-none d-sm-inline">Sales</span> Toko
                        </button>
                    </li>
                    @endif
                </ul>
            </div>

            <div class="card-body p-0 p-md-3">
                <div class="tab-content" id="visitTabsContent">

                    {{-- TAB FIELD --}}
                    @if($isManager || $isField)
                    <div class="tab-pane fade {{ $activeTab == 'field' ? 'show active' : '' }}" id="field">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-secondary small">
                                    <tr>
                                        <th class="ps-3 ps-md-2">Waktu</th>
                                        <th>Salesman</th>
                                        <th>Customer</th>
                                        <th class="d-none d-md-table-cell">Lokasi</th>
                                        <th class="text-end pe-3 pe-md-2">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($fieldVisits as $visit)
                                        <tr>
                                            <td class="ps-3 ps-md-2">
                                                <div class="fw-bold">{{ $visit->created_at->format('d M') }}</div>
                                                <small class="text-muted">{{ $visit->created_at->format('H:i') }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-success rounded-pill" style="font-size: 0.7rem;">{{ explode(' ', $visit->user->name)[0] }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-truncate" style="max-width: 120px;">{{ $visit->customer->name ?? 'Guest' }}</div>
                                                <small class="text-muted d-block text-truncate" style="max-width: 120px; font-size: 0.7rem;">
                                                    {{ $visit->customer->address ?? '-' }}
                                                </small>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                @if ($visit->latitude)
                                                    <a href="http://maps.google.com/?q={{ $visit->latitude }},{{ $visit->longitude }}" target="_blank" class="text-decoration-none">
                                                        <i class="bi bi-geo-alt-fill text-danger"></i> Peta
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-3 pe-md-2">
                                                <button class="btn btn-sm btn-light border rounded-circle shadow-sm" data-bs-toggle="modal"
                                                    data-bs-target="#modalVisit{{ $visit->id }}" style="width: 32px; height: 32px; padding: 0;">
                                                    <i class="bi bi-eye text-primary"></i>
                                                </button>

                                                {{-- MODAL DETAIL --}}
                                                <div class="modal fade text-start" id="modalVisit{{ $visit->id }}" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content border-0 shadow">
                                                            <div class="modal-header bg-light">
                                                                <h6 class="modal-title fw-bold">Detail #{{ $visit->id }}</h6>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-center p-0">
                                                                @if($visit->photo_path)
                                                                    <img src="{{ asset('storage/' . $visit->photo_path) }}" class="img-fluid bg-dark w-100" style="max-height: 300px; object-fit: contain;">
                                                                @else
                                                                    <div class="py-5 bg-light text-muted">Tidak ada foto</div>
                                                                @endif
                                                                <div class="p-3 text-start">
                                                                    <label class="small fw-bold text-muted">Laporan:</label>
                                                                    <p class="mb-0 bg-light p-2 rounded small">"{{ $visit->notes }}"</p>
                                                                    @if($visit->latitude)
                                                                        <a href="http://maps.google.com/?q={{ $visit->latitude }},{{ $visit->longitude }}" target="_blank" class="btn btn-outline-danger btn-sm w-100 mt-3">
                                                                            <i class="bi bi-map me-1"></i> Buka Lokasi di Maps
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center py-4 text-muted small">Belum ada data.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- TAB STORE --}}
                    @if($isManager || $isStore)
                    <div class="tab-pane fade {{ $activeTab == 'store' ? 'show active' : '' }}" id="store">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-secondary small">
                                    <tr>
                                        <th class="ps-3 ps-md-2">Waktu</th>
                                        <th>Sales</th>
                                        <th>Tamu</th>
                                        <th class="d-none d-md-table-cell">Catatan</th>
                                        <th class="text-end pe-3 pe-md-2">Foto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($storeVisits as $visit)
                                        <tr>
                                            <td class="ps-3 ps-md-2">
                                                <div class="fw-bold">{{ $visit->created_at->format('d M') }}</div>
                                                <small class="text-muted">{{ $visit->created_at->format('H:i') }}</small>
                                            </td>
                                            <td><span class="badge bg-info text-dark rounded-pill" style="font-size: 0.7rem;">{{ explode(' ', $visit->user->name)[0] }}</span></td>
                                            <td>
                                                <div class="fw-bold text-truncate" style="max-width: 120px;">{{ $visit->customer->name ?? '-' }}</div>
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.6rem;">{{ $visit->customer_category ?? 'Tamu' }}</span>
                                            </td>
                                            <td class="d-none d-md-table-cell text-truncate" style="max-width: 200px;">
                                                {{ $visit->notes }}
                                            </td>
                                            <td class="text-end pe-3 pe-md-2">
                                                @if ($visit->photo_path)
                                                    <a href="{{ asset('storage/' . $visit->photo_path) }}" target="_blank" class="btn btn-sm btn-outline-primary border-0">
                                                        <i class="bi bi-image"></i>
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center py-4 text-muted small">Belum ada data.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection
