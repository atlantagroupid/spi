<!DOCTYPE html>
<html>

<head>
    <title>Laporan Riwayat Transaksi</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            text-transform: uppercase;
        }

        .header p {
            margin: 2px 0;
            font-size: 10px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #eee;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            color: white;
            display: inline-block;
        }

        /* Warna Status Manual untuk PDF */
        .status-approved {
            color: #198754;
            font-weight: bold;
        }

        /* Hijau */
        .status-pending {
            color: #fd7e14;
            font-weight: bold;
        }

        /* Orange */
        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }

        /* Merah */

        .meta {
            margin-top: 20px;
            font-size: 10px;
            color: #777;
            text-align: right;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>Laporan Riwayat Transaksi</h2>
        <p>SFA Bintang Interior System</p>
        <p>Dicetak Oleh: {{ $user->name }} ({{ $user->role }}) | Tanggal: {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">No Invoice & Tgl</th>
                <th width="20%">Pelanggan</th>
                <th width="15%">Sales</th>
                <th width="15%" class="text-right">Total Transaksi</th>
                <th width="15%" class="text-center">Status Pembayaran</th>
                <th width="15%" class="text-center">Status Order</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $order)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $order->invoice_number }}</strong><br>
                        <span style="color: #555;">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                    </td>
                    <td>
                        {{ $order->customer->store_name ?? '-' }}<br>
                        <small style="color:#555">{{ $order->customer->name ?? '' }}</small>
                    </td>
                    <td>{{ $order->sales->name ?? '-' }}</td>
                    <td class="text-right">
                        <strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong>
                    </td>
                    <td class="text-center">
                        @if ($order->payment_status == 'paid')
                            <span style="color: #198754; font-weight:bold;">LUNAS</span>
                        @elseif($order->payment_status == 'partial')
                            <span style="color: #0dcaf0; font-weight:bold;">SEBAGIAN</span>
                        @else
                            <span style="color: #dc3545; font-weight:bold;">BELUM LUNAS</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if ($order->status == 'approved')
                            <span class="status-approved">DISETUJUI</span>
                        @elseif($order->status == 'rejected')
                            <span class="status-rejected">DITOLAK</span>
                        @elseif($order->status == 'completed')
                            <span class="status-approved">SELESAI</span>
                        @else
                            <span class="status-pending">{{ strtoupper($order->status) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data transaksi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- TOTAL REKAP (Opsional) --}}
    <div style="margin-top: 15px; text-align: right;">
        <strong>Total Omset: Rp {{ number_format($orders->sum('total_price'), 0, ',', '.') }}</strong>
    </div>

    <div class="meta">
        <i>Dicetak otomatis oleh sistem.</i>
    </div>

</body>

</html>
