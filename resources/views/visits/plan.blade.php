@extends('layouts.app')

@section('title', 'Buat Rencana Visit')

@section('content')
<div class="row justify-content-center">
    {{-- Kita batasi lebar di desktop agar mirip tampilan aplikasi mobile --}}
    <div class="col-md-5 col-lg-4">

        {{-- HEADER: Tombol Kembali & Judul --}}
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('dashboard') }}" class="btn btn-light shadow-sm rounded-circle me-3 text-secondary" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-arrow-left fs-4"></i>
            </a>
            <div>
                <h5 class="fw-bold mb-0 text-dark">Rencana Kunjungan</h5>
                <small class="text-muted">Tentukan target toko hari ini</small>
            </div>
        </div>

        {{-- FORM CARD --}}
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form action="{{ route('visits.storePlan') }}" method="POST">
                    @csrf

                    {{-- 1. INPUT TOKO --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary" style="font-size: 0.8rem; letter-spacing: 0.5px;">PILIH TOKO / CUSTOMER</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-3 ps-3">
                                <i class="bi bi-shop text-primary fs-5"></i>
                            </span>
                            <select name="customer_id" class="form-select form-select-lg bg-light border-start-0 rounded-end-3 shadow-none" required style="font-size: 0.95rem;">
                                <option value="">-- Pilih Nama Toko --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- 2. INPUT TANGGAL --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary" style="font-size: 0.8rem; letter-spacing: 0.5px;">TANGGAL RENCANA</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-3 ps-3">
                                <i class="bi bi-calendar-event text-primary fs-5"></i>
                            </span>
                            <input type="date" name="visit_date"
                                   class="form-control form-control-lg bg-light border-start-0 rounded-end-3 shadow-none"
                                   value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}" required
                                   style="font-size: 0.95rem;">
                        </div>
                    </div>

                    {{-- 3. INPUT CATATAN --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary" style="font-size: 0.8rem; letter-spacing: 0.5px;">CATATAN (OPSIONAL)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-3 ps-3 pt-3 align-items-start">
                                <i class="bi bi-journal-text text-primary fs-5"></i>
                            </span>
                            <textarea name="notes" class="form-control bg-light border-start-0 rounded-end-3 shadow-none"
                                      rows="3" placeholder="Contoh: Menawarkan promo akhir tahun..." style="resize: none;"></textarea>
                        </div>
                    </div>

                    {{-- TOMBOL AKSI --}}
                    <div class="d-grid gap-2 mt-5">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-3">
                            <i class="bi bi-save2 me-2"></i> Simpan Rencana
                        </button>

                        {{-- Tombol Batal (Opsional jika user salah pencet) --}}
                        <a href="{{ route('dashboard') }}" class="btn btn-link text-decoration-none text-muted btn-sm mt-2">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
