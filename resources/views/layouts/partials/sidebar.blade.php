@php
    $userRole = Auth::user()->role;
    $isManagerOperasional = $userRole === 'manager_operasional';
    $isManagerBisnis = $userRole === 'manager_bisnis';
    $isKepalaGudang = $userRole === 'kepala_gudang';
    $isAdminGudang = $userRole === 'admin_gudang';
    $isPurchase = $userRole === 'purchase';
    $isFinance = $userRole === 'finance';

    // Definisikan variabel Sales Spesifik
    $isSalesStore = $userRole === 'sales_store';
    $isSalesField = $userRole === 'sales_field';

    // GROUP SALES: Gabungan semua jenis sales agar logic lebih simpel
    $isAnySales = in_array($userRole, ['sales_store', 'sales_field']);

    // --- 1. CEK PERMISSION SUB-MENU ---
    $canViewProducts = in_array($userRole, ['manager_operasional', 'kepala_gudang', 'admin_gudang', 'purchase']);
    $canViewCustomers = !in_array($userRole, ['kepala_gudang', 'admin_gudang', 'purchase']);
    $canManageUsers = $isManagerOperasional;
    $cannotViewManajemenInternal = !in_array($userRole, ['kasir', 'purchase', 'sales_store', 'sales_field', 'finance']);
    // PERBAIKAN DISINI: Tambahkan $isAnySales agar Sales Lapangan bisa lihat menu Visit
    $canViewVisits = $isAnySales || in_array($userRole, ['manager_operasional', 'manager_bisnis']);

    $canCreateOrder = in_array($userRole, ['manager_operasional', 'manager_bisnis', 'sales_store', 'sales_field']);
    $canViewOrderHistory = true;

    $canViewReceivables = in_array($userRole, [
        'manager_operasional',
        'manager_bisnis',
        'finance',
        'sales_store',
        'sales_field',
    ]);
    $canRequestTop = in_array($userRole, ['manager_operasional', 'manager_bisnis', 'sales_store', 'sales_field']);

    $isApprover = in_array($userRole, ['manager_bisnis', 'kepala_gudang']);
    $canViewApprovals = $isApprover || $isManagerOperasional;

    // --- 2. HITUNG VISIBILITAS MENU UTAMA (PARENT) ---
    $showFinanceMenu = $canViewReceivables || $canRequestTop;
    $showOrderMenu = $canCreateOrder || $canViewOrderHistory;
    $showApprovalMenu = $canViewApprovals;
    $showVisitMenu = $canViewVisits; // Sekarang sudah TRUE untuk Sales Lapangan
    $showCustomerMenu = $canViewCustomers;
    $showUserMenu = $canManageUsers;
    $showQuotaMenu = $isManagerOperasional || $isManagerBisnis || $isAnySales;
    $showSettingsMenu = $isManagerOperasional;
@endphp

<nav id="sidebar">
    {{-- 1. SIDEBAR BRAND/LOGO --}}
    <a class="sidebar-brand d-flex align-items-center justify-content-start text-decoration-none"
        href="{{ route('dashboard') }}">
        <div class="sidebar-brand-icon">
            <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center p-1"
                style="width: 42px; height: 42px;">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="img-fluid"
                    style="max-height: 28px; width: auto;"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <i class="bi bi-building-fill text-primary fs-5" style="display: none;"></i>
            </div>
        </div>
        <div class="sidebar-brand-text ms-2 text-start d-flex flex-column justify-content-center">
            <span class="text-white text-uppercase">
                {{ \App\Models\Setting::where('key', 'app_name')->value('value') ?? 'SFA BINTANG' }}
            </span>
            <span class="text-white-50 fst-italic">Interior System</span>
        </div>
    </a>

    {{-- 2. SIDEBAR NAVIGATION --}}
    <ul class="list-unstyled components">
        {{-- Menu Utama --}}
        <li class="sidebar-heading">Menu Utama</li>
        <li>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 me-2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        {{-- Penjualan & Pemasaran --}}
        <li class="sidebar-heading">Penjualan & Pemasaran</li>

        {{-- MENU KUNJUNGAN --}}
        @if ($showVisitMenu)
            <li>
                <a href="#visitSubmenu" data-bs-toggle="collapse" class="dropdown-toggle"
                    aria-expanded="{{ request()->is('visits*') ? 'true' : 'false' }}">
                    <span><i class="bi bi-geo-alt me-2"></i> Kunjungan</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <ul class="collapse list-unstyled {{ request()->is('visits*') ? 'show' : '' }}" id="visitSubmenu">
                    {{-- Semua jenis Sales boleh buat Rencana Visit --}}
                    @if ($isSalesField)
                        <li><a href="{{ route('visits.plan') }}"
                                class="{{ request()->is('visits/plan') ? 'active' : '' }}">Rencana Visit</a></li>
                    @elseif ($isSalesStore)
                        <li><a href="{{ route('visits.plan') }}"
                                class="{{ request()->is('visits/create') ? 'active' : '' }}">Create Visit</a></li>
                    @endif

                    {{-- Label dinamis: Sales = Riwayat, Manager = Monitoring --}}
                    <li>
                        <a href="{{ route('visits.index') }}"
                            class="{{ request()->is('visits', 'visits/index') ? 'active' : '' }}">
                            {{ $isAnySales ? 'Riwayat Visit' : 'Monitoring Sales' }}
                        </a>
                    </li>
                </ul>
            </li>
        @endif

        {{-- MENU PELANGGAN --}}
        @if ($showCustomerMenu)
            <li>
                <a href="#customerSubmenu" data-bs-toggle="collapse" class="dropdown-toggle"
                    aria-expanded="{{ request()->is('customers*') ? 'true' : 'false' }}">
                    <span><i class="bi bi-people me-2"></i> Pelanggan</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <ul class="collapse list-unstyled {{ request()->is('customers*') ? 'show' : '' }}"
                    id="customerSubmenu">
                    <li><a href="{{ route('customers.index') }}"
                            class="{{ request()->routeIs('customers.index', 'customers.create', 'customers.edit') ? 'active' : '' }}">Data
                            Customer</a></li>
                    <li>
                        <a href="{{ route('customers.top_list') }}"
                            class="{{ request()->routeIs('customers.top_list') ? 'active' : '' }} d-flex justify-content-between align-items-center">
                            <span>Customer TOP</span>
                            <i class="bi bi-star-fill text-warning small"></i>
                        </a>
                    </li>
                </ul>
            </li>
        @endif

        {{-- MENU PESANAN --}}
        @if ($showOrderMenu)
            <li>
                <a href="#orderSubmenu" data-bs-toggle="collapse" class="dropdown-toggle"
                    aria-expanded="{{ request()->is('orders*') ? 'true' : 'false' }}">
                    <span><i class="bi bi-cart me-2"></i> Pesanan</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <ul class="collapse list-unstyled {{ request()->is('orders*') ? 'show' : '' }}" id="orderSubmenu">
                    @if ($canCreateOrder)
                        <li><a href="{{ route('orders.create') }}"
                                class="{{ request()->is('orders/create') ? 'active' : '' }}">Buat Order Baru</a></li>
                    @endif
                    <li><a href="{{ route('orders.index') }}"
                            class="{{ request()->is('orders') || (request()->is('orders/*') && !request()->is('orders/create')) ? 'active' : '' }}">Riwayat
                            Order</a></li>
                </ul>
            </li>
        @endif

        {{-- MENU KEUANGAN --}}
        @if ($showFinanceMenu)
            <li>
                <a href="#financeSubmenu" data-bs-toggle="collapse" class="dropdown-toggle"
                    aria-expanded="{{ request()->is('receivables*', 'top-submissions/create') ? 'true' : 'false' }}">
                    <span><i class="bi bi-wallet2 me-2"></i> Keuangan</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <ul class="collapse list-unstyled {{ request()->is('receivables*', 'top-submissions/create') ? 'show' : '' }}"
                    id="financeSubmenu">
                    @if ($canViewReceivables)
                        <li><a href="{{ route('receivables.index') }}"
                                class="{{ request()->is('receivables*') ? 'active' : '' }}">Data Piutang</a></li>
                    @endif
                    @if ($canRequestTop)
                        <li><a href="{{ route('top-submissions.create') }}"
                                class="{{ request()->routeIs('top-submissions.create') ? 'active' : '' }}">Pengajuan
                                TOP</a></li>
                    @endif
                </ul>
            </li>
        @endif
        @if ($cannotViewManajemenInternal)
            {{-- Manajemen Internal --}}
            <li class="sidebar-heading">Manajemen Internal</li>
        @endif

        @if ($canViewProducts)
            <li>
                <a href="{{ route('products.index') }}" class="{{ request()->is('products*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam me-2"></i>
                    <span>Manajemen Produk</span>
                </a>
            </li>
        @endif

        {{-- MENU USER & KUOTA --}}
        @if ($showUserMenu || $showQuotaMenu)
            <li>
                <a href="#userSubmenu" data-bs-toggle="collapse" class="dropdown-toggle"
                    aria-expanded="{{ request()->is('users*', 'quotas*') ? 'true' : 'false' }}">
                    {{-- Ubah Label Parent agar masuk akal buat Sales --}}
                    @if ($showUserMenu)
                        <span><i class="bi bi-person-gear me-2"></i> Manajemen Tim</span>
                    @else
                        <span><i class="bi bi-person-badge me-2"></i> Profil & Limit</span>
                    @endif
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <ul class="collapse list-unstyled {{ request()->is('users*', 'quotas*') ? 'show' : '' }}"
                    id="userSubmenu">

                    {{-- Kelola User: Hanya Manager Ops --}}
                    @if ($showUserMenu)
                        <li><a href="{{ route('users.index') }}"
                                class="{{ request()->is('users*') ? 'active' : '' }}">Kelola User</a></li>
                    @endif

                    {{-- Kelola Plafon: Manager Ops, Bisnis & Sales --}}
                    @if ($showQuotaMenu)
                        <li>
                            <a href="{{ route('quotas.index') }}"
                                class="{{ request()->is('quotas*') ? 'active' : '' }}">
                                {{-- Label Dinamis: Manager = Kelola, Sales = Saya --}}
                                {{ $isManagerOperasional || $isManagerBisnis ? 'Kelola Plafon Kredit' : 'Plafon Kredit Saya' }}
                            </a>
                        </li>
                    @endif

                </ul>
            </li>
        @endif

        {{-- MENU PERSETUJUAN --}}
        @if ($showApprovalMenu)
            <li>
                <a href="#approvalSubmenu" data-bs-toggle="collapse"
                    class="dropdown-toggle d-flex align-items-center justify-content-between"
                    aria-expanded="{{ request()->is('approvals*') || request()->is('top-submissions*') ? 'true' : 'false' }}">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-shield-check me-2"></i>
                        <span>Persetujuan</span>
                    </div>
                    <div class="d-flex align-items-center">
                        @if (isset($notifTotal) && $notifTotal > 0)
                            <span class="badge bg-danger rounded-pill me-2"
                                style="font-size: 0.7rem;">{{ $notifTotal }}</span>
                        @endif
                        <i class="bi bi-chevron-down small"></i>
                    </div>
                </a>
                <ul class="collapse list-unstyled {{ request()->is('approvals*') || request()->is('top-submissions*') ? 'show' : '' }}"
                    id="approvalSubmenu">
                    @if ($isManagerOperasional)
                        <li><a href="{{ route('approvals.index') }}"
                                class="{{ request()->routeIs('approvals.index') ? 'active' : '' }}">Dashboard
                                Approval</a></li>
                    @endif
                    @if ($isManagerBisnis || $isManagerOperasional)
                        <li>
                            <a href="{{ route('approvals.customers') }}"
                                class="d-flex justify-content-between align-items-center {{ request()->routeIs('approvals.customers') ? 'active' : '' }}">
                                <span>Data Customer</span>
                                @if (isset($notifPendingCustomers) && $notifPendingCustomers > 0)
                                    <span class="badge bg-warning text-dark rounded-pill"
                                        style="font-size: 0.7rem;">{{ $notifPendingCustomers }}</span>
                                @endif
                            </a>
                        </li>
                    @endif
                    @if ($isKepalaGudang || $isManagerOperasional)
                        <li>
                            <a href="{{ route('approvals.products') }}"
                                class="d-flex justify-content-between align-items-center {{ request()->routeIs('approvals.products') ? 'active' : '' }}">
                                <span>Produk / Stok</span>
                                @if (isset($notifPendingProducts) && $notifPendingProducts > 0)
                                    <span class="badge bg-secondary text-white rounded-pill"
                                        style="font-size: 0.7rem;">{{ $notifPendingProducts }}</span>
                                @endif
                            </a>
                        </li>
                    @endif
                    @if ($isManagerBisnis || $isManagerOperasional)
                        <li>
                            <a href="{{ route('approvals.transaksi') }}"
                                class="d-flex justify-content-between align-items-center {{ request()->routeIs('approvals.transaksi') ? 'active' : '' }}">
                                <span>Order Baru</span>
                                @if (isset($notifPendingOrders) && $notifPendingOrders > 0)
                                    <span class="badge bg-primary rounded-pill"
                                        style="font-size: 0.7rem;">{{ $notifPendingOrders }}</span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('approvals.piutang') }}"
                                class="d-flex justify-content-between align-items-center {{ request()->routeIs('approvals.piutang') ? 'active' : '' }}">
                                <span>Bayar Piutang</span>
                                @if (isset($notifPendingPayments) && $notifPendingPayments > 0)
                                    <span class="badge bg-success rounded-pill"
                                        style="font-size: 0.7rem;">{{ $notifPendingPayments }}</span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('top-submissions.index') }}"
                                class="d-flex justify-content-between align-items-center {{ request()->routeIs('top-submissions.index') ? 'active' : '' }}">
                                <span>Limit Kredit / TOP</span>
                                @if (isset($notifPendingTOP) && $notifPendingTOP > 0)
                                    <span class="badge bg-info rounded-pill"
                                        style="font-size: 0.7rem;">{{ $notifPendingTOP }}</span>
                                @endif
                            </a>
                        </li>
                    @endif
                    <li><a href="{{ route('approvals.history') }}"
                            class="{{ request()->routeIs('approvals.history') ? 'active' : '' }}">Riwayat Approval</a>
                    </li>
                </ul>
            </li>
        @endif

        {{-- MENU SISTEM / PENGATURAN --}}
        @if ($showSettingsMenu)
            <li class="sidebar-heading">Sistem</li>
            <li>
                <a href="#settingsSubmenu" data-bs-toggle="collapse" class="dropdown-toggle"
                    aria-expanded="{{ request()->is('settings*') ? 'true' : 'false' }}">
                    <span><i class="bi bi-gear me-2"></i> Pengaturan</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <ul class="collapse list-unstyled {{ request()->is('settings*') ? 'show' : '' }}"
                    id="settingsSubmenu">
                    <li><a href="{{ route('settings.index') }}"
                            class="{{ request()->is('settings') && !request()->is('settings/locations*') ? 'active' : '' }}">Pengaturan
                            Umum</a></li>
                    <li><a href="{{ route('settings.locations.index') }}"
                            class="{{ request()->is('settings/locations*') ? 'active' : '' }}">Kelola Lokasi</a></li>
                </ul>
            </li>
        @endif
    </ul>
</nav>
