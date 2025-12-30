@extends('layouts.app')

@section('title', 'Riwayat Approval')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Riwayat Approval</h1>
    </div>

    {{-- FILTER BAR --}}
    <div class="card shadow mb-4 border-bottom-primary">
        <div class="card-body py-3">
            <form action="{{ route('approvals.history') }}" method="GET" class="row g-3 align-items-end">

                {{-- 1. Input Tanggal --}}
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Filter Tanggal</label>
                    <input type="date" name="date" class="form-control form-control-sm"
                           value="{{ request('date') }}">
                </div>

                {{-- 2. Input Search --}}
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Cari Pengaju</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control"
                               placeholder="Nama sales / pengaju..."
                               value="{{ request('search') }}">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </div>

                {{-- 3. Tombol Reset & PDF --}}
                <div class="col-md-5 text-md-end">
                    @if(request('date') || request('search'))
                        <a href="{{ route('approvals.history') }}" class="btn btn-sm btn-secondary me-2">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    @endif

                    {{-- Link PDF membawa parameter filter yang sedang aktif --}}
                    <a href="{{ route('approvals.history.pdf', ['date' => request('date'), 'search' => request('search')]) }}"
                       class="btn btn-sm btn-danger shadow-sm">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Log Riwayat (Disetujui / Ditolak)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 12%">Waktu</th>
                            <th style="width: 15%">Pengaju</th>
                            <th style="width: 15%">Tipe & Aksi</th>
                            <th>Detail Data</th>
                            <th class="text-center" style="width: 10%">Status</th>
                            <th style="width: 15%">Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($histories as $item)
                            <tr>
                                {{-- 1. WAKTU --}}
                                <td>
                                    <span class="fw-bold">{{ $item->updated_at->format('d/m/Y') }}</span><br>
                                    <small class="text-muted">{{ $item->updated_at->format('H:i') }} WIB</small>
                                </td>

                                {{-- 2. PENGAJU --}}
                                <td>
                                    {{-- Cek jika ini TOP (karena relasinya beda: 'sales' bukan 'requester') --}}
                                    @if($item->history_type == 'TOP')
                                        <div class="fw-bold text-dark">{{ $item->sales->name ?? 'Sales' }}</div>
                                        <small class="text-muted">Sales</small>
                                    @else
                                        <div class="fw-bold text-dark">{{ $item->requester->name ?? 'User Terhapus' }}</div>
                                        <small class="text-muted text-capitalize">{{ $item->requester->role ?? '-' }}</small>
                                    @endif
                                </td>

                                {{-- 3. TIPE & AKSI --}}
                                <td>
                                    {{-- FIX: Gunakan history_type dari controller --}}
                                    <span class="badge bg-secondary mb-1">
                                        {{ $item->history_type == 'Product' ? 'Produk' : ($item->history_type == 'Order' ? 'Order' : $item->history_type) }}
                                    </span><br>

                                    @if($item->history_type == 'TOP')
                                         <span class="text-primary small fw-bold"><i class="bi bi-graph-up-arrow"></i> Limit Kredit</span>
                                    @elseif (isset($item->action) && $item->action == 'delete')
                                        <span class="text-danger small"><i class="bi bi-trash"></i> Hapus</span>
                                    @elseif(isset($item->action) && $item->action == 'create')
                                        <span class="text-success small"><i class="bi bi-plus-circle"></i> Baru</span>
                                    @elseif(isset($item->action) && $item->action == 'update')
                                        <span class="text-primary small"><i class="bi bi-pencil"></i> Edit Data</span>
                                    @elseif(isset($item->action) && str_contains($item->action, 'approve'))
                                        <span class="text-info small"><i class="bi bi-check-all"></i> Approval</span>
                                    @else
                                        <span class="text-muted small">{{ $item->action ?? '-' }}</span>
                                    @endif
                                </td>

                                {{-- 4. DETAIL DATA --}}
                                <td>
                                    {{-- A. LOGIKA TOP (LIMIT KREDIT) --}}
                                    @if($item->history_type == 'TOP')
                                        <div class="fw-bold">{{ $item->customer->name ?? '-' }}</div>
                                        <div class="small">
                                            Limit Pengajuan: <span class="fw-bold text-primary">Rp {{ number_format($item->submission_amount ?? 0, 0, ',', '.') }}</span>
                                        </div>

                                    {{-- B. LOGIKA CUSTOMER --}}
                                    @elseif($item->history_type == 'Customer')
                                         <div class="fw-bold">{{ $item->new_data['name'] ?? 'Pelanggan' }}</div>
                                         <small class="text-muted">Update Profil Pelanggan</small>

                                    {{-- C. LOGIKA PAYMENT (KEUANGAN) --}}
                                    @elseif($item->history_type == 'PaymentLog')
                                        <div class="fw-bold text-success">Pembayaran Masuk</div>
                                        <div class="small">
                                            Nominal: Rp {{ number_format($item->new_data['amount'] ?? 0, 0, ',', '.') }}
                                        </div>

                                    {{-- D. LOGIKA ORDER --}}
                                    @elseif($item->history_type == 'Order')
                                        <div class="fw-bold text-primary">
                                            {{ $item->new_data['invoice_number'] ?? '-' }}
                                        </div>
                                        <div class="small">
                                            Total: Rp {{ number_format($item->new_data['total_price'] ?? 0, 0, ',', '.') }}
                                        </div>

                                    {{-- E. LOGIKA PRODUK (UPDATE) --}}
                                    @elseif ($item->history_type == 'Product' && isset($item->action) && $item->action == 'update' && $item->new_data)
                                        <div class="fw-bold border-bottom pb-1 mb-1">
                                            {{ $item->original_data['name'] ?? '-' }}
                                        </div>
                                        <ul class="list-unstyled small mb-0">
                                            @foreach ($item->new_data as $key => $val)
                                                @if (in_array($key, ['updated_at', 'created_at', 'id', 'user_id', 'slug', 'image', 'name'])) @continue @endif
                                                @php
                                                    $oldVal = $item->original_data[$key] ?? '-';
                                                    if ($oldVal == $val) continue;
                                                    // Format Rupiah
                                                    if (str_contains($key, 'price') || str_contains($key, 'cost')) {
                                                        $oldDisplay = is_numeric($oldVal) ? 'Rp ' . number_format($oldVal, 0, ',', '.') : $oldVal;
                                                        $newDisplay = is_numeric($val) ? 'Rp ' . number_format($val, 0, ',', '.') : $val;
                                                    } else {
                                                        $oldDisplay = $oldVal;
                                                        $newDisplay = $val;
                                                    }
                                                @endphp
                                                <li class="d-flex align-items-start text-muted mb-1">
                                                    <span class="me-1 text-capitalize fw-bold" style="font-size: 0.85em; min-width: 80px;">
                                                        {{ str_replace('_', ' ', $key) }}:
                                                    </span>
                                                    <div class="d-flex flex-wrap">
                                                        <span class="text-danger text-decoration-line-through me-1">{{ $oldDisplay }}</span>
                                                        <i class="bi bi-arrow-right mx-1"></i>
                                                        <span class="text-success fw-bold">{{ $newDisplay }}</span>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>

                                    {{-- F. LOGIKA PRODUK (BARU) --}}
                                    @elseif($item->history_type == 'Product' && isset($item->action) && $item->action == 'create')
                                        <div class="fw-bold text-success">{{ $item->new_data['name'] ?? '-' }}</div>
                                        <div class="small text-muted">
                                            Stok: {{ $item->new_data['stock'] ?? 0 }} | Harga: Rp {{ number_format($item->new_data['price'] ?? 0, 0, ',', '.') }}
                                        </div>

                                    {{-- G. LOGIKA PRODUK (HAPUS) --}}
                                    @elseif($item->history_type == 'Product' && isset($item->action) && $item->action == 'delete')
                                        <div class="fw-bold text-danger">{{ $item->original_data['name'] ?? '-' }}</div>
                                        <div class="small text-danger bg-danger bg-opacity-10 p-1 rounded">
                                            <i class="bi bi-exclamation-circle"></i> Data dihapus permanen.
                                        </div>

                                    {{-- DEFAULT --}}
                                    @else
                                        -
                                    @endif
                                </td>

                                {{-- 5. STATUS --}}
                                <td class="text-center">
                                    @if ($item->status == 'approved')
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Disetujui</span>
                                    @elseif($item->status == 'rejected')
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Ditolak</span>
                                        @if ($item->reason)
                                            <div class="small text-danger mt-1 fst-italic">"{{ $item->reason }}"</div>
                                        @endif
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>

                                {{-- 6. OLEH (APPROVER) --}}
                                <td>
                                    @if ($item->approver)
                                        <div class="fw-bold text-dark">{{ $item->approver->name }}</div>
                                        <small class="text-muted">{{ $item->approver->role }}</small>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-clock-history fs-1 mb-3 d-block"></i>
                                    Belum ada riwayat approval.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-3">
                {{ $histories->links() }}
            </div>
        </div>
    </div>
@endsection
