<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gate extends Model
{
    use HasFactory;

    protected $table = 'gates';

    protected $fillable = ['gudang_id', 'name'];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function blocks()
    {
        return $this->hasMany(Block::class);
    }
}
