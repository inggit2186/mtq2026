# TODO: Perbaikan & Konsolidasi Leaderboard MTQ

**Created:** 2026-06-17
**Updated:** 2026-06-17
**Project:** Leaderboard Consolidation & Fixes
**Status:** COMPLETED (UI Fixes)

---

## RINGKASAN ISSUE

1. **Gabungkan Leaderboard** - Semua jadi satu menu `/leaderboard`
2. **Integrasi MFQ** - Fahmil Qur'an sebagai branch
3. **Category Cards Horizontal** - Max 4 per baris
4. **Fix District Count** - Show all (14) vs participating (12)
5. **Verify Tiebreaker** - Sudah ada logic-nya
6. **Fix MFQ Ranking** - Rankings untuk Penyisihan dan Final

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

### Phase 2: UI Improvements (2026-06-17)
- [x] Stats cards di header diperkecil
- [x] Bagian Pilih Cabang diperkecil
- [x] Pilih Golongan改成 horizontal (max 4 per row)
- [x] Fix MFQ ranking functions untuk properly handle MFQ branch

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
   - Stats cards diperkecil
   - Pilih Cabang diperkecil
   - Pilih Golongan改成 horizontal (grid 4 per row)
   - Fix functions untuk properly handle MFQ branch

3. `routes/web.php`
   - Removed old routes

4. Deleted files:
   - `MfqLeaderboardController.php`
   - `GeneralChampionController.php`
   - `leaderboard-mfq-v2.php`
   - `juara-umum-v2.php`

---

## LAST UPDATED
2026-06-17 - All UI improvements completed, MFQ ranking functions fixed

---

## Phase 5: Peserta Final System (2026-06-20)

### Overview
Added a system to generate and manage finalists (top 3 participants per gender per category).

### Features
- **Generate Finalis**: Take top 3 participants (putra) and top 3 participants (putri) per category
- **Smart Ranking**: Final scores > Penyisihan scores, with tiebreakers
- **Status Tracking**: Track finalist status (pending, active, completed, scratched)
- **Regenerate**: Can regenerate finalists at any time
- **Use for Scoring/Maqra**: Data stored in DB for reuse

### Files Created

1. **Database Migration**: `database/migrations/2026_06_20_000001_create_finalists_table.php`
   - Stores finalist records with participant_id, category, gender, rank, score, round, status

2. **Model**: `app/Models/Finalist.php`
   - Relationships: participant(), competitionCategory()
   - Helper methods: rankLabel, genderLabel, scopes for filtering

3. **Controller**: `app/Http/Controllers/FinalistController.php`
   - `index()` - Display finalists management page
   - `generate()` - Generate finalists for a specific category
   - `generateAll()` - Generate finalists for ALL categories
   - `updateStatus()` - Update finalist status (scratch, etc.)
   - `destroy()` - Delete finalists for a category
   - `getByCategory()` - API endpoint for fetching finalists

4. **View**: `resources/views/pages/finalists.php`
   - Category cards with finalist lists
   - Generate/Regenerate/Delete buttons
   - Modals for confirmation

5. **Routes Added** (`routes/web.php`):
   - `GET /admin/finalis` - Finalists management page
   - `POST /admin/finalis/generate` - Generate all finalists
   - `POST /admin/finalis/generate/{category}` - Generate for specific category
   - `POST /admin/finalis/{finalist}/status` - Update status
   - `POST /admin/finalis/category/{category}` - Delete finalists

6. **Leaderboard Update** (`resources/views/pages/leaderboard-v2.php`):
   - Added "Kelola Finalis" button linking to /admin/finalis

### Usage
1. Go to `/leaderboard` and click "Kelola Finalis"
2. Or go directly to `/admin/finalis`
3. Click "Generate Semua Golongan" to create finalists for all categories
4. Or click "Generate" on individual category cards
5. Data can be regenerated anytime - old data is replaced
