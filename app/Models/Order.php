<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id']; // Semua boleh diisi kecuali ID

    protected $fillable = [
        'user_id',
        'customer_id',
        'invoice_number',
        'total_price',
        'status',
        'payment_status',
        'due_date',
        'notes',
        'payment_type', // <--- TAMBAHKAN INI
        'delivery_proof', // <--- TAMBAHKAN INI (Untuk Kasir)
    ];

    // Tambahkan ini agar due_date dibaca sebagai tanggal (Carbon)
    protected $casts = [
        'due_date' => 'date',
    ];

    // Relasi: 1 Order dimiliki 1 Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    // Relasi: 1 Order dibuat oleh 1 User (Sales)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sales()
    {
        // Relasi ke tabel users, via kolom user_id
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi: 1 Order punya BANYAK Item
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // --- TAMBAHKAN INI YANG KURANG ---
    // Relasi ke Riwayat Pembayaran (PaymentLog)
    public function paymentLogs()
    {
        return $this->hasMany(PaymentLog::class)->latest();
    }
}
