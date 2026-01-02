@extends('layouts.app')

@section('title', 'Manajemen Plafon Kredit')

@section('content')
<div class="container-fluid px-0 px-md-3 pb-5">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-gray-800 mb-1">Manajemen Limit</h4>
            <p class="text-muted small mb-0 d-none d-md-block">Atur plafon kredit untuk Sales & Manager Bisnis.</p>
        </div>
    </div>

    {{-- ALERT KHUSUS MANAGER BISNIS (DATA FRESH) --}}
    @if(Auth::user()->role == 'manager_bisnis')
        <div class="card border-0 shadow-sm mb-4 bg-info bg-opacity-10 rounded-3">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="bg-white p-3 rounded-circle text-info me-3 shadow-sm">
                        <i class="bi bi-wallet2 fs-3"></i>
                    </div>
                    <div>
                        <h6 class="text-info fw-bold mb-1 text-uppercase small">Limit Anda Saat Ini</h6>
                        <h4 class="mb-0 fw-bold text-dark">
                            {{-- FIX: Gunakan fresh() agar data selalu update realtime dari DB --}}
                            Rp {{ number_format(auth()->user()->fresh()->credit_limit_quota, 0, ',', '.') }}
                        </h4>
                        <small class="text-muted">Limit ini berkurang saat Anda memberikannya ke Sales.</small>
                    </div>
                </div>
                <button class="btn btn-info text-white fw-bold shadow-sm w-100 w-md-auto rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalRequestOps">
                    <i class="bi bi-plus-circle me-2"></i> Minta Tambahan
                </button>
            </div>
        </div>

        {{-- MODAL REQUEST KE OPS --}}
        <div class="modal fade" id="modalRequestOps" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('quotas.store') }}" method="POST" class="w-100">
                    @csrf
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title fw-bold">Ajukan Limit ke Manager Operasional</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="fw-bold small text-muted">Jumlah (Rp)</label>
                                <input type="number" name="amount" min="1" class="form-control fw-bold text-primary" required placeholder="0">
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold small text-muted">Alasan</label>
                                <textarea name="reason" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-info text-white fw-bold">Kirim</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- 1. DAFTAR PENGAJUAN PENDING (CARD LIST MOBILE) --}}
    <div class="card shadow-sm border-0 border-start border-4 border-warning mb-4 rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-dark d-flex align-items-center">
                <i class="bi bi-hourglass-split text-warning me-2 fs-5"></i> Permintaan Pending
                @if($pendingRequests->count() > 0)
                    <span class="badge bg-danger rounded-pill ms-2">{{ $pendingRequests->count() }}</span>
                @endif
            </h6>
        </div>

        {{-- DESKTOP TABLE --}}
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-4">Tanggal</th>
                        <th>Pemohon</th>
                        <th>Jumlah</th>
                        <th>Alasan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingRequests as $req)
                        <tr>
                            <td class="ps-4">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="fw-bold text-dark">{{ $req->user->name }}</span><br>
                                <small class="text-muted badge bg-light border text-dark">{{ $req->user->role }}</small>
                            </td>
                            <td class="fw-bold text-danger">Rp {{ number_format($req->amount, 0, ',', '.') }}</td>
                            <td class="small text-muted">{{ $req->reason }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <form action="{{ route('quotas.approve', $req->id) }}" method="POST" onsubmit="return confirm('Tolak?')">
                                        @csrf @method('PUT') <input type="hidden" name="action" value="reject">
                                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                    <form action="{{ route('quotas.approve', $req->id) }}" method="POST" onsubmit="return confirm('Setujui?')">
                                        @csrf @method('PUT') <input type="hidden" name="action" value="approve">
                                        <button class="btn btn-success btn-sm text-white"><i class="bi bi-check-lg"></i> Setuju</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada permintaan pending.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- MOBILE LIST --}}
        <div class="d-md-none bg-light p-3">
            @forelse($pendingRequests as $req)
                <div class="card mb-3 border shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-warning text-dark">Pending</span>
                            <small class="text-muted">{{ $req->created_at->format('d M H:i') }}</small>
                        </div>
                        <h6 class="fw-bold text-dark mb-0">{{ $req->user->name }}</h6>
                        <small class="text-muted d-block mb-2">{{ $req->user->role }}</small>

                        <div class="bg-white border rounded p-2 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="fw-bold text-uppercase text-muted">Minta:</small>
                                <span class="fw-bold text-danger">Rp {{ number_format($req->amount, 0, ',', '.') }}</span>
                            </div>
                            <small class="d-block mt-1 fst-italic text-muted">"{{ $req->reason }}"</small>
                        </div>

                        <div class="d-flex gap-2">
                            <form action="{{ route('quotas.approve', $req->id) }}" method="POST" class="flex-fill" onsubmit="return confirm('Tolak?')">
                                @csrf @method('PUT') <input type="hidden" name="action" value="reject">
                                <button class="btn btn-outline-danger w-100 fw-bold">Tolak</button>
                            </form>
                            <form action="{{ route('quotas.approve', $req->id) }}" method="POST" class="flex-fill" onsubmit="return confirm('Setujui?')">
                                @csrf @method('PUT') <input type="hidden" name="action" value="approve">
                                <button class="btn btn-success w-100 fw-bold text-white">Setujui</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted">Tidak ada permintaan.</div>
            @endforelse
        </div>
    </div>

    {{-- 2. MANAGEMENT MANUAL (KHUSUS OPS) --}}
    @if(Auth::user()->role == 'manager_operasional')
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-sliders me-2"></i>Atur Limit Manual</h6>
            </div>

            {{-- DESKTOP --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">User</th>
                            <th>Role</th>
                            <th>Limit Saat Ini</th>
                            <th width="35%">Update Limit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allUsers as $u)
                            <tr>
                                <td class="ps-4 fw-bold">{{ $u->name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $u->role }}</span></td>
                                <td class="fw-bold text-dark">Rp {{ number_format($u->credit_limit_quota, 0, ',', '.') }}</td>
                                <td>
                                    <form action="{{ route('quotas.update', $u->id) }}" method="POST" class="d-flex gap-2">
                                        @csrf @method('PUT')
                                        <input type="number" name="credit_limit_quota" class="form-control form-control-sm"
                                               value="{{ $u->credit_limit_quota }}" min="0">
                                        <button type="submit" class="btn btn-primary btn-sm px-3">
                                            <i class="bi bi-save"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MOBILE --}}
            <div class="d-md-none bg-light p-3">
                @foreach($allUsers as $u)
                    <div class="card mb-3 border shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-dark mb-0">{{ $u->name }}</h6>
                                <span class="badge bg-secondary" style="font-size: 0.65rem;">{{ $u->role }}</span>
                            </div>

                            <form action="{{ route('quotas.update', $u->id) }}" method="POST">
                                @csrf @method('PUT')
                                <label class="small text-muted fw-bold">Limit (Rp)</label>
                                <div class="input-group">
                                    <input type="number" name="credit_limit_quota" class="form-control fw-bold text-primary"
                                           value="{{ $u->credit_limit_quota }}" min="0">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
