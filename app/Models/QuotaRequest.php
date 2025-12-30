<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotaRequest extends Model
{
    use HasFactory;

    protected $table = 'quota_requests';

    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'user_id',      // Sales/Manager yang minta
        'amount',       // Jumlah yang diminta
        'reason',       // Alasan
        'status',       // pending, approved, rejected
        'approver_id',  // Siapa yang menyetujui (Manager Bisnis/Ops)
    ];

    /**
     * Relasi ke User yang meminta (Requester)
     * Contoh penggunaan: $quotaRequest->user->name
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke User yang menyetujui (Approver)
     * Contoh penggunaan: $quotaRequest->approver->name
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
