<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        // --- LOGIC SIDEBAR TOGGLE ---
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        const overlay = document.getElementById('overlay');
        const btn = document.getElementById('sidebarCollapse');

        function toggleSidebar() {
            if(sidebar) sidebar.classList.toggle('active');
            if(overlay) overlay.classList.toggle('active');
        }

        if (btn) {
            btn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);
        }
    });
</script>

{{-- JS SweetAlert2 Logic --}}
<script>
    // 1. Cek apakah ada pesan SUKSES
    @if (session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    @endif

    // 2. Cek apakah ada pesan ERROR
    @if (session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: "{{ session('error') }}",
            confirmButtonColor: '#d33',
            confirmButtonText: 'Tutup'
        });
    @endif

    // 3. Konfirmasi Hapus
    function confirmDelete(event) {
        event.preventDefault();
        var form = event.target.form;
        Swal.fire({
            title: 'Yakin mau hapus?',
            text: "Data yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        })
    }
</script>
{{-- JS Global Confirm Submit Function --}}
<script>
    // FUNGSI GLOBAL KONFIRMASI (Bisa dipanggil dari mana saja)
    function confirmSubmit(event, title = 'Konfirmasi', text = 'Apakah Anda yakin ingin melanjutkan?') {
        // 1. Cegah form agar tidak langsung submit
        event.preventDefault();

        // 2. Cari elemen form terdekat dari tombol yang diklik
        const form = event.target.closest('form');

        // 3. Tampilkan SweetAlert
        Swal.fire({
            title: title,
            text: text,
            icon: 'question',         // Ikon tanda tanya
            showCancelButton: true,
            confirmButtonColor: '#0d6efd', // Warna tombol YA (Biru Primary)
            cancelButtonColor: '#6c757d',  // Warna tombol BATAL (Abu)
            confirmButtonText: 'Ya, Lanjutkan!',
            cancelButtonText: 'Batal',
            reverseButtons: true      // Tombol Batal di kiri
        }).then((result) => {
            // 4. Jika user klik "Ya", baru submit form secara manual
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>
