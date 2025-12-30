@extends('layouts.app')

@section('title', 'Manajemen Plafon Kredit')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Plafon Kredit (Credit Limit)</h1>
    </div>

    {{-- ALERT INFO LIMIT PRIBADI + TOMBOL REQUEST (Khusus Manager Bisnis) --}}
    @if(Auth::user()->role == 'manager_bisnis')
        <div class="card shadow-sm mb-4 border-left-info">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="text-info fw-bold mb-1">
                        <i class="bi bi-wallet2 me-2"></i> Kolam Kredit Anda
                    </h5>
                    <div class="h4 mb-0 fw-bold text-gray-800">
                        Rp {{ number_format(Auth::user()->credit_limit_quota, 0, ',', '.') }}
                    </div>
                    <small class="text-muted">Limit ini berkurang saat Anda memberikannya ke Sales.</small>
                </div>
                <div>
                    <button class="btn btn-info text-white fw-bold" data-bs-toggle="modal" data-bs-target="#modalRequestOps">
                        <i class="bi bi-plus-circle"></i> Minta Tambahan ke Ops
                    </button>
                </div>
            </div>
        </div>

        {{-- MODAL REQUEST KE OPS --}}
        <div class="modal fade" id="modalRequestOps" tabindex="-1">
            <div class="modal-dialog">
                <form action="{{ route('quotas.store') }}" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title">Ajukan Limit ke Manager Operasional</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>Jumlah Diminta (Rp)</label>
                                <input type="number" name="amount" class="form-control" required placeholder="Contoh: 100000000">
                            </div>
                            <div class="mb-3">
                                <label>Alasan</label>
                                <textarea name="reason" class="form-control" rows="3" required placeholder="Alasan..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-info text-white">Kirim Pengajuan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- 1. DAFTAR PENGAJUAN PENDING (APPROVAL) --}}
    <div class="card shadow mb-4 border-left-warning">
        <div class="card-header py-3 bg-warning bg-opacity-10">
            <h6 class="m-0 fw-bold text-dark">Permintaan Tambahan Limit (Pending)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Pemohon</th>
                            <th>Jumlah Diminta</th>
                            <th>Alasan</th>
                            <th class="text-center" width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingRequests as $req)
                            <tr>
                                <td>{{ $req->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="fw-bold">{{ $req->user->name }}</span><br>
                                    <small class="text-muted">{{ $req->user->role }}</small>
                                </td>
                                <td class="fw-bold text-danger">
                                    Rp {{ number_format($req->amount, 0, ',', '.') }}
                                </td>
                                <td>{{ $req->reason }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        {{-- Form Reject --}}
                                        <form action="{{ route('quotas.approve', $req->id) }}" method="POST">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tolak pengajuan ini?')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>

                                        {{-- Form Approve --}}
                                        <form action="{{ route('quotas.approve', $req->id) }}" method="POST">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Setujui dan transfer limit?')">
                                                <i class="bi bi-check-lg"></i> Setuju
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Tidak ada permintaan pending.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 2. TABEL MANAJEMEN MANUAL (KHUSUS MANAGER OPERASIONAL) --}}
    @if(Auth::user()->role == 'manager_operasional')
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary">Atur Limit Manual (Semua User)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%">No</th>
                                <th>Role</th>
                                <th>Nama User</th>
                                <th>Limit Saat Ini</th>
                                <th width="30%">Update Limit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allUsers as $u)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span class="badge bg-secondary">{{ $u->role }}</span></td>
                                    <td class="fw-bold">{{ $u->name }}</td>
                                    <td>Rp {{ number_format($u->credit_limit_quota, 0, ',', '.') }}</td>
                                    <td>
                                        <form action="{{ route('quotas.update', $u->id) }}" method="POST" class="d-flex gap-2">
                                            @csrf @method('PUT')
                                            <input type="number" name="credit_limit_quota" class="form-control form-control-sm"
                                                   value="{{ $u->credit_limit_quota }}" min="0">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="bi bi-save"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
