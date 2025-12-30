{{-- File: resources/views/dashboard/_kepala_gudang.blade.php --}}
<div class="row mt-4">
    {{-- BARANG MASUK HARI INI --}}
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-success"><i class="bi bi-box-arrow-in-down me-2"></i>Barang Masuk Hari Ini</h6>
                <span class="badge bg-success">{{ $incomingGoodsToday->count() }}</span>
            </div>
            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0">
                    <tbody>
                        @forelse ($incomingGoodsToday as $item)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold">{{ $item->new_data['name'] ?? 'N/A' }}</div>
                                    <small class="text-muted">Stok Awal: {{ $item->new_data['stock'] ?? 'N/A' }} unit</small>
                                </td>
                                <td class="text-end pe-3">
                                    <span class="badge bg-light text-dark border">Produk Baru</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-4">Tidak ada produk baru yang disetujui hari ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- BARANG KELUAR HARI INI --}}
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-box-arrow-up me-2"></i>Barang Keluar Hari Ini</h6>
                <span class="badge bg-danger">{{ $outgoingGoodsToday->count() }}</span>
            </div>
            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0">
                    <tbody>
                        @forelse ($outgoingGoodsToday as $item)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold">{{ $item->product->name ?? 'N/A' }}</div>
                                    <small class="text-muted">Inv: {{ $item->order->invoice_number ?? 'N/A' }}</small>
                                </td>
                                <td class="text-end pe-3">
                                    <span class="fw-bold text-danger">-{{ $item->quantity }} unit</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-4">Tidak ada barang keluar hari ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <a href="{{ route('stock_movements.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-file-earmark-text me-2"></i>Lihat Laporan Pergerakan Stok
    </a>
</div>
