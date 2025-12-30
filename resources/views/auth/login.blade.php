<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SFA Bintang Interior</title>

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    {{-- Font Google (Poppins biar modern) --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;

            /* BACKGROUND GAMBAR INTERIOR (Ganti URL ini dengan foto toko Anda jika mau) */
            background: url('images/logo.jpeg') no-repeat center center;

            /* Overlay Hitam Transparan (Biar tulisan terbaca) */
            position: relative;
        }

        /* Lapisan Gelap di atas Gambar */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            /* Gelap 40% */
            z-index: 0;
        }

        /* EFEK KACA (GLASSMORPHISM) */
        .glass-card {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.15);
            /* Putih Transparan */
            backdrop-filter: blur(15px);
            /* Efek Buram di belakangnya */
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            /* Garis tepi tipis */
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            max-width: 450px;
            width: 100%;
            color: #fff;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 0.95rem;
        }

        .form-control:focus {
            background: #fff;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.3);
        }

        .btn-login {
            background: #0d6efd;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: 0.3s;
        }

        .btn-login:hover {
            background: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4);
        }

        .logo-icon {
            font-size: 3rem;
            color: #fff;
            text-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .drop-shadow {
            filter: drop-shadow(0 5px 5px rgba(0, 0, 0, 0.3));
        }

        .text-shadow {
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>

<body>

    <div class="glass-card text-center">

        {{-- Logo Perusahaan --}}
        <div class="mb-4">
            {{-- GANTI ICON DENGAN GAMBAR --}}
            <img src="{{ asset('images/logo.png') }}" alt="Logo Bintang Interior" class="img-fluid drop-shadow"
                style="max-height: 80px; width: auto;">
            {{-- Atur max-height sesuai bentuk logo Anda --}}
        </div>

        {{-- Judul Aplikasi --}}

        <div class="sidebar-brand-text ms-2 text-start d-flex flex-column justify-content-center">
            <div class="fw-bold text-white text-uppercase" style="font-size: 2rem; letter-spacing: 1px;">
                {{ \App\Models\Setting::where('key', 'app_name')->value('value') ?? 'SFA BINTANG' }}
            </div>
            <div class="text-white-50 fst-italic mt-1" style="font-size: 0.65rem; font-weight: 300;">
                Interior System
            </div>
        </div>

        {{-- Form Login Laravel --}}
        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- Input Email --}}
            <div class="mb-3 text-start">
                <label class="form-label small ms-1 opacity-75">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text border-0 rounded-start-3 bg-white text-secondary">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" name="email"
                        class="form-control rounded-end-3 @error('email') is-invalid @enderror"
                        placeholder="nama@bintang.com" value="{{ old('email') }}" required autofocus>
                </div>
                @error('email')
                    <span class="text-warning small mt-1 d-block text-shadow">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </span>
                @enderror
            </div>

            {{-- Input Password --}}
            <div class="mb-4 text-start">
                <label class="form-label small ms-1 opacity-75">Password</label>
                <div class="input-group">
                    <span class="input-group-text border-0 rounded-start-3 bg-white text-secondary">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" name="password"
                        class="form-control rounded-end-3 @error('password') is-invalid @enderror"
                        placeholder="••••••••" required>
                </div>
                @error('password')
                    <span class="text-warning small mt-1 d-block text-shadow">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </span>
                @enderror
            </div>

            {{-- Remember Me --}}
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember"
                        {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label small" for="remember">
                        Ingat Saya
                    </label>
                </div>
                @if (Route::has('password.request'))
                    {{-- <a class="text-white small text-decoration-none opacity-75 hover-opacity-100" href="{{ route('password.request') }}">
                        Lupa Password?
                    </a> --}}
                @endif
            </div>

            {{-- Tombol Login --}}
            <button type="submit" class="btn btn-primary w-100 btn-login shadow-sm">
                MASUK APLIKASI <i class="bi bi-box-arrow-in-right ms-2"></i>
            </button>
        </form>

        <div class="mt-4 pt-3 border-top border-white border-opacity-25">
            <small class="opacity-50" style="font-size: 0.75rem;">
                &copy; {{ date('Y') }} CV. Bintang Interior & Keramik
            </small>
        </div>
    </div>

</body>

</html>
