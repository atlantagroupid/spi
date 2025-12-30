<!DOCTYPE html>
<html>
<head>
    <title>Laporan Monitoring Piutang</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #eee; text-align: center; }
        .text-right { text-align: right; }
        .text-danger { color: red; }
    </style>
</head>
<body>
    <div style="text-align: center; margin-bottom: 20px;">
        <h2>LAPORAN PIUTANG (BELUM LUNAS)</h2>
        <p>CV. Bintang Interior & Keramik</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Jatuh Tempo</th>
                <th>Customer</th>
                <th>Sales</th>
                <th>Sisa Tagihan</th>
                <th>Ket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receivables as $row)
            @php
                // Hitung sisa hari
                $due = \Carbon\Carbon::parse($row->due_date);
                $diff = now()->diffInDays($due, false);
                $status = $diff < 0 ? "Telat " . abs(intval($diff)) . " Hari" : $diff . " Hari Lagi";
                $color = $diff < 0 ? 'color: red; font-weight: bold;' : '';
            @endphp
            <tr>
                <td>{{ $row->invoice_number }}</td>
                <td class="text-center">{{ date('d/m/Y', strtotime($row->due_date)) }}</td>
                <td>{{ $row->customer->name }}</td>
                <td>{{ $row->user->name }}</td>
                <td class="text-right">{{ number_format($row->total_price, 0, ',', '.') }}</td>
                <td style="{{ $color }} text-align: center;">{{ $status }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
             <tr>
                <th colspan="4" class="text-right">TOTAL PIUTANG</th>
                <th class="text-right">{{ number_format($receivables->sum('total_price'), 0, ',', '.') }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
