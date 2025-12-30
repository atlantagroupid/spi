@extends('layouts.app')

@section('title', 'Monitoring Piutang')

@section('content')
    <div class="container-fluid px-4">
        <h1 class="mt-4">Monitoring Piutang & Jatuh Tempo</h1>

        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('receivables.completed') }}" class="btn btn-outline-success me-2">
                <i class="bi bi-archive"></i> Arsip Lunas
            </a>
            <a href="{{ route('receivables.printPdf') }}" class="btn btn-danger" target="_blank">
                <i class="bi bi-file-pdf"></i> Cetak Laporan
            </a>
        </div>

        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Total Tagihan</th>
                                <th>Sisa Tagihan</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                @php
                                    // Hitung Pembayaran
                                    $paid = $invoice->paymentLogs->where('status', 'approved')->sum('amount');
                                    $pending = $invoice->paymentLogs->where('status', 'pending')->sum('amount');
                                    $remainingOfficial = $invoice->total_price - $paid;
                                    $remainingWithPending = $remainingOfficial - $pending;
                                @endphp

                                <tr>
                                    {{-- 1. Invoice --}}
                                    <td>
                                        <span class="fw-bold text-primary">{{ $invoice->invoice_number }}</span><br>
                                        <small class="text-muted">{{ $invoice->created_at->format('d M Y') }}</small>
                                    </td>

                                    {{-- 2. Customer --}}
                                    <td>
                                        <div class="fw-bold">{{ $invoice->customer->name }}</div>
                                        <small class="text-muted">{{ $invoice->user->name ?? 'Tanpa Sales' }}</small>
                                    </td>

                                    {{-- 3. Total Tagihan --}}
                                    <td>
                                        Rp {{ number_format($invoice->total_price, 0, ',', '.') }}
                                    </td>

                                    {{-- 4. Sisa Tagihan (Logic Pending) --}}
                                    <td>
                                        <span class="fw-bold text-danger">
                                            Rp {{ number_format($remainingOfficial, 0, ',', '.') }}
                                        </span>
                                        @if ($pending > 0)
                                            <div class="small text-warning fst-italic mt-1">
                                                <i class="bi bi-hourglass-split"></i> - Rp
                                                {{ number_format($pending, 0, ',', '.') }} (Proses)
                                            </div>
                                        @endif
                                    </td>

                                    {{-- 5. Jatuh Tempo (SUDAH DIPERBAIKI JADI TANGGAL) --}}
                                    <td>
                                        <div class="{{ $invoice->due_date < now() ? 'text-danger fw-bold' : 'text-dark' }}">
                                            {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
                                        </div>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($invoice->due_date)->diffForHumans() }}
                                        </small>
                                    </td>

                                    {{-- 6. Status --}}
                                    <td>
                                        @if ($remainingWithPending <= 0 && $pending > 0)
                                            <span class="badge bg-warning text-dark">Menunggu Approval</span>
                                        @elseif($invoice->due_date < now())
                                            <span class="badge bg-danger">Jatuh Tempo</span>
                                        @else
                                            <span class="badge bg-success">Aman</span>
                                        @endif
                                    </td>

                                    {{-- 7. Aksi (Tombol Bayar Kembali Ada) --}}
                                    <td class="text-end">
                                        <a href="{{ route('receivables.show', $invoice->id) }}"
                                            class="btn btn-sm btn-outline-primary me-1">
                                            Detail
                                        </a>

                                        {{-- Tombol Bayar: Muncukan Modal --}}
                                        @if (in_array(Auth::user()->role, ['finance', 'manager_operasional']))
                                            @if ($remainingOfficial > 0)
                                                <button type="button" class="btn btn-sm btn-success btn-pay"
                                                    data-bs-toggle="modal" data-bs-target="#paymentModal"
                                                    data-id="{{ $invoice->id }}"
                                                    data-invoice="{{ $invoice->invoice_number }}"
                                                    data-remaining="{{ $remainingOfficial }}">
                                                    <i class="bi bi-cash"></i> Bayar
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-3">
                    {{ $invoices->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL INPUT PEMBAYARAN --}}
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="paymentForm" action="" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="order_id" id="hiddenOrderId">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Input Pembayaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Info Invoice --}}
                        <div class="alert alert-light border mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>Invoice:</strong>
                                <span id="modalInvoice"></span>
                            </div>
                            <div class="d-flex justify-content-between text-danger mt-1">
                                <strong>Sisa Tagihan:</strong>
                                <span id="modalRemaining"></span>
                            </div>
                        </div>

                        {{-- Input Nominal --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jumlah Bayar (Rp)</label>
                            <input type="number" name="amount" class="form-control" required min="1"
                                placeholder="Contoh: 500000">
                        </div>

                        {{-- Tanggal Bayar --}}
                        <div class="mb-3">
                            <label class="form-label">Tanggal Bayar</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}"
                                required>
                        </div>

                        {{-- Metode Bayar --}}
                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Transfer">Transfer Bank</option>
                                <option value="Tunai">Tunai / Cash</option>
                                <option value="Giro">Giro / Cek</option>
                            </select>
                        </div>

                        {{-- Upload Bukti --}}
                        <div class="mb-3">
                            <label class="form-label">Bukti Transfer (Struk)</label>
                            <input type="file" name="proof_file" class="form-control" accept="image/*">
                            <div class="form-text small">Format: JPG, PNG. Maks 2MB.</div>
                        </div>

                        {{-- Catatan --}}
                        <div class="mb-3">
                            <label class="form-label">Catatan (Opsional)</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPT UNTUK MODAL --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var paymentModal = document.getElementById('paymentModal');

            if (paymentModal) {
                paymentModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget; // Tombol yang diklik

                    // Ambil data dari tombol
                    var id = button.getAttribute('data-id');
                    var invoice = button.getAttribute('data-invoice');
                    var remaining = button.getAttribute('data-remaining'); // Angka mentah (contoh: 500000)

                    // Update teks di Modal
                    var modalInvoice = paymentModal.querySelector('#modalInvoice');
                    var modalRemaining = paymentModal.querySelector('#modalRemaining');

                    if (modalInvoice) modalInvoice.textContent = invoice;
                    if (modalRemaining) modalRemaining.textContent = 'Rp ' + new Intl.NumberFormat('id-ID')
                        .format(remaining);

                    var hiddenInput = paymentModal.querySelector('#hiddenOrderId');
                    if (hiddenInput) {
                        hiddenInput.value = id;
                    }

                    // --- PERBAIKAN UTAMA: ARAHKAN KE ROUTE BARU ---
                    var form = paymentModal.querySelector('#paymentForm');

                    // Gunakan URL Helper dari Laravel agar link selalu valid di server manapun
                    var baseUrl = "{{ route('receivables.pay', ['id' => ':id']) }}";
                    form.action = baseUrl.replace(':id', id);
                });
            }
        });
    </script>
@endsection
