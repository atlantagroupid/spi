@extends('layouts.app')
@section('title', 'Approval Transaksi')

@section('content')
    <div class="card shadow mb-4 border-start-lg border-start-primary">
        <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold"><i class="bi bi-cart-check me-2"></i> Approval Transaksi (Order Baru)</h6>
            <span class="badge bg-white text-primary fw-bold">Manager Bisnis</span>
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
                                        <button class="btn btn-sm btn-primary btn-review" data-id="{{ $item->id }}"
                                            data-order-id="{{ $item->model_id }}"> {{-- <--- INI WAJIB ADA --}}
                                            <i class="bi bi-search"></i> Review
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

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>

                    {{-- Tombol ini yang akan membawa Manager ke halaman Detail Order untuk eksekusi --}}
                    <a href="#" id="btnShowDetail" class="btn btn-primary fw-bold shadow-sm">
                        <i class="bi bi-eye-fill me-1"></i> Lihat Detail & Eksekusi
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        // EVENT SAAT TOMBOL "REVIEW" DIKLIK
        $(document).on('click', '.btn-review', function() {
            let id = $(this).data('id'); // ID Tiket Approval
            let orderId = $(this).data('order-id'); // ID Order (Wajib ada di tombol tabel)

            // 1. URL untuk konten ringkasan modal (AJAX)
            // Pastikan route ini sesuai dengan route di web.php untuk ambil detail approval
            let urlDetail = "{{ route('approvals.detail', ':id') }}".replace(':id', id);

            // 2. URL untuk tombol "Lihat Detail & Eksekusi"
            // Kita tambah parameter ?source=approval agar tombol Approve/Reject muncul di sana
            let urlOrderShow = "{{ route('orders.show', ':id') }}";
            urlOrderShow = urlOrderShow.replace(':id', orderId) + "?source=approval";

            // 3. Update Link Tombol di Footer Modal
            $('#btnShowDetail').attr('href', urlOrderShow);

            // 4. Tampilkan Loading & Buka Modal
            $('#modalContent').html(
                '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Memuat ringkasan...</p></div>'
            );
            $('#modalReview').modal('show');

            // 5. Ambil Data Ringkasan via AJAX
            $.get(urlDetail, function(data) {
                $('#modalContent').html(data);
            }).fail(function() {
                $('#modalContent').html('<div class="alert alert-danger m-3">Gagal mengambil data.</div>');
            });
        });

        // Manual Close (Jaga-jaga jika tombol X tidak jalan)
        $('#btnCloseModal').on('click', function() {
            $('#modalReview').modal('hide');
        });
    </script>
@endpush
