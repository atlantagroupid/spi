@extends('layouts.app')

@section('title', 'Edit Data Toko')
{{-- LEAFLET CSS & JS (GRATIS) --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

{{-- SEARCH BOX PLUGIN --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<style>
    #map {
        height: 400px;
        width: 100%;
        border-radius: 8px;
        border: 2px solid #ddd;
        z-index: 1;
    }
</style>
@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Perbarui Data Toko: {{ $customer->name }}</h5>
                </div>
                <div class="card-body">
                    {{-- Form mengarah ke Route UPDATE --}}
                    <form action="{{ route('customers.update', $customer->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- 1. Nama Toko --}}
                        <div class="mb-3">
                            <label class="form-label">Nama Toko</label>
                            <input type="text" name="name" class="form-control" required
                                value="{{ old('name', $customer->name) }}">
                        </div>

                        {{-- 2. Kontak & Telepon --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Pemilik / PIC</label>
                                <input type="text" name="contact_person" class="form-control"
                                    value="{{ old('contact_person', $customer->contact_person) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No. Telepon / WA</label>
                                <input type="text" name="phone" class="form-control" required
                                    value="{{ old('phone', $customer->phone) }}">
                            </div>
                        </div>

                        {{-- 3. Alamat --}}
                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="address" class="form-control" rows="3" required>{{ old('address', $customer->address) }}</textarea>
                        </div>

                        {{-- 4. Kategori (Dropdown) --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kategori Pelanggan</label>
                            <select name="category" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->name }}"
                                        {{ old('category', $customer->category) == $cat->name ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <hr>

                        <h5 class="text-primary mt-4"><i class="bi bi-map-fill"></i> Update Lokasi Toko</h5>
                        <hr>

                        {{-- 1. AREA PETA --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Geser Marker untuk Update Lokasi</label>
                            <div id="map"
                                style="height: 400px; width: 100%; border-radius: 8px; border: 2px solid #ddd;"></div>
                        </div>

                        {{-- 2. INPUT KOORDINAT (Readonly) --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Latitude</label>
                                <input type="text" name="latitude" id="latInput" class="form-control bg-light"
                                    value="{{ old('latitude', $customer->latitude) }}" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Longitude</label>
                                <input type="text" name="longitude" id="lngInput" class="form-control bg-light"
                                    value="{{ old('longitude', $customer->longitude) }}" readonly>
                            </div>
                        </div>

                        {{-- Input hidden untuk nilai default TOP & Limit agar tidak error di database --}}
                        <input type="hidden" name="top_days" value="0">
                        <input type="hidden" name="credit_limit" value="0">

                        {{-- Tombol Aksi --}}
                        <div class="d-flex justify-content-end mt-3">
                            <a href="{{ route('customers.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Ambil Data Lama
            var latLama = "{{ $customer->latitude }}";
            var lngLama = "{{ $customer->longitude }}";

            // Cek apakah ada data lama? Kalau tidak, default ke Jakarta
            var startLat = (latLama && latLama != 0) ? latLama : -6.200000;
            var startLng = (lngLama && lngLama != 0) ? lngLama : 106.816666;
            var zoomLevel = (latLama) ? 16 : 13;

            // 2. Inisialisasi Peta
            var map = L.map('map').setView([startLat, startLng], zoomLevel);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            var marker;

            // 3. Jika ada lokasi lama, pasang marker
            if (latLama && lngLama) {
                marker = L.marker([startLat, startLng]).addTo(map);
            }

            // 4. Fungsi Update Input
            function updateInputs(lat, lng) {
                document.getElementById('latInput').value = lat;
                document.getElementById('lngInput').value = lng;
            }

            // 5. Listener Klik Peta (Ganti Lokasi)
            map.on('click', function(e) {
                var lat = e.latlng.lat;
                var lng = e.latlng.lng;

                if (marker) {
                    marker.setLatLng(e.latlng);
                } else {
                    marker = L.marker(e.latlng).addTo(map);
                }
                updateInputs(lat, lng);
            });
        });
    </script>
@endsection
