@extends('layouts.app')

@section('title', 'Tambah Toko Baru')

{{-- LEAFLET ASSETS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<style>
    #map { height: 400px; width: 100%; border-radius: 8px; border: 1px solid #dee2e6; z-index: 1; }
    /* Peta agak pendek di HP biar tidak makan tempat */
    @media (max-width: 576px) { #map { height: 300px; } }
</style>

@section('content')
<div class="container-fluid px-0 px-md-3">

    {{-- HEADER DESKTOP (Hidden di Mobile) --}}
    <div class="d-none d-md-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Registrasi Toko Baru</h1>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- HEADER MOBILE (Simple Link) --}}
    <div class="d-md-none mb-3">
        <a href="{{ route('customers.index') }}" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke List Pelanggan
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 d-none d-md-block">
                    <h5 class="mb-0 fw-bold text-primary">Formulir Data Toko</h5>
                </div>
                <div class="card-body p-3 p-md-4">
                    <form action="{{ route('customers.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary text-uppercase">Nama Toko</label>
                            <input type="text" name="name" class="form-control" required placeholder="Contoh: TB. Maju Jaya">
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label fw-bold small text-secondary text-uppercase">Pemilik / PIC</label>
                                <input type="text" name="contact_person" class="form-control" placeholder="Contoh: Pak Budi">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label fw-bold small text-secondary text-uppercase">No. HP / WA</label>
                                <input type="number" name="phone" class="form-control" required placeholder="0812...">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary text-uppercase">Alamat Lengkap</label>
                            <textarea name="address" class="form-control" rows="3" required placeholder="Nama jalan, nomor, kelurahan..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary text-uppercase">Kategori</label>
                            <select name="category" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->name }}" {{ (old('category') ?? ($customer->category ?? '')) == $cat->name ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="alert alert-light border border-primary border-opacity-25 rounded-3 mb-3">
                            <h6 class="fw-bold text-primary mb-2"><i class="bi bi-map-fill me-2"></i>Lokasi Toko</h6>
                            <small class="d-block text-muted mb-2">Cari alamat atau geser pin di peta untuk titik akurat.</small>

                            <div id="map" class="mb-3 shadow-sm"></div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="text" name="latitude" id="latInput" class="form-control form-control-sm bg-light" readonly required placeholder="Latitude">
                                </div>
                                <div class="col-6">
                                    <input type="text" name="longitude" id="lngInput" class="form-control form-control-sm bg-light" readonly required placeholder="Longitude">
                                </div>
                            </div>
                        </div>

                        {{-- Hidden Default Values --}}
                        <input type="hidden" name="top_days" value="0">
                        <input type="hidden" name="credit_limit" value="0">

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary fw-bold py-2 shadow-sm">
                                <i class="bi bi-save2 me-2"></i> SIMPAN TOKO
                            </button>
                            <a href="{{ route('customers.index') }}" class="btn btn-light text-muted btn-sm">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var map = L.map('map').setView([-6.200000, 106.816666], 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);

        var marker;

        function updateInputs(lat, lng) {
            document.getElementById('latInput').value = lat;
            document.getElementById('lngInput').value = lng;
        }

        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;
            if (marker) marker.setLatLng(e.latlng);
            else marker = L.marker(e.latlng).addTo(map);
            updateInputs(lat, lng);
        });

        L.Control.geocoder({ defaultMarkGeocode: false })
            .on('markgeocode', function(e) {
                var center = e.geocode.center;
                map.setView(center, 16);
                if (marker) marker.setLatLng(center);
                else marker = L.marker(center).addTo(map);
                updateInputs(center.lat, center.lng);
            })
            .addTo(map);

        if (navigator.geolocation) {
            var locationControl = L.Control.extend({
                options: { position: 'topleft' },
                onAdd: function(map) {
                    var btn = L.DomUtil.create('button', 'btn btn-light btn-sm shadow mt-2 ms-2 fw-bold');
                    btn.innerHTML = '<i class="bi bi-crosshair"></i> Lokasi Saya';
                    btn.onclick = function(e) {
                        e.preventDefault();
                        navigator.geolocation.getCurrentPosition(function(position) {
                            var lat = position.coords.latitude;
                            var lng = position.coords.longitude;
                            var latlng = [lat, lng];
                            map.setView(latlng, 18);
                            if (marker) marker.setLatLng(latlng);
                            else marker = L.marker(latlng).addTo(map);
                            updateInputs(lat, lng);
                        });
                    }
                    return btn;
                }
            });
            map.addControl(new locationControl());
        }
    });
</script>
@endsection
