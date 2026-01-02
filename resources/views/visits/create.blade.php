@extends('layouts.app')

@section('title', 'Input Laporan Kunjungan')

@section('content')
    {{-- ================================================================= --}}
    {{-- FIX: LOAD CSS LANGSUNG DI SINI --}}
    {{-- ================================================================= --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        /* CUSTOM STYLING SELECT2 UNTUK MOBILE */

        /* 1. Samakan Tinggi & Padding dengan Input Bootstrap (48px) */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 48px;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem; /* Rounded standard bootstrap */
            background-color: #fff;
        }

        /* 2. Styling Teks Pilihan */
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            font-size: 1rem;
            line-height: 26px;
            color: #212529;
            padding-left: 0;
        }

        /* 3. Posisi Panah Dropdown */
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            top: 50%;
            transform: translateY(-50%);
            right: 15px;
        }

        /* 4. Paksa Lebar Penuh & Z-Index Aman */
        .select2-container {
            width: 100% !important;
            z-index: 100;
        }

        /* 5. Styling Error State (Jika validasi gagal) */
        .is-invalid + .select2-container .select2-selection {
            border-color: #dc3545;
        }
    </style>

    <div class="container-fluid px-0 px-md-3">

        {{-- HEADER DESKTOP (Hidden di Mobile) --}}
        <div class="d-none d-md-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Input Laporan Kunjungan</h1>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        {{-- HEADER MOBILE (Simple Link) --}}
        <div class="d-md-none mb-3">
            <a href="{{ route('dashboard') }}" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        </div>

        {{-- ALERT ERROR --}}
        @if ($errors->any())
            <div class="alert alert-danger shadow-sm border-0 mb-3" role="alert">
                <strong class="d-block mb-1">Gagal Menyimpan!</strong>
                <ul class="mb-0 ps-3 small">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif

        {{-- FORM CARD --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-3 p-md-4">
                <form action="{{ route('visits.store') }}" method="POST" enctype="multipart/form-data" id="visitForm">
                    @csrf
                    <input type="hidden" name="type" id="inputType" value="existing">

                    {{-- TOGGLE BUTTONS (Langganan vs Baru) --}}
                    <div class="btn-group w-100 mb-4" role="group">
                        <button type="button" class="btn btn-primary fw-bold py-2" id="btnExisting" onclick="setMode('existing')">
                            <i class="bi bi-shop me-1"></i> Langganan
                        </button>
                        <button type="button" class="btn btn-outline-primary fw-bold py-2" id="btnNew" onclick="setMode('new')">
                            <i class="bi bi-plus-circle me-1"></i> Baru
                        </button>
                    </div>

                    {{-- SECTION 1: CUSTOMER LANGGANAN --}}
                    <div id="sectionExisting">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Cari Customer</label>
                            {{-- Tambahkan style width 100% langsung di elemen --}}
                            <select name="customer_id" id="customerId" class="form-select select2" required style="width: 100%;">
                                <option value="">-- Cari Nama Customer --</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ Str::limit($c->address, 20) }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- SECTION 2: CUSTOMER BARU --}}
                    <div id="sectionNew" style="display: none;">
                        <div class="alert alert-info border-0 bg-info bg-opacity-10 small mb-3 py-2 px-3">
                            <i class="bi bi-info-circle me-1"></i> Customer baru butuh persetujuan Manager.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Nama Toko</label>
                            <input type="text" name="new_name" id="newName" class="form-control form-control-lg fs-6" placeholder="Nama Toko">
                        </div>
                        <div class="row g-2">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-muted">No. HP/WA</label>
                                <input type="number" name="new_phone" id="newPhone" class="form-control" placeholder="0812...">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-muted">PIC</label>
                                <input type="text" name="new_contact" class="form-control" placeholder="Nama Pemilik">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Alamat</label>
                            <textarea name="new_address" id="newAddress" class="form-control" rows="2" placeholder="Alamat lengkap..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Kategori</label>
                            <select name="new_category" id="newCategory" class="form-select">
                                <option value="">- Pilih -</option>
                                @foreach ($categories as $cat) <option value="{{ $cat->name }}">{{ $cat->name }}</option> @endforeach
                            </select>
                        </div>
                    </div>

                    <hr class="border-light my-4">

                    {{-- WAKTU & FOTO --}}
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Check-in</label>
                            <input type="datetime-local" name="check_in_time" class="form-control form-control-sm"
                                value="{{ now()->subMinutes(30)->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Check-out</label>
                            <input type="datetime-local" name="check_out_time" class="form-control form-control-sm"
                                value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Hasil Kunjungan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Tulis hasil laporan di sini..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Foto (Wajib)</label>
                        <input type="file" name="photo" class="form-control" accept="image/*" capture="environment" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-3 shadow-sm rounded-pill">
                        SIMPAN LAPORAN
                    </button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                // Inisialisasi dengan setTimeout agar aman
                setTimeout(function() {
                    $('.select2').select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        placeholder: '-- Cari Nama Customer --',
                        allowClear: true
                    });
                }, 100);
            });

            function setMode(mode) {
                document.getElementById('inputType').value = mode;

                if (mode === 'existing') {
                    $('#btnExisting').removeClass('btn-outline-primary').addClass('btn-primary');
                    $('#btnNew').removeClass('btn-primary').addClass('btn-outline-primary');

                    $('#sectionExisting').show();
                    $('#sectionNew').hide();

                    // RE-INIT SELECT2 SAAT PINDAH TAB (Penting!)
                    setTimeout(function() {
                        $('#customerId').select2({
                            theme: 'bootstrap-5',
                            width: '100%',
                            placeholder: '-- Cari Nama Customer --'
                        });
                    }, 50);

                    // ATUR VALIDASI
                    $('#customerId').prop('required', true);
                    $('#newName, #newCategory, #newPhone, #newAddress').prop('required', false);
                } else {
                    $('#btnNew').removeClass('btn-outline-primary').addClass('btn-primary');
                    $('#btnExisting').removeClass('btn-primary').addClass('btn-outline-primary');

                    $('#sectionExisting').hide();
                    $('#sectionNew').show();

                    // ATUR VALIDASI
                    $('#customerId').prop('required', false);
                    $('#newName, #newCategory, #newPhone, #newAddress').prop('required', true);
                }
            }
        </script>
    @endpush
@endsection
