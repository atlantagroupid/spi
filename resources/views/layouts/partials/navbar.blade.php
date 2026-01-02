{{-- NAVBAR ATAS --}}
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm px-3 py-2 sticky-top" style="height: 60px;">
    <div class="container-fluid p-0">

        {{-- 1. TOMBOL TOGGLE SIDEBAR (HAMBURGER) --}}
        @auth
            <button type="button" id="sidebarCollapse" class="btn btn-link text-secondary border-0 p-1 me-2 d-lg-none">
                <i class="bi bi-list fs-2"></i>
            </button>
        @endauth

        {{-- 2. JUDUL HALAMAN (Responsive Text) --}}
        <span class="navbar-brand fw-bold text-dark text-truncate"
              style="max-width: 60vw; font-size: 1.1rem;">
            @yield('title')
        </span>

        <div class="ms-auto d-flex align-items-center">
            @auth
                {{-- 3. NOTIFIKASI --}}
                <div class="dropdown me-2 me-md-3">
                    <a class="nav-link position-relative text-secondary p-1" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5"></i>
                        @if (Auth::user()->unreadNotifications->count() > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 0.6rem;">
                                {{ Auth::user()->unreadNotifications->count() > 9 ? '9+' : Auth::user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3"
                        style="width: 280px; max-height: 400px; overflow-y: auto;">
                        <li class="dropdown-header fw-bold bg-light py-2 border-bottom">Notifikasi</li>
                        @forelse(Auth::user()->unreadNotifications as $notification)
                            <li>
                                <a class="dropdown-item d-flex align-items-start p-2 border-bottom"
                                    href="{{ $notification->data['link'] ?? '#' }}?read={{ $notification->id }}">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                        <i class="bi {{ $notification->data['icon'] ?? 'bi-info-circle' }} fs-6"></i>
                                    </div>
                                    <div class="w-100">
                                        <h6 class="mb-0 small fw-bold text-wrap">
                                            {{ $notification->data['title'] ?? 'Info' }}</h6>
                                        <p class="mb-0 small text-muted text-truncate" style="max-width: 180px;">
                                            {{ $notification->data['message'] ?? '' }}</p>
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li class="text-center py-4 text-muted small">
                                <i class="bi bi-bell-slash d-block fs-4 mb-2 opacity-50"></i>
                                Tidak ada notifikasi baru.
                            </li>
                        @endforelse
                        @if (Auth::user()->unreadNotifications->count() > 0)
                            <li><a class="dropdown-item text-center small text-primary fw-bold py-2 bg-light"
                                    href="{{ route('notifications.markRead') }}">Tandai Semua Dibaca</a></li>
                        @endif
                    </ul>
                </div>

                {{-- 4. PROFIL USER --}}
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark"
                        id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        @if (Auth::user()->photo)
                            <img src="{{ asset('storage/' . Auth::user()->photo) }}" width="35" height="35"
                                class="rounded-circle border object-fit-cover shadow-sm">
                        @else
                            <div class="bg-primary rounded-circle text-white d-flex justify-content-center align-items-center shadow-sm"
                                style="width: 35px; height: 35px; font-size: 0.9rem; font-weight: bold;">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                        @endif
                        {{-- Nama User: Hidden di Mobile --}}
                        <span class="d-none d-lg-inline ms-2 small fw-bold text-secondary">{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3">
                        <li class="px-3 py-2 d-lg-none border-bottom mb-2">
                            <div class="fw-bold text-dark">{{ Auth::user()->name }}</div>
                            <div class="small text-muted" style="font-size: 0.75rem;">{{ Auth::user()->role }}</div>
                        </li>
                        <li><a class="dropdown-item small py-2" href="{{ route('profile.edit') }}"><i
                                    class="bi bi-person me-2 text-primary"></i>Profil Saya</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item small text-danger py-2"><i
                                        class="bi bi-box-arrow-right me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth
        </div>
    </div>
</nav>
