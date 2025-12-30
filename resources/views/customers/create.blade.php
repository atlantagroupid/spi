@extends('layouts.app')

@section('title', 'Tambah Toko Baru')
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
                    <h5 class="mb-0 fw-bold">Registrasi Toko Baru</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('customers.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Nama Toko</label>
                            <input type="text" name="name" class="form-control" required
                                placeholder="Contoh: TB. Maju Jaya">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Pemilik / PIC</label>
                                <input type="text" name="contact_person" class="form-control"
                                    placeholder="Contoh: Pak Budi">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No. Telepon / WA</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="address" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kategori Pelanggan</label>
                            <select name="category" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>

                                {{-- Looping Data dari Database --}}
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->name }}"
                                        {{ (old('category') ?? ($customer->category ?? '')) == $cat->name ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach

                            </select>
                        </div>
                        <hr>
                        <h5 class="text-primary mt-4"><i class="bi bi-map-fill"></i> Lokasi Toko (Maps)</h5>
                        <hr>

                        {{-- 1. AREA PETA --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Cari Alamat / Klik pada Peta</label>
                            <div id="map"></div>
                            <small class="text-muted"><i class="bi bi-info-circle"></i> Gunakan fitur pencarian (ikon kaca
                                pembesar) atau klik langsung di peta untuk menandai lokasi toko.</small>
                        </div>

                        {{-- 2. INPUT KOORDINAT (Readonly - Terisi Otomatis) --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Latitude</label>
                                <input type="text" name="latitude" id="latInput" class="form-control bg-light" readonly
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Longitude</label>
                                <input type="text" name="longitude" id="lngInput" class="form-control bg-light" readonly
                                    required>
                            </div>
                        </div>

                        {{-- Input hidden untuk nilai default TOP & Limit agar tidak error di database --}}
                        <input type="hidden" name="top_days" value="0">
                        <input type="hidden" name="credit_limit" value="0">

                        <div class="d-flex justify-content-end mt-3">
                            <a href="{{ route('customers.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Toko</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Inisialisasi Peta
            // Default view: Jakarta (Bisa diganti ke lokasi default perusahaan Anda)
            var map = L.map('map').setView([-6.200000, 106.816666], 13);

            // 2. Tambahkan Tile Layer (Peta Jalan OpenStreetMap)
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            // 3. Variabel Marker
            var marker;

            // 4. Fungsi Update Input saat Marker Berubah
            function updateInputs(lat, lng) {
                document.getElementById('latInput').value = lat;
                document.getElementById('lngInput').value = lng;
            }

            // 5. Listener: Klik pada Peta
            map.on('click', function(e) {
                var lat = e.latlng.lat;
                var lng = e.latlng.lng;

                // Pindahkan Marker
                if (marker) {
                    marker.setLatLng(e.latlng);
                } else {
                    marker = L.marker(e.latlng).addTo(map);
                }

                updateInputs(lat, lng);
            });

            // 6. Fitur Pencarian Alamat (Geocoder)
            L.Control.geocoder({
                    defaultMarkGeocode: false
                })
                .on('markgeocode', function(e) {
                    var bbox = e.geocode.bbox;
                    var poly = L.polygon([
                        bbox.getSouthEast(),
                        bbox.getNorthEast(),
                        bbox.getNorthWest(),
                        bbox.getSouthWest()
                    ]); // Zoom ke area hasil pencarian

                    map.fitBounds(poly.getBounds());

                    // Pasang Marker di hasil pencarian
                    var lat = e.geocode.center.lat;
                    var lng = e.geocode.center.lng;

                    if (marker) {
                        marker.setLatLng(e.geocode.center);
                    } else {
                        marker = L.marker(e.geocode.center).addTo(map);
                    }

                    updateInputs(lat, lng);
                })
                .addTo(map);

            // 7. (Opsional) Deteksi Lokasi Saya (GPS)
            if (navigator.geolocation) {
                // Buat tombol custom "Lokasi Saya"
                var locationControl = L.Control.extend({
                    options: {
                        position: 'topleft'
                    },
                    onAdd: function(map) {
                        var btn = L.DomUtil.create('button', 'btn btn-light btn-sm shadow mt-2 ms-2');
                        btn.innerHTML = '<i class="bi bi-crosshair"></i> Lokasi Saya';
                        btn.style.zIndex = 999;
                        btn.onclick = function(e) {
                            e.preventDefault(); // Cegah submit form
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
