<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil Bahan Baku
        $products = Product::all();
        $customers = Customer::with('user')->get(); // Ambil semua toko beserta sales pemiliknya

        if ($customers->count() == 0 || $products->count() == 0) {
            $this->command->info('⚠️ Harap jalankan ProductSeeder dan CustomerSeeder dulu!');
            return;
        }

        // 2. GENERATE ORDER BULANAN (Januari - Sekarang)
        // Loop setiap bulan
        for ($bulan = 1; $bulan <= date('n'); $bulan++) {

            // Di setiap bulan, kita pilih 10-15 toko acak untuk belanja
            // Biar gak semua toko belanja tiap bulan (lebih realistis)
            $randomCustomers = $customers->random(min(15, $customers->count()));

            foreach ($randomCustomers as $customer) {
                // Sales pemilik toko yang input order
                $sales = $customer->user;

                // Kalau toko ini "Yatim Piatu" (gak ada sales), skip atau kasih ke admin
                if (!$sales) continue;

                // Buat 1-2 order per toko di bulan itu
                $jumlahOrder = rand(1, 2);
                for ($k = 0; $k < $jumlahOrder; $k++) {
                    // Status bayar acak (80% lunas, 20% belum)
                    $statusBayar = rand(1, 10) > 2 ? 'paid' : 'unpaid';

                    // Khusus bulan ini, banyakin yang 'unpaid' biar tabel piutang ramai
                    if ($bulan == date('n')) {
                        $statusBayar = rand(1, 10) > 5 ? 'unpaid' : 'paid';
                    }

                    $this->createOrder($sales, $customer, $products, $bulan, $statusBayar);
                }
            }
        }
    }

    // Fungsi Pembantu bikin Order
    private function createOrder($user, $customer, $products, $month, $paymentStatus = 'paid')
    {
        $year = date('Y');
        // Tanggal acak (hindari tanggal 31 biar aman semua bulan)
        $date = Carbon::create($year, $month, rand(1, 28), rand(8, 17), rand(0, 59));

        $order = Order::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'SO-' . $date->format('Ymd') . '-' . rand(1000,9999),
            'total_price' => 0, // Nanti diupdate
            'status' => $paymentStatus == 'paid' ? 'completed' : 'process',
            'payment_status' => $paymentStatus,
            'due_date' => $date->copy()->addDays($customer->top_days),
            'amount_paid' => 0,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        // Isi Keranjang Belanja (Random 1-5 jenis barang)
        $total = 0;
        $items = $products->random(rand(1, 5));

        foreach($items as $prod) {
            $qty = rand(2, 20); // Beli 2-20 pcs
            $subtotal = $prod->price * $qty;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $prod->id,
                'quantity' => $qty,
                'price' => $prod->price
            ]);
            $total += $subtotal;
        }

        // Update Total Harga
        $order->total_price = $total;
        if($paymentStatus == 'paid') {
            $order->amount_paid = $total;
        }
        $order->save();
    }
}
