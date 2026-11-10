# LearnBridge

Fondasi aplikasi pembelajaran berbasis web untuk proposal PPL Kelompok E. Aplikasi menggunakan Laravel 13, PHP 8.3+, MySQL 8.0+, dan Blade. Antarmuka menggunakan bahasa Indonesia.

## Persyaratan

- PHP 8.3 atau lebih baru dengan ekstensi PDO MySQL, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON, Fileinfo, dan BCMath.
- Composer 2.
- MySQL 8.0 atau lebih baru.
- Visual Studio Code.

## Menjalankan aplikasi

1. Ekstrak ZIP lalu buka folder `LearnBridge` di Visual Studio Code.
2. Buat basis data MySQL, misalnya `learnbridge`.
3. Di terminal VS Code, jalankan:

   ```powershell
   composer install
   Copy-Item .env.example .env
   php artisan key:generate
   ```

4. Sesuaikan `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env` dengan MySQL lokal.
5. Buat tabel dan akun contoh:

   ```powershell
   php artisan migrate --seed
   ```

6. Jalankan server lokal:

   ```powershell
   php artisan serve
   ```

7. Buka `http://127.0.0.1:8000`.

## Akun demo

| Peran | Email | Kata sandi |
|---|---|---|
| Admin | `admin@learnbridge.test` | `Belajar123!` |
| Institusi | `institusi@learnbridge.test` | `Belajar123!` |
| Pelajar | `pelajar@learnbridge.test` | `Belajar123!` |

Ganti semua kata sandi demo sebelum aplikasi dipakai di luar komputer lokal.

## Modul yang tersedia

- Login, pendaftaran akun, dashboard berbasis peran, profil profesional, dan pengaturan visibilitas.
- Pengelolaan akun, institusi dan verifikasi, keterampilan, kategori, kursus, bootcamp, materi, serta pendaftaran.
- Progres dan catatan belajar, kuis pilihan ganda, forum, komentar, ulasan dan rating, pengumuman, serta laporan konten.
- Penerbitan sertifikat dengan kode verifikasi publik dan pembatalan oleh Admin.

Sebagian besar modul menggunakan formulir generik agar mudah dipelajari dan dikembangkan bersama. Kolom relasi seperti ID kategori, program, materi, kuis, pelajar, dan diskusi diisi menggunakan ID yang terlihat di daftar modul terkait.

## Struktur dasar

- `app/Http/Controllers` — alur autentikasi dan fitur aplikasi.
- `app/Http/Middleware` — pembatasan peran.
- `database/migrations` — struktur tabel LearnBridge.
- `database/seeders` — akun dan contoh kursus/kuis.
- `resources/views` — halaman Blade.
- `routes/web.php` — rute aplikasi.

Ini adalah fondasi pembelajaran, bukan konfigurasi produksi. Sebelum diterapkan untuk pengguna sungguhan, tim perlu memperkuat pengelolaan dokumen verifikasi, kebijakan penyimpanan data, notifikasi, audit aktivitas, dan pemeriksaan kelulusan sertifikat.
