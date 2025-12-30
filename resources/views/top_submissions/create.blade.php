@extends('layouts.app')

@section('content')
<div class="container pb-5">

    <div class="row mb-3">
        <div class="col-12">
            <h4 class="fw-bold mb-1">Pengajuan TOP</h4>
            <p class="text-muted small">Ajukan Limit, Tempo, atau Keduanya.</p>
        </div>
    </div>

    <div class="card bg-info text-white shadow-sm mb-4">
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

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <label class="form-label fw-bold text-uppercase small text-muted">Customer</label>
                <select name="customer_id" id="customerSelect" class="form-select form-select-lg" required onchange="updateCustomerInfo()">
                    <option value="" data-limit="0" data-days="0">-- Pilih Customer --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}"
                                data-limit="{{ $customer->credit_limit }}"
                                data-days="{{ $customer->top_days }}">
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
                <div class="mt-2 p-2 bg-light rounded border small text-muted" id="currentInfo" style="display: none;">
                    Saat ini: <span class="fw-bold" id="infoText">-</span>
                </div>
            </div>
        </div>

        <ul class="nav nav-pills nav-fill gap-2 mb-3" id="pills-tab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active py-2 shadow-sm border fw-bold small" id="pills-limit-tab"
                        data-bs-toggle="pill" data-bs-target="#pills-limit" type="button"
                        onclick="setType('limit')">
                    Limit
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-2 shadow-sm border fw-bold small" id="pills-days-tab"
                        data-bs-toggle="pill" data-bs-target="#pills-days" type="button"
                        onclick="setType('days')">
                    Hari
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

        <div class="tab-content" id="pills-tabContent">

            <div class="tab-pane fade show active" id="pills-limit">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <label class="form-label fw-bold">Nominal Limit Baru</label>
                        <div class="input-group input-group-lg mb-2">
                            <span class="input-group-text bg-white">Rp</span>
                            <input type="number" name="limit_only" id="inputLimitOnly"
                                   class="form-control fw-bold text-primary" placeholder="0">
                        </div>
                        <div class="form-text small text-danger">* Hari/Tempo tetap sama.</div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pills-days">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <label class="form-label fw-bold">Total Tempo Baru</label>
                        <div class="input-group input-group-lg mb-2">
                            <input type="number" name="days_only" id="inputDaysOnly"
                                   class="form-control fw-bold text-primary" placeholder="0">
                            <span class="input-group-text bg-white">Hari</span>
                        </div>
                        <div class="form-text small text-success">* Limit uang tetap sama.</div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pills-both">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nominal Limit Baru</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white">Rp</span>
                                <input type="number" name="limit_both" id="inputLimitBoth"
                                       class="form-control fw-bold text-primary">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Total Tempo Baru</label>
                            <div class="input-group input-group-lg">
                                <input type="number" name="days_both" id="inputDaysBoth"
                                       class="form-control fw-bold text-primary">
                                <span class="input-group-text bg-white">Hari</span>
                            </div>
                        </div>

                        <div class="alert alert-warning small py-2">
                            <i class="bi bi-exclamation-circle"></i> Limit dan Tempo akan diperbarui sekaligus.
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="fixed-bottom bg-white p-3 border-top shadow-lg" style="z-index: 1000;">
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill">
                    <i class="bi bi-send-fill me-2"></i> Kirim Pengajuan
                </button>
            </div>
        </div>
        <div style="height: 80px;"></div>

    </form>
</div>

<script>
    function setType(type) {
        // Set nilai hidden input agar Controller tahu tab mana yg aktif
        document.getElementById('submissionType').value = type;

        // Ambil elemen input
        const limitOnly = document.getElementById('inputLimitOnly');
        const daysOnly = document.getElementById('inputDaysOnly');
        const limitBoth = document.getElementById('inputLimitBoth');
        const daysBoth = document.getElementById('inputDaysBoth');

        // Reset & Atur Required sesuai Tab
        if (type === 'limit') {
            limitOnly.required = true;
            daysOnly.required = false;
            limitBoth.required = false;
            daysBoth.required = false;
        } else if (type === 'days') {
            limitOnly.required = false;
            daysOnly.required = true;
            limitBoth.required = false;
            daysBoth.required = false;
        } else if (type === 'both') {
            limitOnly.required = false;
            daysOnly.required = false;
            limitBoth.required = true;
            daysBoth.required = true;
        }
    }

    function updateCustomerInfo() {
        const select = document.getElementById('customerSelect');
        const option = select.options[select.selectedIndex];
        const infoDiv = document.getElementById('currentInfo');
        const infoText = document.getElementById('infoText');

        if (select.value) {
            infoDiv.style.display = 'block';
            const limit = parseFloat(option.getAttribute('data-limit')) || 0;
            const days = parseInt(option.getAttribute('data-days')) || 0;
            const formattedLimit = new Intl.NumberFormat('id-ID').format(limit);
            infoText.innerText = `Limit Rp ${formattedLimit} / ${days} Hari`;
        } else {
            infoDiv.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        setType('limit'); // Default tab
    });
</script>
@endsection
