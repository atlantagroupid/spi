<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <--- 1. Import Ini

class Customer extends Model
{
    use HasFactory, SoftDeletes; // <--- 2. Pasang di sini

    protected $guarded = ['id'];

    protected $fillable = [
        'user_id',
        'name',
        'contact_person',
        'phone',
        'address',
        'category',
        'latitude',
        'longitude',
        'top_days',
        'credit_limit',
        'status',
    ];
    protected $casts = [
        'top_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'original_data' => 'array',
        'new_data' => 'array',
    ];

    // Helper untuk cek apakah boleh hutang
    public function hasTop()
    {
        return $this->credit_limit > 0;
    }

    // Relasi: Customer dimiliki oleh satu Sales (User)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi: Satu Customer bisa memiliki BANYAK Order (Riwayat Belanja)
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
