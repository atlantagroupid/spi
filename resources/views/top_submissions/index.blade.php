@extends('layouts.app')

@section('title', 'Approval TOP')

@section('content')
    <div class="container-fluid px-0 px-md-3">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3 mb-md-4">
            <h3 class="fw-bold text-gray-800 mb-0 d-none d-md-block">Approval TOP & Limit</h3>
            <h5 class="fw-bold text-gray-800 mb-0 d-md-none">Approval TOP</h5>
        </div>

        {{-- WRAPPER KONTEN --}}
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">

            {{-- CARD HEADER --}}
            <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold fs-6 fs-md-5">
                    <i class="bi bi-file-earmark-check me-2"></i>Daftar Pengajuan
                </h5>
                <span class="badge bg-white text-success fw-bold px-3 py-1 rounded-pill small">
                    Manager
                </span>
            </div>

            <div class="card-body p-0 p-md-3">

                {{-- ALERT INFO (HANYA DESKTOP) --}}
                <div class="alert alert-light border shadow-sm d-none d-md-flex align-items-center mb-4 mx-3 mx-md-0"
                    role="alert">
                    <i class="bi bi-info-circle-fill text-success fs-4 me-3"></i>
                    <div>
                        <strong>Penting:</strong> Periksa riwayat pembayaran customer sebelum menyetujui.
                        <div class="small text-muted">Menyetujui kenaikan limit akan memotong kuota kredit pribadi Anda.
                        </div>
                    </div>
                </div>

                {{-- ========================================== --}}
                {{-- DESKTOP: TABEL --}}
                {{-- ========================================== --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary text-uppercase small fw-bold">
                            <tr>
                                <th class="py-3 ps-3">Tanggal & Sales</th>
                                <th class="py-3">Customer</th>
                                <th class="py-3">Pengajuan Baru</th>
                                <th class="py-3">Kondisi Lama</th>
                                <th class="py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($submissions as $submission)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark">{{ $submission->created_at->format('d M Y') }}</div>
                                        <div class="small text-muted">
                                            <i class="bi bi-person-fill me-1"></i>{{ $submission->user->name ?? 'Sales' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $submission->customer->name ?? '-' }}</div>
                                        <span class="badge bg-light text-secondary border mt-1">
                                            Hutang: Rp {{ number_format($submission->customer->debt ?? 0, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($submission->submission_limit > 0)
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="badge bg-success me-2">LIMIT</span>
                                                <span class="fw-bold text-success">
                                                    Rp {{ number_format($submission->submission_limit, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @endif
                                        @if ($submission->submission_days > 0)
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-info text-dark me-2">TEMPO</span>
                                                <span class="fw-bold text-dark">{{ $submission->submission_days }}
                                                    Hari</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small text-muted">Limit: Rp
                                            {{ number_format($submission->customer->credit_limit, 0, ',', '.') }}</div>
                                        <div class="small text-muted">Tempo: {{ $submission->customer->top_days }} Hari
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            {{-- TOMBOL APPROVE DESKTOP --}}
                                            <form id="form-approve-{{ $submission->id }}"
                                                action="{{ route('top-submissions.approve', $submission->id) }}"
                                                method="POST">
                                                @csrf @method('PUT')
                                                <button type="button" onclick="confirmAction('{{ $submission->id }}', 'approve')"
                                                    class="btn btn-sm btn-success text-white shadow-sm" title="Setujui">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>

                                            {{-- TOMBOL REJECT DESKTOP --}}
                                            <form id="form-reject-{{ $submission->id }}"
                                                action="{{ route('top-submissions.reject', $submission->id) }}"
                                                method="POST">
                                                @csrf @method('PUT')
                                                <button type="button" onclick="confirmAction('{{ $submission->id }}', 'reject')"
                                                    class="btn btn-sm btn-outline-danger shadow-sm" title="Tolak">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">Tidak ada pengajuan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ========================================== --}}
                {{-- MOBILE: CARD LIST --}}
                {{-- ========================================== --}}
                <div class="d-md-none bg-light pt-3 pb-5">
                    @forelse($submissions as $submission)
                        <div class="card mb-3 border shadow-sm mx-3 rounded-3 overflow-hidden">
                            {{-- Header Kartu --}}
                            <div
                                class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-light rounded-circle p-1 border d-flex align-items-center justify-content-center"
                                        style="width:32px; height:32px;">
                                        <i class="bi bi-person-fill text-secondary"></i>
                                    </div>
                                    <div>
                                        <small
                                            class="fw-bold d-block lh-1 text-dark">{{ $submission->user->name ?? 'Sales' }}</small>
                                        <small class="text-muted"
                                            style="font-size: 0.65rem;">{{ $submission->created_at->format('d M, H:i') }}</small>
                                    </div>
                                </div>
                                <span class="badge bg-warning text-dark border">Pending</span>
                            </div>

                            <div class="card-body p-3">
                                <h6 class="fw-bold text-dark mb-2">{{ $submission->customer->name }}</h6>
                                <div class="small text-muted mb-3"><i class="bi bi-exclamation-circle me-1"></i>Hutang saat
                                    ini: Rp {{ number_format($submission->customer->debt ?? 0, 0, ',', '.') }}</div>

                                <div class="row g-2 mb-3">
                                    {{-- LIMIT --}}
                                    @if ($submission->submission_limit > 0)
                                        <div class="col-12">
                                            <div
                                                class="p-2 border rounded bg-success bg-opacity-10 border-success position-relative overflow-hidden">
                                                <div class="position-absolute top-0 end-0 px-2 py-1 bg-success text-white"
                                                    style="font-size: 0.6rem; border-bottom-left-radius: 6px;">LIMIT</div>
                                                <small class="text-muted text-uppercase d-block"
                                                    style="font-size: 0.65rem;">Dari Rp
                                                    {{ number_format($submission->customer->credit_limit / 1000, 0) }}k
                                                    Menjadi</small>
                                                <span class="fw-bold text-dark fs-5">Rp
                                                    {{ number_format($submission->submission_limit, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- TEMPO --}}
                                    @if ($submission->submission_days > 0)
                                        <div class="col-12">
                                            <div
                                                class="p-2 border rounded bg-info bg-opacity-10 border-info position-relative overflow-hidden">
                                                <div class="position-absolute top-0 end-0 px-2 py-1 bg-info text-white"
                                                    style="font-size: 0.6rem; border-bottom-left-radius: 6px;">TEMPO</div>
                                                <small class="text-muted text-uppercase d-block"
                                                    style="font-size: 0.65rem;">Dari {{ $submission->customer->top_days }}
                                                    Hari Menjadi</small>
                                                <span class="fw-bold text-dark fs-5">{{ $submission->submission_days }}
                                                    Hari</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="d-flex gap-2">
                                    {{-- TOMBOL APPROVE MOBILE --}}
                                    <form id="form-approve-mobile-{{ $submission->id }}"
                                        action="{{ route('top-submissions.approve', $submission->id) }}" method="POST"
                                        class="flex-fill">
                                        @csrf @method('PUT')
                                        <button type="button" onclick="confirmAction('{{ $submission->id }}', 'approve', true)"
                                            class="btn btn-success w-100 fw-bold shadow-sm text-white">SETUJUI</button>
                                    </form>

                                    {{-- TOMBOL REJECT MOBILE --}}
                                    <form id="form-reject-mobile-{{ $submission->id }}"
                                        action="{{ route('top-submissions.reject', $submission->id) }}" method="POST"
                                        class="flex-fill">
                                        @csrf @method('PUT')
                                        <button type="button" onclick="confirmAction('{{ $submission->id }}', 'reject', true)"
                                            class="btn btn-outline-danger w-100 fw-bold shadow-sm bg-white">TOLAK</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">Tidak ada pengajuan.</div>
                    @endforelse
                </div>

                {{-- PAGINATION --}}
                <div class="p-3 bg-white">
                    {{ $submissions->links() }}
                </div>

            </div>
        </div>
    </div>

    {{-- SCRIPT SWEETALERT (LETAKKAN DISINI) --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmAction(id, type, isMobile = false) {
            let title, text, icon, confirmBtnColor, confirmBtnText;

            // Tentukan Pesan Berdasarkan Aksi
            if (type === 'approve') {
                title = 'Setujui Pengajuan?';
                text = 'Pastikan data sudah benar. Limit customer akan diperbarui & kuota Anda terpotong.';
                icon = 'question'; // Ikon tanda tanya
                confirmBtnColor = '#198754'; // Hijau Sukses
                confirmBtnText = 'Ya, Setujui!';
            } else {
                title = 'Tolak Pengajuan?';
                text = 'Pengajuan ini akan ditandai sebagai ditolak dan tidak bisa dikembalikan.';
                icon = 'warning'; // Ikon Peringatan
                confirmBtnColor = '#dc3545'; // Merah Bahaya
                confirmBtnText = 'Ya, Tolak!';
            }

            // Tampilkan SweetAlert
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: confirmBtnColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmBtnText,
                cancelButtonText: 'Batal',
                reverseButtons: true // Tombol aksi di sebelah kanan
            }).then((result) => {
                if (result.isConfirmed) {
                    // Cari Form ID berdasarkan Desktop atau Mobile
                    let formId = isMobile ? 'form-' + type + '-mobile-' + id : 'form-' + type + '-' + id;
                    document.getElementById(formId).submit();
                }
            });
        }
    </script>
@endsection
