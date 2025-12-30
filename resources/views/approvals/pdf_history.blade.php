<!DOCTYPE html>
<html>
<head>
    <title>Laporan Riwayat Approval</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px; vertical-align: top; }
        th { background-color: #f2f2f2; }
        .badge { padding: 3px 6px; border-radius: 4px; color: white; font-weight: bold; font-size: 9px; display: inline-block; }
        .bg-success { background-color: #1cc88a; color: white; }
        .bg-danger { background-color: #e74a3b; color: white; }
        .text-muted { color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0;">LAPORAN RIWAYAT APPROVAL</h2>
        <p style="margin:5px 0;">SFA Bintang Interior System</p>
        <small>Dicetak Oleh: {{ $user->name }} ({{ $user->role }}) | Tgl: {{ date('d/m/Y H:i') }}</small>
    </div>

    <table>
        <thead>
            <tr>
                {{-- 1. HEADER NOMOR --}}
                <th width="5%" style="text-align: center;">No</th>

                <th width="12%">Waktu</th>
                <th width="15%">Pengaju</th>
                <th width="10%">Tipe</th>
                <th>Detail Data</th>
                <th width="10%">Status</th>
                <th width="15%">Approver</th>
            </tr>
        </thead>
        <tbody>
            @foreach($histories as $item)
                <tr>
                    {{-- 2. ISI NOMOR (Pakai loop iteration) --}}
                    <td style="text-align: center;">{{ $loop->iteration }}</td>

                    <td>{{ $item->updated_at->format('d/m/Y H:i') }}</td>

                    {{-- PENGAJU --}}
                    <td>
                        @if($item->history_type == 'TOP')
                            {{ $item->sales->name ?? '-' }}
                        @else
                            {{ $item->requester->name ?? 'System' }}
                        @endif
                    </td>

                    {{-- TIPE --}}
                    <td>
                        <strong>
                            @if($item->history_type == 'Product') Produk
                            @elseif($item->history_type == 'Order') Order
                            @elseif($item->history_type == 'PaymentLog') Keuangan
                            @elseif($item->history_type == 'Customer') Pelanggan
                            @else {{ $item->history_type }}
                            @endif
                        </strong>
                        <br>

                        @if($item->history_type == 'TOP')
                            <span style="color:blue;">(Limit)</span>
                        @elseif(isset($item->action) && $item->action == 'create')
                            <span style="color:green;">(Baru)</span>
                        @elseif(isset($item->action) && $item->action == 'delete')
                            <span style="color:red;">(Hapus)</span>
                        @else
                            <span style="color:orange;">(Edit)</span>
                        @endif
                    </td>

                    {{-- DETAIL DATA --}}
                    <td>
                        {{-- 1. PRODUK --}}
                        @if($item->history_type == 'Product')
                            @php
                                $data = (isset($item->action) && $item->action == 'delete')
                                    ? ($item->original_data ?? [])
                                    : ($item->new_data ?? []);
                            @endphp
                            <strong>{{ $data['name'] ?? '-' }}</strong><br>
                            <span class="text-muted">Stok: {{ $data['stock'] ?? 0 }} | Harga: {{ number_format($data['price'] ?? 0, 0, ',', '.') }}</span>

                        {{-- 2. ORDER --}}
                        @elseif($item->history_type == 'Order')
                             @php $data = $item->new_data ?? []; @endphp
                             <strong>{{ $data['invoice_number'] ?? '-' }}</strong><br>
                             Total: Rp {{ number_format($data['total_price'] ?? 0, 0, ',', '.') }}

                        {{-- 3. TOP (LIMIT KREDIT) --}}
                        @elseif($item->history_type == 'TOP')
                            <strong>{{ $item->customer->name ?? '-' }}</strong><br>
                            Limit Baru: Rp {{ number_format($item->submission_amount ?? 0, 0, ',', '.') }}

                        {{-- 4. PAYMENT LOG (PIUTANG) --}}
                        @elseif($item->history_type == 'PaymentLog')
                            @php $data = $item->new_data ?? []; @endphp
                            <strong>Pembayaran Masuk</strong><br>
                            Nominal: Rp {{ number_format($data['amount'] ?? 0, 0, ',', '.') }}

                        {{-- 5. CUSTOMER --}}
                        @elseif($item->history_type == 'Customer')
                            @php $data = $item->new_data ?? []; @endphp
                            <strong>{{ $data['name'] ?? 'Pelanggan' }}</strong><br>
                            <span class="text-muted">Update Data Profil</span>

                        @else
                            -
                        @endif
                    </td>

                    {{-- STATUS --}}
                    <td style="text-align: center;">
                        @if($item->status == 'approved')
                            <span class="badge bg-success">DISETUJUI</span>
                        @else
                            <span class="badge bg-danger">DITOLAK</span>
                            <br><small style="color:red; font-style:italic;">{{ $item->reason ?? '-' }}</small>
                        @endif
                    </td>

                    {{-- APPROVER --}}
                    <td>
                        {{ $item->approver->name ?? '-' }}
                        <br><small class="text-muted">{{ $item->approver->role ?? '' }}</small>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
