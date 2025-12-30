<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // Pastikan GD Driver terinstall di PHP

trait HasImageUpload
{
    /**
     * Upload gambar, convert ke WebP, resize jika perlu.
     * Jika file adalah PDF, simpan apa adanya.
     */
    public function uploadCompressed(UploadedFile $file, $folder, $oldFile = null)
    {
        // 1. HAPUS FILE LAMA (Jika ada)
        if ($oldFile && Storage::disk('public')->exists($folder . '/' . $oldFile)) {
            Storage::disk('public')->delete($folder . '/' . $oldFile);
        }

        $filename = time() . '_' . uniqid() . '.';

        // 2. CEK TIPE FILE
        // Kalau PDF, jangan di-convert, langsung simpan
        if ($file->getClientOriginalExtension() == 'pdf') {
            $filename .= 'pdf';
            $file->storeAs('public/' . $folder, $filename);
            return $filename;
        }

        // 3. KALAU GAMBAR, PROSES KE WEBP
        $filename .= 'webp';

        // Setup Image Manager (Versi Baru Intervention Image)
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file);

        // Resize (Opsional): Max lebar 1000px biar tidak berat (rasio tetap)
        // Kalau gambar aslinya kecil, dia tidak akan diperbesar (aman)
        $image->scale(width: 1000);

        // Encode ke WebP dengan kualitas 80%
        $encoded = $image->toWebp(quality: 80);

        // Simpan ke Storage Public
        Storage::disk('public')->put($folder . '/' . $filename, (string) $encoded);

        return $filename;
    }
}
