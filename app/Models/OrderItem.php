<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    // Relasi: Item milik Order siapa?
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relasi: Item ini produk yang mana?
    public function product()
    {
        // 1. Pakai 'belongsTo' (Karena 1 Baris Order milik 1 Jenis Produk)
        // 2. Tambah 'withTrashed()' (Supaya produk yang sudah dihapus tetap muncul di riwayat order)
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
