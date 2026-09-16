# Payroll App (HTML + CSS + JS + PHP) — v6 (Revisi)

Aplikasi sederhana untuk mengelola data karyawan dan menghitung/mengelola
(CRUD) penggajian. Versi ini adalah revisi dari versi sebelumnya, dengan
5 perbaikan utama:

## Revisi di Versi Ini (v6)

1. **Perbaikan error saat simpan gaji.** Sebelumnya proses simpan gaji bisa
   gagal karena struktur tabel `gaji` di database tidak sama persis dengan
   kolom yang dipakai kode (misalnya ada sisa kolom `nama` dari versi lama,
   padahal nama karyawan sekarang diambil lewat relasi `karyawan_id` ke
   tabel `karyawan`, bukan disimpan ulang di tabel `gaji`). Sekarang:
   - `database/payroll.sql` di-DROP & dibuat ulang setiap kali di-import,
     supaya strukturnya dijamin sama persis dengan kode PHP.
   - Proses simpan gaji di `hitung_gaji.php` dibungkus `try/catch`, jadi
     kalau terjadi error database akan muncul **pesan yang jelas**
     (bukan halaman putih/fatal error).
2. **Menu & CRUD Periode dihapus dari sidebar.** Mengelola periode sekarang
   cukup lewat popup di halaman **Hitung Gaji** (pilih periode aktif yang
   sudah ada, atau tambah periode baru langsung dari situ).
3. **Jumlah file dirapikan/dikurangi**, tapi fungsinya tetap sama. Beberapa
   file lama yang terpisah sekarang digabung jadi satu file (lihat bagian
   "Struktur File" di bawah).
4. **Tombol WHATSAPP di halaman Detail Gaji** langsung membuka WhatsApp Web
   (`web.whatsapp.com/send?phone=...&text=...`) di tab baru dengan nomor dan
   isi pesan slip gaji sudah terisi otomatis — tidak butuh API/token pihak
   ketiga.
5. **Captcha dihapus dari halaman Login**, tapi tetap ada di halaman
   **Hitung Gaji** (sebagai pengaman sebelum data gaji disimpan). Gambar
   captcha juga diperbaiki supaya tidak "broken image": kalau ekstensi GD
   PHP aktif, captcha digambar sebagai PNG (seperti sebelumnya); kalau GD
   TIDAK aktif di server, captcha otomatis digambar sebagai SVG (teks biasa,
   tidak butuh ekstensi tambahan apa pun), jadi gambarnya tetap muncul.

## Struktur File

```
payroll-app/
├── assets/
│   ├── css/style.css          -> semua styling
│   └── js/main.js             -> semua logic JS (preview hitung, search,
│                                  modal, captcha, kirim WhatsApp Web,
│                                  buka form tambah/edit)
├── config/
│   ├── database.php            -> koneksi ke MySQL
│   └── smtp_config.php         -> konfigurasi SMTP untuk kirim email
├── database/
│   ├── payroll.sql             -> struktur tabel (users, karyawan, periode,
│   │                               gaji) + contoh data. DROP & CREATE ulang
│   │                               setiap import, supaya struktur selalu
│   │                               sinkron dengan kode.
│   └── seed_admin.php          -> jalankan sekali untuk buat akun admin
├── includes/
│   ├── auth.php                -> cek session login
│   ├── sidebar.php             -> menu sidebar (Dashboard, Karyawan,
│   │                               Hitung Gaji, Riwayat) — Periode dihapus
│   ├── delete_modal.php        -> modal konfirmasi hapus (dipakai berulang)
│   ├── periode_helper.php      -> hitung & sinkronkan status Aktif/Tidak
│   │                               Aktif periode otomatis
│   └── SimpleSMTPMailer.php    -> class pengirim email lewat SMTP
├── captcha.php                 -> generate gambar captcha (PNG via GD,
│                                   atau SVG kalau GD tidak aktif)
├── kirim_email.php             -> proses kirim slip gaji lewat SMTP
│
├── index.php                    -> Login (tampil form + proses login jadi satu)
├── logout.php                   -> Logout
├── dashboard.php                -> Dashboard
│
├── karyawan.php                  -> List + Tambah + Edit + Hapus Karyawan
│                                     (satu file, modal popup untuk
│                                     Tambah/Edit)
│
├── hitung_gaji.php               -> Pilih/Tambah Periode (popup) + Hitung
│                                     & Simpan Gaji (satu file, dibedakan
│                                     lewat field tersembunyi "aksi")
├── riwayat.php                   -> List + Edit + Hapus Gaji (satu file,
│                                     modal popup untuk Edit)
└── detail_gaji.php               -> Slip gaji + popup kirim Email /
                                      WhatsApp Web
```

**Pola penggabungan file:** setiap file boleh menangani lebih dari satu
aksi (tampil, tambah, edit, hapus), dibedakan lewat:
- Method request (`GET` untuk menampilkan, `POST` untuk menyimpan), dan/atau
- Parameter tersembunyi seperti `id` (kosong = tambah baru, terisi = edit)
  atau `aksi` (di `hitung_gaji.php`, karena ada 3 jenis proses simpan yang
  berbeda: pilih periode, tambah periode, simpan gaji).

## Setup SMTP (supaya tombol EMAIL benar-benar mengirim)

1. Buka `config/smtp_config.php`.
2. Kalau pakai Gmail:
   - Aktifkan verifikasi 2 langkah di akun Google kamu.
   - Buat App Password di https://myaccount.google.com/apppasswords
   - Isi `$smtp_username` dengan email Gmail kamu, dan `$smtp_password` dengan
     App Password 16 digit tadi (BUKAN password akun Gmail biasa).
3. Kalau pakai provider lain (Outlook, Mailtrap untuk testing, hosting sendiri, dll),
   sesuaikan `$smtp_host`, `$smtp_port`, dan `$smtp_encryption` ('tls' atau 'ssl')
   dengan dokumentasi provider tersebut.

## Kirim WhatsApp

Tombol WHATSAPP di halaman **Detail Gaji** tidak butuh setup apa pun — begitu
diklik dan nomor tujuan diisi di popup, browser langsung membuka tab baru ke
WhatsApp Web (`web.whatsapp.com/send?phone=...&text=...`) dengan nomor dan isi
pesan slip gaji sudah terisi otomatis. Syaratnya cuma perangkat yang dipakai
sudah login WhatsApp Web (scan QR sekali di `web.whatsapp.com`); setelah itu
tinggal klik kirim di sana. Kalau belum pernah login WhatsApp Web di browser
itu, WhatsApp akan menampilkan halaman perantara ("Buka aplikasi" /
"Lanjutkan ke WhatsApp Web") sebelum masuk ke chat — itu perilaku bawaan
WhatsApp, bukan bug aplikasi ini.

## Cara Menjalankan (XAMPP / Laragon)

1. Copy folder `payroll-app` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka phpMyAdmin, **hapus database `payroll_db` lama kalau ada**, lalu
   import file `database/payroll.sql`. File ini akan membuat ulang database
   `payroll_db` beserta tabel `users`, `karyawan`, `periode`, dan `gaji`
   dengan struktur yang sudah sinkron dengan kode terbaru.
3. Cek `config/database.php`, sesuaikan `$username`/`$password` kalau perlu.
4. Isi `config/smtp_config.php` (lihat panduan di atas) supaya tombol EMAIL
   benar-benar bisa mengirim. Tombol WHATSAPP tidak butuh setup file config.
5. Buka `http://localhost/payroll-app/database/seed_admin.php` untuk membuat akun admin
   (default: email `admin@payroll.com`, password `admin123`). Hapus file ini setelah dipakai.
6. Buka `http://localhost/payroll-app/`, masukkan email & password, lalu login
   (tidak ada captcha lagi di halaman ini).

## Alur Pemakaian

1. Tambahkan data karyawan dulu lewat menu **Karyawan** (tombol "+ Tambah
   Karyawan" membuka popup, begitu juga tombol "Edit").
2. Buka **Hitung Gaji** → karena belum ada periode terpilih, akan muncul popup
   **Pilih Periode Penggajian**. Pilih periode Aktif yang sudah ada, atau isi
   form "Tambah Periode Baru" lalu klik **TAMBAH & GUNAKAN PERIODE INI**.
3. Setelah periode terisi, form Hitung Gaji muncul. Pilih karyawan, isi gaji
   pokok/lembur/pinjaman, masukkan kode captcha, klik **HITUNG GAJI**.
4. Hasilnya otomatis masuk ke **Riwayat**, dan bisa dilihat detailnya di
   halaman **Detail Gaji** — di sana tersedia tombol Cetak PDF, EMAIL, dan
   WHATSAPP. Di **Riwayat**, data juga bisa langsung di-Edit (popup) atau
   Dihapus.

## Catatan

- Captcha memakai library GD bawaan PHP kalau tersedia; kalau tidak, otomatis
  memakai gambar SVG sebagai cadangan (tidak butuh ekstensi tambahan).
- Karakter captcha sengaja tidak memakai O/0 dan I/1/l supaya tidak membingungkan saat dibaca.
- Kirim Email memakai `SimpleSMTPMailer` (socket PHP native, tanpa PHPMailer) yang benar-benar
  mengirim lewat server SMTP — wajib isi `config/smtp_config.php` dulu.
- Status Aktif/Tidak Aktif periode murni otomatis berdasarkan tanggal selesai vs tanggal
  hari ini.
- Untuk keamanan produksi nyata, tambahkan validasi input lebih ketat dan CSRF token.
