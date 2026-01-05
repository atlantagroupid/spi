@extends('layouts.app')

@section('title', 'Edit Profil Saya')

@section('content')
<div class="container-fluid px-0 px-md-3 pb-5">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted small mb-0 d-none d-md-block">Perbarui informasi akun dan keamanan Anda.</p>
        </div>
    </div>

    <div class="row g-4">

        {{-- KOLOM KIRI: DATA DIRI --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-person-bounding-box me-2"></i>Data Diri</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- FOTO PROFIL DENGAN TOMBOL OVERLAY --}}
                        <div class="d-flex flex-column align-items-center mb-4">
                            <div class="position-relative">
                                @if($user->photo)
                                    <img src="{{ asset('storage/' . $user->photo) }}" class="rounded-circle border border-3 border-white shadow" style="width: 120px; height: 120px; object-fit: cover;">
                                @else
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center border shadow-sm" style="width: 120px; height: 120px;">
                                        <i class="bi bi-person-fill text-secondary" style="font-size: 3.5rem;"></i>
                                    </div>
                                @endif

                                {{-- Tombol Kamera Kecil --}}
                                <label for="photoInput" class="btn btn-sm btn-primary rounded-circle position-absolute bottom-0 end-0 shadow-sm border border-2 border-white"
                                       style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                                       title="Ganti Foto">
                                    <i class="bi bi-camera-fill"></i>
                                </label>
                            </div>

                            {{-- Input File Hidden --}}
                            <input type="file" name="photo" id="photoInput" class="d-none" accept="image/*" onchange="this.form.submit()">
                            <small class="text-muted mt-2 fst-italic" style="font-size: 0.75rem;">Ketuk ikon kamera untuk ganti foto</small>
                        </div>

                        {{-- INPUT FORM --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">No. HP / WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="phone" class="form-control border-start-0" value="{{ old('phone', $user->phone) }}" placeholder="0812...">
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-bold py-2 shadow-sm rounded-pill">
                                <i class="bi bi-save me-2"></i> SIMPAN PERUBAHAN
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: GANTI PASSWORD --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-warning">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-shield-lock-fill text-warning me-2"></i>Keamanan Akun</h6>
                </div>
                <div class="card-body p-4">

                    <div class="alert alert-light border small text-muted mb-4 d-flex align-items-start rounded-3">
                        <i class="bi bi-info-circle-fill text-warning me-2 mt-1 fs-5"></i>
                        <div>Gunakan password yang kuat (minimal 6 karakter) untuk menjaga keamanan akun Anda.</div>
                    </div>

                    <form action="{{ route('profile.password') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Password Saat Ini</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key"></i></span>
                                <input type="password" name="current_password" class="form-control border-start-0" required placeholder="••••••">
                            </div>
                            @error('current_password') <small class="text-danger fw-bold mt-1 d-block"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small> @enderror
                        </div>

                        <hr class="my-4 text-muted opacity-25">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Password Baru</label>
                            <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
                            @error('password') <small class="text-danger fw-bold mt-1 d-block"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" class="form-control" required placeholder="Ulangi password baru">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning text-dark fw-bold py-2 shadow-sm rounded-pill">
                                <i class="bi bi-check-circle-fill me-2"></i> UPDATE PASSWORD
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
