@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Edit User: {{ $user->name }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name', $user->name) }}">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required value="{{ old('email', $user->email) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No HP / WhatsApp</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                        </div>
                    </div>

                    <div class="mb-3">
                                <label for="role" class="form-label">Role / Jabatan</label>
                                <select name="role" id="role" class="form-select" required>
                                    <option value="" disabled selected>-- Pilih Jabatan --</option>

                                    {{-- Looping Role dari Controller --}}
                                    @foreach ($roles as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach

                                </select>
                            </div>

                    <div id="kpi-section" style="display: none;">
                        <hr class="my-4 border-secondary opacity-25">

                        <div class="row">
                            <div class="col-12 mb-3">
                                <h5 class="fw-bold text-primary">
                                    <i class="bi bi-graph-up-arrow me-2"></i>Target Kinerja (KPI)
                                </h5>
                                <p class="text-muted small">Atur target individu khusus untuk Sales ini.</p>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Target Kunjungan Harian</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-geo-alt"></i></span>
                                    <input type="number" name="daily_visit_target" class="form-control"
                                           value="{{ old('daily_visit_target', $user->daily_visit_target ?? 5) }}"
                                           min="0">
                                    <span class="input-group-text bg-light small">Visit / Hari</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Target Omset Bulanan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">Rp</span>
                                    <input type="number" name="sales_target" class="form-control fw-bold text-success"
                                           value="{{ old('sales_target', $user->sales_target ?? 0) }}"
                                           placeholder="Contoh: 50000000">
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-4 border-secondary opacity-25">

                    <div class="alert alert-light border">
                        <label class="form-label fw-bold text-dark"><i class="bi bi-key me-2"></i>Ubah Password (Opsional)</label>
                        <input type="password" name="password" class="form-control" placeholder="Biarkan kosong jika tidak ingin mengganti password">
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('users.index') }}" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Update Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleKPI() {
        const roleSelect = document.getElementById('roleSelect');
        const kpiSection = document.getElementById('kpi-section');
        const role = roleSelect.value;

        // Daftar role yang dianggap sebagai SALES (yang butuh target)
        const salesRoles = ['sales', 'sales_field', 'sales_store'];

        // Jika role yang dipilih ada di dalam daftar salesRoles -> Tampilkan
        if (salesRoles.includes(role)) {
            kpiSection.style.display = 'block';
        } else {
            kpiSection.style.display = 'none';
            // Opsional: Reset nilai target jadi 0 kalau bukan sales?
            // document.getElementsByName('sales_target')[0].value = 0;
        }
    }

    // Jalankan fungsi saat halaman pertama kali dimuat (untuk handle edit data lama)
    document.addEventListener('DOMContentLoaded', function() {
        toggleKPI();
    });
</script>
@endsection
