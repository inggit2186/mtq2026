<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\MaqraPackage;
use App\Models\Participant;
use App\Models\ParticipantMaqraDraw;
use App\Models\SessionSchedule;
use App\Models\ScoreEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $districtNames = collect([
            'X Koto',
            'Batipuh',
            'Batipuh Selatan',
            'Lima Kaum',
            'Lintau Buo',
            'Lintau Buo Utara',
            'Padang Ganting',
            'Pariangan',
            'Rambatan',
            'Salimpaung',
            'Sungai Tarab',
            'Sungayang',
            'Tanjung Baru',
            'Tanjung Emas',
        ]);

        $districtSlugs = $districtNames->map(fn (string $name): string => Str::slug($name))->all();

        District::query()
            ->whereNotIn('slug', $districtSlugs)
            ->delete();

        $districts = $districtNames
            ->map(fn (string $name): District => District::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            ))
            ->keyBy('slug');

        $accounts = collect([
            [
                'name' => 'Admin e-MTQ',
                'email' => 'admin@emtq.test',
                'nomor_induk' => '198001012005011001',
                'password' => 'password',
                'role' => 'admin',
                'district_id' => null,
            ],
            [
                'name' => 'Panitia Arena',
                'email' => 'panitia@emtq.test',
                'nomor_induk' => '198502142010121002',
                'password' => 'password',
                'role' => 'panitia',
                'district_id' => null,
            ],
            [
                'name' => 'Alya Zahra',
                'email' => 'peserta@emtq.test',
                'nomor_induk' => '1304010101100001',
                'password' => 'password',
                'role' => 'peserta',
                'district_id' => $districts->get(Str::slug('X Koto'))?->id,
            ],
        ])->merge(
            $districtNames->values()->map(function (string $districtName, int $index) use ($districts): array {
                $slug = Str::slug($districtName);

                return [
                    'name' => 'Official '.$districtName,
                    'email' => 'official.'.str_replace('-', '', $slug).'@emtq.test',
                    'nomor_induk' => '3201123456789'.str_pad((string) ($index + 11), 3, '0', STR_PAD_LEFT),
                    'password' => 'password',
                    'role' => 'official',
                    'district_id' => $districts->get($slug)?->id,
                ];
            })
        )->map(fn (array $account): User => User::query()->updateOrCreate(
            ['email' => $account['email']],
            $account,
        ));

        $admin = $accounts->firstWhere('role', 'admin');

        $categoryDefinitions = collect([
            ['branch' => 'Seni Baca Al Qur`an', 'name' => 'Tilawah Kanak-kanak', 'quota' => 2, 'age_requirement' => '6 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#38bdf8'],
            ['branch' => 'Seni Baca Al Qur`an', 'name' => 'Tilawah Anak Anak', 'quota' => 2, 'age_requirement' => '12 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#38bdf8'],
            ['branch' => 'Seni Baca Al Qur`an', 'name' => 'Tilawah Remaja', 'quota' => 2, 'age_requirement' => '22 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#38bdf8'],
            ['branch' => 'Seni Baca Al Qur`an', 'name' => 'Tilawah Dewasa', 'quota' => 2, 'age_requirement' => '38 tahun 11 bulan 29 hari', 'notes' => 'KK Kabupaten', 'color' => '#0ea5e9'],

            ['branch' => 'Hafalan Al Qur`an', 'name' => 'Gol. 1 Juz Tilawah', 'quota' => 2, 'age_requirement' => '13 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#22c55e'],
            ['branch' => 'Hafalan Al Qur`an', 'name' => 'Gol. 1 Juz Non Tilawah', 'quota' => 2, 'age_requirement' => '13 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#22c55e'],
            ['branch' => 'Hafalan Al Qur`an', 'name' => 'Gol. 5 Juz Tilawah', 'quota' => 2, 'age_requirement' => '18 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#22c55e'],
            ['branch' => 'Hafalan Al Qur`an', 'name' => 'Gol. 5 Juz Non Tilawah', 'quota' => 2, 'age_requirement' => '18 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#22c55e'],
            ['branch' => 'Hafalan Al Qur`an', 'name' => 'Gol. 10 Juz', 'quota' => 2, 'age_requirement' => '20 tahun 11 bulan 29 hari', 'notes' => 'KK Kabupaten', 'color' => '#16a34a'],

            ['branch' => 'Tartil Al Qur`an', 'name' => 'Gol. Dasar', 'quota' => 2, 'age_requirement' => '10 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#f59e0b'],
            ['branch' => 'Tartil Al Qur`an', 'name' => 'Gol. Menengah', 'quota' => 2, 'age_requirement' => '18 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#f59e0b'],
            ['branch' => 'Tartil Al Qur`an', 'name' => 'Gol. Umum', 'quota' => 2, 'age_requirement' => '25 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#f59e0b'],

            ['branch' => 'Tafsir Al Qur`an', 'name' => 'Bahasa Indonesia', 'quota' => 2, 'age_requirement' => '32 tahun 11 bulan 29 hari', 'notes' => 'KK Kabupaten', 'color' => '#8b5cf6'],
            ['branch' => 'Tafsir Al Qur`an', 'name' => 'Bahasa Arab', 'quota' => 2, 'age_requirement' => '20 tahun 11 bulan 29 hari', 'notes' => 'KK Kabupaten', 'color' => '#8b5cf6'],
            ['branch' => 'Tafsir Al Qur`an', 'name' => 'Bahasa Inggris', 'quota' => 2, 'age_requirement' => '32 tahun 11 bulan 29 hari', 'notes' => 'KK Kabupaten', 'color' => '#8b5cf6'],

            ['branch' => 'Seni Kaligrafi Al Qur`an', 'name' => 'Golongan Naskah', 'quota' => 2, 'age_requirement' => '32 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#ec4899'],
            ['branch' => 'Seni Kaligrafi Al Qur`an', 'name' => 'Golongan Hiasan Mushaf', 'quota' => 2, 'age_requirement' => '32 tahun 11 bulan 29 hari', 'notes' => 'KK Kabupaten', 'color' => '#ec4899'],
            ['branch' => 'Seni Kaligrafi Al Qur`an', 'name' => 'Golongan Dekorasi', 'quota' => 2, 'age_requirement' => '32 tahun 11 bulan 29 hari', 'notes' => 'KK Kabupaten', 'color' => '#ec4899'],
            ['branch' => 'Seni Kaligrafi Al Qur`an', 'name' => 'Golongan Kontemporer', 'quota' => 2, 'age_requirement' => '32 tahun 11 bulan 29 hari', 'notes' => 'KK Kabupaten', 'color' => '#ec4899'],

            ['branch' => 'Fahmil Qur`an', 'name' => 'Golongan Putra', 'quota' => 3, 'age_requirement' => '16 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#06b6d4'],
            ['branch' => 'Fahmil Qur`an', 'name' => 'Golongan Putri', 'quota' => 3, 'age_requirement' => '16 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#06b6d4'],

            ['branch' => 'Syarhil Qur`an', 'name' => 'Golongan Putra', 'quota' => 3, 'age_requirement' => '16 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#f97316'],
            ['branch' => 'Syarhil Qur`an', 'name' => 'Golongan Putri', 'quota' => 3, 'age_requirement' => '16 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan', 'color' => '#f97316'],

            ['branch' => 'Khutbah Jumat dan Adzan', 'name' => 'Khatib dan Muadzin', 'quota' => 2, 'age_requirement' => 'Minimal 16 tahun, maksimal 18 tahun 11 bulan 29 hari', 'notes' => 'KK Kecamatan - 2 putra', 'color' => '#14b8a6'],

            ['branch' => 'Kitab Standar', 'name' => 'Kitab Standar', 'quota' => 2, 'age_requirement' => '22 tahun 11 bulan 29 hari', 'notes' => 'KK Kabupaten', 'color' => '#eab308'],

            ['branch' => 'Karya Tulis Ilmiah Al Qur`an (KTIQ)', 'name' => 'KTIQ', 'quota' => 2, 'age_requirement' => '22 tahun 11 bulan 29 hari', 'notes' => 'KK Kabupaten', 'color' => '#facc15'],

            ['branch' => 'Hafalan Hadits Nabi', 'name' => 'Hafalan 50 Hadits dengan Sanad', 'quota' => 2, 'age_requirement' => '21 tahun 11 bulan 29 hari', 'notes' => 'KK Kabupaten', 'color' => '#84cc16'],
            ['branch' => 'Hafalan Hadits Nabi', 'name' => 'Hafalan 250 Hadits dengan Sanad', 'quota' => 2, 'age_requirement' => '21 tahun 11 bulan 29 hari', 'notes' => 'KK Kabupaten', 'color' => '#84cc16'],
        ])->values();

        $categorySlugs = $categoryDefinitions
            ->map(fn (array $item): string => Str::slug($item['branch'].' '.$item['name']))
            ->all();

        CompetitionCategory::query()
            ->whereNotIn('slug', $categorySlugs)
            ->delete();

        $categories = $categoryDefinitions->map(function (array $item, int $index): CompetitionCategory {
            $slug = Str::slug($item['branch'].' '.$item['name']);

            return CompetitionCategory::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'branch' => $item['branch'],
                    'name' => $item['name'],
                    'quota' => $item['quota'],
                    'age_requirement' => $item['age_requirement'],
                    'notes' => $item['notes'],
                    'sort_order' => $index + 1,
                    'description' => 'Kategori resmi MTQ berdasarkan cabang dan golongan peserta.',
                    'round' => 'Penyisihan',
                    'color' => $item['color'],
                ],
            );
        })->keyBy('slug');

        $usesMaqraCategory = function (CompetitionCategory $category): bool {
            $haystack = mb_strtolower(trim((string) $category->branch.' '.(string) $category->name));

            return str_contains($haystack, 'seni baca al qur')
                || str_contains($haystack, 'hafalan al qur')
                || str_contains($haystack, 'tafsir al qur')
                || str_contains($haystack, 'fahmil qur');
        };

        $maqraSystemLabel = function (CompetitionCategory $category): ?string {
            $branch = mb_strtolower((string) $category->branch);

            return match (true) {
                str_contains($branch, 'seni baca al qur') => 'Tilawah',
                str_contains($branch, 'hafalan al qur') => 'Tahfizh',
                str_contains($branch, 'tafsir al qur') => 'Tafsir',
                str_contains($branch, 'fahmil qur') => 'Fahmil',
                default => null,
            };
        };

        $maqraPrefixForSystem = function (?string $system): string {
            return match ($system) {
                'Tilawah' => 'TLW',
                'Tahfizh' => 'HFZ',
                'Tafsir' => 'TFS',
                'Fahmil' => 'FHL',
                default => 'MQR',
            };
        };

        $maqraReferencePool = collect([
            ['title' => 'Al-Fatihah 1-7', 'content' => 'QS. Al-Fatihah ayat 1 sampai 7'],
            ['title' => 'Al-Baqarah 1-5', 'content' => 'QS. Al-Baqarah ayat 1 sampai 5'],
            ['title' => 'Al-Baqarah 30-39', 'content' => 'QS. Al-Baqarah ayat 30 sampai 39'],
            ['title' => 'Ali Imran 1-9', 'content' => 'QS. Ali Imran ayat 1 sampai 9'],
            ['title' => 'Ali Imran 133-136', 'content' => 'QS. Ali Imran ayat 133 sampai 136'],
            ['title' => 'An-Nisa 1-10', 'content' => 'QS. An-Nisa ayat 1 sampai 10'],
            ['title' => "Al-Ma'idah 1-5", 'content' => "QS. Al-Ma'idah ayat 1 sampai 5"],
            ['title' => "Al-An'am 75-83", 'content' => "QS. Al-An'am ayat 75 sampai 83"],
            ['title' => "Al-A'raf 54-58", 'content' => "QS. Al-A'raf ayat 54 sampai 58"],
            ['title' => 'At-Taubah 18-22', 'content' => 'QS. At-Taubah ayat 18 sampai 22'],
            ['title' => 'Yunus 1-10', 'content' => 'QS. Yunus ayat 1 sampai 10'],
            ['title' => 'Hud 1-5', 'content' => 'QS. Hud ayat 1 sampai 5'],
            ['title' => 'Yusuf 1-12', 'content' => 'QS. Yusuf ayat 1 sampai 12'],
            ['title' => "Ar-Ra'd 1-7", 'content' => "QS. Ar-Ra'd ayat 1 sampai 7"],
            ['title' => 'Ibrahim 1-8', 'content' => 'QS. Ibrahim ayat 1 sampai 8'],
            ['title' => 'Al-Hijr 1-15', 'content' => 'QS. Al-Hijr ayat 1 sampai 15'],
            ['title' => 'An-Nahl 1-16', 'content' => 'QS. An-Nahl ayat 1 sampai 16'],
            ['title' => 'Al-Isra 1-10', 'content' => 'QS. Al-Isra ayat 1 sampai 10'],
            ['title' => 'Al-Kahf 1-8', 'content' => 'QS. Al-Kahf ayat 1 sampai 8'],
            ['title' => 'Maryam 1-11', 'content' => 'QS. Maryam ayat 1 sampai 11'],
            ['title' => 'Taha 1-8', 'content' => 'QS. Taha ayat 1 sampai 8'],
            ['title' => 'Al-Anbiya 1-9', 'content' => 'QS. Al-Anbiya ayat 1 sampai 9'],
            ['title' => 'Al-Hajj 1-10', 'content' => 'QS. Al-Hajj ayat 1 sampai 10'],
            ['title' => "Al-Mu'minun 1-11", 'content' => "QS. Al-Mu'minun ayat 1 sampai 11"],
        ]);

        ParticipantMaqraDraw::query()->delete();
        MaqraPackage::query()->delete();

        $maqraPackagesByCategoryRound = [];
        $maqraCategories = $categories->filter($usesMaqraCategory)->values();

        foreach ($maqraCategories as $category) {
            $system = $maqraSystemLabel($category) ?? 'Maqra';
            $prefix = $maqraPrefixForSystem($system);

            foreach (['Penyisihan' => ['code' => 'PS', 'count' => 18], 'Final' => ['code' => 'FN', 'count' => 10]] as $roundLabel => $config) {
                for ($maqraIndex = 1; $maqraIndex <= $config['count']; $maqraIndex++) {
                    $sample = $maqraReferencePool->random();

                    $package = MaqraPackage::query()->updateOrCreate(
                        [
                            'competition_category_id' => $category->id,
                            'round_label' => $roundLabel,
                            'maqra_code' => sprintf('%s-%s-%02d', $prefix, $config['code'], $maqraIndex),
                        ],
                        [
                            'title' => $system.' - '.$sample['title'],
                            'content' => $sample['content'].' | Babak '.$roundLabel.' | Seed test e-MTQ',
                            'notes' => 'Seed data maqra untuk testing.',
                            'sort_order' => $maqraIndex,
                            'is_active' => true,
                        ]
                    );

                    $maqraPackagesByCategoryRound[$category->id][$roundLabel][] = $package;
                }
            }
        }

        $participants = [
            [
                'name' => 'Alya Zahra',
                'institution' => 'MTQ Kota Padang',
                'region' => 'Sumatera Barat',
                'district' => $districts->get(Str::slug('X Koto')),
                'category' => $categories->get(Str::slug('Seni Baca Al Qur`an Tilawah Remaja')),
                'reg' => 'TLA-001',
                'gender' => 'putri',
                'nik' => '1304010101100001',
                'birth_place' => 'Padang',
                'birth_date' => '2010-01-01',
                'phone' => '081200000001',
                'verification_status' => 'verified',
                'verification_notes' => 'Data demo telah diverifikasi.',
            ],
            [
                'name' => 'Muhammad Raihan',
                'institution' => 'MTQ Kabupaten Agam',
                'region' => 'Sumatera Barat',
                'district' => $districts->get(Str::slug('Batipuh')),
                'category' => $categories->get(Str::slug('Hafalan Al Qur`an Gol. 5 Juz Tilawah')),
                'reg' => 'TQ5-002',
                'gender' => 'putra',
                'nik' => '1304020202080002',
                'birth_place' => 'Agam',
                'birth_date' => '2008-02-02',
                'phone' => '081200000002',
                'verification_status' => 'verified',
                'verification_notes' => 'Data demo telah diverifikasi untuk testing leaderboard.',
            ],
            [
                'name' => 'Nur Aisyah',
                'institution' => 'MTQ Kota Bukittinggi',
                'region' => 'Sumatera Barat',
                'district' => $districts->get(Str::slug('Pariangan')),
                'category' => $categories->get(Str::slug('Syarhil Qur`an Golongan Putri')),
                'reg' => 'SQA-003',
                'gender' => 'putri',
                'nik' => '1304030303060003',
                'birth_place' => 'Bukittinggi',
                'birth_date' => '2006-03-03',
                'phone' => '081200000003',
                'verification_status' => 'verified',
                'verification_notes' => 'Data demo telah diverifikasi untuk testing leaderboard.',
            ],
            [
                'name' => 'Fadhil Ramadhan',
                'institution' => 'MTQ Kabupaten Tanah Datar',
                'region' => 'Sumatera Barat',
                'district' => $districts->get(Str::slug('Tanjung Emas')),
                'category' => $categories->get(Str::slug('Tafsir Al Qur`an Bahasa Arab')),
                'reg' => 'QSB-004',
                'gender' => 'putra',
                'nik' => '1304040404040004',
                'birth_place' => 'Batusangkar',
                'birth_date' => '2004-04-04',
                'phone' => '081200000004',
                'verification_status' => 'verified',
                'verification_notes' => 'Data demo telah diverifikasi.',
            ],
            [
                'name' => 'Siti Maryam',
                'institution' => 'MTQ Kota Pariaman',
                'region' => 'Sumatera Barat',
                'district' => $districts->get(Str::slug('Lima Kaum')),
                'category' => $categories->get(Str::slug('Seni Baca Al Qur`an Tilawah Anak Anak')),
                'reg' => 'TLA-005',
                'gender' => 'putri',
                'nik' => '1304050505120005',
                'birth_place' => 'Pariaman',
                'birth_date' => '2012-05-05',
                'phone' => '081200000005',
                'verification_status' => 'verified',
                'verification_notes' => 'Data demo telah diverifikasi untuk testing leaderboard.',
            ],
            [
                'name' => 'Rizky Maulana',
                'institution' => 'MTQ Kabupaten Solok',
                'region' => 'Sumatera Barat',
                'district' => $districts->get(Str::slug('Rambatan')),
                'category' => $categories->get(Str::slug('Fahmil Qur`an Golongan Putra')),
                'reg' => 'FQP-006',
                'gender' => 'putra',
                'nik' => '1304060606060006',
                'birth_place' => 'Solok',
                'birth_date' => '2006-06-06',
                'phone' => '081200000006',
                'verification_status' => 'verified',
                'verification_notes' => 'Data demo telah diverifikasi.',
            ],
            [
                'name' => 'Maya Salsabila',
                'institution' => 'MTQ Kabupaten Lima Puluh Kota',
                'region' => 'Sumatera Barat',
                'district' => $districts->get(Str::slug('Salimpaung')),
                'category' => $categories->get(Str::slug('Tartil Al Qur`an Gol. Menengah')),
                'reg' => 'TRM-007',
                'gender' => 'putri',
                'nik' => '1304070707070007',
                'birth_place' => 'Payakumbuh',
                'birth_date' => '2007-07-07',
                'phone' => '081200000007',
                'verification_status' => 'verified',
                'verification_notes' => 'Data demo telah diverifikasi untuk testing leaderboard.',
            ],
            [
                'name' => 'Ahmad Ghifari',
                'institution' => 'MTQ Kabupaten Pesisir Selatan',
                'region' => 'Sumatera Barat',
                'district' => $districts->get(Str::slug('Sungai Tarab')),
                'category' => $categories->get(Str::slug('Seni Kaligrafi Al Qur`an Golongan Naskah')),
                'reg' => 'KAL-008',
                'gender' => 'putra',
                'nik' => '1304080808080008',
                'birth_place' => 'Painan',
                'birth_date' => '2008-08-08',
                'phone' => '081200000008',
                'verification_status' => 'verified',
                'verification_notes' => 'Data demo telah diverifikasi untuk testing leaderboard.',
            ],
            [
                'name' => 'Aisyah Putri',
                'institution' => 'MTQ Kabupaten Agam',
                'region' => 'Sumatera Barat',
                'district' => $districts->get(Str::slug('Sungayang')),
                'category' => $categories->get(Str::slug('Karya Tulis Ilmiah Al Qur`an (KTIQ) KTIQ')),
                'reg' => 'KTI-009',
                'gender' => 'putri',
                'nik' => '1304090909090009',
                'birth_place' => 'Agam',
                'birth_date' => '2009-09-09',
                'phone' => '081200000009',
                'verification_status' => 'verified',
                'verification_notes' => 'Data demo telah diverifikasi untuk testing leaderboard.',
            ],
        ];

        $mfqPutraCategory = $categories->get(Str::slug('Fahmil Qur`an Golongan Putra'));
        $mfqPutriCategory = $categories->get(Str::slug('Fahmil Qur`an Golongan Putri'));
        $mfqDistricts = $districts->values();

        $mfqTeams = collect([
            ['name' => 'Regu Harmoni Putra', 'institution' => 'Kafilah Kecamatan X Koto', 'category' => $mfqPutraCategory, 'district' => $mfqDistricts->get(0), 'reg' => 'MFQ-P-001', 'gender' => 'putra', 'nik' => '1305000000010001', 'birth_place' => 'X Koto', 'birth_date' => '2006-01-01', 'phone' => '081300000001', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Cendekia Putra', 'institution' => 'Kafilah Kecamatan Batipuh', 'category' => $mfqPutraCategory, 'district' => $mfqDistricts->get(1), 'reg' => 'MFQ-P-002', 'gender' => 'putra', 'nik' => '1305000000020002', 'birth_place' => 'Batipuh', 'birth_date' => '2006-02-02', 'phone' => '081300000002', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Bintang Putra', 'institution' => 'Kafilah Kecamatan Pariangan', 'category' => $mfqPutraCategory, 'district' => $mfqDistricts->get(2), 'reg' => 'MFQ-P-003', 'gender' => 'putra', 'nik' => '1305000000030003', 'birth_place' => 'Pariangan', 'birth_date' => '2006-03-03', 'phone' => '081300000003', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Mutiara Putra', 'institution' => 'Kafilah Kecamatan Lima Kaum', 'category' => $mfqPutraCategory, 'district' => $mfqDistricts->get(3), 'reg' => 'MFQ-P-004', 'gender' => 'putra', 'nik' => '1305000000040004', 'birth_place' => 'Lima Kaum', 'birth_date' => '2006-04-04', 'phone' => '081300000004', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Fikrah Putra', 'institution' => 'Kafilah Kecamatan Rambatan', 'category' => $mfqPutraCategory, 'district' => $mfqDistricts->get(4), 'reg' => 'MFQ-P-005', 'gender' => 'putra', 'nik' => '1305000000050005', 'birth_place' => 'Rambatan', 'birth_date' => '2006-05-05', 'phone' => '081300000005', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Hikmah Putra', 'institution' => 'Kafilah Kecamatan Sungai Tarab', 'category' => $mfqPutraCategory, 'district' => $mfqDistricts->get(5), 'reg' => 'MFQ-P-006', 'gender' => 'putra', 'nik' => '1305000000060006', 'birth_place' => 'Sungai Tarab', 'birth_date' => '2006-06-06', 'phone' => '081300000006', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Nur Putra', 'institution' => 'Kafilah Kecamatan Tanjung Emas', 'category' => $mfqPutraCategory, 'district' => $mfqDistricts->get(6), 'reg' => 'MFQ-P-007', 'gender' => 'putra', 'nik' => '1305000000070007', 'birth_place' => 'Tanjung Emas', 'birth_date' => '2006-07-07', 'phone' => '081300000007', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Cakrawala Putri', 'institution' => 'Kafilah Kecamatan Salimpaung', 'category' => $mfqPutriCategory, 'district' => $mfqDistricts->get(7), 'reg' => 'MFQ-T-001', 'gender' => 'putri', 'nik' => '1305000000080008', 'birth_place' => 'Salimpaung', 'birth_date' => '2006-08-08', 'phone' => '081300000008', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Inspirasi Putri', 'institution' => 'Kafilah Kecamatan Batipuh Selatan', 'category' => $mfqPutriCategory, 'district' => $mfqDistricts->get(8), 'reg' => 'MFQ-T-002', 'gender' => 'putri', 'nik' => '1305000000090009', 'birth_place' => 'Batipuh Selatan', 'birth_date' => '2006-09-09', 'phone' => '081300000009', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Lentera Putri', 'institution' => 'Kafilah Kecamatan Sungayang', 'category' => $mfqPutriCategory, 'district' => $mfqDistricts->get(9), 'reg' => 'MFQ-T-003', 'gender' => 'putri', 'nik' => '1305000000100010', 'birth_place' => 'Sungayang', 'birth_date' => '2006-10-10', 'phone' => '081300000010', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Taqwa Putri', 'institution' => 'Kafilah Kecamatan Tanjung Baru', 'category' => $mfqPutriCategory, 'district' => $mfqDistricts->get(10), 'reg' => 'MFQ-T-004', 'gender' => 'putri', 'nik' => '1305000000110011', 'birth_place' => 'Tanjung Baru', 'birth_date' => '2006-11-11', 'phone' => '081300000011', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Cakra Putri', 'institution' => 'Kafilah Kecamatan Padang Ganting', 'category' => $mfqPutriCategory, 'district' => $mfqDistricts->get(11), 'reg' => 'MFQ-T-005', 'gender' => 'putri', 'nik' => '1305000000120012', 'birth_place' => 'Padang Ganting', 'birth_date' => '2006-12-12', 'phone' => '081300000012', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Cahaya Putri', 'institution' => 'Kafilah Kecamatan Lintau Buo', 'category' => $mfqPutriCategory, 'district' => $mfqDistricts->get(12), 'reg' => 'MFQ-T-006', 'gender' => 'putri', 'nik' => '1305000000130013', 'birth_place' => 'Lintau Buo', 'birth_date' => '2007-01-13', 'phone' => '081300000013', 'region' => 'Sumatera Barat'],
            ['name' => 'Regu Ilmu Putri', 'institution' => 'Kafilah Kecamatan Lintau Buo Utara', 'category' => $mfqPutriCategory, 'district' => $mfqDistricts->get(13), 'reg' => 'MFQ-T-007', 'gender' => 'putri', 'nik' => '1305000000140014', 'birth_place' => 'Lintau Buo Utara', 'birth_date' => '2007-02-14', 'phone' => '081300000014', 'region' => 'Sumatera Barat'],
        ]);

        $participants = array_merge($participants, $mfqTeams->all());

        $participantFirstNames = [
            'Miftah', 'Nadia', 'Rafli', 'Salsa', 'Aldo', 'Nabila', 'Faris', 'Zahra',
            'Ridwan', 'Aisyah', 'Fikri', 'Salwa', 'Hilmi', 'Khadijah', 'Ilham', 'Aulia',
        ];

        $participantLastNames = [
            'Rahman', 'Putri', 'Fadillah', 'Ramadhan', 'Azzahra', 'Mahendra', 'Salsabila', 'Hafizh',
            'Nugraha', 'Lestari', 'Pratama', 'Safitri', 'Maulana', 'Zulkarnain', 'Aminah', 'Wulandari',
        ];

        $generatedParticipants = $categories->values()->flatMap(function (CompetitionCategory $category, int $categoryIndex) use (
            $districts,
            $participantFirstNames,
            $participantLastNames
        ) {
            $districtValues = $districts->values();
            $districtCount = max($districtValues->count(), 1);
            $primaryDistrict = $districtValues->get($categoryIndex % $districtCount);
            $secondaryDistrict = $districtValues->get(($categoryIndex + 5) % $districtCount) ?? $primaryDistrict;

            $makeParticipant = function (
                int $seedIndex,
                string $suffix,
                CompetitionCategory $category,
                ?District $district,
                string $firstName,
                string $lastName,
                string $gender
            ): array {
                $month = ($seedIndex % 12) + 1;
                $day = (($seedIndex * 3) % 27) + 1;

                return [
                    'name' => $firstName.' '.$lastName,
                    'institution' => 'Kafilah '.($district?->name ?? 'Tanah Datar'),
                    'region' => 'Sumatera Barat',
                    'district' => $district,
                    'category' => $category,
                    'reg' => sprintf('TST-%03d-%s', $seedIndex + 1, $suffix),
                    'gender' => $gender,
                    'nik' => '1304'.str_pad((string) (500000000 + $seedIndex + 1), 12, '0', STR_PAD_LEFT),
                    'birth_place' => $district?->name ?? 'Tanah Datar',
                    'birth_date' => Carbon::create(2002 + ($seedIndex % 10), $month, $day)->format('Y-m-d'),
                    'phone' => '0813'.str_pad((string) (700000 + $seedIndex + 1), 7, '0', STR_PAD_LEFT),
                    'verification_status' => 'verified',
                    'verification_notes' => 'Data demo telah diverifikasi untuk testing leaderboard.',
                ];
            };

            return [
                $makeParticipant(
                    $categoryIndex * 2,
                    'A',
                    $category,
                    $primaryDistrict,
                    $participantFirstNames[$categoryIndex % count($participantFirstNames)],
                    $participantLastNames[$categoryIndex % count($participantLastNames)],
                    $categoryIndex % 2 === 0 ? 'putra' : 'putri',
                ),
                $makeParticipant(
                    $categoryIndex * 2 + 1,
                    'B',
                    $category,
                    $secondaryDistrict,
                    $participantFirstNames[($categoryIndex + 5) % count($participantFirstNames)],
                    $participantLastNames[($categoryIndex + 7) % count($participantLastNames)],
                    $categoryIndex % 2 === 0 ? 'putri' : 'putra',
                ),
            ];
        })->values()->all();

        $participants = array_merge($participants, $generatedParticipants);

        collect($participants)->each(function (array $item): void {
            if (! $item['category'] instanceof CompetitionCategory) {
                return;
            }

            $participant = Participant::query()->updateOrCreate(
                ['registration_number' => $item['reg']],
                [
                    'competition_category_id' => $item['category']->id,
                    'district_id' => $item['district']?->id,
                    'participant_role' => $item['participant_role'] ?? 'main',
                    'name' => $item['name'],
                    'gender' => $item['gender'],
                    'nik' => $item['nik'],
                    'place_of_birth' => $item['birth_place'],
                    'date_of_birth' => $item['birth_date'],
                    'phone' => $item['phone'],
                    'institution' => $item['institution'],
                    'current_address' => 'Alamat domisili '.$item['name'].' di '.($item['district']?->name ?? 'Tanah Datar'),
                    'ktp_address' => 'Alamat KTP '.$item['name'],
                    'ktp_district' => $item['district']?->name ?? 'Tanah Datar',
                    'ktp_regency' => 'Tanah Datar',
                    'region' => $item['region'],
                    'status' => 'active',
                    'verification_status' => $item['verification_status'] ?? 'verified',
                    'verification_notes' => $item['verification_notes'] ?? 'Data demo telah diverifikasi.',
                ],
            );

        });

        Participant::query()->update([
            'verification_status' => 'verified',
            'verification_notes' => 'Data demo telah diverifikasi untuk testing leaderboard.',
        ]);

        ParticipantMaqraDraw::query()->delete();

        $maqraParticipants = Participant::query()
            ->with('category')
            ->whereIn('competition_category_id', $maqraCategories->pluck('id'))
            ->where('verification_status', 'verified')
            ->orderBy('competition_category_id')
            ->orderBy('name')
            ->get()
            ->groupBy('competition_category_id');

        foreach ($maqraParticipants as $categoryId => $participantGroup) {
            $participantGroup = $participantGroup->values();
            $roundAssignments = [
                'Penyisihan' => $participantGroup->get(0),
                'Final' => $participantGroup->get(1),
            ];

            foreach ($roundAssignments as $roundLabel => $participant) {
                if (! $participant instanceof Participant) {
                    continue;
                }

                $packages = collect($maqraPackagesByCategoryRound[$categoryId][$roundLabel] ?? []);
                if ($packages->isEmpty()) {
                    continue;
                }
                $package = $packages->random();

                ParticipantMaqraDraw::query()->updateOrCreate(
                    [
                        'participant_id' => $participant->id,
                        'round_label' => $roundLabel,
                    ],
                    [
                        'maqra_package_id' => $package->id,
                        'drawn_at' => Carbon::now()->subMinutes($participant->id % 120),
                    ]
                );
            }
        }

        ScoreEntry::query()->delete();

        Participant::query()
            ->with('category')
            ->orderBy('competition_category_id')
            ->orderBy('name')
            ->get()
            ->each(function (Participant $participant, int $index): void {
                $this->seedLeaderboardScores($participant, $index);
            });

        collect([
            [
                'title' => 'Pendaftaran Ulang Peserta',
                'stage' => 'Sekretariat MTQ',
                'venue' => 'Kecamatan Pariangan',
                'starts_at' => Carbon::create(2026, 6, 23, 14, 0, 0),
                'ends_at' => Carbon::create(2026, 6, 23, 22, 0, 0),
                'status' => 'scheduled',
                'notes' => 'Pendaftaran ulang peserta sesuai rangkaian juknis MTQ 2026.',
            ],
            [
                'title' => 'Pawai dan Pembukaan MTQ',
                'stage' => 'Arena Utama',
                'venue' => 'Kecamatan Pariangan',
                'starts_at' => Carbon::create(2026, 6, 24, 8, 0, 0),
                'ends_at' => Carbon::create(2026, 6, 24, 13, 0, 0),
                'status' => 'scheduled',
                'notes' => 'Pembukaan resmi dan awal pelaksanaan MTQ tingkat kabupaten.',
            ],
            [
                'title' => 'Babak Penyisihan Seluruh Cabang',
                'stage' => 'Venue Cabang',
                'venue' => 'Kecamatan Pariangan',
                'starts_at' => Carbon::create(2026, 6, 25, 8, 0, 0),
                'ends_at' => Carbon::create(2026, 6, 26, 22, 0, 0),
                'status' => 'scheduled',
                'notes' => 'Babak penyisihan untuk cabang yang dipertandingkan sesuai juknis.',
            ],
            [
                'title' => 'Final, Rapat Dewan Hakim, dan Penutupan',
                'stage' => 'Arena Utama',
                'venue' => 'Kecamatan Pariangan',
                'starts_at' => Carbon::create(2026, 6, 27, 8, 0, 0),
                'ends_at' => Carbon::create(2026, 6, 27, 18, 0, 0),
                'status' => 'scheduled',
                'notes' => 'Rangkaian final cabang tertentu sampai penutupan resmi MTQ.',
            ],
        ])->each(function (array $schedule): void {
            SessionSchedule::query()->updateOrCreate(
                ['title' => $schedule['title']],
                $schedule,
            );
        });

        collect([
            [
                'title' => 'Pendaftaran e-MTQ dibuka 20 - 31 Mei 2026',
                'body' => 'Administrator kecamatan wajib melakukan pendaftaran online melalui e-MTQ dan melengkapi seluruh isian serta berkas scan administrasi.',
                'priority' => 'high',
                'audience' => 'all',
                'published_by' => $admin->id,
                'published_at' => Carbon::now()->subMinutes(15),
            ],
            [
                'title' => 'Verifikasi dan masa sanggah peserta',
                'body' => 'Verifikasi tahap I berlangsung 1 - 7 Juni 2026, hasil diumumkan 8 Juni 2026, dan masa sanggah/penggantian peserta dibuka 9 - 11 Juni 2026.',
                'priority' => 'normal',
                'audience' => 'all',
                'published_by' => $admin->id,
                'published_at' => Carbon::now()->subHours(1),
            ],
            [
                'title' => 'Official dapat mengajukan protes resmi',
                'body' => 'Sesuai juknis, official dapat mengajukan protes kepada pengawas dewan hakim paling cepat 5 jam setelah penampilan dengan bukti otentik.',
                'priority' => 'normal',
                'audience' => 'official',
                'published_by' => $admin->id,
                'published_at' => Carbon::now()->subHours(2),
            ],
        ])->each(function (array $announcement): void {
            Announcement::query()->updateOrCreate(
                ['title' => $announcement['title']],
                $announcement,
            );
        });
    }

    protected function seedLeaderboardScores(Participant $participant, int $seedIndex): void
    {
        $branch = (string) ($participant->category?->branch ?? '');
        $criteria = config('scoring.criteria.'.$branch, config('scoring.criteria.default', []));
        $priorityKeys = array_keys($criteria);

        if ($priorityKeys === []) {
            $priorityKeys = array_keys(config('scoring.criteria.default', []));
        }

        foreach (['Penyisihan' => 0, 'Final' => 1] as $roundLabel => $roundOffset) {
            foreach ([1, 2] as $judgeIndex) {
                $scoreValue = $this->buildSeedScoreValue($seedIndex, $roundOffset, $judgeIndex);

                ScoreEntry::query()->create([
                    'participant_id' => $participant->id,
                    'judge_name' => sprintf('Hakim %d', $judgeIndex),
                    'judging_round' => $roundLabel,
                    'score' => $scoreValue,
                    'score_breakdown' => $this->buildSeedScoreBreakdown($priorityKeys, $seedIndex, $roundOffset, $judgeIndex, $scoreValue),
                    'remarks' => sprintf('Nilai %s untuk testing leaderboard.', strtolower($roundLabel)),
                    'submitted_at' => Carbon::now()->subMinutes(($seedIndex * 6) + ($roundOffset * 3) + $judgeIndex),
                ]);
            }
        }
    }

    protected function buildSeedScoreValue(int $seedIndex, int $roundOffset, int $judgeIndex): float
    {
        $baseScore = 80 + (($seedIndex * 7) % 15);
        $roundBonus = $roundOffset * 2.2;
        $judgeBonus = ($judgeIndex - 1) * 0.6;

        return round(min(100, $baseScore + $roundBonus + $judgeBonus), 2);
    }

    protected function buildSeedScoreBreakdown(array $priorityKeys, int $seedIndex, int $roundOffset, int $judgeIndex, float $scoreValue): array
    {
        $baseValue = max(0, $scoreValue - 1.5);

        return collect($priorityKeys)
            ->mapWithKeys(function (string $key, int $position) use ($baseValue, $seedIndex, $roundOffset, $judgeIndex): array {
                $variation = (($seedIndex + $roundOffset + $judgeIndex + $position) % 4) * 0.35;

                return [
                    $key => round(min(100, $baseValue + ($position * 0.2) + $variation), 2),
                ];
            })
            ->all();
    }
}
