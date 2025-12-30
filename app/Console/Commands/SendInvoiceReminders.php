<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Notifications\InvoiceDueReminder; // Pastikan Import ini BENAR
use Carbon\Carbon;

class SendInvoiceReminders extends Command
{
    /**
     * Nama perintah buat si Robot
     */
    protected $signature = 'invoice:remind';

    /**
     * Deskripsi tugasnya
     */
    protected $description = 'Kirim notifikasi otomatis untuk tagihan jatuh tempo (H-7, H-3, H-1, Hari H)';

    /**
     * Eksekusi Perintah
     */
    public function handle()
    {
        $this->info('Sedang memeriksa tagihan bertingkat...');

        // DAFTAR INTERVAL PENGINGAT:
        // 7 = Seminggu lagi
        // 3 = 3 Hari lagi
        // 1 = Besok
        // 0 = HARI INI (Sangat Penting)
        $intervals = [7, 3, 1, 0];

        $totalSent = 0;

        foreach ($intervals as $days) {
            // Hitung tanggal target. Contoh: Hari ini tgl 9. Target H+7 = Tgl 16.
            $targetDate = Carbon::now()->addDays($days)->format('Y-m-d');

            // Cari order unpaid yang due_date-nya PAS tanggal target tersebut
            $orders = Order::where('payment_status', 'unpaid')
                           ->whereDate('due_date', $targetDate)
                           ->get();

            foreach ($orders as $order) {
                // Pastikan user/salesnya masih aktif
                if ($order->user) {
                    // Kirim Notif dengan parameter sisa hari ($days)
                    $order->user->notify(new InvoiceDueReminder($order, $days));

                    // Pesan log di terminal
                    $statusMsg = ($days === 0) ? "HARI INI" : "H-$days";
                    $this->info("[$statusMsg] Dikirim ke {$order->user->name} | No: {$order->invoice_number}");

                    $totalSent++;
                }
            }
        }

        $this->info("Selesai! Total $totalSent notifikasi terkirim hari ini.");
    }
}
