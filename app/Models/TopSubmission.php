<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_id',
        'customer_id',
        'submission_limit',
        'submission_days',
        'status',      // pending, approved, rejected
        'approved_by',
        'notes',
    ];

    // Relasi ke Sales yang mengajukan
    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    // Relasi ke Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Relasi ke Manager yang menyetujui
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
