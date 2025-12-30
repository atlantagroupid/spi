@extends('layouts.app')
@section('title', 'Approval Data Produk')

@section('content')
    <div class="card shadow mb-4 border-start-lg border-start-warning">
        <div class="card-header py-3 bg-warning text-dark d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold"><i class="bi bi-box-seam me-2"></i> Approval Produk / Stok</h6>
            <span class="badge bg-white text-dark fw-bold">GUDANG / MANAGER</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light fw-bold text-muted small text-uppercase">
                        <tr>
                            <th>Tanggal</th>
                            <th>Pengaju</th>
                            <th>Nama Produk</th>
                            <th>Tipe Perubahan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($approvals as $item)
                            @php $product = $item->approveable; @endphp
                            <tr>
                                <td>{{ $item->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $item->requester->name ?? 'System' }}</td>
                                <td class="fw-bold">{{ $product->name ?? 'Produk Baru' }}</td>
                                <td>
                                    @if ($item->action == 'create')
                                        <span class="badge bg-success">Produk Baru</span>
                                    @elseif($item->action == 'delete')
                                        <span class="badge bg-danger">Hapus Produk</span>
                                    @else
                                        <span class="badge bg-info text-dark">Update Info/Stok</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-warning btn-sm text-dark fw-bold btn-review"
                                        data-id="{{ $item->id }}">
                                        <i class="bi bi-search me-1"></i> Review
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">Tidak ada pengajuan produk.</td>
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

    {{-- MODAL PRODUK --}}
    <div class="modal fade" id="modalReview" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                {{-- Header Tanpa Tombol X --}}
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-box-seam me-2"></i>Review Produk</h5>
                </div>

                <div class="modal-body p-0" id="modalContent"></div>

                <div class="modal-footer bg-light d-flex justify-content-between">
                    {{-- TOMBOL TUTUP DIPERBAIKI (Support BS4 & BS5 + ID Script) --}}
                    < <button type="button" class="btn btn-secondary" id="btnCloseModal" data-dismiss="modal"
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
                '<div class="text-center py-5"><div class="spinner-border text-warning"></div><p>Loading...</p></div>'
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
                title: 'Setujui Pengajuan Ini?',
                text: "Pastikan data sudah benar.",
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
