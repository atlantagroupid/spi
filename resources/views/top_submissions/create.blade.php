@extends('layouts.app')

@section('title', 'Pengajuan TOP')

@section('content')
    {{-- LOAD SELECT2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        /* CSS Select2 Mobile Friendly */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 48px;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 0.5rem;
            background-color: #fff;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            font-size: 1rem; line-height: 1.5; color: #212529; padding-left: 0;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            top: 50%; transform: translateY(-50%); right: 15px;
        }
        .select2-container { width: 100% !important; z-index: 100; }
    </style>

<div class="container-fluid px-0 px-md-3 pb-5">

    {{-- HEADER --}}
    <div class="d-none d-md-block mb-3">
        <h4 class="fw-bold mb-1 text-dark">Pengajuan TOP</h4>
        <p class="text-muted small">Ajukan kenaikan Limit atau Tempo pembayaran.</p>
    </div>

    {{-- INFO KUOTA (Mobile Card) --}}
    <div class="card bg-info text-white shadow-sm mb-4 border-0 rounded-3">
        <div class="card-body py-3 d-flex align-items-center justify-content-between">
            <div>
                <small class="text-uppercase fw-bold opacity-75" style="font-size: 0.7rem;">Sisa Kuota Anda</small>
                <h3 class="mb-0 fw-bold">Rp {{ number_format(auth()->user()->credit_limit_quota, 0, ',', '.') }}</h3>
            </div>
            <i class="bi bi-wallet2 fs-1 opacity-50"></i>
        </div>
    </div>

    <form action="{{ route('top-submissions.store') }}" method="POST" id="submissionForm">
        @csrf
        <input type="hidden" name="submission_type" id="submissionType" value="limit">

        {{-- 1. PILIH CUSTOMER --}}
        <div class="card border-0 shadow-sm mb-4 rounded-3">
            <div class="card-body p-3">
                <label class="form-label fw-bold text-uppercase small text-muted">Pilih Customer</label>
                <select name="customer_id" id="customerSelect" class="form-select select2" required onchange="updateCustomerInfo()">
                    <option value="" data-limit="0" data-days="0">-- Cari Nama Customer --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}"
                                data-limit="{{ $customer->credit_limit }}"
                                data-days="{{ $customer->top_days }}">
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>

                {{-- Info Current Limit --}}
                <div class="mt-3 p-3 bg-light rounded border border-secondary border-opacity-10" id="currentInfo" style="display: none;">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem;">Limit Saat Ini</small>
                            <span class="fw-bold text-dark" id="infoLimit">-</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem;">Tempo Saat Ini</small>
                            <span class="fw-bold text-dark" id="infoDays">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. TABS PILIHAN --}}
        <ul class="nav nav-pills nav-fill gap-2 mb-3 px-1" id="pills-tab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active py-2 shadow-sm border fw-bold small" id="pills-limit-tab"
                        data-bs-toggle="pill" data-bs-target="#pills-limit" type="button"
                        onclick="setType('limit')">
                    <i class="bi bi-cash-coin me-1"></i> Limit Uang
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-2 shadow-sm border fw-bold small" id="pills-days-tab"
                        data-bs-toggle="pill" data-bs-target="#pills-days" type="button"
                        onclick="setType('days')">
                    <i class="bi bi-calendar-day me-1"></i> Tempo Hari
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-2 shadow-sm border fw-bold small" id="pills-both-tab"
                        data-bs-toggle="pill" data-bs-target="#pills-both" type="button"
                        onclick="setType('both')">
                    Keduanya
                </button>
            </li>
        </ul>

        {{-- 3. ISI FORM --}}
        <div class="tab-content" id="pills-tabContent">

            {{-- TAB 1: LIMIT --}}
            <div class="tab-pane fade show active" id="pills-limit">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Nominal Limit Baru</label>
                        <div class="input-group input-group-lg mb-2">
                            <span class="input-group-text bg-white border-end-0">Rp</span>
                            <input type="number" name="limit_only" id="inputLimitOnly"
                                   class="form-control border-start-0 fw-bold text-primary" placeholder="0">
                        </div>
                        <div class="form-text small text-danger"><i class="bi bi-info-circle me-1"></i> Tempo hari tidak berubah.</div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: DAYS --}}
            <div class="tab-pane fade" id="pills-days">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Total Tempo Baru</label>
                        <div class="input-group input-group-lg mb-2">
                            <input type="number" name="days_only" id="inputDaysOnly"
                                   class="form-control border-end-0 fw-bold text-primary" placeholder="0">
                            <span class="input-group-text bg-white border-start-0">Hari</span>
                        </div>
                        <div class="form-text small text-success"><i class="bi bi-info-circle me-1"></i> Limit uang tidak berubah.</div>
                    </div>
                </div>
            </div>

            {{-- TAB 3: BOTH --}}
            <div class="tab-pane fade" id="pills-both">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Nominal Limit Baru</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0">Rp</span>
                                <input type="number" name="limit_both" id="inputLimitBoth"
                                       class="form-control border-start-0 fw-bold text-primary">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Total Tempo Baru</label>
                            <div class="input-group input-group-lg">
                                <input type="number" name="days_both" id="inputDaysBoth"
                                       class="form-control border-end-0 fw-bold text-primary">
                                <span class="input-group-text bg-white border-start-0">Hari</span>
                            </div>
                        </div>
                        <div class="alert alert-warning small py-2 mb-0 rounded-3">
                            <i class="bi bi-exclamation-triangle me-1"></i> Limit dan Tempo diperbarui sekaligus.
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- TOMBOL KIRIM MOBILE (Sticky) --}}
        <div class="fixed-bottom bg-white p-3 border-top shadow-lg d-md-none" style="z-index: 1000;">
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill">
                    <i class="bi bi-send-fill me-2"></i> Kirim Pengajuan
                </button>
            </div>
        </div>

        {{-- TOMBOL KIRIM DESKTOP --}}
        <div class="d-none d-md-grid mt-4">
             <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill shadow-sm">
                <i class="bi bi-send-fill me-2"></i> Kirim Pengajuan
            </button>
        </div>

        <div style="height: 100px;" class="d-md-none"></div>

    </form>
</div>

{{-- SCRIPT --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        setTimeout(function() {
            $('.select2').select2({ theme: 'bootstrap-5', width: '100%', placeholder: '-- Cari Nama Customer --' });
        }, 100);

        setType('limit');
    });

    function setType(type) {
        document.getElementById('submissionType').value = type;
        const limitOnly = document.getElementById('inputLimitOnly');
        const daysOnly = document.getElementById('inputDaysOnly');
        const limitBoth = document.getElementById('inputLimitBoth');
        const daysBoth = document.getElementById('inputDaysBoth');

        limitOnly.required = false; daysOnly.required = false; limitBoth.required = false; daysBoth.required = false;

        if (type === 'limit') {
            limitOnly.required = true;
        } else if (type === 'days') {
            daysOnly.required = true;
        } else if (type === 'both') {
            limitBoth.required = true; daysBoth.required = true;
        }
    }

    function updateCustomerInfo() {
        const select = document.getElementById('customerSelect');
        const option = select.options[select.selectedIndex];
        const infoDiv = document.getElementById('currentInfo');

        if (select.value) {
            infoDiv.style.display = 'block';
            const limit = parseFloat(option.getAttribute('data-limit')) || 0;
            const days = parseInt(option.getAttribute('data-days')) || 0;
            document.getElementById('infoLimit').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(limit);
            document.getElementById('infoDays').innerText = days + " Hari";
        } else {
            infoDiv.style.display = 'none';
        }
    }
</script>
@endsection
