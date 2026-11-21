# Rencana Implementasi PBI-9 — Mengelola Materi Pembelajaran (UC-09)

## Konteks
PBI-9 (UC-09) mensyaratkan CRUD materi pembelajaran oleh **Admin & Institusi**: daftar materi, pilih program (kursus/bootcamp), tambah/lihat/ubah materi, validasi judul+deskripsi+berkas/tautan, hapus sesuai kewenangan, dan pesan error jika format berkas tidak didukung.

**Status saat ini:** sisi web PBI-9 sudah jadi di working tree (belum di-commit):
- Migrasi `2026_11_21_000002_add_resources_to_materials_table.php` (kolom `source_url`, `file_path`, `file_name`)
- CRUD materi lengkap di [PortalController.php](C:\Users\Aulia\Documents\Campusstuff\Sem 5\PPL\Projek\TUBES_LearnBridge\app\Http\Controllers\PortalController.php) (validasi, upload berkas ke disk `local`, ownership `assertOwnedProgram`, scoping visibilitas pelajar)
- Form di [module.blade.php](C:\Users\Aulia\Documents\Campusstuff\Sem 5\PPL\Projek\TUBES_LearnBridge\resources\views\module.blade.php) + route unduh `/materi/{id}/berkas`
- `resources/lang/id/validation.php` (pesan validasi Indonesia, aktif karena `APP_LOCALE=id`)

**Yang kurang** — mengikuti pola setiap PBI lain (BootcampApiController+BootcampApiTest, CourseApiController+CourseApiTest): **API REST materi + test feature-nya belum ada.** Itu yang akan dikerjakan.

## Perubahan

### 1. File baru: `app/Http/Controllers/MaterialApiController.php`
Klon pola [BootcampApiController.php](C:\Users\Aulia\Documents\Campusstuff\Sem 5\PPL\Projek\TUBES_LearnBridge\app\Http\Controllers\BootcampApiController.php):
- **Endpoint institusi**: `store` (201), `index`, `show`, `update` (PATCH, partial), `destroy`
- **Endpoint admin**: `adminIndex`, `adminShow`, `adminDestroy` (langkah 6 alur utama: hapus sesuai kewenangan)
- `materialRules(bool $partial = false)` — sama dengan aturan web yang sudah ada: `title` required string max:180; `body` required string; `program_type` in:course,bootcamp; `program_id` integer; `position` nullable integer min:0; `source_url` nullable url max:2048; `attachment` nullable file mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,mp4 max:10240 (alur alternatif: format tidak didukung → error 422)
- `institutionId($request, $mustBeApproved)` + `assertOwnedProgram()`: resolve material→program→institution, 404/403 sesuai pola bootcamp; institusi wajib `approved` untuk membuat materi
- Upload: `$file->store('materials', 'local')` + `file_name` = nama asli; saat update ganti berkas → hapus berkas lama; saat destroy → hapus berkas (pola `storeMaterialAttachment`/`deleteMaterialAttachment` di PortalController)
- `baseQuery()`: leftJoin `courses`+`bootcamps` untuk menyertakan `program_title`; sertakan `file_url` (route `material.file`) bila ada berkas
- Respon: `['data'=>...]` untuk baca, `['message'=>..., 'data'=>...]` untuk tulis (pesan Indonesia)

### 2. Edit: `routes/web.php`
Di dalam blok `Route::prefix('api/v1')`, tambah komentar `// PBI 9 - API pengelolaan materi pembelajaran`:
- `Route::middleware(['auth','role:institusi'])->prefix('institution/materials')`: POST `/`, GET `/`, GET|PATCH|DELETE `/{id}` (dengan `whereNumber('id')`)
- `Route::middleware(['auth','role:admin'])->prefix('admin/materials')`: GET `/`, GET `/{id}`, DELETE `/{id}`

### 3. File baru: `tests/Feature/MaterialApiTest.php`
Cermin `BootcampApiTest` (RefreshDatabase, helper `institutionUser()` + helper program milik sendiri):
1. Institusi buat materi + unggah berkas (`Storage::fake('local')`, `UploadedFile::fake()`) → 201, berkas tersimpan
2. Validasi: payload tanpa judul/deskripsi, `program_type` salah, berkas `.exe` → 422 (menguji alur alternatif)
3. Institusi tidak dapat mengakses materi institusi lain → 404
4. Institusi belum terverifikasi membuat materi → 403
5. Update materi + ganti berkas → berkas lama terhapus dari disk
6. Hapus materi sendiri → baris + berkas terhapus; admin bisa hapus materi siapa pun; pelajar → 403
7. `index` hanya menampilkan materi program milik sendiri

### 4. Edit kecil: `resources/lang/id/validation.php`
Tambah attributes `body`, `source_url`, `attachment` agar pesan validasi materi berbahasa Indonesia lengkap.

## Verifikasi
1. `php artisan test --filter=MaterialApiTest` → semua lulus
2. `php artisan test` → seluruh suite (Course/Bootcamp/web) tetap hijau
3. Cek ulang alur UC-09 di web: `php artisan migrate:fresh --seed`, login institusi (`institusi@learnbridge.test`/`Belajar123!`), buka `/modul/materi` — tambah materi dengan berkas + tautan, edit, unduh via `/materi/{id}/berkas`, hapus