<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'user_id', 'amount', 'payment_date',
        'payment_method', 'proof_file', 'status', 'notes'
    ];

    // Relasi ke Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relasi ke User (Penginput)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
