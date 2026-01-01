@extends('layouts.app')
@section('title', 'Approval Pembayaran Piutang')

@section('content')
    <div class="card shadow mb-4 border-start-lg border-start-success">
        <div class="card-header py-3 bg-success text-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold"><i class="bi bi-cash-coin me-2"></i> Approval Pembayaran Piutang</h6>
            <span class="badge bg-white text-success fw-bold">Manager Bisnis</span>
        </div>
        <div class="card-body">
            <div class="alert alert-light border border-success border-start-0 border-end-0 shadow-sm mb-4">
                <i class="bi bi-info-circle-fill text-success me-2"></i>
                <strong>Info:</strong> Pastikan dana sudah masuk mutasi bank sebelum klik "Setujui".
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light fw-bold text-muted small text-uppercase">
                        <tr>
                            <th>Tanggal & Sales</th>
                            <th>Customer & Invoice</th>
                            <th>Nominal Bayar</th>
                            <th>Metode</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($approvals as $item)
                            @php
                                $log = \App\Models\PaymentLog::find($item->model_id);
                                $order = $log ? $log->order : null;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $item->created_at->format('d/m/Y') }}</strong><br>
                                    <small class="text-muted">{{ $item->created_at->format('H:i') }}</small><br>
                                    <small class="text-primary fw-bold">{{ $item->requester->name ?? 'Sales' }}</small>
                                </td>
                                <td>
                                    @if ($order)
                                        <div class="fw-bold">{{ $order->customer->name ?? '-' }}</div>
                                        <small class="text-muted">{{ $order->invoice_number ?? '-' }}</small>
                                    @else
                                        <span class="text-danger fst-italic">Data Order Hilang</span>
                                    @endif
                                </td>
                                <td>
                                    <h6 class="fw-bold text-success mb-0">Rp
                                        {{ number_format($item->new_data['amount'] ?? 0, 0, ',', '.') }}</h6>
                                </td>
                                <td>
                                    <span
                                        class="badge bg-light text-dark border">{{ $item->new_data['payment_method'] ?? '-' }}</span>
                                    @if (!empty($item->new_data['proof_file']))
                                        <div class="mt-1 small text-primary"><i class="bi bi-image"></i> Ada Foto</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-success btn-sm fw-bold btn-review"
                                        data-id="{{ $item->id }}">
                                        <i class="bi bi-search me-1"></i> Cek Bukti
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">Tidak ada pembayaran baru.</td>
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

    {{-- MODAL PIUTANG --}}
    <div class="modal fade" id="modalReview" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                {{-- Header Tanpa Tombol X --}}
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2"></i>Verifikasi Pembayaran</h5>
                </div>

                <div class="modal-body p-0" id="modalContent"></div>

                <div class="modal-footer bg-light d-flex justify-content-between">
                     <button type="button" class="btn btn-secondary" id="btnCloseModal" data-dismiss="modal"
                        data-bs-dismiss="modal">Tutup</button>

                    <div class="d-flex gap-2">
                        <form id="formReject" method="POST">
                            @csrf @method('PUT')
                            <input type="hidden" name="reason" id="reasonInput">
                            <button type="button" id="btnRejectAction" class="btn btn-danger">Tolak</button>
                        </form>
                        <form id="formApprove" method="POST">
                            @csrf @method('PUT')
                            <button type="button" id="btnApproveAction" class="btn btn-success">Valid (Setujui)</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // SCRIPT MANUAL UNTUK TOMBOL TUTUP (Jaga-jaga jika atribut data-dismiss gagal)
        $('#btnCloseModal').on('click', function() {
            $('#modalReview').modal('hide');
        });
        $(document).on('click', '.btn-review', function() {
            let id = $(this).data('id');
            let urlDetail = "{{ route('approvals.detail', 0) }}".replace('/0', '/' + id);
            let urlApprove = "{{ route('approvals.approve', 0) }}".replace('/0', '/' + id);
            let urlReject = "{{ route('approvals.reject', 0) }}".replace('/0', '/' + id);

            $('#formApprove').attr('action', urlApprove);
            $('#formReject').attr('action', urlReject);

            $('#modalContent').html(
                '<div class="text-center py-5"><div class="spinner-border text-success"></div><p>Loading...</p></div>'
                );
            $('#modalReview').modal('show');

            $.get(urlDetail, function(data) {
                    $('#modalContent').html(data);
                })
                .fail(function() {
                    $('#modalContent').html('<div class="alert alert-danger m-3">Gagal mengambil data.</div>');
                });
        });

        // SWEETALERT APPROVE
    $('#btnApproveAction').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Setujui Perubahan Produk?',
            text: "Data produk/stok akan diperbarui.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#1cc88a'
        }).then((result) => {
            if (result.isConfirmed) $('#formApprove').submit();
        });
    });

    // SWEETALERT REJECT
    $('#btnRejectAction').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Tolak Pengajuan?',
            text: "Masukkan alasan penolakan:",
            icon: 'warning',
            input: 'text',
            inputPlaceholder: 'Contoh: Stok tidak sesuai fisik',
            showCancelButton: true,
            confirmButtonText: 'Tolak',
            confirmButtonColor: '#e74a3b',
            inputValidator: (value) => {
                if (!value) return 'Alasan wajib diisi!'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $('#reasonInput').val(result.value);
                $('#formReject').submit();
            }
        });
    });
    </script>
@endpush
