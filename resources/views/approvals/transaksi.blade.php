@extends('layouts.app')
@section('title', 'Approval Transaksi')

@section('content')
    <div class="card shadow mb-4 border-start-lg border-start-primary">
        <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold"><i class="bi bi-cart-check me-2"></i> Approval Transaksi (Order Baru)</h6>
            <span class="badge bg-white text-primary fw-bold">MANAGER</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light fw-bold text-muted small text-uppercase">
                        <tr>
                            <th>Tanggal & Sales</th>
                            <th>Customer</th>
                            <th>Metode Bayar & TOP</th> {{-- Judul kolom diupdate --}}
                            <th class="text-end">Total Tagihan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($approvals as $item)
                            @php
                                $order = $item->approveable;
                                // Hitung sisa hari jika TOP
                                $dueDate = $order->due_date ? \Carbon\Carbon::parse($order->due_date) : null;
                                $diff = $dueDate ? now()->diffInDays($dueDate, false) : 0;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $item->created_at->format('d M Y') }}</strong><br>
                                    <small class="text-primary"><i class="bi bi-person me-1"></i>
                                        {{ $item->requester->name ?? 'Sales' }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $order->customer->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $order->invoice_number ?? '-' }}</small>
                                </td>
                                <td>
                                    @if ($order->payment_type == 'top' || $order->payment_type == 'kredit')
                                        <span class="badge bg-warning text-dark mb-1">TOP / KREDIT</span>

                                        {{-- INFO TAMBAHAN TOP --}}
                                        <div style="font-size: 0.8rem; line-height: 1.2;">
                                            @if ($dueDate)
                                                <span class="text-muted">Jatuh Tempo:</span><br>
                                                <strong class="text-danger">{{ $dueDate->format('d M Y') }}</strong>

                                                {{-- Opsional: Info durasi hari --}}
                                                @if (isset($order->top_days))
                                                    <span class="text-muted fst-italic">({{ $order->top_days }} Hari)</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge bg-success">CASH / TUNAI</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-nowrap text-primary">
                                    Rp {{ number_format($item->new_data['total_price'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        {{-- 1. TOMBOL DETAIL SO (Mata Biru Muda) --}}
                                        {{-- Tambahkan parameter 'source' => 'approval' --}}
                                        <a href="{{ route('orders.show', ['order' => $order->id, 'source' => 'approval']) }}"
                                            class="btn btn-info btn-sm text-white" title="Lihat Detail SO Lengkap">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        {{-- 2. TOMBOL REVIEW (Kaca Pembesar Biru Tua) --}}
                                        <button type="button" class="btn btn-primary btn-sm fw-bold btn-review"
                                            data-id="{{ $item->id }}" title="Review Approval">
                                            <i class="bi bi-search me-1"></i> Review
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">Tidak ada pengajuan transaksi baru.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (method_exists($approvals, 'links'))
                <div class="mt-3 px-3">{{ $approvals->links() }}</div>
            @endif
        </div>
    </div>

    {{-- MODAL REVIEW --}}
    <div class="modal fade" id="modalReview" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cart-check me-2"></i>Review Transaksi</h5>
                </div>

                <div class="modal-body p-0" id="modalContent"></div>

                <div class="modal-footer bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" id="btnCloseModal" data-dismiss="modal"
                        data-bs-dismiss="modal">Tutup</button>

                    <div class="d-flex gap-2">
                        <form id="formRejectTrx" action="" method="POST">
                            @csrf @method('PUT')
                            <input type="hidden" name="reason" id="reasonTrx">
                            <button type="button" id="btnRejectAction" class="btn btn-danger">Tolak</button>
                        </form>

                        <form id="formApproveTrx" action="" method="POST">
                            @csrf @method('PUT')
                            <button type="button" id="btnApproveAction" class="btn btn-success">Setujui</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // 1. SCRIPT MANUAL UNTUK TOMBOL TUTUP
        $('#btnCloseModal').on('click', function() {
            $('#modalReview').modal('hide');
        });

        // 2. LOAD DATA MODAL
        $(document).on('click', '.btn-review', function() {
            let id = $(this).data('id');
            let urlDetail = "{{ route('approvals.detail', 0) }}".replace('/0', '/' + id);
            let urlApprove = "{{ route('approvals.approve', 0) }}".replace('/0', '/' + id);
            let urlReject = "{{ route('approvals.reject', 0) }}".replace('/0', '/' + id);

            $('#formApproveTrx').attr('action', urlApprove);
            $('#formRejectTrx').attr('action', urlReject);

            $('#modalContent').html(
                '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p>Loading data transaksi...</p></div>'
            );
            $('#modalReview').modal('show');

            $.get(urlDetail, function(data) {
                $('#modalContent').html(data);
            }).fail(function() {
                $('#modalContent').html('<div class="alert alert-danger m-3">Gagal mengambil data.</div>');
            });
        });

        // 3. LOGIKA APPROVE
        $('#btnApproveAction').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Setujui Transaksi Ini?',
                text: "Status order akan berubah menjadi Disetujui (Approved).",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Ya, Setujui!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) $('#formApproveTrx').submit();
            });
        });

        // 4. LOGIKA REJECT
        $('#btnRejectAction').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Tolak Transaksi?',
                text: "Harap masukkan alasan penolakan:",
                icon: 'warning',
                input: 'text',
                inputPlaceholder: 'Contoh: Harga salah / Stok habis',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Tolak Data',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) return 'Anda wajib mengisi alasan penolakan!'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#reasonTrx').val(result.value);
                    $('#formRejectTrx').submit();
                }
            });
        });
    </script>
@endpush
