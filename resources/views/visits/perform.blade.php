@extends('layouts.app')

@section('title', 'Eksekusi Kunjungan')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-geo-alt-fill"></i> Check-in: {{ $visit->customer->name }}</h5>
                </div>
                <div class="card-body">

                    <div class="alert alert-light border mb-3">
                        <small class="text-muted d-block">Catatan Rencana:</small>
                        <strong>"{{ $visit->notes ?? '-' }}"</strong>
                    </div>

                    <form action="{{ route('visits.update', $visit->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT') <div class="mb-3">
                            <label class="form-label fw-bold">Lokasi Anda Sekarang</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light"><i class="bi bi-geo"></i></span>
                                <input type="text" id="locationDisplay" class="form-control" readonly
                                    placeholder="Wajib ambil lokasi">
                            </div>
                            <input type="hidden" name="latitude" id="lat">
                            <input type="hidden" name="longitude" id="long">
                            <button type="button" onclick="getLocation()" class="btn btn-outline-success w-100"
                                id="btnGetLoc">
                                <i class="bi bi-crosshair"></i> Ambil Lokasi
                            </button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Bukti Foto Realisasi</label>
                            <input type="file" name="photo" class="form-control" accept="image/*" capture="environment"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Update Laporan (Opsional)</label>
                            <textarea name="notes" class="form-control" rows="2">{{ $visit->notes }}</textarea>
                        </div>
                        <div class="card-footer bg-white">

                            <button type="submit" id="btn-finish" class="btn btn-secondary w-100 py-2" disabled>
                                <i class="fas fa-hourglass-half me-2"></i>
                                Menunggu Waktu Minimal... (<span id="timer-display">--:--</span>)
                            </button>

                            <div class="text-center mt-2">
                                <small class="text-muted" id="status-text">
                                    *Tombol akan aktif otomatis setelah 20 menit kunjungan.
                                </small>
                            </div>
                        </div>

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
                    </form>
                </div>
            </div>
        </div>
    </div>

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
