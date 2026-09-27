# Aktivasi Portal DWA di Hostinger

## Yang sudah disiapkan

- Beranda mengarahkan semua tautan pengunjung ke portal akun (kecuali tautan aksesibilitas untuk melewati navigasi).
- Daftar: nama + email wajib, WhatsApp opsional, persetujuan penggunaan data.
- Masuk: email atau nomor WhatsApp yang terdaftar. Kode OTP selalu dikirim ke email akun.
- OTP berlaku 10 menit, maksimal 5 percobaan per kode; kirim ulang minimal 60 detik. Batas tambahan per alamat email dan IP.
- Dashboard klien berisi sembilan layanan, konsultasi, keluar akun, serta panduan pemasangan PWA.
- Layanan lengkap dilindungi sesi server. Sesi berlaku maksimal 12 jam, lalu login lagi.
- Web app berupa PWA dari browser, bukan APK atau publikasi App Store. Pemasangan tergantung dukungan browser dan HTTPS. Hanya halaman offline serta ikon yang dicache, bukan data klien.

## 1. Hosting & database

1. Pilih hosting PHP, bukan hosting statis/Node. Gunakan PHP 8.2 atau lebih baru dengan PDO MySQL dan OpenSSL aktif. Aktifkan HTTPS/SSL dan pengalihan HTTP ke HTTPS di panel hosting.
2. Deploy branch `main` ke `public_html`. `index.html`, `portal.php`, `dashboard.php`, `layanan.php`, `api/`, `server/`, `vendor/`, ikon, dan aset harus berada di sana. Sertakan `.htaccess` dan `.htaccess` dalam subfolder.
3. Buat database MySQL dan user database melalui panel hosting. Catat nama lengkap database, user, sandi, dan host.
4. Import `setup/schema.sql` sekali melalui phpMyAdmin. Tabel menggunakan InnoDB.

## 2. Siapkan email pengirim OTP

1. Buat mailbox domain milik DWA, misalnya `noreply@dwabussines.com`, jika domain itu memang dikelola Anda. Langganan email mungkin diperlukan; kode ini tidak membeli layanan email.
2. Buka pengaturan koneksi SMTP mailbox tersebut. Gunakan hostname, port, dan sandi yang benar dari penyedia email. Jangan gunakan sandi akun Hostinger utama.
3. Gunakan SMTP terenkripsi: port 465 dengan `ssl`, atau port 587 dengan `tls`, sesuai penyedia. Verifikasi sertifikat tetap aktif.
4. Pastikan DNS email (SPF, DKIM, dan DMARC) disiapkan sesuai panduan penyedia. `from_email` harus mailbox yang diizinkan oleh SMTP.

## 3. Simpan konfigurasi privat

Struktur yang harus dibuat melalui File Manager:

```text
folder-domain/
  dwa-private/
    config.php
  public_html/
    index.html
    portal.php
    dashboard.php
    layanan.php
    server/
    api/
    ...
```

Salin isi `setup/config.example.php` ke `dwa-private/config.php`. Folder ini berada sejajar dengan `public_html`, bukan di dalamnya. Kode membaca path tersebut, jadi posisi harus tepat. Jika deployment memakai symlink atau struktur khusus, sesuaikan path konfigurasi di `server/bootstrap.php` dengan path absolut privat yang benar.

Isi konfigurasi berikut di Hostinger saja:

- `db.host`, `db.name`, `db.user`, `db.password`
- `smtp.host`, `smtp.port`, `smtp.encryption`, `smtp.username`, `smtp.password`, `smtp.from_email`
- `app_key`: 64 karakter hex acak. Buat lewat terminal hosting: `php -r "echo bin2hex(random_bytes(32));"`
- Ubah `enabled` menjadi `true` setelah semua nilai tersedia.

Jangan kirim sandi SMTP, sandi database, atau app_key melalui chat dan jangan commit ke GitHub. Atur izin file konfigurasi hanya untuk pemilik akun hosting jika didukung.

Jika konfigurasi belum lengkap, portal menampilkan status sedang disiapkan dan tombol pendaftaran dinonaktifkan. Tidak ada OTP bypass, OTP demo, atau akun admin bawaan.

## 4. Pemeriksaan aktivasi terakhir di hosting

Pengiriman email sungguhan dan database produksi belum dapat diverifikasi sebelum konfigurasi tersedia. Setelah konfigurasi dipasang, lakukan satu rangkaian pemeriksaan:

1. Daftar menggunakan email Anda; terima OTP dan verifikasi sampai dashboard terbuka.
2. Pastikan kode yang sudah dipakai tidak bisa dipakai kembali; kode salah ditolak.
3. Keluar; akses langsung `dashboard.php` dan `layanan.php` harus kembali ke portal.
4. Login memakai WhatsApp yang dicantumkan; OTP tetap masuk ke email akun tersebut.
5. Dari dashboard pada HTTPS, pasang web app lewat tombol atau menu browser. Saat offline, hanya halaman pemberitahuan offline yang tampil.

Jika email tidak masuk: periksa spam, setelan SMTP, log PHP generik `DWA: OTP delivery unavailable.`, batas kirim penyedia, serta DNS email. Respons publik tetap generik untuk melindungi informasi keberadaan akun. Kode OTP dan kredensial tidak ditulis ke log.

## Operasional

- Profil nama/WhatsApp akun yang sudah ada tidak ditimpa oleh pendaftaran ulang. Koreksi akun melalui dukungan dan verifikasi email pemilik akun.
- Nomor WhatsApp belum diverifikasi kepemilikannya; jangan menggunakannya sebagai bukti identitas atau untuk tindakan sensitif.
- Permintaan hapus akun ditangani manual melalui email dukungan. Hapus baris akun dan catatan OTP terkait sesuai permintaan yang telah diverifikasi. Sesi yang sudah aktif berakhir paling lambat 12 jam; pencabutan segera membutuhkan penghapusan sesi PHP terkait oleh operator.
- Catatan OTP dan pembatasan yang kedaluwarsa lebih dari 24 jam dibersihkan secara bertahap pada permintaan OTP. Untuk situs sepi, tambahkan cron SQL penghapusan catatan kedaluwarsa bila diperlukan.
- Cadangkan database sesuai kebijakan usaha Anda. Halaman `privasi.html` menjelaskan penggunaan data yang diimplementasikan dan perlu ditinjau pemilik usaha sebelum peluncuran.

PHPMailer: https://github.com/PHPMailer/PHPMailer/releases/tag/v7.1.1 — lisensi tersedia di `vendor/phpmailer/LICENSE`.
