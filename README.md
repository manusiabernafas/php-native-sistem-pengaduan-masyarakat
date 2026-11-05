# Sistem Pengaduan Masyarakat / e-Lapor (PHP Native + MySQL)

Sistem Pengaduan Masyarakat (e-Lapor) berbasis PHP Native dan MySQL untuk pemerintah kota/kabupaten, desa, kelurahan, kampus, atau instansi layanan publik. Warga cukup membuka portal, menulis laporan (boleh anonim) beserta lokasi dan foto, lalu mendapat nomor tiket untuk melacak perkembangan laporannya sampai selesai, lengkap dengan penilaian kepuasan di akhir.

Di sisi petugas, setiap aduan diverifikasi admin, diteruskan otomatis ke dinas/unit penanggung jawab sesuai kategori, lalu ditindaklanjuti dengan riwayat tanggapan yang rapi. Sistem menghitung umur aduan terhadap target SLA per kategori dan menandai yang terlambat. Tersedia juga halaman transparansi publik berisi statistik penanganan. Tampilannya dark dashboard modern, cocok dipakai langsung atau untuk tugas kuliah dan skripsi sistem informasi layanan publik.

## Fitur

- Portal warga tanpa login: buat laporan dengan kategori, lokasi, isi, dan foto
- Opsi laporan anonim (identitas pelapor tidak ditampilkan ke petugas)
- Nomor tiket otomatis (ADU-YYMM-0001) untuk melacak laporan
- Halaman lacak tiket dengan timeline penanganan yang bisa dilihat publik
- Penilaian kepuasan pelapor (1 sampai 5) setelah aduan selesai
- Halaman transparansi publik: jumlah aduan, persentase selesai, rata-rata waktu, kepuasan
- Routing otomatis ke unit/dinas penanggung jawab sesuai kategori
- Alur status: Baru, Diverifikasi, Diproses, Selesai, Ditolak
- Target SLA per kategori dan penanda aduan yang lewat SLA
- Prioritas aduan (rendah, sedang, tinggi) dan pengurutan otomatis
- Catatan internal petugas yang tidak terlihat oleh pelapor
- Petugas hanya bisa mengakses aduan unitnya (dicek di server)
- Tombol hubungi pelapor via WhatsApp
- Dashboard: aduan baru, dalam proses, selesai bulan ini, rata-rata penyelesaian, kepuasan
- Grafik masuk vs selesai 8 minggu dan kategori terbanyak
- Laporan kinerja per unit (persentase tepat SLA) dan per kategori, ekspor CSV
- Anti spam: honeypot dan batas 5 laporan per jam, validasi MIME foto
- Foto disimpan di folder terlindungi dan disajikan lewat skrip
- Data master: kategori, SLA, unit, pengguna, identitas layanan
- Data contoh 6 bulan (280+ aduan) dan tampilan dark dashboard yang responsif

## Instalasi

**Kebutuhan:** PHP 8.0+ (ekstensi `fileinfo` dan `gd` aktif, bawaan XAMPP) dan MySQL/MariaDB.

1. Ekstrak ZIP, lalu pindahkan folder `pengaduan` ke:
   - XAMPP: `C:\xampp\htdocs\pengaduan`
   - Laragon: `C:\laragon\www\pengaduan`

2. Jalankan **Apache** dan **MySQL**.

3. Buka `http://localhost/phpmyadmin` → **Import** → pilih `database/pengaduan_masyarakat.sql` → **Import**.

   Atau lewat terminal:
   ```bash
   mysql -u root -p < database/pengaduan_masyarakat.sql
   ```

4. Sesuaikan `config/database.php` jika user/password MySQL kamu berbeda:
   ```php
   return [
       'host'     => 'localhost',
       'port'     => 3306,
       'database' => 'pengaduan_masyarakat',
       'username' => 'root',
       'password' => '',
   ];
   ```

5. Portal warga: `http://localhost/pengaduan`

6. Panel petugas: `http://localhost/pengaduan/index.php?page=login` (password semua akun: `admin123`)
   - `admin`: verifikasi, teruskan ke unit, data master
   - `dpu`, `dlh`, `dishub`: petugas unit, hanya melihat aduan unitnya

7. Buka **Master** untuk mengganti nama layanan, instansi, kategori, unit, dan target SLA.

**Catatan:** folder `uploads/` dilindungi `.htaccess` (Apache). Untuk Nginx tambahkan aturan `deny all` pada folder tersebut.

---
Source code ini dibagikan gratis oleh **Papicode** — https://papicode.id
