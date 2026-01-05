<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Order extends Model
{
    use HasFactory;

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
        'payment_type',
        'rejection_note',
        'delivery_proof',
        'driver_name',
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
    // Relasi untuk mengambil tiket approval terakhir terkait order ini
    public function latestApproval()
    {
        return $this->morphOne(\App\Models\Approval::class, 'model')->latest();
    }
    // Relasi ke Riwayat Pembayaran (PaymentLog)
    public function paymentLogs()
    {
        return $this->hasMany(PaymentLog::class)->latest();
    }

    // Relasi ke History
    public function histories()
    {
        return $this->hasMany(OrderHistory::class)->latest();
    }

    // Fungsi Pembantu Mencatat History
    public function recordHistory($action, $description = null)
    {
        $this->histories()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description
        ]);
    }
}
