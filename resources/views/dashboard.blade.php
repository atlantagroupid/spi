@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $userRole = Auth::user()->role;
        $isSales = $userRole === 'sales';
        // Logic for Manager/Warehouse View
        $isManager = in_array($userRole, ['manager_operasional', 'manager_bisnis']);
        $hasApprovalAccess = in_array($userRole, ['manager_operasional', 'manager_bisnis', 'kepala_gudang']);
        $isWarehouseOrPurchase = in_array($userRole, ['kepala_gudang', 'admin_gudang', 'purchase']);
    @endphp

    <div class="row">
        <div class="col-md-12 mb-4">

            {{-- HEADER DASHBOARD --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="fw-bold text-primary">
                        @if ($isSales)
                            Dashboard Sales
                        @else
                            Dashboard Overview
                        @endif
                    </h4>
                    <p class="text-muted">Halo {{ Auth::user()->name }}, selamat beraktivitas!</p>
                </div>
            </div>
            {{-- WIDGET PLAFON KREDIT (LOGIKA DINAMIS) --}}
            @php
                $userRole = Auth::user()->role;
                $isManagerOps = $userRole === 'manager_operasional';
                // Sales & Manager Bisnis butuh lihat sisa limit mereka
                $showPersonalLimit = in_array($userRole, ['sales', 'sales_store', 'sales_field', 'manager_bisnis']);
            @endphp

            @if ($isManagerOps)
                {{-- A. KHUSUS MANAGER OPS: TAMPILKAN INFO TOTAL LIMIT YANG BEREDAR (BUKAN SISA PRIBADI) --}}
                <div class="card shadow mb-4 border-left-primary">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Plafon Kredit Beredar (Seluruh Tim)
                                </div>
                                @php
                                    // Hitung total limit yang dipegang semua user
                                    $totalLimitDistributed = \App\Models\User::sum('credit_limit_quota');
                                @endphp
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    Rp {{ number_format($totalLimitDistributed, 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-bank fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif ($showPersonalLimit)
                {{-- B. UNTUK SALES & MGR BISNIS: TAMPILKAN SISA LIMIT SEPERTI BIASA + WARNING --}}
                @if ($isCritical)
                    {{-- Tampilan Warning Merah (Sudah ada di kode sebelumnya) --}}
                    <div class="alert alert-danger shadow-sm border-left-danger d-flex align-items-center justify-content-between"
                        role="alert">
                        <div>
                            <h4 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Limit Kredit
                                Menipis!</h4>
                            <p class="mb-0">
                                Sisa limit Anda tinggal <strong>Rp {{ number_format($remaining, 0, ',', '.') }}</strong>
                                (Terpakai: Rp {{ number_format($usedCredit, 0, ',', '.') }} dari Rp
                                {{ number_format($limitQuota, 0, ',', '.') }}).
                            </p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-light text-danger fw-bold" data-bs-toggle="modal"
                                data-bs-target="#requestLimitModal">
                                <i class="bi bi-arrow-up-circle"></i> Minta Limit
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Tampilan Normal Hijau (Sudah ada di kode sebelumnya) --}}
                    <div class="card shadow mb-4 border-left-success">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sisa Plafon
                                        Kredit</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp
                                        {{ number_format($remaining, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-wallet2 fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif


            {{-- ============================================================ --}}
            {{-- 1. TAMPILAN KHUSUS SALES --}}
            {{-- ============================================================ --}}
            @if ($isSales)

                {{-- A. WIDGET TARGET KUNJUNGAN --}}
                <div class="card shadow-sm border-0 mb-4 bg-primary text-white overflow-hidden">
                    <div class="card-body p-4 position-relative">
                        <div class="row align-items-center position-relative z-1">
                            <div class="col-md-8">
                                <h4 class="fw-bold mb-1">Target Kunjungan Harian</h4>
                                <p class="mb-3 text-white-50">Ayo kejar targetmu hari ini!</p>
                                <div class="d-flex align-items-end mb-2">
                                    <h1 class="display-4 fw-bold mb-0 me-2">{{ $todayVisits ?? 0 }}</h1>
                                    <span class="fs-5 mb-2">/ {{ $visitTarget ?? 0 }} Toko</span>
                                </div>

                                @php
                                    $vPercent = $visitPercentage ?? 0;
                                    $vColor = $vPercent >= 100 ? 'bg-success' : 'bg-warning';
                                @endphp

                                <div class="progress" style="height: 10px; background-color: rgba(255,255,255,0.3);">
                                    <div class="progress-bar {{ $vColor }}" role="progressbar"
                                        style="width: {{ min($vPercent, 100) }}%"></div>
                                </div>
                                <small class="mt-2 d-block">
                                    {{ number_format($vPercent, 0) }}% Tercapai
                                </small>
                            </div>
                            <div class="col-md-4 text-end d-none d-md-block">
                                <i class="bi bi-geo-alt-fill text-white opacity-25"
                                    style="font-size: 8rem; position: absolute; right: 20px; top: -20px;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- B. RENCANA KUNJUNGAN --}}
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-check text-primary"></i> Rencana Kunjungan
                                    Hari Ini</h6>
                                <a href="{{ route('visits.plan') }}" class="btn btn-sm btn-outline-primary"><i
                                        class="bi bi-plus"></i> Tambah</a>
                            </div>
                            <div class="list-group list-group-flush">
                                @forelse($plannedVisits ?? [] as $plan)
                                    <div class="card mb-3 shadow-sm border-0">
                                        @php
                                            $borderColor = 'primary'; // Default Biru (Planned)
                                            if ($plan->status == 'in_progress') {
                                                $borderColor = 'warning';
                                            }
                                            if ($plan->status == 'completed') {
                                                $borderColor = 'success';
                                            }
                                        @endphp

                                        <div class="card-body p-3 border-start border-4 border-{{ $borderColor }}">
                                            <div class="d-flex justify-content-between align-items-center">

                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        @if ($plan->status == 'planned')
                                                            <div class="bg-light text-primary rounded-circle p-2">
                                                                <i class="fas fa-map-marker-alt fa-lg"></i>
                                                            </div>
                                                        @elseif($plan->status == 'in_progress')
                                                            <div
                                                                class="bg-warning bg-opacity-25 text-warning rounded-circle p-2">
                                                                <i class="fas fa-stopwatch fa-lg fa-spin"></i>
                                                                {{-- Ikon muter dikit biar keren --}}
                                                            </div>
                                                        @else
                                                            <div
                                                                class="bg-success bg-opacity-25 text-success rounded-circle p-2">
                                                                <i class="fas fa-check-double fa-lg"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-bold text-dark">{{ $plan->customer->name }}</h6>
                                                        <small class="text-muted d-block" style="font-size: 0.85rem;">
                                                            <i class="fas fa-map-pin me-1"></i>
                                                            {{ Str::limit($plan->customer->address, 30) }}
                                                        </small>

                                                        @if ($plan->status == 'in_progress')
                                                            <small class="text-warning fw-bold" style="font-size: 0.75rem;">
                                                                <i class="fas fa-clock"></i> Dimulai:
                                                                {{ \Carbon\Carbon::parse($plan->check_in_time)->format('H:i') }}
                                                            </small>
                                                        @elseif($plan->status == 'completed')
                                                            <small class="text-success" style="font-size: 0.75rem;">
                                                                Selesai jam
                                                                {{ \Carbon\Carbon::parse($plan->check_out_time)->format('H:i') }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div>
                                                    {{-- 1. TOMBOL CHECK IN (Biru) --}}
                                                    @if ($plan->status == 'planned')
                                                        <form action="{{ route('visits.checkIn', $plan->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            <button type="submit"
                                                                class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm">
                                                                <i class="fas fa-play me-1"></i> Check In
                                                            </button>
                                                        </form>

                                                        {{-- 2. TOMBOL CHECK OUT (Kuning & Warning) --}}
                                                    @elseif($plan->status == 'in_progress')
                                                        <a href="{{ route('visits.perform', $plan->id) }}"
                                                            class="btn btn-warning btn-sm px-3 rounded-pill shadow-sm text-dark fw-bold">
                                                            <i class="fas fa-sign-out-alt me-1"></i> Check Out
                                                        </a>

                                                        {{-- 3. LABEL SELESAI (Hijau) --}}
                                                    @else
                                                        <button class="btn btn-light btn-sm text-success fw-bold border-0"
                                                            disabled>
                                                            <i class="fas fa-check-circle"></i> Selesai
                                                        </button>
                                                    @endif
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-4 text-muted">
                                        <i class="fas fa-clipboard-list fa-3x mb-3 text-secondary opacity-25"></i>
                                        <p>Belum ada rencana kunjungan hari ini.</p>
                                        <a href="{{ route('visits.createPlan') }}" class="btn btn-outline-primary btn-sm">
                                            + Buat Rencana Baru
                                        </a>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- KARTU PENCAPAIAN OMSET (VERSI BIG PERCENTAGE) --}}
                    <div class="col-lg-6 mb-4">
                        <div class="card bg-success text-white shadow-sm border-0 h-100 position-relative overflow-hidden">

                            {{-- Hiasan Background (Piala Samar di Kiri Bawah) --}}
                            <div class="position-absolute bottom-0 start-0 opacity-10"
                                style="transform: translate(-10%, 20%)">
                                <i class="fas fa-trophy" style="font-size: 8rem;"></i>
                            </div>

                            <div class="card-body position-relative p-4">

                                {{-- LOGIKA HITUNG PERSENTASE (Di dalam View) --}}
                                @php
                                    $target = $salesUser->sales_target ?? 0;
                                    $achieved = $currentOmset ?? 0; // Pastikan variabel ini dikirim dari Controller
                                    $percentage = $target > 0 ? round(($achieved / $target) * 100, 1) : 0;
                                @endphp

                                {{-- TOMBOL EDIT (HANYA MUNCUL JIKA MANAGER) --}}
                                @if (in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional']))
                                    <button
                                        class="btn btn-sm btn-light text-success fw-bold shadow-sm position-absolute top-0 end-0 m-3"
                                        style="z-index: 20;" data-bs-toggle="modal" data-bs-target="#modalEditTarget">
                                        <i class="fas fa-edit me-1"></i> Atur
                                    </button>
                                @endif

                                <div class="row align-items-end">
                                    {{-- KOLOM KIRI: Judul & Nominal Uang --}}
                                    <div class="col-7">
                                        <h6 class="text-uppercase text-white-50 fw-bold mb-1"
                                            style="font-size: 0.8rem; letter-spacing: 1px;">
                                            Pencapaian Omset
                                        </h6>
                                        <h3 class="fw-bold mb-0">
                                            Rp {{ number_format($achieved, 0, ',', '.') }}
                                        </h3>
                                        <small class="text-white-50 mt-1 d-block">
                                            Target: Rp {{ number_format($target, 0, ',', '.') }}
                                        </small>
                                    </div>

                                    {{-- KOLOM KANAN: Persentase Besar --}}
                                    <div class="col-5 text-end">
                                        {{-- Spacer agar tidak ketabrak tombol edit (jika ada) --}}
                                        <div style="height: 20px;"></div>

                                        <div class="fw-bold" style="font-size: 3rem; line-height: 1;">
                                            {{ $percentage }}<span style="font-size: 1.5rem;">%</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Progress Bar --}}
                                <div class="progress mt-4" style="height: 8px; background-color: rgba(255,255,255,0.2);">
                                    <div class="progress-bar bg-white" role="progressbar"
                                        style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}"
                                        aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- C. MODAL EDIT TARGET OMSET --}}

                {{-- MODAL EDIT TARGET (Taruh di paling bawah file dashboard.blade.php) --}}
                @if (in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional']))
                    <div class="modal fade" id="modalEditTarget" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold"><i class="fas fa-bullseye text-primary me-2"></i>Atur
                                        Target Omset</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('users.updateTarget') }}" method="POST">
                                    @csrf
                                    <div class="modal-body">

                                        {{-- 1. Pilih Sales (Supaya Manager bisa set target buat siapa) --}}
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Pilih Sales</label>
                                            <select name="user_id" class="form-select">
                                                {{-- Asumsi kamu mengirim $allSales dari controller dashboard --}}
                                                @foreach ($allSales ?? [] as $sales)
                                                    <option value="{{ $sales->getKey() }}"
                                                        {{ ($salesUser->id ?? '') == $sales->id ? 'selected' : '' }}>
                                                        {{ $sales->name }} (Target Saat Ini: Rp
                                                        {{ number_format($sales->sales_target, 0, ',', '.') }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- 2. Input Nominal Target --}}
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Target Baru (Rupiah)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" name="target" class="form-control"
                                                    placeholder="Contoh: 50000000" required>
                                            </div>
                                            <div class="form-text">Masukkan angka saja tanpa titik/koma.</div>
                                        </div>

                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light"
                                            data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-success">Simpan Target</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- D. ORDER TERBARU --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>5 Transaksi Terakhir Anda
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Invoice</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders ?? [] as $order)
                                    <tr>
                                        <td class="ps-4 fw-bold">{{ $order->invoice_number }}</td>
                                        <td>{{ $order->customer->name }}</td>
                                        <td>
                                            @if ($order->status == 'pending_approval')
                                                <span class="badge bg-warning text-dark">Menunggu</span>
                                            @elseif($order->status == 'approved')
                                                <span class="badge bg-info text-dark">Disetujui</span>
                                            @elseif($order->status == 'processed')
                                                <span class="badge bg-primary">Diantar</span>
                                            @elseif($order->status == 'completed')
                                                <span class="badge bg-success">Selesai</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $order->status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">Rp
                                            {{ number_format($order->total_price, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Belum ada transaksi.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>


                {{-- ============================================================ --}}
                {{-- 2. TAMPILAN NON-SALES (MANAGER, GUDANG, ADMIN) --}}
                {{-- ============================================================ --}}
            @else
                {{-- WIDGET ATAS (STOK / APPROVAL) --}}
                <div class="row g-3 mb-4">
                    {{-- Total Stok --}}
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100 bg-primary text-white">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-uppercase mb-1 opacity-75 small fw-bold">Total Stok Fisik</h6>
                                    <h2 class="mb-0 fw-bold">
                                        {{ number_format($warehouseStats['total_items'] ?? 0, 0, ',', '.') }}</h2>
                                </div>
                                <i class="bi bi-box-seam fs-2 bg-white bg-opacity-25 p-3 rounded-circle"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Total Aset --}}
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100 bg-success text-white">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-uppercase mb-1 opacity-75 small fw-bold">Nilai Aset</h6>
                                    <h2 class="mb-0 fw-bold">Rp
                                        {{ number_format($warehouseStats['total_asset'] ?? 0, 0, ',', '.') }}</h2>
                                </div>
                                <i class="bi bi-cash-stack fs-2 bg-white bg-opacity-25 p-3 rounded-circle"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Approval / Low Stock (Logic Kondisional) --}}
                    {{-- 3. Widget Kondisional (Approval / Restock) --}}
                    <div class="col-md-4">
                        @if ($hasApprovalAccess)
                            {{-- LOGIC: Determine Link Based on Role --}}
                            @php
                                $role = Auth::user()->role;
                                $approvalLink = '#'; // Default fallback

                                if ($role == 'manager_operasional') {
                                    $approvalLink = route('approvals.index'); // All Approvals
                                } elseif ($role == 'kepala_gudang') {
                                    $approvalLink = route('approvals.products'); // Only Product Approvals
                                } elseif ($role == 'manager_bisnis') {
                                    $approvalLink = route('approvals.transaksi'); // Only Transaction Approvals
                                }
                            @endphp

                            {{-- Manager & Kepala Gudang: Lihat Approval --}}
                            <div class="card shadow-sm border-0 border-start border-4 border-danger h-100">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted small fw-bold text-uppercase mb-1">Menunggu Approval</h6>
                                        <h2 class="fw-bold text-danger mb-0">{{ $pendingApprovalCount ?? 0 }}</h2>
                                        <small class="text-muted">Permintaan Pending</small>
                                    </div>
                                    <div class="position-relative">
                                        <i
                                            class="bi bi-shield-exclamation fs-2 text-danger bg-danger bg-opacity-10 p-3 rounded-circle"></i>
                                        @if (($pendingApprovalCount ?? 0) > 0)
                                            <span
                                                class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle"></span>
                                        @endif
                                    </div>
                                </div>
                                {{-- USE THE DYNAMIC LINK HERE --}}
                                <a href="{{ $approvalLink }}" class="stretched-link"></a>
                            </div>
                        @else
                            {{-- Admin Gudang & Purchase: Lihat Restock --}}
                            <div class="card shadow-sm border-0 h-100 bg-warning text-dark">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-uppercase mb-1 opacity-75 small fw-bold">Perlu Restock</h6>
                                        <h2 class="mb-0 fw-bold text-danger">{{ $lowStockCount ?? 0 }}</h2>
                                        <small class="opacity-75">Item Stok Menipis</small>
                                    </div>
                                    <i
                                        class="bi bi-exclamation-triangle-fill fs-2 text-danger bg-white bg-opacity-25 p-3 rounded-circle"></i>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- STATISTIK KEUANGAN (Disembunyikan dari Gudang/Purchase) --}}
                @if (!$isWarehouseOrPurchase)
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body">
                                    <h6 class="text-muted small fw-bold text-uppercase">Total Penjualan</h6>
                                    <h3 class="fw-bold text-dark">Rp
                                        {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}
                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body">
                                    <h6 class="text-muted small fw-bold text-uppercase">Uang Diterima</h6>
                                    <h3 class="fw-bold text-success">Rp
                                        {{ number_format($cashReceived ?? 0, 0, ',', '.') }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-.blade.php                                    <h6 class="text-muted small
                                    fw-bold text-uppercase">Sisa Piutang</h6>
                                    <h3 class="fw-bold text-danger">Rp
                                        {{ number_format($totalReceivable ?? 0, 0, ',', '.') }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- GRAFIK PENJUALAN BULANAN --}}
                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Grafik Penjualan Bulanan ({{ date('Y') }})</h5>
                        </div>
                        <div class="card-body"><canvas id="salesChart" style="max-height: 400px;"></canvas></div>
                    </div>

                    {{-- LEADERBOARD KHUSUS MANAGER --}}
                    @if ($isManager)
                        <div class="row mt-4">
                            <div class="col-lg-7 mb-4">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-header bg-white py-3">
                                        <h5 class="mb-0 fw-bold">Top Sales Bulan Ini</h5>
                                    </div>
                                    <div class="card-body"><canvas id="leaderboardChart"
                                            style="max-height: 300px;"></canvas></div>
                                </div>
                            </div>
                            <div class="col-lg-5 mb-4">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-header bg-white py-3">
                                        <h5 class="mb-0 fw-bold">Efektivitas Sales</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="ps-3">Nama</th>
                                                        <th class="text-center">Visit</th>
                                                        <th class="text-end pe-3">Omset</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($topSales ?? [] as $s)
                                                        <tr>
                                                            <td class="ps-3 fw-bold">{{ $s->name }}</td>
                                                            <td class="text-center"><span
                                                                    class="badge bg-info text-dark">{{ $s->visits_count }}</span>
                                                            </td>
                                                            <td class="text-end pe-3 text-success fw-bold">Rp
                                                                {{ number_format($s->orders_sum_total_price ?? 0, 0, ',', '.') }}
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="3" class="text-center py-3 text-muted">
                                                                Belum
                                                                ada data.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- MODAL FORM REQUEST (Taruh di paling bawah file) --}}
                    <div class="modal fade" id="requestLimitModal" tabindex="-1">
                        <div class="modal-dialog">
                            <form action="{{ route('quotas.store') }}" method="POST">
                                @csrf
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title">Ajukan Tambahan Limit</h5>
                                        <button type="button" class="btn-close btn-close-white"
                                            data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label>Jumlah (Rp)</label>
                                            <input type="number" name="amount" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Alasan</label>
                                            <textarea name="reason" class="form-control" rows="3" required
                                                placeholder="Contoh: Limit habis tapi ada order besar dari Customer X..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-danger">Kirim Pengajuan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    {{-- SCRIPT CHART UNTUK ADMIN/MANAGER --}}
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            // 1. Chart Penjualan
                            const ctx = document.getElementById('salesChart');
                            if (ctx) {
                                new Chart(ctx.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @json($chartLabels ?? []),
                                        datasets: [{
                                            label: 'Omset',
                                            data: @json($chartData ?? []),
                                            backgroundColor: 'rgba(54, 162, 235, 0.6)'
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                ticks: {
                                                    callback: function(val) {
                                                        return 'Rp ' + new Intl.NumberFormat('id-ID', {
                                                            notation: "compact"
                                                        }).format(val);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            }

                            // 2. Chart Leaderboard (Manager Only)
                            const ctxL = document.getElementById('leaderboardChart');
                            if (ctxL) {
                                new Chart(ctxL.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @json($salesNames ?? []),
                                        datasets: [{
                                            label: 'Omset',
                                            data: @json($salesRevenue ?? []),
                                            backgroundColor: ['rgba(255, 206, 86, 0.7)',
                                                'rgba(192, 192, 192, 0.7)',
                                                'rgba(205, 127, 50, 0.7)', 'rgba(54, 162, 235, 0.5)'
                                            ]
                                        }]
                                    },
                                    options: {
                                        indexAxis: 'y',
                                        responsive: true,
                                        plugins: {
                                            legend: {
                                                display: false
                                            }
                                        },
                                        scales: {
                                            x: {
                                                ticks: {
                                                    callback: function(val) {
                                                        return 'Rp ' + new Intl.NumberFormat('id-ID', {
                                                            notation: "compact"
                                                        }).format(val);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        });
                    </script>
                @endif

                {{-- TAMPILAN KHUSUS KEPALA GUDANG (Barang Masuk/Keluar) --}}
                @if (Auth::user()->role == 'kepala_gudang')
                    @include('dashboard._kepala_gudang')
                @endif

            @endif
        </div>
    </div>
@endsection
