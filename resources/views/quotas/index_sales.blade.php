@extends('layouts.app')

@section('title', 'Plafon Kredit Saya')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Plafon Kredit Saya</h1>
    </div>

    <div class="row">
        {{-- KIRI: INFO & FORM --}}
        <div class="col-xl-4 col-md-5 mb-4">
            {{-- Kartu Info Limit --}}
            <div class="card shadow mb-4 border-bottom-primary">
                <div class="card-body text-center py-5">
                    <h5 class="text-muted mb-3">Limit Kredit Saat Ini</h5>
                    <h2 class="display-5 fw-bold text-primary">
                        Rp {{ number_format($user->credit_limit_quota, 0, ',', '.') }}
                    </h2>
                    <p class="small text-muted">Gunakan limit ini untuk melakukan transaksi TOP/Kredit.</p>
                </div>
            </div>

            {{-- Form Pengajuan --}}
            <div class="card shadow">
                <div class="card-header py-3 bg-danger text-white">
                    <h6 class="m-0 fw-bold"><i class="bi bi-arrow-up-circle me-1"></i> Ajukan Tambahan Limit</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('quotas.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Jumlah Diminta (Rp)</label>
                            <input type="number" name="amount" class="form-control" required placeholder="Contoh: 5000000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Alasan</label>
                            <textarea name="reason" class="form-control" rows="3" required placeholder="Jelaskan alasan kebutuhan limit..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Kirim Pengajuan</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- KANAN: RIWAYAT PENGAJUAN --}}
        <div class="col-xl-8 col-md-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Riwayat Pengajuan Limit</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Disetujui Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($myRequests as $req)
                                    <tr>
                                        <td>{{ $req->created_at->format('d/m/Y') }}</td>
                                        <td class="fw-bold">Rp {{ number_format($req->amount, 0, ',', '.') }}</td>
                                        <td class="small">{{ Str::limit($req->reason, 30) }}</td>
                                        <td>
                                            @if($req->status == 'approved')
                                                <span class="badge bg-success">Disetujui</span>
                                            @elseif($req->status == 'rejected')
                                                <span class="badge bg-danger">Ditolak</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($req->approver)
                                                {{ $req->approver->name }}<br>
                                                <small class="text-muted">{{ $req->approver->role }}</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">Belum ada riwayat pengajuan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
