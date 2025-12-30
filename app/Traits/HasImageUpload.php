<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

trait HasImageUpload
{
    public function uploadCompressed(UploadedFile $file, $folder, $oldFile = null)
    {
        // 1. Hapus File Lama
        if ($oldFile && Storage::disk('public')->exists($oldFile)) {
            Storage::disk('public')->delete($oldFile);
        }

        $filename = time() . '_' . uniqid() . '.';

        // 2. Jika PDF
        if (strtolower($file->getClientOriginalExtension()) == 'pdf') {
            $filename .= 'pdf';
            // Simpan dan RETURN PATH LENGKAP
            return $file->storeAs($folder, $filename, 'public');
        }

        // 3. Jika Gambar (WebP)
        $filename .= 'webp';

        $manager = new ImageManager(new Driver());
        $image = $manager->read($file);
        $image->scale(width: 1000);
        $encoded = $image->toWebp(quality: 80);

        // Definisikan Path Lengkap
        $fullPath = $folder . '/' . $filename;

        // Simpan ke Public
        Storage::disk('public')->put($fullPath, (string) $encoded);

        // RETURN PATH LENGKAP (Ini kuncinya!)
        // Database akan menyimpan: "delivery_notes/namafile.webp"
        return $fullPath;
    }
}
