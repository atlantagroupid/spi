<!DOCTYPE html>
<html>
<head>
    <title>Laporan Riwayat Order</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #eee; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        h2, h4 { margin: 0; text-align: center; }
        .header { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>CV. BINTANG INTERIOR & KERAMIK</h2>
        <h4>Laporan Riwayat Transaksi</h4>
        <p class="text-center small">Dicetak Tanggal: {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No Invoice</th>
                <th>Tanggal</th>
                <th>Toko / Customer</th>
                <th>Sales</th>
                <th>Status</th>
                <th>Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $index => $order)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $order->invoice_number }}</td>
                <td class="text-center">{{ $order->created_at->format('d/m/Y') }}</td>
                <td>{{ $order->customer->name }}</td>
                <td>{{ $order->user->name }}</td>
                <td class="text-center">{{ ucfirst($order->status) }}</td>
                <td class="text-right">{{ number_format($order->total_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="text-right">GRAND TOTAL</th>
                <th class="text-right">{{ number_format($orders->sum('total_price'), 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
