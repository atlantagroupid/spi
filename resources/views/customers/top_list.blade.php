@extends('layouts.app')

@section('title', 'Daftar Customer TOP')

@section('content')
<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h3 class="fw-bold text-primary"><i class="bi bi-star-fill text-warning me-2"></i>Customer TOP</h3>
            <p class="text-muted mb-0">Daftar customer yang memiliki fasilitas pembayaran tempo (Term of Payment).</p>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Customer</th>
                            <th>Alamat / Wilayah</th>
                            <th>Plafon Kredit (Limit)</th>
                            <th>Tenor (Hari)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $customer->name }}</div>
                                <small class="text-muted">{{ $customer->phone }}</small>
                            </td>
                            <td>
                                {{ Str::limit($customer->address, 40) }}
                            </td>
                            <td>
                                <h6 class="text-primary fw-bold mb-0">
                                    Rp {{ number_format($customer->credit_limit, 0, ',', '.') }}
                                </h6>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark border">
                                    <i class="bi bi-calendar-check me-1"></i> {{ $customer->top_days }} Hari
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-success rounded-pill">Active</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-folder-x fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada customer dengan fasilitas TOP.
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
