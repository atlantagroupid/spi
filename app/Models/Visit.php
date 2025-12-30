<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Visit extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    // Ini berfungsi mengubah String database menjadi Carbon Object otomatis
    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Fungsi untuk memotong paksa kunjungan yang lebih dari 2 jam
     */
    public static function runAutoCutoff()
    {
        // 1. Cari visit yang statusnya 'in_progress' TAPI sudah mulai lebih dari 2 jam lalu
        $staleVisits = self::where('status', 'in_progress')
            ->where('created_at', '<=', Carbon::now()->subHours(2)) // Lebih dari 2 jam yang lalu
            ->get();

        foreach ($staleVisits as $visit) {
            // 2. Tentukan waktu checkout = Waktu Checkin + 2 Jam
            // Jadi durasinya pas 2 jam, tidak lebih.
            $cutoffTime = Carbon::parse($visit->created_at)->addHours(2);

            // 3. Update Data
            $visit->update([
                'check_out_time' => $cutoffTime,
                'status' => 'completed',
                'notes' => $visit->notes . "\n\n[SYSTEM]: Auto Cutoff (Melebihi batas waktu 2 jam).",
            ]);
        }
    }
}
