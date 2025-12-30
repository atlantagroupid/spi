<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderHistory extends Model
{
    use HasFactory;

    // Kita izinkan kolom-kolom ini untuk diisi data
    protected $fillable = [
        'order_id',
        'user_id',
        'action',
        'description',
    ];

    // Relasi ke User (Siapa yang melakukan aksi)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Order (Order mana yang diubah)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
