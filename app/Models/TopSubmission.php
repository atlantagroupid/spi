<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;     // <--- WAJIB ADA
use App\Models\Customer; // <--- WAJIB ADA

class TopSubmission extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // 1. Relasi ke User (Sales yang mengajukan)
    public function user()
    {
        return $this->belongsTo(User::class, 'sales_id', 'id');
    }

    // 2. Relasi ke Customer (Toko yang diajukan)
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // Relasi ke Manager yang menyetujui
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
