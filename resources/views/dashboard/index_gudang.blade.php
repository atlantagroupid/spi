@extends('layouts.app')

@section('title', 'Dashboard Gudang')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-dark">Dashboard Gudang</h4>
        @if(!$showFinancials)
            <span class="badge bg-secondary">Mode Admin</span>
        @endif
    </div>

    {{-- STATISTIK GUDANG --}}
    <div class="row g-3 mb-4">
        {{-- Total Item (Semua Boleh Lihat) --}}
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 border-start border-4 border-primary">
                <div class="card-body">
                    <small class="text-muted fw-bold">TOTAL ITEM FISIK</small>
                    <h3 class="fw-bold">{{ number_format($totalItems, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>

        {{-- Stok Menipis (Semua Boleh Lihat - Urgent) --}}
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 border-start border-4 border-warning">
                <div class="card-body">
                    <small class="text-muted fw-bold">PERLU RESTOCK</small>
                    <h3 class="fw-bold text-danger">{{ $lowStockCount }}</h3>
                    <a href="{{ route('products.index') }}" class="stretched-link small text-muted">Cek Barang &rarr;</a>
                </div>
            </div>
        </div>

        {{-- Barang Masuk (Semua Boleh Lihat) --}}
        <div class="col-md-3">
             <div class="card shadow-sm border-0 h-100 bg-light">
                <div class="card-body text-center">
                    <h6 class="text-success fw-bold">Masuk (Hari Ini)</h6>
                    <h2 class="fw-bold">{{ $incomingGoods }}</h2>
                </div>
            </div>
        </div>

        {{-- Nilai Aset (DIBATASI: HANYA KEPALA GUDANG) --}}
        @if($showFinancials)
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100 bg-success text-white">
                   <div class="card-body">
                       <small class="text-white-50 fw-bold">NILAI ASET GUDANG</small>
                       <h4 class="fw-bold">Rp {{ number_format($totalAsset, 0, ',', '.') }}</h4>
                   </div>
               </div>
           </div>
        @else
            {{-- Tampilan Pengganti untuk Admin Gudang (Biar gridnya gak bolong) --}}
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100 bg-light">
                   <div class="card-body text-center">
                       <h6 class="text-primary fw-bold">Keluar (Hari Ini)</h6>
                       <h2 class="fw-bold">{{ $outgoingGoods }}</h2>
                   </div>
               </div>
           </div>
        @endif
    </div>

    {{-- AREA KERJA GUDANG --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Aksi Cepat</div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('products.create') }}" class="btn btn-outline-primary text-start">
                        <i class="bi bi-box-seam me-2"></i> Tambah Produk Baru
                    </a>
                    <a href="{{ route('stock_movements.index') }}" class="btn btn-outline-secondary text-start">
                        <i class="bi bi-arrow-left-right me-2"></i> Riwayat Keluar/Masuk
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
             <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold text-danger">Pending Approval</div>
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <h2 class="fw-bold text-danger mb-0">{{ $pendingApproval }}</h2>
                    <small class="text-muted">Item Menunggu Persetujuan</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
