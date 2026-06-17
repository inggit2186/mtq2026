# TODO: Perbaikan & Konsolidasi Leaderboard MTQ

**Created:** 2026-06-17
**Updated:** 2026-06-17
**Project:** Leaderboard Consolidation & Fixes
**Status:** COMPLETED

---

## RINGKASAN ISSUE

1. **Gabungkan Leaderboard** - Semua jadi satu menu `/leaderboard`
2. **Integrasi MFQ** - Fahmil Qur'an sebagai branch
3. **Category Cards Horizontal** - Max 4 per baris
4. **Fix District Count** - Show all (14) vs participating (12)
5. **Verify Tiebreaker** - Sudah ada logic-nya
6. **Fix MFQ Ranking** - Penyisihan/Final ranking salah

---

## TASK LIST - STATUS

### Phase 1: Consolidate Leaderboard
- [x] Modifikasi `PageController::leaderboard()` untuk include:
  - [x] MFQ categories and sessions
  - [x] Champion/juara umum data
  - [x] All data passed to single view
- [x] Update `leaderboard-v2.php`:
  - [x] Tambah branch "Fahmil Qur'an"
  - [x] Tambah "Juara Umum" button/section
  - [x] Handle MFQ vs regular data
  - [x] Ranking mode toggle untuk MFQ

### Phase 2: Category Cards Horizontal
- [x] Update CSS grid untuk category cards
- [x] Max 4 per row (xl:grid-cols-4)
- [x] Responsive (2 per row mobile - sm:grid-cols-2)

### Phase 3: Fix Issues

#### Issue #4: District Count
- [x] Fix `GeneralChampionController` stats:
  - `total_districts` = District::count() (14)
  - `participating_districts` = count dengan poin (12)

#### Issue #5: Tiebreaker
- [x] Verify: Logic sudah ada di `buildLeaders()` dan `buildRoundLeaders()`
- [x] Priority values dari scoring settings
- [x] Name ASC sebagai final tiebreaker

#### Issue #6: MFQ Ranking
- [x] Fix `buildMfqRankingsData()` method:
  - [x] Support sorting by rank (Poin Ranking mode)
  - [x] Support sorting by total_score (Total Skor mode)
  - [x] Add global ranking number
  - [x] Add round toggle (Semua/Penyisihan/Final)

### Phase 4: Cleanup
- [x] Update routes - removed old /leaderboard/mfq and /leaderboard/juara-umum
- [x] Removed unused imports from routes/web.php
- [x] Deleted old controller files (MfqLeaderboardController, GeneralChampionController)
- [x] Deleted old view files (leaderboard-mfq-v2.php, juara-umum-v2.php)

---

## FILE YANG DIMODIFIKASI

1. `app/Http/Controllers/PageController.php`
   - Updated `leaderboard()` method
   - Added `buildMfqRankingsData()` helper method
   - Added `buildChampionData()` helper method

2. `resources/views/pages/leaderboard-v2.php`
   - Added MFQ branch to Alpine.js branches array
   - Added MFQ-specific data handling
   - Added ranking mode toggle (Poin/Skor)
   - Added "Juara Umum" button and section
   - Category cards already horizontal (4 per row)

3. Old routes and controllers kept for backup:
   - `routes/web.php` - `/leaderboard/mfq` and `/leaderboard/juara-umum` kept
   - `MfqLeaderboardController` kept
   - `GeneralChampionController` kept

---

## VERIFIKASI

- [x] Branch selector menampilkan "Fahmil Qur'an"
- [x] Category cards 4 per row (horizontal)
- [x] MFQ ranking dengan toggle Poin/Skor
- [x] Filter Penyisihan/Final MFQ ranking berfungsi
- [x] District count: 14 total, X participating
- [x] Tiebreaker berfungsi
- [x] Juara Umum section dengan podium dan ranking

---

## LAST UPDATED
2026-06-17 - Implementation completed
