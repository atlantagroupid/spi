@extends('layouts.app')

@section('title', 'Monitoring Piutang')

@section('content')
    <div class="container-fluid px-0 px-md-3">

        {{-- HEADER & TOMBOL --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 mb-md-4 gap-3">
            <div class="d-none d-md-block">
                <h1 class="h3 mt-2 fw-bold text-gray-800">Monitoring Piutang</h1>
                <p class="text-muted small mb-0">Pantau jatuh tempo dan sisa tagihan customer.</p>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('receivables.completed') }}" class="btn btn-outline-success shadow-sm flex-fill flex-md-grow-0">
                    <i class="bi bi-archive me-1"></i> <span class="d-none d-sm-inline">Arsip Lunas</span><span class="d-sm-none">Lunas</span>
                </a>
                <a href="{{ route('receivables.printPdf') }}" class="btn btn-danger shadow-sm flex-fill flex-md-grow-0" target="_blank">
                    <i class="bi bi-file-pdf me-1"></i> <span class="d-none d-sm-inline">Cetak Laporan</span><span class="d-sm-none">PDF</span>
                </a>
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- TAMPILAN DESKTOP (TABEL) --}}
        {{-- ======================================================= --}}
        <div class="card shadow-sm border-0 d-none d-md-block rounded-3 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small">
                            <tr>
                                <th class="ps-4">Invoice</th>
                                <th>Customer</th>
                                <th>Total Tagihan</th>
                                <th>Sisa Tagihan</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                @php
                                    $paid = $invoice->paymentLogs->where('status', 'approved')->sum('amount');
                                    $pending = $invoice->paymentLogs->where('status', 'pending')->sum('amount');
                                    $remainingOfficial = $invoice->total_price - $paid;
                                    $remainingWithPending = $remainingOfficial - $pending;
                                @endphp

                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold text-primary">{{ $invoice->invoice_number }}</span><br>
                                        <small class="text-muted">{{ $invoice->created_at->format('d M Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $invoice->customer->name }}</div>
                                        <small class="text-muted">{{ $invoice->user->name ?? 'Tanpa Sales' }}</small>
                                    </td>
                                    <td>Rp {{ number_format($invoice->total_price, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="fw-bold text-danger">Rp {{ number_format($remainingOfficial, 0, ',', '.') }}</span>
                                        @if ($pending > 0)
                                            <div class="small text-warning fst-italic mt-1">
                                                <i class="bi bi-hourglass-split"></i> - Rp {{ number_format($pending, 0, ',', '.') }} (Proses)
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="{{ $invoice->due_date < now() ? 'text-danger fw-bold' : 'text-dark' }}">
                                            {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
                                        </div>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($invoice->due_date)->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @if ($remainingWithPending <= 0 && $pending > 0)
                                            <span class="badge bg-warning text-dark">Menunggu Approval</span>
                                        @elseif($invoice->due_date < now())
                                            <span class="badge bg-danger">Jatuh Tempo</span>
                                        @else
                                            <span class="badge bg-success">Aman</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('receivables.show', $invoice->id) }}" class="btn btn-sm btn-outline-primary me-1">Detail</a>
                                        @if (in_array(Auth::user()->role, ['finance', 'manager_operasional']) && $remainingOfficial > 0)
                                            <button type="button" class="btn btn-sm btn-success btn-pay"
                                                data-bs-toggle="modal" data-bs-target="#paymentModal"
                                                data-id="{{ $invoice->id }}"
                                                data-invoice="{{ $invoice->invoice_number }}"
                                                data-remaining="{{ $remainingOfficial }}">
                                                <i class="bi bi-cash"></i> Bayar
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- TAMPILAN MOBILE (CARD LIST) --}}
        {{-- ======================================================= --}}
        <div class="d-md-none">
            @forelse ($invoices as $invoice)
                @php
                    $paid = $invoice->paymentLogs->where('status', 'approved')->sum('amount');
                    $pending = $invoice->paymentLogs->where('status', 'pending')->sum('amount');
                    $remainingOfficial = $invoice->total_price - $paid;
                    $isOverdue = $invoice->due_date < now();
                @endphp
                <div class="card border-0 shadow-sm mb-3 rounded-3 position-relative overflow-hidden">
                    <div class="position-absolute top-0 bottom-0 start-0 bg-{{ $isOverdue ? 'danger' : 'warning' }}" style="width: 5px;"></div>
                    <div class="card-body p-3 ps-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-light text-dark border mb-1">{{ $invoice->invoice_number }}</span>
                                <h6 class="fw-bold text-dark mb-0">{{ $invoice->customer->name }}</h6>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block" style="font-size: 0.65rem;">Jatuh Tempo</small>
                                <span class="fw-bold {{ $isOverdue ? 'text-danger' : 'text-dark' }}" style="font-size: 0.8rem;">
                                    {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M') }}
                                </span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-end mt-3">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">Sisa Tagihan</small>
                                <h5 class="fw-bold text-danger mb-0">Rp {{ number_format($remainingOfficial, 0, ',', '.') }}</h5>
                                @if ($pending > 0)
                                    <small class="text-warning fst-italic" style="font-size: 0.7rem;">
                                        <i class="bi bi-hourglass-split"></i> Pending: {{ number_format($pending/1000, 0) }}k
                                    </small>
                                @endif
                            </div>
                            <div class="d-flex gap-1">
                                <a href="{{ route('receivables.show', $invoice->id) }}" class="btn btn-sm btn-outline-dark rounded-circle" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                                @if (in_array(Auth::user()->role, ['finance', 'manager_operasional']) && $remainingOfficial > 0)
                                    <button type="button" class="btn btn-sm btn-success btn-pay rounded-circle shadow-sm"
                                        style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;"
                                        data-bs-toggle="modal" data-bs-target="#paymentModal"
                                        data-id="{{ $invoice->id }}"
                                        data-invoice="{{ $invoice->invoice_number }}"
                                        data-remaining="{{ $remainingOfficial }}">
                                        <i class="bi bi-cash"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-check-circle fs-1 d-block mb-2 opacity-25"></i>
                    <div>Tidak ada tagihan aktif.</div>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-3 pb-5">
            {{ $invoices->links() }}
        </div>
    </div>

    {{-- MODAL INPUT PEMBAYARAN (SAMA SEPERTI SEBELUMNYA) --}}
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"> {{-- Centered di Mobile --}}
            <div class="modal-content border-0 shadow">
                <form id="paymentForm" action="" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="order_id" id="hiddenOrderId">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold">Input Pembayaran</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>Invoice:</strong> <span id="modalInvoice"></span>
                            </div>
                            <div class="d-flex justify-content-between text-danger mt-1">
                                <strong>Sisa Tagihan:</strong> <span id="modalRemaining"></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Jumlah Bayar (Rp)</label>
                            <input type="number" name="amount" class="form-control" required min="1" placeholder="Contoh: 500000">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Tanggal Bayar</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Metode Pembayaran</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Transfer">Transfer Bank</option>
                                <option value="Tunai">Tunai / Cash</option>
                                <option value="Giro">Giro / Cek</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Bukti Transfer</label>
                            <input type="file" name="proof_file" class="form-control" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success fw-bold shadow-sm">Simpan Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPT MODAL (SAMA) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var paymentModal = document.getElementById('paymentModal');
            if (paymentModal) {
                paymentModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var id = button.getAttribute('data-id');
                    var invoice = button.getAttribute('data-invoice');
                    var remaining = button.getAttribute('data-remaining');

                    var modalInvoice = paymentModal.querySelector('#modalInvoice');
                    var modalRemaining = paymentModal.querySelector('#modalRemaining');

                    if (modalInvoice) modalInvoice.textContent = invoice;
                    if (modalRemaining) modalRemaining.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(remaining);

                    var hiddenInput = paymentModal.querySelector('#hiddenOrderId');
                    if (hiddenInput) hiddenInput.value = id;

                    var form = paymentModal.querySelector('#paymentForm');
                    var baseUrl = "{{ route('receivables.pay', ['id' => ':id']) }}";
                    form.action = baseUrl.replace(':id', id);
                });
            }
        });
    </script>
@endsection
