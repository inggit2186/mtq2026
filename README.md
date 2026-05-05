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

## Roadmap Realtime Reverb

### Tahap 1: Quick Wins

- Live leaderboard saat nilai berubah.
- Status sesi lomba realtime: `menunggu`, `ongoing`, `selesai`, `tertunda`.
- Notifikasi pengumuman penting yang lebih informatif.
- Notifikasi verifikasi peserta dengan ringkasan alasan penolakan atau revisi.

### Tahap 2: Operasional Panitia

- Presence channel untuk melihat panitia atau juri yang sedang online.
- Activity feed realtime untuk aksi penting: broadcast pengumuman, ubah jadwal, input nilai.
- Indikator status koneksi websocket yang lebih jelas di dashboard.
- Fallback polling yang tetap aman jika koneksi Reverb putus.

### Tahap 3: Pengalaman Peserta

- Notifikasi khusus peserta saat status berkas berubah.
- Alert jadwal tampil otomatis ketika sesi peserta akan dimulai.
- Acknowledge pengumuman agar panitia tahu informasi sudah dibaca.
- Progress update saat proses upload atau verifikasi masih berlangsung.

### Tahap 4: Monitoring dan Stabilitas

- Notifikasi jika broadcast gagal dikirim.
- Logging metrik sederhana: jumlah event yang terkirim, gagal, dan tertunda.
- Audit trail realtime untuk aksi admin dan panitia.
- Pengelompokan channel per domain, misalnya `mtq-live`, `mtq-admin`, dan `mtq-juri`.

### Prioritas Rekomendasi

1. Live leaderboard.
2. Status sesi lomba realtime.
3. Presence panitia atau juri.
4. Acknowledge pengumuman.
5. Monitoring dan alert kegagalan.
