@extends('layouts.app')

@section('title', 'Check-in Visit')

@section('content')
    <div class="container-fluid px-0 px-md-3">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                {{-- HEADER SIMPLE --}}
                <div class="card shadow border-0 rounded-4 overflow-hidden mb-3">
                    <div class="card-body bg-success text-white p-4 text-center">
                        <div class="bg-white bg-opacity-25 rounded-circle d-inline-flex p-3 mb-2">
                            <i class="bi bi-geo-alt-fill fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-1">{{ $visit->customer->name }}</h5>
                        <p class="mb-0 opacity-75 small">{{ $visit->customer->address }}</p>
                    </div>
                </div>

                {{-- FORM CARD --}}
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">

                        {{-- Catatan Rencana (Jika ada) --}}
                        @if ($visit->notes)
                            <div
                                class="alert alert-warning border-0 bg-warning bg-opacity-10 d-flex align-items-start small mb-4">
                                <i class="bi bi-sticky me-2 mt-1"></i>
                                <div>
                                    <strong class="d-block text-dark">Rencana:</strong>
                                    <span class="text-muted">"{{ $visit->notes }}"</span>
                                </div>
                            </div>
                        @endif

                        <form action="{{ route('visits.update', $visit->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf @method('PUT')

                            {{-- LOKASI --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">Lokasi GPS</label>
                                <div class="input-group mb-2">
                                    <button type="button" onclick="getLocation()" class="btn btn-outline-success"
                                        id="btnGetLoc">
                                        <i class="bi bi-crosshair"></i> Ambil
                                    </button>
                                    <input type="text" id="locationDisplay" class="form-control bg-light" readonly
                                        placeholder="Koordinat..." style="font-size: 0.9rem;">
                                </div>
                                <input type="hidden" name="latitude" id="lat">
                                <input type="hidden" name="longitude" id="long">
                                <div class="form-text small text-muted">*Pastikan GPS aktif & browser diizinkan.</div>
                            </div>

                            {{-- FOTO --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">Foto Bukti</label>
                                <div class="card bg-light border-dashed text-center p-3"
                                    onclick="document.getElementById('photoInput').click()"
                                    style="cursor: pointer; border: 2px dashed #dee2e6;">
                                    <i class="bi bi-camera fs-3 text-secondary"></i>
                                    <p class="mb-0 small text-muted">Ketuk untuk ambil foto</p>
                                </div>
                                <input type="file" name="photo" id="photoInput" class="d-none" accept="image/*"
                                    capture="environment" required onchange="previewFile()">
                                <small id="fileNameDisplay"
                                    class="d-block mt-2 text-primary text-center fst-italic"></small>
                            </div>

                            {{-- LAPORAN --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">Laporan Hasil</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Hasil kunjungan..." required>{{ $visit->notes }}</textarea>
                            </div>

                            {{-- TOMBOL SELESAI --}}
                            <div class="d-grid">
                                <button type="submit" id="btn-finish" class="btn btn-secondary py-3 rounded-pill fw-bold"
                                    disabled>
                                    <i class="fas fa-hourglass-half me-2"></i> Tunggu (<span
                                        id="timer-display">--:--</span>)
                                </button>
                            </div>
                            <p class="text-center small text-muted mt-2 mb-0" id="status-text">Minimal durasi 20 menit.</p>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- SCRIPT SAMA SEPERTI SEBELUMNYA, TAMBAHAN PREVIEW FILE --}}
    <script>
        function previewFile() {
            const input = document.getElementById('photoInput');
            const display = document.getElementById('fileNameDisplay');
            if (input.files.length > 0) {
                display.innerText = "Foto dipilih: " + input.files[0].name;
            }
        }
        // ... (Script Timer & Geolocation tetap sama) ...
    </script>
    {{-- SCRIPT PENGHITUNG MUNDUR (Letakkan di bawah tombol atau di paling bawah file sebelum @endsection) --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Ambil waktu check-in dari database (Format ISO biar aman)
            const checkInString = "{{ \Carbon\Carbon::parse($visit->check_in_time)->format('Y-m-d H:i:s') }}";
            const checkInTime = new Date(checkInString).getTime();

            // 2. Set Aturan Waktu (20 Menit dalam milidetik)
            const durationMinutes = 20;
            const requiredTime = durationMinutes * 60 * 1000;

            // 3. Tentukan Target Waktu Selesai (Jam Checkin + 20 menit)
            const unlockTime = checkInTime + requiredTime;

            // Elemen HTML
            const btn = document.getElementById('btn-finish');
            const timerDisplay = document.getElementById('timer-display');
            const statusText = document.getElementById('status-text');

            // Fungsi Update Timer
            const updateTimer = setInterval(function() {
                const now = new Date().getTime(); // Waktu browser sekarang
                const distance = unlockTime - now; // Selisih waktu

                // JIKA WAKTU SUDAH HABIS (Sudah > 20 menit)
                if (distance < 0) {
                    clearInterval(updateTimer); // Stop timer

                    // Ubah Tampilan Tombol jadi Hijau & Aktif
                    btn.disabled = false;
                    btn.classList.remove('btn-secondary');
                    btn.classList.add('btn-success', 'fw-bold'); // Hijau tebal
                    btn.innerHTML = '<i class="fas fa-check-circle me-2"></i> SELESAIKAN KUNJUNGAN';

                    // Ubah teks info
                    statusText.innerHTML =
                        '<span class="text-success"><i class="fas fa-check"></i> Waktu kunjungan tercapai. Silakan selesaikan.</span>';
                }
                // JIKA MASIH KURANG DARI 20 MENIT
                else {
                    // Hitung menit dan detik sisa
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    // Tampilkan di dalam kurung tombol
                    timerDisplay.innerText = minutes + "m " + seconds + "s";
                }
            }, 1000); // Update setiap 1 detik
        });
    </script>

    <script>
        function getLocation() {
            const btn = document.getElementById('btnGetLoc');
            const display = document.getElementById('locationDisplay');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        document.getElementById('lat').value = pos.coords.latitude;
                        document.getElementById('long').value = pos.coords.longitude;
                        display.value = pos.coords.latitude + ", " + pos.coords.longitude;
                        btn.className = "btn btn-success w-100";
                        btn.innerHTML = '<i class="bi bi-check-circle"></i> Lokasi OK';
                    },
                    (err) => {
                        alert("Gagal ambil lokasi. Pastikan GPS aktif.");
                        btn.innerHTML = 'Coba Lagi';
                    }, {
                        enableHighAccuracy: true
                    }
                );
            } else {
                alert("Browser tidak support GPS.");
            }
        }
    </script>
@endsection
