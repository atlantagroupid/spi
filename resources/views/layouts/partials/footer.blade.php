<footer class="sticky-footer bg-white py-3 border-top shadow-sm mt-auto">
    <div class="container-fluid px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small">

            {{-- BAGIAN KIRI: Copyright & Perusahaan --}}
            <div class="text-muted mb-2 mb-md-0">
                <span>Copyright &copy; {{ date('Y') }}</span>
                <span class="fw-bold text-primary mx-1">
                    {{ \App\Models\Setting::where('key', 'company_name')->value('value') ?? 'Bintang Interior' }}
                </span>
                <span class="d-none d-sm-inline">&bull; All Rights Reserved.</span>
            </div>

            {{-- BAGIAN KANAN: Versi & Credits --}}
            <div class="text-muted">
                <span class="me-3">
                    <i class="bi bi-box-seam me-1"></i>Versi 1.0.0
                </span>
                <span>
                    Dibuat dengan <i class="bi bi-heart-fill text-danger mx-1"></i> oleh
                    <a href="#" class="text-decoration-none fw-bold text-secondary">Tim IT</a>
                </span>
            </div>

        </div>
    </div>
</footer>
