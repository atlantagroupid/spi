@extends('layouts.app')

@section('title', 'Dashboard Approval Operasional')

@section('content')
    <div class="card shadow mb-4 border-left-primary">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-primary text-white">
            <h6 class="m-0 font-weight-bold"><i class="bi bi-speedometer2 me-2"></i> Dashboard Approval (Operasional)</h6>
            <span class="badge bg-light text-primary fw-bold">ALL ACCESS</span>
        </div>
        <div class="card-body">

            {{-- A. STATISTIK RINGKAS --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-light border-0 h-100">
                        <div class="card-body text-center p-2">
                            <small class="text-uppercase text-muted fw-bold">Total Pending</small>
                            <h3 class="fw-bold text-dark mb-0">{{ $approvals->total() }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-9 d-flex align-items-center">
                    <div class="alert alert-info w-100 mb-0 py-2 border-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Info:</strong> Halaman ini menampilkan seluruh permintaan persetujuan dari divisi Bisnis (Order/Customer) dan Gudang (Produk).
                    </div>
                </div>
            </div>

            {{-- B. TABEL DATA --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th>Tanggal Request</th>
                            <th>Divisi / Tipe</th>
                            <th>Pengaju (Sales/Staff)</th>
                            <th>Detail Permintaan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($approvals as $item)
                            <tr>
                                {{-- 1. TANGGAL --}}
                                <td>
                                    <div class="fw-bold text-dark">{{ $item->created_at->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $item->created_at->format('H:i') }}</small>
                                </td>

                                {{-- 2. DIVISI / TIPE --}}
                                <td>
                                    @if(str_contains($item->model_type, 'Order'))
                                        <span class="badge bg-primary w-100 py-2">ORDER / TRANSAKSI</span>
                                    @elseif(str_contains($item->model_type, 'Payment'))
                                        <span class="badge bg-success w-100 py-2">KEUANGAN / PIUTANG</span>
                                    @elseif(str_contains($item->model_type, 'Customer'))
                                        <span class="badge bg-warning text-dark w-100 py-2">DATA CUSTOMER</span>
                                    @elseif(str_contains($item->model_type, 'Product'))
                                        <span class="badge bg-secondary w-100 py-2">STOK / PRODUK</span>
                                    @else
                                        <span class="badge bg-dark w-100 py-2">LAINNYA</span>
                                    @endif
                                </td>

                                {{-- 3. PENGAJU --}}
                                <td>
                                    <div class="fw-bold">{{ $item->requester->name ?? '-' }}</div>
                                    <small class="text-muted text-uppercase" style="font-size: 0.7rem;">
                                        {{ $item->requester->role ?? 'Staff' }}
                                    </small>
                                </td>

                                {{-- 4. KETERANGAN --}}
                                <td>
                                    @if (str_contains($item->model_type, 'Order'))
                                        <div class="fw-bold text-primary">Invoice: {{ $item->new_data['invoice_number'] ?? '-' }}</div>
                                        <small class="text-muted">Total: Rp {{ number_format($item->new_data['total_price'] ?? 0, 0, ',', '.') }}</small>

                                    @elseif (str_contains($item->model_type, 'Payment'))
                                        <div class="fw-bold text-success">Pelunasan Piutang</div>
                                        <small class="text-muted">Nominal: Rp {{ number_format($item->new_data['amount'] ?? 0, 0, ',', '.') }}</small>

                                    @elseif (str_contains($item->model_type, 'Customer'))
                                        <div class="fw-bold text-dark">{{ $item->action == 'update_customer' ? 'Edit Data Toko' : 'Hapus Toko' }}</div>
                                        <small class="text-muted">{{ $item->original_data['store_name'] ?? ($item->new_data['store_name'] ?? '-') }}</small>

                                    @else
                                        <div class="fw-bold text-dark">{{ $item->new_data['name'] ?? ($item->original_data['name'] ?? '-') }}</div>
                                        <small class="text-muted text-capitalize">{{ str_replace('_', ' ', $item->action) }}</small>
                                    @endif
                                </td>

                                {{-- 5. AKSI (UPDATED: Pakai Script Baru) --}}
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold shadow-sm px-3 btn-review"
                                        data-id="{{ $item->id }}">
                                        <i class="bi bi-search me-1"></i> Review
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-check-circle-fill fs-1 text-success d-block mb-3"></i>
                                    <h5>Semua Aman!</h5>
                                    <p class="mb-0">Tidak ada permintaan persetujuan pending saat ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-3">
                {{ $approvals->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL REVIEW GLOBAL --}}
    <div class="modal fade" id="modalReview" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                {{-- Header Tanpa Tombol X --}}
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Review Pengajuan</h5>
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
                            <button type="button" id="btnApproveAction" class="btn btn-success">Setujui</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPT DI STACK (PASTI JALAN) --}}
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

        // Reset & Update Form
        $('#formApprove').attr('action', urlApprove);
        $('#formReject').attr('action', urlReject);

        // Show Modal with Loading
        $('#modalContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p>Loading...</p></div>');
        $('#modalReview').modal('show');

        // Fetch Content
        $.get(urlDetail, function(data) {
            $('#modalContent').html(data);
        }).fail(function() {
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
            inputPlaceholder: 'Contoh: Data tidak sesuai',
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
