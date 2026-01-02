@extends('layouts.app')

@section('title', 'Buat Rencana Visit')

@section('content')
    {{-- ================================================================= --}}
    {{-- FIX: CSS DILOAD LANGSUNG DI SINI AGAR TIDAK GAGAL TAMPIL --}}
    {{-- ================================================================= --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        /* CUSTOM STYLING SELECT2 AGAR MENYATU DENGAN INPUT GROUP */

        /* 1. Samakan Tinggi & Padding dengan Input Bootstrap */
        .select2-container .select2-selection--single {
            height: 48px; /* Tinggi input-group-lg */
            padding: 10px 12px;
            background-color: #f8f9fa !important; /* bg-light */
            border: 1px solid #dee2e6;
            border-left: 0; /* Hapus border kiri agar nyambung */
            border-top-right-radius: 0.5rem !important;
            border-bottom-right-radius: 0.5rem !important;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        /* 2. Styling Teks di dalam */
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 26px;
            color: #212529;
            padding-left: 0;
            font-size: 0.95rem;
        }

        /* 3. Posisi Panah Dropdown */
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            top: 50%;
            transform: translateY(-50%);
            right: 15px;
        }

        /* 4. Fix Sudut Icon Kiri (Input Group Text) */
        .input-group-text {
            border-top-left-radius: 0.5rem !important;
            border-bottom-left-radius: 0.5rem !important;
            border-color: #dee2e6;
            background-color: #f8f9fa;
        }

        /* 5. Pastikan Dropdown Muncul di Atas Elemen Lain */
        .select2-container {
            z-index: 9999;
            width: 100% !important; /* Paksa lebar penuh */
        }
    </style>

    <div class="container-fluid px-0 px-md-3">
        <div class="row justify-content-center">

            {{-- LAYOUT RESPONSIVE --}}
            <div class="col-md-6 col-lg-5">

                {{-- HEADER DESKTOP (Hidden di Mobile) --}}
                <div class="d-none d-md-flex align-items-center mb-4">
                    <a href="{{ route('dashboard') }}" class="btn btn-light shadow-sm rounded-circle me-3 text-secondary"
                       style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-arrow-left fs-4"></i>
                    </a>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Rencana Kunjungan</h5>
                        <small class="text-muted">Tentukan target toko hari ini</small>
                    </div>
                </div>

                {{-- HEADER MOBILE (Simple Back Link) --}}
                <div class="d-md-none mb-3 px-1">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none text-muted small">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                </div>

                {{-- FORM CARD --}}
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <form action="{{ route('visits.storePlan') }}" method="POST">
                            @csrf

                            {{-- 1. INPUT TOKO (SELECT2) --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing: 0.5px;">
                                    Pilih Toko / Customer
                                </label>

                                <div class="input-group">
                                    {{-- Icon Toko --}}
                                    <span class="input-group-text border-end-0 ps-3">
                                        <i class="bi bi-shop text-primary fs-5"></i>
                                    </span>

                                    {{-- Wrapper Select2 --}}
                                    <div class="flex-grow-1">
                                        <select name="customer_id" class="form-select select2" required>
                                            <option value="">-- Cari Nama Toko --</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- 2. INPUT TANGGAL --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing: 0.5px;">
                                    Tanggal Rencana
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text border-end-0 ps-3">
                                        <i class="bi bi-calendar-event text-primary fs-5"></i>
                                    </span>
                                    <input type="date" name="visit_date"
                                           class="form-control form-control-lg bg-light border-start-0 shadow-none"
                                           value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}" required
                                           style="font-size: 0.95rem; border-top-right-radius: 0.5rem; border-bottom-right-radius: 0.5rem;">
                                </div>
                            </div>

                            {{-- 3. INPUT CATATAN --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing: 0.5px;">
                                    Catatan (Opsional)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text border-end-0 ps-3 pt-3 align-items-start">
                                        <i class="bi bi-journal-text text-primary fs-5"></i>
                                    </span>
                                    <textarea name="notes" class="form-control bg-light border-start-0 shadow-none"
                                              rows="3" placeholder="Contoh: Menawarkan promo akhir tahun..."
                                              style="resize: none; border-top-right-radius: 0.5rem; border-bottom-right-radius: 0.5rem;"></textarea>
                                </div>
                            </div>

                            {{-- TOMBOL AKSI --}}
                            <div class="d-grid gap-2 mt-5">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-3">
                                    <i class="bi bi-save2 me-2"></i> Simpan Rencana
                                </button>

                                <a href="{{ route('dashboard') }}" class="btn btn-link text-decoration-none text-muted btn-sm mt-2 text-center">
                                    Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                // Beri jeda sedikit agar DOM siap sepenuhnya sebelum Select2 dirender
                setTimeout(function() {
                    $('.select2').select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        placeholder: '-- Cari Nama Toko --',
                        allowClear: true
                    });
                }, 100);
            });
        </script>
    @endpush
@endsection
