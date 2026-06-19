# CLAUDE.md

File ini memberikan panduan untuk Claude Code (claude.ai/code) saat bekerja dengan kode di repository ini.

## Gambaran Proyek

e-MTQ adalah aplikasi Laravel 12 untuk mengelola kompetisi MTQ (Musabaqah Tilawatil Quran). Aplikasi ini menangani pendaftaran peserta, verifikasi, penilaian, drawing lot/maqra, jadwal sesi, pengumuman, dan pembaruan real-time via broadcasting Reverb.

## Perintah Pengembangan

```bash
# Instal dependensi
composer install
npm install

# Jalankan semua layanan dev (server PHP, queue worker, Vite dev)
npm run dev

# Jalankan tes
php artisan test

# Jalankan satu file tes
php artisan test tests/Feature/ParticipantRegistrationTest.php

# Jalankan dengan coverage
php artisan test --coverage

# Format kode (Pint)
./vendor/bin/pint

# Jalankan server PHP saja
php artisan serve --no-reload
```

## Arsitektur

### Peran & Kontrol Akses
Pengguna memiliki empat peran: `admin`, `panitia`, `official`, `pendamping`, dan `peserta`. Pemeriksaan peran menggunakan middleware `role:...` dengan logika OR. Middleware `password.change` memaksa perubahan password pada login pertama (`flag must_change_password` di tabel users).

### Cakupan Akses Pengguna
Pengguna dapat dibatasi pada districts atau kategori tertentu (`district_id`, relasi `categoryAccesses`, `districtAccesses`). Controller menyaring query berdasarkan relasi akses ini, bukan hanya pemeriksaan peran.

### Model-model Kunci
- **Participant**: Entitas utama dengan soft delete (`archive`), pelacakan status verifikasi, nomor lot, draw maqra, path dokumen (disimpan sebagai array)
- **SessionSchedule**: Memiliki sinkronisasi status otomatis berdasarkan timestamp `starts_at`/`ends_at`
- **JuknisSetting**: Konfigurasi global yang dimuat via `AppServiceProvider` boots ke `config('juknis'...)` dan `config('mtq...')`
- **OfficialAccessSetting**: Flag akses per-official untuk pendaftaran, lot/maqra, dengan kontrol spesifik kategori
- **ScoringSetting**: Konfigurasi penilaian global dengan pelacakan status edit

### Broadcasting (Reverb)
- Broadcasts pada channel `mtq-live` (dikonfigurasi di `config/broadcasting.php`)
- Events: `ScoreUpdated`, `ParticipantVerificationUpdated`, `AnnouncementPublished`, `SessionScheduleUpdated`
- Client mendengarkan di `resources/js/app.js` untuk `.score.updated`, `.participant.verification-updated`, `.announcement.published`, `.schedule.updated`
- Echo diinisialisasi dengan Reverb; polling fallback untuk jadwal berlangsung dipicu jika koneksi gagal

### Routes
- `routes/web.php`: Semua route autentikasi dan ter-authentication
- `routes/api.php`: Kosong (endpoint API tidak digunakan)
- Route dengan peran menggunakan middleware: `role:admin,panitia`, dll.

### Frontend
- Alpine.js untuk reaktivitas dan toggle tema gelap/terang
- SweetAlert2 untuk alert dan konfirmasi
- Overlay loading submisi kustom dengan pelacakan progress upload XHR
- Notifikasi langsung via Alpine store (`$store.ui.notifications`)
- Tailwind CSS v4 dengan tema glassmorphism gelap (default)

## Konvensi Penting

### Database
- Menggunakan MySQL di production (`database kemenantd_mtq`), SQLite untuk tes
- Migration timestamped (`2026_04_16_...`) dengan prefix 20 karakter
- Soft delete pada participants; peserta diarsipkan masuk ke tabel `archived_participants`

### Pengaturan Juknis
Dimuat saat boot ke config Laravel. Saat mengedit pengaturan yang bergantung pada JuknisSetting:
```php
// Pengaturan di-cache, gunakan config() setelah membersihkan cache
config('juknis.scoring.rule');  // Teks rule lengkap
config('mtq.branding');         // Nama dan branding aplikasi
```

### Penanganan Submisi
Formulir menggunakan submisi XHR kustom dengan overlay progress:
- Form multipart: progress upload file otomatis
- Form reguler: progress animasi dengan penanganan error
- Pesan error dinormalisasi via `normalizeSubmitErrorMessage()` di `app.js`

### Sistem Penilaian
- MFQ (Musabaqah Fq) memiliki controller penilaian terpisah (`MfqScoringController`)
- Penilaian reguler menggunakan workflow `ScoringSetting.edit_state`: minta → admin buka → submit nilai

### Sistem Maqra
Draw pemilihan cabang maqra (`ParticipantMaqraDraw`) terpisah dari draw `lot_number` reguler:
- Route `maqraMenu` dan `maqraDraw`
- `OfficialAccessSetting` mengontrol kategori mana yang memiliki akses maqra
