@extends('layouts.app')

@section('title', 'Monitoring Visit')

@section('content')
    <div class="container-fluid">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">

            <div>
                <h3 class="fw-bold text-primary mb-1">
                    <i class="bi bi-bar-chart-line-fill me-2"></i>Monitoring Sales
                </h3>
                <p class="text-muted small mb-0">Pantau pergerakan Sales Lapangan & aktivitas Sales Toko secara realtime.</p>
            </div>

            <div class="bg-white p-2 rounded shadow-sm border d-inline-block">
                <form action="{{ route('visits.index') }}" method="GET" class="d-flex align-items-center gap-2">

                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-primary">
                            <i class="bi bi-calendar-event"></i>
                        </span>
                        <input type="date" name="start_date"
                            class="form-control border-start-0 ps-0 fw-bold text-secondary"
                            value="{{ request('start_date', date('Y-m-d')) }}" style="max-width: 130px;">
                    </div>

                    <span class="text-muted fw-bold">-</span>

                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-primary">
                            <i class="bi bi-calendar-check"></i>
                        </span>
                        <input type="date" name="end_date"
                            class="form-control border-start-0 ps-0 fw-bold text-secondary"
                            value="{{ request('end_date', date('Y-m-d')) }}" style="max-width: 130px;">
                    </div>

                    <button type="submit" class="btn btn-sm btn-primary px-3 fw-bold shadow-sm rounded-pill">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>

                    <a href="{{ route('visits.index') }}"
                        class="btn btn-sm btn-light text-danger border rounded-circle shadow-sm" data-bs-toggle="tooltip"
                        title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>

                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 border-start border-4 border-primary">
                    <div class="card-body py-3">
                        <small class="text-muted text-uppercase fw-bold">Total Aktivitas</small>
                        <h3 class="fw-bold mb-0">{{ $summary['total_all'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 border-start border-4 border-success">
                    <div class="card-body py-3">
                        <small class="text-muted text-uppercase fw-bold">Kunjungan Lapangan</small>
                        <h3 class="fw-bold mb-0">{{ $summary['total_field'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 border-start border-4 border-info">
                    <div class="card-body py-3">
                        <small class="text-muted text-uppercase fw-bold">Pelayanan Toko</small>
                        <h3 class="fw-bold mb-0">{{ $summary['total_store'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
        @if (isset($monthlyRecap) && count($monthlyRecap) > 0)
            <div class="card shadow border-0 mb-4">
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
                                    <th class="text-center">Target Kunjungan</th>
                                    <th class="text-center">Realisasi Kunjungan</th>
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
                                                <span class="badge bg-info text-dark" style="font-size: 0.65rem;">Sales
                                                    Toko</span>
                                            @else
                                                <span class="badge bg-success" style="font-size: 0.65rem;">Sales
                                                    Lapangan</span>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            <span class="text-muted small">Target:</span>
                                            <strong>{{ $recap['monthly_visit_target'] }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <span
                                                    class="fw-bold {{ $recap['visit_percentage'] >= 100 ? 'text-success' : 'text-primary' }}">
                                                    {{ $recap['actual_visit'] }}
                                                </span>
                                                <div class="progress" style="height: 6px; width: 80px;">
                                                    <div class="progress-bar {{ $recap['visit_percentage'] >= 100 ? 'bg-success' : 'bg-primary' }}"
                                                        role="progressbar"
                                                        style="width: {{ min($recap['visit_percentage'], 100) }}%">
                                                    </div>
                                                </div>
                                                <small class="text-muted"
                                                    style="font-size: 0.7rem;">{{ $recap['visit_percentage'] }}%</small>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <span class="text-muted small">Rp</span>
                                            <strong>{{ number_format($recap['target_omset'], 0, ',', '.') }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <span
                                                    class="fw-bold {{ $recap['omset_percentage'] >= 100 ? 'text-success' : ($recap['omset_percentage'] >= 50 ? 'text-warning' : 'text-danger') }}">
                                                    Rp {{ number_format($recap['current_omset'], 0, ',', '.') }}
                                                </span>
                                                <div class="progress" style="height: 6px; width: 100px;">
                                                    <div class="progress-bar {{ $recap['omset_percentage'] >= 100 ? 'bg-success' : ($recap['omset_percentage'] >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                                        role="progressbar"
                                                        style="width: {{ min($recap['omset_percentage'], 100) }}%">
                                                    </div>
                                                </div>
                                                <small class="text-muted"
                                                    style="font-size: 0.7rem;">{{ $recap['omset_percentage'] }}%</small>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
        <div class="card shadow border-0">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                <ul class="nav nav-tabs card-header-tabs" id="visitTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold" id="field-tab" data-bs-toggle="tab"
                            data-bs-target="#field" type="button">
                            <i class="bi bi-geo-alt-fill text-success me-1"></i> Sales Lapangan
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold" id="store-tab" data-bs-toggle="tab" data-bs-target="#store"
                            type="button">
                            <i class="bi bi-shop text-info me-1"></i> Sales Toko
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="visitTabsContent">

                    <div class="tab-pane fade show active" id="field">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Salesman</th>
                                        <th>Customer</th>
                                        <th>Lokasi (GPS)</th>
                                        <th>Foto & Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($fieldVisits as $visit)
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $visit->created_at->format('d M') }}</div>
                                                <small class="text-muted">{{ $visit->created_at->format('H:i') }}</small>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-success rounded-pill">{{ $visit->user->name }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $visit->customer->name }}</div>
                                                <small
                                                    class="text-muted">{{ Str::limit($visit->customer->address, 30) }}</small>
                                            </td>
                                            <td>
                                                @if ($visit->latitude && $visit->longitude)
                                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $visit->latitude }},{{ $visit->longitude }}"
                                                        target="_blank" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-map"></i> Lihat Peta
                                                    </a>
                                                @else
                                                    <span class="text-muted small fst-italic">No GPS</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-light border" data-bs-toggle="modal"
                                                    data-bs-target="#modalVisit{{ $visit->id }}">
                                                    <i class="bi bi-eye"></i> Detail
                                                </button>

                                                <div class="modal fade" id="modalVisit{{ $visit->id }}"
                                                    tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Detail Kunjungan</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-center">
                                                                <img src="{{ asset('storage/' . $visit->photo_path) }}"
                                                                    class="img-fluid rounded mb-3" alt="Foto">
                                                                <p class="text-start p-3 bg-light rounded">
                                                                    "{{ $visit->notes }}"</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Belum ada data
                                                kunjungan lapangan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="store">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Petugas Toko</th>
                                        <th>Customer / Tamu</th>
                                        <th>Layanan / Hasil</th>
                                        <th>Foto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($storeVisits as $visit)
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $visit->created_at->format('d M') }}</div>
                                                <small class="text-muted">{{ $visit->created_at->format('H:i') }}</small>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-info text-dark rounded-pill">{{ $visit->user->name }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $visit->customer->name }}</div>
                                                <small class="text-success fw-bold">Store Visit</small>
                                            </td>
                                            <td style="width: 40%;">
                                                {{ $visit->notes }}
                                            </td>
                                            <td>
                                                @if ($visit->photo_path)
                                                    <a href="{{ asset('storage/' . $visit->photo_path) }}"
                                                        target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-image"></i>
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Belum ada data layanan
                                                toko.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
