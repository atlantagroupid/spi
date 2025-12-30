{{-- File: resources/views/reports/stock/pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pergerakan Stok</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; }
        .table th { background-color: #f2f2f2; text-align: left; }
        .text-center { text-align: center; }
        .text-success { color: #198754; }
        .text-danger { color: #dc3545; }
        .fw-bold { font-weight: bold; }
        h2 { text-align: center; }
        .header-info { margin-bottom: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <h2>Laporan Pergerakan Stok</h2>
    <div class="header-info">
        <strong>Periode:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
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
                    <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i') }}</td>
                    <td>{{ $item['type'] == 'in' ? 'MASUK' : 'KELUAR' }}</td>
                    <td class="fw-bold">{{ $item['product_name'] }}</td>
                    <td class="text-center fw-bold {{ $item['type'] == 'in' ? 'text-success' : 'text-danger' }}">
                        {{ $item['type'] == 'in' ? '+' : '-' }}{{ $item['quantity'] }}
                    </td>
                    <td>{{ $item['reference'] }}</td>
                    <td>{{ $item['notes'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
