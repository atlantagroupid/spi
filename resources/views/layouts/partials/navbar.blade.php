{{-- NAVBAR ATAS --}}
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-3 py-2 sticky-top">
    <div class="container-fluid">

        @auth
            <button type="button" id="sidebarCollapse" class="btn btn-outline-secondary border-0 d-md-none me-2">
                <i class="bi bi-list fs-4"></i>
            </button>
        @endauth

        <span class="navbar-brand fw-bold fs-5 text-dark">@yield('title')</span>

        <div class="ms-auto d-flex align-items-center">
            @auth
                {{-- Notifikasi --}}
                <div class="dropdown me-3">
                    <a class="nav-link position-relative text-secondary p-1" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5"></i>
                        @if (Auth::user()->unreadNotifications->count() > 0)
                            <span
                                class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                <span class="visually-hidden">New alerts</span>
                            </span>
                        @endif
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2"
                        style="width: 300px; max-height: 400px; overflow-y: auto;">
                        <li class="dropdown-header fw-bold bg-light py-2">Notifikasi</li>
                        @forelse(Auth::user()->unreadNotifications as $notification)
                            <li>
                                <a class="dropdown-item d-flex align-items-start p-2 border-bottom"
                                    href="{{ $notification->data['link'] ?? '#' }}?read={{ $notification->id }}">
                                    <i
                                        class="bi {{ $notification->data['icon'] ?? 'bi-info-circle' }} fs-4 text-primary me-2"></i>
                                    <div>
                                        <h6 class="mb-0 small fw-bold">
                                            {{ $notification->data['title'] ?? 'Info' }}</h6>
                                        <p class="mb-0 small text-muted text-truncate" style="max-width: 200px;">
                                            {{ $notification->data['message'] ?? '' }}</p>
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li class="text-center py-3 text-muted small">Tidak ada notifikasi.</li>
                        @endforelse
                        @if (Auth::user()->unreadNotifications->count() > 0)
                            <li><a class="dropdown-item text-center small text-primary fw-bold py-2 bg-light"
                                    href="{{ route('notifications.markRead') }}">Tandai Semua Dibaca</a></li>
                        @endif
                    </ul>
                </div>

                {{-- Profil User --}}
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark"
                        id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        @if (Auth::user()->photo)
                            <img src="{{ asset('storage/' . Auth::user()->photo) }}" width="32" height="32"
                                class="rounded-circle me-2 border object-fit-cover">
                        @else
                            <div class="bg-primary rounded-circle text-white d-flex justify-content-center align-items-center me-2"
                                style="width: 32px; height: 32px; font-size: 0.9rem;">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                        @endif
                        <span class="d-none d-sm-inline small fw-semibold">{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2"
                        style="max-height: 400px; overflow-y: auto;">
                        <li><a class="dropdown-item small" href="{{ route('profile.edit') }}"><i
                                    class="bi bi-person me-2"></i>Profil Saya</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item small text-danger"><i
                                        class="bi bi-box-arrow-right me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth
        </div>
    </div>
</nav>
