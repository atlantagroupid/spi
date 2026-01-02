@extends('layouts.app')

@section('title', 'Detail Pembayaran')

@section('content')
<div class="container-fluid px-0 px-md-3">

    {{-- HEADER --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('receivables.index') }}" class="btn btn-secondary btn-sm rounded-circle d-md-none">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h4 fw-bold text-gray-800 mb-0">Invoice: {{ $order->invoice_number }}</h1>
            </div>
            <p class="text-muted mb-0 small ms-md-0 ms-5">{{ $order->customer->name }}</p>
        </div>
        <div class="ms-5 ms-md-0">
            @if ($order->payment_status == 'paid')
                <span class="badge bg-success px-3 py-2 rounded-pill">LUNAS</span>
            @elseif($order->payment_status == 'partial')
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">CICILAN</span>
            @else
                <span class="badge bg-danger px-3 py-2 rounded-pill">BELUM DIBAYAR</span>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- KOLOM KIRI: SUMMARY & INPUT FORM --}}
        <div class="col-lg-4 order-1 order-lg-1">

            {{-- 1. RINGKASAN TAGIHAN --}}
            <div class="card shadow-sm mb-4 border-0 border-start border-4 border-primary rounded-3">
                <div class="card-body p-4">
                    <h6 class="text-muted small fw-bold mb-3 text-uppercase">Ringkasan Tagihan</h6>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-dark">Total Invoice</span>
                        <span class="fw-bold">Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Sudah Masuk</span>
                        <span class="fw-bold text-success">Rp {{ number_format($paidAmount, 0, ',', '.') }}</span>
                    </div>

                    @if ($pendingAmount > 0)
                        <div class="alert alert-warning py-2 px-2 small mb-2 border-warning rounded">
                            <div class="d-flex justify-content-between text-warning-emphasis">
                                <span><i class="bi bi-hourglass-split me-1"></i> Pending Approval</span>
                                <span class="fw-bold">Rp {{ number_format($pendingAmount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark small">SISA TAGIHAN</span>
                        <span class="h4 fw-bold text-danger mb-0">Rp {{ number_format($remaining, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- 2. FORM INPUT PEMBAYARAN --}}
            @if (in_array(Auth::user()->role, ['finance', 'manager_operasional']) && $remaining > 0)
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white fw-bold py-3">
                        <i class="bi bi-wallet2 me-2 text-primary"></i> Input Pembayaran
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('receivables.store', $order->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf @method('PUT')
                            <input type="hidden" name="order_id" value="{{ $order->id }}">

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Nominal (Rp)</label>
                                <input type="number" name="amount" class="form-control form-control-lg fw-bold text-primary" placeholder="0" max="{{ $remaining }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Tanggal Terima</label>
                                <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Metode</label>
                                <select name="payment_method" class="form-select">
                                    <option value="Transfer">Transfer Bank</option>
                                    <option value="Cash">Tunai (Cash)</option>
                                    <option value="Giro">Giro / Cek</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Bukti Foto</label>
                                <input type="file" name="proof_file" class="form-control" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm rounded-pill">
                                Simpan Pembayaran
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- KOLOM KANAN: RIWAYAT PEMBAYARAN --}}
        <div class="col-lg-8 order-2 order-lg-2">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Riwayat Pembayaran</h6>
                </div>

                {{-- DESKTOP TABLE --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light text-secondary small">
                            <tr>
                                <th class="ps-4">Tgl & Input</th>
                                <th>Nominal</th>
                                <th>Metode</th>
                                <th>Bukti</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->paymentLogs as $log)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ date('d M Y', strtotime($log->payment_date)) }}</div>
                                        <small class="text-muted">{{ $log->user->name ?? 'User' }}</small>
                                    </td>
                                    <td><span class="fw-bold text-dark">Rp {{ number_format($log->amount, 0, ',', '.') }}</span></td>
                                    <td>{{ $log->payment_method }}</td>
                                    <td>
                                        @if ($log->proof_file)
                                            <a href="{{ asset('storage/payment_proofs/' . $log->proof_file) }}" target="_blank" class="btn btn-sm btn-light border"><i class="bi bi-image"></i></a>
                                        @else - @endif
                                    </td>
                                    <td class="text-center">
                                        @include('receivables.partials.status_badge', ['log' => $log])
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE CARD LIST --}}
                <div class="d-md-none p-3">
                    @forelse($order->paymentLogs as $log)
                        <div class="card mb-3 border bg-light shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-bold text-dark">{{ date('d M Y', strtotime($log->payment_date)) }}</span>
                                    <span class="badge bg-white text-dark border">{{ $log->payment_method }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <div class="small text-muted mb-1">Nominal Bayar</div>
                                        <h5 class="fw-bold text-primary mb-0">Rp {{ number_format($log->amount, 0, ',', '.') }}</h5>
                                    </div>
                                    @if ($log->proof_file)
                                        <a href="{{ asset('storage/payment_proofs/' . $log->proof_file) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-image"></i> Bukti</a>
                                    @endif
                                </div>
                                <hr class="my-2 border-secondary opacity-10">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Input: {{ $log->user->name ?? '-' }}</small>
                                    @include('receivables.partials.status_badge', ['log' => $log])
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">Belum ada riwayat.</div>
                    @endforelse
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
