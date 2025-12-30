<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ \App\Models\Setting::where('key', 'app_name')->value('value') ?? 'Aplikasi' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicon & Manifest --}}
    <link rel="icon" type="image/png" href="{{ asset('logo-512.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0d6efd">
    <link rel="apple-touch-icon" href="{{ asset('logo-192.png') }}">

    {{-- CSS & JS (Vite) --}}
    @vite(['resources/css/app.scss', 'resources/js/app.js'])

    {{-- CSS Libraries --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    {{-- FIX UTAMA: JQUERY & BOOTSTRAP DI TARUH DI SINI (HEAD) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Service Worker --}}
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').then(function() {
                console.log('PWA Service Worker Registered');
            });
        }
    </script>
</head>

<body>

    <div id="wrapper">
        {{-- 1. INCLUDE SIDEBAR --}}
        @auth
            @include('layouts.partials.sidebar')
        @endauth

        {{-- Overlay untuk Mobile --}}
        <div class="overlay" id="overlay"></div>

        {{-- KONTEN SEBELAH KANAN --}}
        <div id="content">
            {{-- 2. INCLUDE NAVBAR --}}
            @include('layouts.partials.navbar')

            {{-- 3. KONTEN UTAMA (Dinamis) --}}
            <div class="container-fluid p-4">
                @yield('content')
            </div>
        </div>
    </div>

    {{-- 4. INCLUDE FOOTER --}}
    @include('layouts.partials.footer')

    {{-- 1. JS Libraries Global --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- 2. Panggil Script Custom (Sidebar toggle, jQuery bawaan template, dll) --}}
    @include('layouts.partials.scripts')

    {{-- 3. [PENTING] TEMPAT MENAMPUNG SCRIPT DARI HALAMAN LAIN --}}
    @stack('scripts')  {{-- <--- TAMBAHKAN BARIS INI!!! --}}

</body>
</html>
