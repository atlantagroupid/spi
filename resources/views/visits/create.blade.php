@extends('layouts.app')

@section('title', 'Check-in Kunjungan')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">

        <div class="card shadow border-0">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-geo-alt-fill"></i> Form Check-in</h5>
            </div>
            <div class="card-body">

                <form action="{{ route('visits.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <ul class="nav nav-pills nav-fill mb-4 gap-2" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active border" id="pills-existing-tab" data-bs-toggle="pill" data-bs-target="#pills-existing" type="button" onclick="setMode('existing')">
                                <i class="bi bi-shop"></i> Toko Langganan
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link border" id="pills-new-tab" data-bs-toggle="pill" data-bs-target="#pills-new" type="button" onclick="setMode('new')">
                                <i class="bi bi-plus-circle"></i> Toko Baru
                            </button>
                        </li>
                    </ul>

                    <input type="hidden" name="type" id="inputType" value="existing">

                    <div class="tab-content mb-4" id="pills-tabContent">

                        <div class="tab-pane fade show active" id="pills-existing">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Cari Nama Toko</label>
                                <select name="customer_id" class="form-select form-select-lg">
                                    <option value="">-- Pilih Toko --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->address }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="pills-new">
                            <div class="alert alert-info small">
                                <i class="bi bi-info-circle"></i> Toko ini akan otomatis tersimpan ke Master Data.
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Toko Baru</label>
                                <input type="text" name="new_name" class="form-control" placeholder="Contoh: TB. Barokah Jaya">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">No. HP / WA</label>
                                    <input type="text" name="new_phone" class="form-control" placeholder="0812...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Pemilik (Opsional)</label>
                                    <input type="text" name="new_contact" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="new_address" class="form-control" rows="2" placeholder="Jalan, Desa, Kecamatan..."></textarea>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Lokasi GPS</label>

                            @if(auth()->user()->role == 'sales_store')
                                <div class="alert alert-success d-flex align-items-center" role="alert">
                                    <i class="bi bi-shop me-2 fs-4"></i>
                                    <div>
                                        <strong>Mode Sales Store</strong><br>
                                        <small>Lokasi GPS tidak diperlukan.</small>
                                    </div>
                                </div>
                            @else
                                <div class="input-group mb-2">
                                    <button type="button" onclick="getLocation()" class="btn btn-outline-primary" id="btnGetLoc">
                                        <i class="bi bi-crosshair"></i> Ambil
                                    </button>
                                    <input type="text" id="locationDisplay" class="form-control" readonly placeholder="Wajib diambil">
                                </div>
                                <input type="hidden" name="latitude" id="lat">
                                <input type="hidden" name="longitude" id="long">
                            @endif
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Bukti Foto</label>
                            <input type="file" name="photo" class="form-control" accept="image/*" capture="environment" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Laporan Hasil</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Hasil kunjungan..." required></textarea>
                    </div>

                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold">Simpan Kunjungan</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->role != 'sales_store')
<script>
    function setMode(mode) {
        document.getElementById('inputType').value = mode;
    }

    function getLocation() {
        const btn = document.getElementById('btnGetLoc');
        const display = document.getElementById('locationDisplay');
        btn.innerHTML = '...';

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    document.getElementById('lat').value = pos.coords.latitude;
                    document.getElementById('long').value = pos.coords.longitude;
                    display.value = pos.coords.latitude + ", " + pos.coords.longitude;
                    btn.className = "btn btn-success";
                    btn.innerHTML = '<i class="bi bi-check"></i>';
                },
                (err) => { alert("Gagal ambil lokasi."); btn.innerHTML = 'Ulang'; },
                { enableHighAccuracy: true }
            );
        } else { alert("Browser tidak support GPS."); }
    }
</script>
@else
<script>
    function setMode(mode) {
        document.getElementById('inputType').value = mode;
    }
</script>
@endif

@endsection
