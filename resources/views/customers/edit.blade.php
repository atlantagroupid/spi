@extends('layouts.app')

@section('title', 'Edit Data Toko')

{{-- LEAFLET ASSETS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<style>
    #map { height: 400px; width: 100%; border-radius: 8px; border: 1px solid #dee2e6; z-index: 1; }
    @media (max-width: 576px) { #map { height: 300px; } }
</style>

@section('content')
<div class="container-fluid px-0 px-md-3">

    {{-- HEADER DESKTOP --}}
    <div class="d-none d-md-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Edit Data Toko</h1>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- HEADER MOBILE --}}
    <div class="d-md-none mb-3">
        <a href="{{ route('customers.index') }}" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke List
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 d-none d-md-block">
                    <h5 class="mb-0 fw-bold text-warning"><i class="bi bi-pencil-square me-2"></i>Update: {{ $customer->name }}</h5>
                </div>
                <div class="card-body p-3 p-md-4">
                    <form action="{{ route('customers.update', $customer->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary text-uppercase">Nama Toko</label>
                            <input type="text" name="name" class="form-control" required value="{{ old('name', $customer->name) }}">
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label fw-bold small text-secondary text-uppercase">Pemilik / PIC</label>
                                <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $customer->contact_person) }}">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label fw-bold small text-secondary text-uppercase">No. HP / WA</label>
                                <input type="text" name="phone" class="form-control" required value="{{ old('phone', $customer->phone) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary text-uppercase">Alamat Lengkap</label>
                            <textarea name="address" class="form-control" rows="3" required>{{ old('address', $customer->address) }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary text-uppercase">Kategori</label>
                            <select name="category" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->name }}" {{ old('category', $customer->category) == $cat->name ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="alert alert-light border border-warning border-opacity-25 rounded-3 mb-3">
                            <h6 class="fw-bold text-warning mb-2"><i class="bi bi-map-fill me-2"></i>Update Lokasi</h6>
                            <div id="map" class="mb-3 shadow-sm"></div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="text" name="latitude" id="latInput" class="form-control form-control-sm bg-light" value="{{ old('latitude', $customer->latitude) }}" readonly>
                                </div>
                                <div class="col-6">
                                    <input type="text" name="longitude" id="lngInput" class="form-control form-control-sm bg-light" value="{{ old('longitude', $customer->longitude) }}" readonly>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden --}}
                        <input type="hidden" name="top_days" value="{{ $customer->top_days ?? 0 }}">
                        <input type="hidden" name="credit_limit" value="{{ $customer->credit_limit ?? 0 }}">

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-warning text-dark fw-bold py-2 shadow-sm">
                                <i class="bi bi-save2 me-2"></i> SIMPAN PERUBAHAN
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
        var latLama = "{{ $customer->latitude }}";
        var lngLama = "{{ $customer->longitude }}";
        var startLat = (latLama && latLama != 0) ? latLama : -6.200000;
        var startLng = (lngLama && lngLama != 0) ? lngLama : 106.816666;
        var zoomLevel = (latLama) ? 16 : 13;

        var map = L.map('map').setView([startLat, startLng], zoomLevel);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);

        var marker;
        if (latLama && lngLama) {
            marker = L.marker([startLat, startLng]).addTo(map);
        }

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
    });
</script>
@endsection
