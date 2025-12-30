<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // Nama tabel (Opsional jika nama tabel jamak dari nama model, tapi aman ditulis)
    protected $table = 'products';

    // Kolom yang boleh diisi secara massal (Create/Update)
    protected $fillable = [
        'name',
        'category',
        'price',
        'stock',
        'gudang_id',
        'gate_id',
        'block_id',
        'description',
        'image',
        'discount_price',
        'restock_date',
    ];

    // Opsional: Casting tipe data agar 'price' selalu dianggap angka/integer saat diambil
    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * Get the gudang that the product belongs to.
     */
    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    /**
     * Get the gate that the product belongs to.
     */
    public function gate()
    {
        return $this->belongsTo(Gate::class);
    }

    /**
     * Get the block that the product belongs to.
     */
    public function block()
    {
        return $this->belongsTo(Block::class);
    }
}
