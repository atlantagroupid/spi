@extends('layouts.app')

@section('title', 'Plafon Kredit Saya')

@section('content')
<div class="container-fluid px-0 px-md-3">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3 mb-md-4">
        <div>
            <p class="text-muted small mb-0 d-none d-md-block">Kelola kuota kredit untuk transaksi.</p>
        </div>
    </div>

    <div class="row g-4">

        {{-- KOLOM KIRI: INFO & FORM PENGAJUAN --}}
        <div class="col-xl-4 col-md-5">

            {{-- 1. KARTU SISA LIMIT --}}
            <div class="card shadow-sm border-0 bg-primary text-white mb-4 rounded-3 overflow-hidden position-relative">
                {{-- Hiasan Background --}}
                <div class="position-absolute top-0 end-0 opacity-25 p-3">
                    <i class="bi bi-wallet2" style="font-size: 5rem;"></i>
                </div>

                <div class="card-body text-center py-5 position-relative z-1">
                    <h6 class="text-white-50 text-uppercase fw-bold mb-2" style="letter-spacing: 1px;">Sisa Limit Saat Ini</h6>
                    <h2 class="display-5 fw-bold mb-3">
                        {{-- FIX: Menggunakan variabel hasil hitungan Controller (Limit - Hutang) --}}
                        Rp {{ number_format($remainingLimit ?? 0, 0, ',', '.') }}
                    </h2>
                    <p class="small text-white-50 mb-0 px-3">
                        Total Pagu Kredit: Rp {{ number_format($user->credit_limit_quota, 0, ',', '.') }}
                        <br><span class="opacity-75">(Limit berkurang jika ada tagihan belum lunas)</span>
                    </p>
                </div>
            </div>

            {{-- 2. FORM PENGAJUAN --}}
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-danger">
                        <i class="bi bi-arrow-up-circle me-2"></i>Minta Tambahan Limit
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('quotas.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Jumlah Diminta</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 fw-bold text-secondary">Rp</span>
                                <input type="number" name="amount" min="1" class="form-control border-start-0 fw-bold text-dark" required placeholder="0">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Alasan Kebutuhan</label>
                            <textarea name="reason" class="form-control" rows="3" required placeholder="Contoh: Ada project besar di Toko A..."></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger fw-bold py-2 shadow-sm rounded-pill">
                                <i class="bi bi-send-fill me-2"></i> Kirim Pengajuan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: RIWAYAT PENGAJUAN --}}
        <div class="col-xl-8 col-md-7">
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-dark">Riwayat Pengajuan</h6>
                </div>

                {{-- DESKTOP TABLE --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3">Tanggal</th>
                                <th>Jumlah</th>
                                <th>Alasan</th>
                                <th>Status</th>
                                <th>Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myRequests as $req)
                                <tr>
                                    <td class="ps-4 fw-bold text-dark">{{ $req->created_at->format('d/m/Y') }}</td>
                                    <td class="fw-bold text-primary">Rp {{ number_format($req->amount, 0, ',', '.') }}</td>
                                    <td class="small text-muted">{{ Str::limit($req->reason, 40) }}</td>
                                    <td>
                                        @if($req->status == 'approved')
                                            <span class="badge bg-success rounded-pill px-3">Disetujui</span>
                                        @elseif($req->status == 'rejected')
                                            <span class="badge bg-danger rounded-pill px-3">Ditolak</span>
                                        @else
                                            <span class="badge bg-warning text-dark rounded-pill px-3">Pending</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">
                                        {{ $req->approver->name ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">Belum ada riwayat pengajuan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE CARD LIST --}}
                <div class="d-md-none bg-light p-3">
                    @forelse($myRequests as $req)
                        <div class="card mb-3 border shadow-sm rounded-3">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark">{{ $req->created_at->format('d M Y') }}</span>
                                    @if($req->status == 'approved')
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif($req->status == 'rejected')
                                        <span class="badge bg-danger">Ditolak</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </div>
                                <h5 class="fw-bold text-primary mb-1">Rp {{ number_format($req->amount, 0, ',', '.') }}</h5>
                                <p class="text-muted small mb-2 fst-italic">"{{ $req->reason }}"</p>

                                @if($req->approver)
                                    <div class="border-top pt-2 mt-2 text-muted small">
                                        <i class="bi bi-check-circle-fill me-1 text-success"></i> Oleh: {{ $req->approver->name }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">Belum ada riwayat.</div>
                    @endforelse
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
