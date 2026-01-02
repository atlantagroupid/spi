<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ \App\Models\Setting::where('key', 'app_name')->value('value') ?? 'SFA BINTANG' }}</title>

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    {{-- Font Google (Poppins) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            /* BACKGROUND IMAGE: Pastikan file ini ada di public/images/ */
            background: url("{{ asset('images/logo.jpeg') }}") no-repeat center center;
            background-size: cover;
            position: relative;
        }

        /* Overlay Gelap agar teks terbaca jelas */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.55); /* Tingkat kegelapan 55% */
            z-index: 0;
        }

        /* CARD GLASSMORPHISM */
        .glass-card {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.1); /* Transparan */
            backdrop-filter: blur(20px);          /* Efek Blur Kuat */
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2); /* Border halus */
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            max-width: 420px;
            width: 90%;
            color: #fff;
            overflow: hidden;
        }

        /* Header Logo & Title */
        .login-header {
            margin-bottom: 2rem;
        }

        .app-title {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .app-subtitle {
            font-size: 0.75rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            opacity: 0.8;
            margin-top: -5px;
        }

        /* Form Inputs */
        .form-control {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 0.95rem;
            color: #333;
        }

        .form-control:focus {
            background: #fff;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.2);
        }

        .input-group-text {
            border: none;
            background: rgba(255, 255, 255, 0.9);
            color: #666;
            border-radius: 12px 0 0 12px !important;
            padding-left: 15px;
        }

        .input-group .form-control {
            border-radius: 0 12px 12px 0 !important;
        }

        /* Button */
        .btn-login {
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4);
            background: linear-gradient(135deg, #0b5ed7 0%, #00358a 100%);
        }

        /* Checkbox & Links */
        .form-check-input {
            background-color: rgba(255, 255, 255, 0.5);
            border: none;
        }
        .form-check-input:checked {
            background-color: #0d6efd;
        }
        .form-check-label {
            font-size: 0.85rem;
            font-weight: 300;
        }

        /* Logo Animation */
        .logo-img {
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
            transition: transform 0.5s;
        }
        .logo-img:hover {
            transform: scale(1.05);
        }

        /* Footer kecil */
        .login-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 0.7rem;
            opacity: 0.6;
        }
    </style>
</head>

<body>

    <div class="glass-card text-center">

        {{-- HEADER: LOGO & JUDUL --}}
        <div class="login-header">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="img-fluid logo-img mb-3" style="height: 70px; width: auto;">

            <div class="app-title">
                {{ \App\Models\Setting::where('key', 'app_name')->value('value') ?? 'SFA BINTANG' }}
            </div>
            <div class="app-subtitle">Interior System</div>
        </div>

        {{-- FORM LOGIN --}}
        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- Input Email --}}
            <div class="mb-3 text-start">
                <label class="form-label small ms-1 text-white-50">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope-fill"></i>
                    </span>
                    <input type="email" name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="nama@email.com" value="{{ old('email') }}" required autofocus>
                </div>
                @error('email')
                    <span class="text-warning small mt-1 d-block fw-bold shadow-sm">
                        <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                    </span>
                @enderror
            </div>

            {{-- Input Password --}}
            <div class="mb-4 text-start">
                <label class="form-label small ms-1 text-white-50">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password" name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="••••••••" required>
                </div>
                @error('password')
                    <span class="text-warning small mt-1 d-block fw-bold shadow-sm">
                        <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                    </span>
                @enderror
            </div>

            {{-- Remember Me & Forgot Password --}}
            <div class="d-flex justify-content-between align-items-center mb-4 px-1">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">
                        Ingat Saya
                    </label>
                </div>
                {{-- Uncomment jika route reset password sudah ada --}}
                {{-- @if (Route::has('password.request'))
                    <a class="text-white small text-decoration-none opacity-75 hover-opacity-100" href="{{ route('password.request') }}">
                        Lupa Password?
                    </a>
                @endif --}}
            </div>

            {{-- Tombol Login --}}
            <button type="submit" class="btn btn-primary w-100 btn-login shadow-lg">
                MASUK APLIKASI <i class="bi bi-arrow-right-circle-fill ms-2"></i>
            </button>
        </form>

        {{-- COPYRIGHT FOOTER --}}
        <div class="login-footer">
            &copy; {{ date('Y') }} CV. Bintang Interior & Keramik<br>
            All Rights Reserved.
        </div>
    </div>

</body>
</html>
