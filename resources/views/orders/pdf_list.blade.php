<!DOCTYPE html>
<html>
<head>
    <title>Laporan Order</title>
    <style>
        body {
            font-family: 'sans-serif';
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Laporan Order</h1>
    <table>
        <thead>
            <tr>
                <th>No Invoice</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Sales</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
                <tr>
                    <td>{{ $order->invoice_number }}</td>
                    <td>{{ $order->created_at->format('d M Y') }}</td>
                    <td>{{ $order->customer->name }}</td>
                    <td>{{ $order->user->name }}</td>
                    <td>Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                    <td>{{ $order->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
