@extends('layouts.app')

@section('title', 'Daftar Customer TOP')

@section('content')
<div class="container-fluid px-0 px-md-3">

    {{-- HEADER DESKTOP --}}
    <div class="d-none d-md-flex align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1"><i class="bi bi-star-fill text-warning me-2"></i>Customer TOP</h3>
            <p class="text-muted mb-0 small">Pelanggan dengan fasilitas pembayaran tempo (Term of Payment).</p>
        </div>
    </div>

    {{-- HEADER MOBILE --}}
    <div class="d-md-none mb-3">
        <h5 class="fw-bold text-primary mb-1"><i class="bi bi-star-fill text-warning me-2"></i>Customer TOP</h5>
        <p class="text-muted small mb-0">List pelanggan Prioritas / Kredit.</p>
    </div>

    <div class="card shadow border-0 rounded-3 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3 ps-md-4 py-3">Nama Customer</th>
                            <th class="d-none d-md-table-cell">Alamat / Wilayah</th>
                            <th>Plafon Kredit</th>
                            <th class="d-none d-md-table-cell">Tenor</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td class="ps-3 ps-md-4">
                                <div class="fw-bold text-dark">{{ $customer->name }}</div>
                                <small class="text-muted d-block">{{ $customer->phone }}</small>
                                {{-- Info Tenor muncul kecil di HP --}}
                                <div class="d-md-none mt-1">
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">
                                        {{ $customer->top_days }} Hari
                                    </span>
                                </div>
                            </td>

                            {{-- Alamat (Desktop Only) --}}
                            <td class="d-none d-md-table-cell text-muted small">
                                {{ Str::limit($customer->address, 40) }}
                            </td>

                            <td>
                                <h6 class="text-primary fw-bold mb-0">
                                    Rp {{ number_format($customer->credit_limit, 0, ',', '.') }}
                                </h6>
                            </td>

                            {{-- Tenor (Desktop Only) --}}
                            <td class="d-none d-md-table-cell">
                                <span class="badge bg-info bg-opacity-10 text-dark border border-info rounded-pill">
                                    <i class="bi bi-calendar-check me-1"></i> {{ $customer->top_days }} Hari
                                </span>
                            </td>

                            <td class="text-center">
                                <span class="badge bg-success rounded-pill" style="font-size: 0.7rem;">Active</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-folder-x fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada customer TOP.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
