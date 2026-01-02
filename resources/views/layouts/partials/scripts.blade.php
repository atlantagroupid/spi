<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        // --- LOGIC SIDEBAR TOGGLE ---
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const btnOpen = document.getElementById('sidebarCollapse'); // Tombol di Navbar
        const btnClose = document.getElementById('sidebarClose');   // Tombol X di Sidebar

        // Fungsi Buka/Tutup
        function toggleSidebar() {
            if(sidebar) sidebar.classList.toggle('active');
            if(overlay) overlay.classList.toggle('active');
        }

        // Fungsi Tutup Saja (dipakai saat klik overlay atau tombol X)
        function closeSidebar() {
            if(sidebar) sidebar.classList.remove('active');
            if(overlay) overlay.classList.remove('active');
        }

        // Event Listeners
        if (btnOpen) {
            btnOpen.addEventListener('click', function(e) {
                e.stopPropagation(); // Mencegah event bubbling
                toggleSidebar();
            });
        }

        if (btnClose) {
            btnClose.addEventListener('click', closeSidebar);
        }

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }
    });
</script>

{{-- Sisa script SweetAlert di bawahnya biarkan saja --}}
{{-- JS SweetAlert2 Logic --}}
<script>
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

    @if (session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: "{{ session('error') }}",
            confirmButtonColor: '#d33',
            confirmButtonText: 'Tutup'
        });
    @endif

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
    function confirmSubmit(event, title = 'Konfirmasi', text = 'Apakah Anda yakin ingin melanjutkan?') {
        event.preventDefault();
        const form = event.target.closest('form');
        Swal.fire({
            title: title,
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Lanjutkan!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>
