@extends('layouts.app')

@section('title', 'Input Laporan Kunjungan')

@section('content')
    {{-- TARUH DISINI SAJA AGAR LANGSUNG TERBACA --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Input Laporan Kunjungan</h1>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
        {{-- AREA MENAMPILKAN ERROR VALIDASI --}}
        @if ($errors->any())
            <div class="alert alert-danger border-left-danger" role="alert">
                <h4 class="alert-heading h5 font-weight-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Gagal
                    Menyimpan!</h4>
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary">Form Laporan (Sales Store)</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('visits.store') }}" method="POST" enctype="multipart/form-data" id="visitForm">
                    @csrf

                    {{-- INPUT HIDDEN TIPE KUNJUNGAN (Default: existing) --}}
                    <input type="hidden" name="type" id="inputType" value="existing">

                    {{-- TOGGLE BUTTONS --}}
                    <div class="d-flex gap-2 mb-4">
                        <button type="button" class="btn btn-primary w-50 fw-bold" id="btnExisting"
                            onclick="setMode('existing')">
                            <i class="bi bi-shop me-2"></i>Customer Langganan
                        </button>
                        <button type="button" class="btn btn-outline-primary w-50 fw-bold" id="btnNew"
                            onclick="setMode('new')">
                            <i class="bi bi-plus-circle me-2"></i>Customer Baru
                        </button>
                    </div>

                    {{-- BAGIAN 1: Customer LANGGANAN (EXISTING) --}}
                    <div id="sectionExisting">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Customer<span
                                    class="text-danger">*</span></label>
                            <select name="customer_id" id="customerId" class="form-select select2" required>
                                <option value="">-- Cari Nama Customer --</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} - {{ $c->address }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Hanya menampilkan customer kelolaan Anda.</div>
                        </div>
                    </div>

                    {{-- BAGIAN 2: Customer BARU (NEW) --}}
                    <div id="sectionNew" style="display: none;">
                        <div class="alert alert-info border-info small mb-3">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            Data Customer baru akan berstatus <strong>Pending</strong> sampai disetujui Manager.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Customer Baru <span class="text-danger">*</span></label>
                            <input type="text" name="new_name" id="newName" class="form-control"
                                placeholder="Contoh: Toko Maju Jaya">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">No. Telepon / WA <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="new_phone" id="newPhone" class="form-control"
                                    placeholder="0812...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nama Kontak (PIC)</label>
                                <input type="text" name="new_contact" class="form-control"
                                    placeholder="Contoh: Pak Budi">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Alamat Lengkap <span class="text-danger">*</span></label>
                            <textarea name="new_address" id="newAddress" class="form-control" rows="2" placeholder="Jalan, Nomor, Kota..."></textarea>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori Pelanggan <span class="text-danger">*</span></label>
                        <select name="new_category" id="newCategory" class="form-select">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <hr class="my-4">

                    {{-- BAGIAN UMUM (WAKTU & CATATAN) --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Waktu Mulai (Check-in) <span
                                    class="text-danger">*</span></label>
                            <input type="datetime-local" name="check_in_time" class="form-control"
                                value="{{ now()->subMinutes(30)->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Waktu Selesai (Check-out) <span
                                    class="text-danger">*</span></label>
                            <input type="datetime-local" name="check_out_time" class="form-control"
                                value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Laporan Hasil / Catatan <span
                                class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="4"
                            placeholder="Contoh: Customer menanyakan katalog granit terbaru..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Foto Dokumentasi (Wajib) <span
                                class="text-danger">*</span></label>
                        <input type="file" name="photo" class="form-control" accept="image/*" required>
                        <div class="form-text small">Upload foto Customer atau nota bukti kunjungan.</div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary fw-bold px-4 py-2">
                            <i class="bi bi-save me-1"></i> Simpan Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                $('.select2').select2({
                    theme: 'bootstrap-5'
                });
            });

            function setMode(mode) {
                document.getElementById('inputType').value = mode;

                if (mode === 'existing') {
                    $('#btnExisting').removeClass('btn-outline-primary').addClass('btn-primary');
                    $('#btnNew').removeClass('btn-primary').addClass('btn-outline-primary');

                    $('#sectionExisting').show();
                    $('#sectionNew').hide();

                    // ATUR VALIDASI
                    $('#customerId').prop('required', true);
                    // Matikan validasi form baru
                    $('#newName, #newCategory, #newPhone, #newAddress').prop('required', false);
                } else {
                    $('#btnNew').removeClass('btn-outline-primary').addClass('btn-primary');
                    $('#btnExisting').removeClass('btn-primary').addClass('btn-outline-primary');

                    $('#sectionExisting').hide();
                    $('#sectionNew').show();

                    // ATUR VALIDASI
                    $('#customerId').prop('required', false);
                    // Hidupkan validasi form baru (termasuk kategori)
                    $('#newName, #newCategory, #newPhone, #newAddress').prop('required', true);
                }
            }
        </script>
    @endpush
@endsection
