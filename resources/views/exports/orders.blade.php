<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        /* CSS STANDAR UNTUK EXCEL */
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000000;
            padding: 10px; /* Tambah padding biar lega */
            vertical-align: middle;

            /* INI KUNCINYA: */
            white-space: nowrap; /* Mencegah Wrap Text (Teks tidak akan turun ke bawah) */
        }

        /* WARNA HEADER */
        th {
            background-color: #28a745; /* Hijau */
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            height: 30px;
        }

        /* ALIGNMENT */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        /* FORMAT JUDUL */
        .title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            background-color: #f8f9fa;
            border: none;
        }

        /* WARNA STATUS (Opsional) */
        .status-pending { background-color: #fff3cd; color: #000000; }
        .status-paid { background-color: #d4edda; color: #000000; }
    </style>
</head>
<body>
    <table>
        <thead>
            {{-- JUDUL --}}
            <tr>
                {{-- Colspan sesuaikan dengan jumlah kolom --}}
                <th colspan="7" class="title" style="height: 50px; vertical-align: middle;">
                    LAPORAN SALES ORDER - BINTANG INTERIOR
                </th>
            </tr>
            <tr>
                <td colspan="7" style="text-align: center; font-style: italic; border: none;">
                    Dicetak Tanggal: {{ date('d F Y, H:i') }}
                </td>
            </tr>
            <tr>
                <td colspan="7" style="border: none;"></td>
            </tr>

            {{-- HEADER TABEL --}}
            {{-- Hapus width manual agar Excel menghitung otomatis (Auto Fit) --}}
            <tr>
                <th>No SO / Invoice</th>
                <th>Tanggal</th>
                <th>Nama Sales</th>
                <th>Nama Pelanggan</th>
                <th>Status Order</th>
                <th>Pembayaran</th>
                <th>Total Nilai (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
                <tr>
                    {{-- Gunakan spasi non-breaking (&nbsp;) jika ingin memberi jarak aman --}}
                    <td class="text-center">{{ $order->invoice_number }}</td>
                    <td class="text-center">{{ $order->created_at->format('d/m/Y') }}</td>
                    <td class="text-left">{{ $order->user->name }}</td>
                    <td class="text-left">{{ $order->customer->name }}</td>

                    <td class="text-center {{ $order->status == 'pending' ? 'status-pending' : '' }}">
                        {{ strtoupper($order->status) }}
                    </td>

                    <td class="text-center {{ $order->payment_status == 'paid' ? 'status-paid' : '' }}">
                        {{ ucfirst($order->payment_status) }}
                    </td>

                    {{-- Format Angka Excel (Tanpa Rp agar bisa di-sum) --}}
                    <td class="text-right">{{ $order->total_price }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" style="text-align: right; font-weight: bold; background-color: #e2e3e5;">GRAND TOTAL</td>
                <td style="font-weight: bold; background-color: #e2e3e5; text-align: right;">
                    {{ $orders->sum('total_price') }}
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
