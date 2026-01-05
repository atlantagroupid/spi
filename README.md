# SFA - Bintang Interior System

## Deskripsi

Sistem SFA (Sales Force Automation) Bintang Interior adalah aplikasi web berbasis Laravel yang dirancang untuk mengelola penjualan, stok, dan piutang secara terintegrasi. Sistem ini memfasilitasi proses bisnis interior dengan fitur-fitur canggih untuk meningkatkan efisiensi operasional dan pengambilan keputusan.

## Fitur Unggulan

- **Sales Order**: Sistem pemesanan produk dengan tracking status dan approval berjenjang
- **Sistem Approval Berjenjang**: Mekanisme persetujuan multi-level untuk transaksi, piutang, customer, dan produk
- **Manajemen Kunjungan (Visit)**: Pelacakan kunjungan sales ke customer dengan check-in/out otomatis
- **Manajemen Piutang (Receivables)**: Pengelolaan pembayaran dan pengingat otomatis untuk piutang jatuh tempo
- **Kontrol Stok Multi-Gudang**: Sistem inventori dengan lokasi gudang, gate, dan block
- **Manajemen Customer**: Database customer dengan kategori dan TOP (Terms of Payment)
- **Manajemen Produk**: Katalog produk dengan diskon dan restock management
- **Dashboard Analytics**: Laporan visual untuk monitoring kinerja sales dan bisnis
- **User Quota Management**: Sistem plafon kredit per user dengan approval

## Persyaratan Sistem

- **PHP**: ^8.2
- **Laravel Framework**: ^12.0
- **Database**: MySQL atau kompatibel
- **Web Server**: Apache/Nginx dengan mod_rewrite
- **Composer**: Untuk dependency management
- **Node.js & NPM**: Untuk asset compilation

## Library Utama

- **DomPDF**: Untuk generate PDF laporan dan invoice
- **Intervention Image**: Untuk manipulasi gambar produk dan foto profil
- **Maatwebsite Excel**: Untuk export data ke format Excel
- **Laravel Sanctum**: Untuk autentikasi API (jika diperlukan)

## Panduan Instalasi

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd sfa-bintang-native
   ```

2. **Install Dependencies PHP**
   ```bash
   composer install
   ```

3. **Install Dependencies JavaScript**
   ```bash
   npm install
   ```

4. **Konfigurasi Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Edit file `.env` dengan konfigurasi database dan pengaturan lainnya.

5. **Jalankan Migration dan Seeder**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Compile Assets**
   ```bash
   npm run build
   # atau untuk development
   npm run dev
   ```

7. **Jalankan Server**
   ```bash
   php artisan serve
   ```

## Sistem Role & Hak Akses

Sistem ini menggunakan role-based access control dengan hak akses sebagai berikut:

| Role | Deskripsi | Hak Akses Utama |
|------|-----------|-----------------|
| **Manager Operasional** | Pengelola operasional keseluruhan | Akses penuh ke manajemen user, pengaturan sistem, lokasi gudang, approval semua modul (produk, customer, order, pembayaran), laporan pergerakan stok, manajemen kuota tim |
| **Manager Bisnis** | Pengawas bisnis dan sales | Berwenang menyetujui customer baru, approval order sales (termasuk mengurangi credit_limit_quota untuk pembelian kredit/TOP), approval pembayaran piutang (mengembalikan debt customer), manajemen kuota tim, monitoring penjualan |
| **Sales Store** | Sales di toko | Hanya bisa melihat dan mengelola order yang dibuat sendiri, customer yang ditugaskan, receivables order sendiri, melakukan kunjungan toko, request kuota kredit |
| **Sales Field** | Sales lapangan | Hanya bisa melihat dan mengelola order yang dibuat sendiri, customer yang ditugaskan, receivables order sendiri, melakukan kunjungan lapangan dan order di lokasi customer, request kuota kredit |
| **Kasir** | Pengelola kasir | Memproses Surat Jalan (upload bukti pengiriman), mengelola receivables, submit pembayaran customer |
| **Finance** | Pengelola keuangan | Mengajukan pembayaran piutang (bukan menyetujui), mengelola receivables, monitoring pembayaran |
| **Purchase** | Pengelola pembelian | Menghitung stok produk, mengatur harga diskon jika ada diskon, melihat manajemen produk (tidak mengupdate stok) |
| **Kepala Gudang** | Pengelola gudang | Mengelola produk dan stok (CRUD produk), approval perubahan produk, melihat laporan pergerakan stok, manajemen lokasi gudang |
| **Admin Gudang** | Staff gudang | Update stok produk harian, manajemen produk tanpa approval |

## Panduan Maintenance

### Pembersihan Cache
```bash
php artisan optimize:clear
# atau
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Sinkronisasi Route
```bash
php artisan route:cache
```

### Pengecekan Log
Log aplikasi tersimpan di `storage/logs/laravel.log`. Pantau log secara berkala untuk mendeteksi error.

### Backup Database
```bash
php artisan db:dump
# atau menggunakan mysqldump langsung
mysqldump -u username -p database_name > backup.sql
```

## Troubleshooting

### Masalah Focus Trap pada Modal
**Gejala**: Modal tidak dapat ditutup atau elemen di luar modal dapat diklik.
**Solusi**:
1. Pastikan tidak ada elemen dengan `tabindex` yang mengganggu
2. Periksa event listener untuk modal backdrop
3. Gunakan JavaScript untuk memaksa fokus tetap di dalam modal:
   ```javascript
   $('#modal').on('shown.bs.modal', function() {
       $(this).find('[autofocus]').focus();
   });
   ```

### Konflik Fokus SweetAlert di Atas Modal Bootstrap
**Gejala**: Tidak bisa mengetik di kolom input SweetAlert (seperti alasan penolakan) jika muncul di atas Modal Bootstrap.
**Penyebab**: Atribut `tabindex="-1"` pada Bootstrap Modal mengunci fokus kursor hanya di dalam elemen modal tersebut.
**Solusi**:
1. Hapus atribut `tabindex="-1"` pada elemen modal di file Blade.
2. Gunakan "Direct Fix" pada JavaScript saat memanggil SweetAlert:
   ```javascript
   $('#btnReject').on('click', function() {
       $('#modalId').removeAttr('tabindex'); // Hapus atribut pengunci
       $(document).off('focusin.modal');    // Matikan listener fokus Bootstrap
       Swal.fire({ ... });
   });
   ```

### Sinkronisasi Route Cache
**Gejala**: Perubahan route tidak tercermin setelah deployment.
**Solusi**:
```bash
php artisan route:clear
php artisan route:cache
```
Pastikan semua route middleware dan controller tersedia.

### Error Upload File
**Gejala**: Upload gambar produk/foto profil gagal.
**Solusi**:
1. Periksa permission folder `storage/app/public`
2. Jalankan `php artisan storage:link`
3. Pastikan file size tidak melebihi `upload_max_filesize` di php.ini

### Performance Issue
**Solusi**:
1. Jalankan `php artisan optimize`
2. Monitor query dengan Laravel Debugbar
3. Implementasi caching untuk data yang sering diakses

## Kontribusi

1. Fork repository
2. Buat branch fitur baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## Lisensi

Proyek ini menggunakan lisensi MIT. Lihat file `LICENSE` untuk detail lebih lanjut.

---

**Dikembangkan dengan ❤️ untuk Bintang Interior**
