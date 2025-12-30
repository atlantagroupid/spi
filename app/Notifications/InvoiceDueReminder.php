<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Order;

class InvoiceDueReminder extends Notification
{
    use Queueable;

    public $order;
    public $daysLeft; // Variabel penampung sisa hari

    // Terima Order DAN Sisa Hari dari Command
    public function __construct(Order $order, $daysLeft)
    {
        $this->order = $order;
        $this->daysLeft = $daysLeft;
    }

    public function via($notifiable)
    {
        return ['database']; // Masuk ke lonceng
    }

    public function toArray($notifiable)
    {
        // 1. Tentukan Warna & Ikon berdasarkan urgensi
        $urgencyColor = 'text-warning'; // Default Kuning (Masih lama)
        $iconType = 'bi-clock-history';

        if ($this->daysLeft <= 0) {
            $urgencyColor = 'text-danger'; // Merah (Hari H atau Telat)
            $iconType = 'bi-exclamation-diamond-fill';
        } elseif ($this->daysLeft == 1) {
            $urgencyColor = 'text-danger'; // Merah (Besok)
            $iconType = 'bi-alarm-fill';
        }

        // 2. Tentukan Judul & Pesan yang Natural
        if ($this->daysLeft == 0) {
            // KASUS HARI INI
            $title = '🚨 HARI INI Jatuh Tempo!';
            $msg   = 'Segera tagih! Sales Order ' . $this->order->invoice_number . ' (' . $this->order->customer->name . ') jatuh tempo HARI INI.';
        } elseif ($this->daysLeft == 1) {
            // KASUS BESOK
            $title = '⚠️ BESOK Jatuh Tempo!';
            $msg   = 'Persiapkan penagihan. Sales Order ' . $this->order->invoice_number . ' jatuh tempo BESOK.';
        } elseif ($this->daysLeft > 1) {
            // KASUS H-Sekian
            $title = '⏳ Sales Order Jatuh Tempo';
            $msg   = 'Sales Order ' . $this->order->invoice_number . ' (' . $this->order->customer->name . ') jatuh tempo ' . $this->daysLeft . ' hari lagi.';
        } else {
            // KASUS MINUS (TELAT - Jaga-jaga kalau command dijalankan manual untuk tgl lampau)
            $telat = abs($this->daysLeft);
            $title = '💀 TELAT BAYAR ' . $telat . ' Hari';
            $msg   = 'Customer ' . $this->order->customer->name . ' sudah menunggak pembayaran SO: ' . $this->order->invoice_number;
        }

        return [
            'order_id' => $this->order->id,
            'title'    => $title,
            'message'  => $msg,
            'link'     => route('orders.show', $this->order->id),
            'icon'     => $iconType . ' ' . $urgencyColor,
            'type'     => ($this->daysLeft <= 1) ? 'danger' : 'warning',
        ];
    }
}
