@extends('layouts.app')

@section('title', 'Detail Pembayaran')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-gray-800">Pembayaran Invoice: {{ $order->invoice_number }}</h1>
            <p class="text-muted mb-0">Customer: {{ $order->customer->name }}</p>
        </div>
        <div class="text-end">
            {{-- Status Pembayaran --}}
            @if ($order->payment_status == 'paid')
                <span class="badge bg-success fs-6 px-3 py-2">LUNAS</span>
            @elseif($order->payment_status == 'partial')
                <span class="badge bg-warning text-dark fs-6 px-3 py-2">CICILAN / PARSIAL</span>
            @else
                <span class="badge bg-danger fs-6 px-3 py-2">BELUM DIBAYAR</span>
            @endif
        </div>
    </div>

    <div class="row">
        {{-- KIRI: FORM INPUT (KHUSUS KASIR) --}}
        <div class="col-md-4">

            {{-- Info Tagihan --}}
            <div class="card shadow-sm mb-4 border-0 border-start border-4 border-primary">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold mb-3">RINGKASAN TAGIHAN</h6>

                    {{-- 1. TOTAL TAGIHAN --}}
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-dark">Total Invoice</span>
                        <span class="fw-bold">Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                    </div>

                    {{-- 2. SUDAH DIBAYAR (SAH) --}}
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Sudah Masuk</span>
                        <span class="fw-bold text-success">Rp {{ number_format($paidAmount, 0, ',', '.') }}</span>
                    </div>

                    {{-- 3. SEDANG DIVERIFIKASI (PENDING) --}}
                    @if ($pendingAmount > 0)
                        <div class="alert alert-warning py-2 px-2 small mb-2 border-warning">
                            <div class="d-flex justify-content-between text-warning-emphasis">
                                <span><i class="bi bi-hourglass-split me-1"></i> Menunggu Approval</span>
                                <span class="fw-bold">Rp {{ number_format($pendingAmount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif

                    <hr>

                    {{-- 4. SISA HUTANG --}}
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">SISA TAGIHAN</span>
                        <span class="h4 fw-bold text-danger mb-0">Rp {{ number_format($remaining, 0, ',', '.') }}</span>
                    </div>

                    @if ($pendingAmount > 0 && $remaining > 0)
                        <small class="text-muted fst-italic mt-2 d-block text-end">
                            *Sisa tagihan akan berkurang setelah Manager menyetujui pembayaran pending.
                        </small>
                    @endif
                </div>
            </div>

            {{-- Form Input --}}
            @if (in_array(Auth::user()->role, ['finance', 'manager_operasional']) && $remaining > 0)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold py-3">
                        <i class="bi bi-wallet2 me-2 text-primary"></i> Input Pembayaran Baru
                    </div>
                    <div class="card-body">
                        <form action="{{ route('receivables.store', $order->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nominal (Rp)</label>
                                <input type="number" name="amount"
                                    class="form-control form-control-lg fw-bold text-primary" placeholder="0"
                                    max="{{ $remaining }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Tanggal Terima Uang</label>
                                <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Metode Pembayaran</label>
                                <select name="payment_method" class="form-select">
                                    <option value="Cash">Tunai (Cash)</option>
                                    <option value="Transfer">Transfer Bank</option>
                                    <option value="Giro">Giro / Cek</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Bukti Foto / Struk</label>
                                <input type="file" name="proof_file" class="form-control" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                                <i class="bi bi-save me-2"></i> Simpan Pembayaran
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- KANAN: RIWAYAT & APPROVAL (KHUSUS MANAGER) --}}
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Riwayat & Status Approval</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Tgl & Penginput</th>
                                <th>Nominal</th>
                                <th>Metode</th>
                                <th>Bukti</th>
                                <th class="text-center">Status Manager</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->paymentLogs as $log)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ date('d M Y', strtotime($log->payment_date)) }}</div>
                                        <small class="text-muted">Input: {{ $log->user->name ?? 'User' }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark">Rp
                                            {{ number_format($log->amount, 0, ',', '.') }}</span>
                                    </td>
                                    <td>{{ $log->payment_method }}</td>
                                    <td>
                                        @if ($log->proof_file)
                                            <a href="{{ asset('storage/payment_proofs/' . $log->proof_file) }}"
                                                target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-image"></i> Lihat
                                            </a>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{-- 1. SUDAH DIAPPROVE --}}
                                        @if ($log->status == 'approved')
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>
                                                Diterima</span>

                                            {{-- 2. DITOLAK --}}
                                        @elseif($log->status == 'rejected')
                                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Ditolak</span>

                                            {{-- 3. PENDING (BUTUH AKSI MANAGER) --}}
                                        @elseif($log->status == 'pending')
                                            @if (in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional']))
                                                <div class="btn-group shadow-sm">
                                                    <form action="{{ route('payments.approve', $log->id) }}" method="POST"
                                                        onsubmit="return confirm('Validasi pembayaran ini?');">
                                                        @csrf
                                                        @method('PUT')
                                                        <button class="btn btn-sm btn-success" title="Terima"><i
                                                                class="bi bi-check-lg"></i></button>
                                                    </form>
                                                    <form action="{{ route('payments.reject', $log->id) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Tolak pembayaran ini?');">
                                                        @csrf
                                                        @method('PUT')
                                                        <button class="btn btn-sm btn-danger" title="Tolak"><i
                                                                class="bi bi-x-lg"></i></button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="badge bg-warning text-dark"><i
                                                        class="bi bi-hourglass-split"></i> Menunggu Manager</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada data pembayaran.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
