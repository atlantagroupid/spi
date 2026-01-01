@extends('layouts.app')

@section('title', 'Approval TOP')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-gray-800 mb-0">Approval TOP & Limit</h3>
    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                <i class="bi bi-file-earmark-check me-2"></i>Daftar Pengajuan TOP
            </h5>
            <span class="badge bg-white text-success fw-bold px-3 py-2 rounded-pill">
                Manager Operasional
            </span>
        </div>

        <div class="card-body">

            <div class="alert alert-light border shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-info-circle-fill text-success fs-4 me-3"></i>
                <div>
                    <strong>Penting:</strong> Periksa riwayat pembayaran customer sebelum menyetujui kenaikan limit.
                    <div class="small text-muted">Menyetujui kenaikan limit akan memotong kuota kredit pribadi Anda.</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary text-uppercase small fw-bold">
                        <tr>
                            <th class="py-3 ps-3">Tanggal & Sales</th>
                            <th class="py-3">Customer</th>
                            <th class="py-3">Pengajuan Baru</th>
                            <th class="py-3">Kondisi Lama</th>
                            <th class="py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submissions as $submission)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-dark">{{ $submission->created_at->format('d M Y') }}</div>
                                <div class="small text-muted">
                                    <i class="bi bi-person-fill me-1"></i>{{ $submission->sales->name ?? 'Sales' }}
                                </div>
                            </td>

                            <td>
                                <div class="fw-bold">{{ $submission->customer->name ?? '-' }}</div>
                                <span class="badge bg-light text-secondary border">
                                    Invoice Pending: Rp {{ number_format($submission->customer->debt ?? 0, 0, ',', '.') }}
                                </span>
                            </td>

                            <td>
                                @if($submission->submission_limit > 0)
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="badge bg-success me-2">LIMIT</span>
                                        <span class="fw-bold text-success">
                                            Rp {{ number_format($submission->submission_limit, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endif

                                @if($submission->submission_days > 0)
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-info text-dark me-2">TEMPO</span>
                                        <span class="fw-bold text-dark">
                                            {{ $submission->submission_days }} Hari
                                        </span>
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="small text-muted">
                                    Limit: Rp {{ number_format($submission->customer->credit_limit, 0, ',', '.') }}
                                </div>
                                <div class="small text-muted">
                                    Tempo: {{ $submission->customer->top_days }} Hari
                                </div>

                                @php
                                    $diff = $submission->submission_limit - $submission->customer->credit_limit;
                                @endphp
                                @if($submission->submission_limit > 0 && $diff > 0)
                                    <small class="text-danger fw-bold fst-italic mt-1 d-block">
                                        (Naik Rp {{ number_format($diff, 0, ',', '.') }})
                                    </small>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <form action="{{ route('top-submissions.approve', $submission->id) }}" method="POST"
                                          onsubmit="return confirm('Setujui pengajuan ini? Kuota Anda akan terpotong.');">
                                        @csrf
                                        @method('PUT') <button type="submit" class="btn btn-sm btn-success text-white shadow-sm" title="Setujui">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                    </form>

                                    <form action="{{ route('top-submissions.reject', $submission->id) }}" method="POST"
                                          onsubmit="return confirm('Tolak pengajuan ini?');">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm" title="Tolak">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-check-circle text-success" style="font-size: 4rem; opacity: 0.5;"></i>
                                    <h5 class="fw-bold text-muted mt-3">Tidak ada pengajuan pending</h5>
                                    <p class="text-muted small">Semua pengajuan TOP sudah diproses.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($submissions) && $submissions instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-3 px-3">
                    {{ $submissions->links() }}
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
