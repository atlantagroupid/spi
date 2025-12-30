<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use HasFactory;

    protected $table = 'blocks';

    protected $fillable = ['gate_id', 'name'];

    public function gate()
    {
        return $this->belongsTo(Gate::class);
    }
}
