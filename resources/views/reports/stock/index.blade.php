{{-- File: resources/views/reports/stock/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Laporan Pergerakan Stok')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Pergerakan Stok</h1>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('stock_movements.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date', today()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date', today()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Tipe Pergerakan</label>
                    <select name="type" id="type" class="form-select">
                        <option value="all" @selected(request('type') == 'all' || !request()->has('type'))>Semua</option>
                        <option value="in" @selected(request('type') == 'in')>Masuk</option>
                        <option value="out" @selected(request('type') == 'out')>Keluar</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>Tampilkan</button>
                    <a href="{{ route('stock_movements.pdf', request()->query()) }}" class="btn btn-danger w-100 mt-2" target="_blank"><i class="bi bi-file-earmark-pdf me-2"></i>Export PDF</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel Laporan --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Tanggal</th>
                            <th>Tipe</th>
                            <th>Produk</th>
                            <th class="text-center">Jumlah</th>
                            <th>Referensi</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $item)
                            <tr>
                                <td class="ps-3">{{ \Carbon\Carbon::parse($item['date'])->format('d M Y, H:i') }}</td>
                                <td>
                                    @if ($item['type'] == 'in')
                                        <span class="badge bg-success">MASUK</span>
                                    @else
                                        <span class="badge bg-danger">KELUAR</span>
                                    @endif
                                </td>
                                <td class="fw-bold">{{ $item['product_name'] }}</td>
                                <td class="text-center fw-bold {{ $item['type'] == 'in' ? 'text-success' : 'text-danger' }}">
                                    {{ $item['type'] == 'in' ? '+' : '-' }}{{ $item['quantity'] }}
                                </td>
                                <td>{{ $item['reference'] }}</td>
                                <td><small class="text-muted">{{ $item['notes'] }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Tidak ada data pergerakan stok pada rentang tanggal yang dipilih.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($movements->hasPages())
                <div class="p-3">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
