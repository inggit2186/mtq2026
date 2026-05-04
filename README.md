# e-MTQ

Starter aplikasi e-MTQ berbasis Laravel, Livewire, Tailwind, Alpine.js, dan Echo.

## Fitur Utama

- Dashboard realtime untuk leaderboard, jadwal, dan pengumuman
- Form penilaian peserta dengan Livewire
- Interaksi UI lebih halus dengan Alpine.js
- Broadcast event lewat Echo + Reverb
- UI/UX bergaya glassmorphism dengan warna yang lebih hidup

## Struktur Halaman

- `/` beranda promosi
- `/dashboard` dashboard utama MTQ
- `/penilaian` form input nilai

## Setup Cepat

1. Install dependency PHP dan Node.js.
2. Jalankan `composer install`.
3. Jalankan `npm install`.
4. Salin `.env.example` menjadi `.env`.
5. Pastikan MySQL lokal aktif dan database `kemenagtd_mtq` sudah tersedia di `localhost/phpmyadmin`.
6. Jalankan migrasi dan seeder: `php artisan migrate --seed`.
7. Jalankan aplikasi: `php artisan serve --no-reload` dan `npm run dev`.

## Catatan Realtime

- Echo sudah disiapkan pada `resources/js/app.js`.
- Broadcast event ada di `app/Events/ScoreUpdated.php`.
- Konfigurasi Reverb ada di `.env.example` dan `config/broadcasting.php`.
