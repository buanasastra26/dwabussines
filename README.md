# DWA Legalitas

Website DWA Legalitas dengan portal klien PHP + MySQL, login OTP email, dan PWA DWA Bussines.

## Isi website
- index.html: beranda
- portal.php: pendaftaran dan login OTP
- dashboard.php: dashboard klien dan pemasangan web app
- layanan.php: layanan lengkap khusus klien yang sudah login
- layanan.html: pengalihan tautan lama ke portal
- testimoni.html: testimoni perusahaan
- assets/: gambar website

## Deployment
Ikuti [DEPLOY-HOSTINGER.md](DEPLOY-HOSTINGER.md). Gunakan PHP 8.2+, MySQL, HTTPS, dan SMTP. PHPMailer 7.1.1 sudah disertakan; tidak perlu npm atau Composer di server.

Portal gagal tertutup sampai database dan SMTP dikonfigurasi. Tidak ada OTP simulasi. Konfigurasi rahasia disimpan di luar public_html dan tidak dimasukkan ke GitHub.

Formulir menyiapkan pesan WhatsApp dan tidak menyimpan data di server.

Pendaftaran menyimpan nama, email terverifikasi, dan WhatsApp opsional di database. WhatsApp dipakai sebagai pengenal login, bukan metode verifikasi. OTP selalu dikirim ke email terdaftar. Dashboard memerlukan internet dan tidak disimpan dalam cache offline.
