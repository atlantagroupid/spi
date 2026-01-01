<!DOCTYPE html>
<html>
<head>
    <title>Laporan Order</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }

        /* Kolom Status agar lebih rapi */
        .badge {
            padding: 2px 6px; border-radius: 4px; font-size: 10px; color: #fff;
            text-transform: uppercase; font-weight: bold;
        }
        .bg-pending { background-color: #ffc107; color: #000; }
        .bg-approved { background-color: #17a2b8; }
        .bg-shipped { background-color: #007bff; }
        .bg-completed { background-color: #28a745; }
        .bg-rejected { background-color: #dc3545; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $title ?? 'Laporan Order' }}</h2>
        <p>Dicetak pada: {{ date('d M Y, H:i') }} | Oleh: {{ Auth::user()->name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No</th> {{-- TAMBAHAN: Kolom No --}}
                <th style="width: 15%;">No Invoice</th>
                <th style="width: 15%;">Tanggal</th>
                <th style="width: 20%;">Pelanggan</th>
                <th style="width: 15%;">Sales</th>
                <th style="width: 15%;">Total</th>
                <th style="width: 15%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td style="text-align: center;">{{ $loop->iteration }}</td> {{-- TAMBAHAN: Nomor Urut --}}
                    <td>{{ $order->invoice_number }}</td>
                    <td>{{ date('d M Y', strtotime($order->created_at)) }}</td>
                    <td>{{ $order->customer->name ?? '-' }}</td>
                    <td>{{ $order->user->name ?? '-' }}</td>
                    <td>Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                    <td>
                        {{-- Logika Warna Badge Sederhana --}}
                        @php
                            $statusClass = match($order->status) {
                                'pending_approval' => 'bg-pending',
                                'approved' => 'bg-approved',
                                'shipped' => 'bg-shipped',
                                'delivered' => 'bg-shipped', // Disamakan dgn shipped
                                'completed' => 'bg-completed',
                                'rejected' => 'bg-rejected',
                                default => ''
                            };
                            $statusLabel = match($order->status) {
                                'pending_approval' => 'Menunggu',
                                'approved' => 'Disetujui',
                                'shipped' => 'Dikirim',
                                'delivered' => 'Sampai',
                                'completed' => 'Selesai',
                                'rejected' => 'Ditolak',
                                default => $order->status
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">Tidak ada data order.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align: right; font-weight: bold;">TOTAL OMSET</td>
                <td colspan="2" style="font-weight: bold;">
                    Rp {{ number_format($orders->sum('total_price'), 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
