<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Otomatis ubah JSON jadi Array saat diambil dari database
    protected $casts = [
        'original_data' => 'array',
        'new_data' => 'array',
    ];

    // --- RELASI ---

    // 1. Relasi ke User yang Meminta (Requester)
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // 2. Relasi ke User yang Menyetujui (Approver)
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // 3. Relasi Polimorfik (Bisa ngambil data Produk/Customer aslinya)
    public function approveable()
    {
        // Ini akan otomatis mencari model berdasarkan kolom 'model_type' dan 'model_id'
        return $this->morphTo(__FUNCTION__, 'model_type', 'model_id');
    }
}
