<?php

namespace App\Console\Commands;

use App\Models\CompetitionCategory;
use App\Models\CompetitionLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SyncCompetitionLocations extends Command
{
    protected $signature = 'locations:sync-mtq {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Sinkronkan data lokasi MTQ ke tabel competition_locations dan relasi golongan.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $categoryBySlug = CompetitionCategory::query()->get()->keyBy('slug');
        $locationRows = $this->loadLocationRows();

        if ($locationRows->isEmpty()) {
            $this->warn('Sumber data lokasi MTQ tidak ditemukan atau kosong.');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $attached = 0;

        foreach ($locationRows as $row) {
            $no = (int) data_get($row, 'no', 0);

            if ($no <= 0) {
                continue;
            }

            $existingLocation = CompetitionLocation::query()
                ->where('sort_order', $no)
                ->first();

            $photoPath = $existingLocation?->photo_path;
            $thumbPath = $existingLocation?->photo_thumb_path;

            if (! filled($photoPath) && file_exists(public_path(sprintf('images/lokasi-mtq-webp/%02d.webp', $no)))) {
                $photoPath = sprintf('images/lokasi-mtq-webp/%02d.webp', $no);
            }

            if (! filled($thumbPath) && file_exists(public_path(sprintf('images/lokasi-mtq-thumb/%02d.webp', $no)))) {
                $thumbPath = sprintf('images/lokasi-mtq-thumb/%02d.webp', $no);
            }

            $locationPayload = [
                'label' => trim((string) data_get($row, 'cabang', 'Lokasi '.$no)),
                'venue_name' => trim((string) data_get($row, 'venue', '')),
                'map_url' => trim((string) data_get($row, 'map_url', '')),
                'photo_path' => $photoPath,
                'photo_thumb_path' => $thumbPath,
                'sort_order' => $no,
            ];

            $categoryIds = collect($this->categorySlugsForLocation($no))
                ->map(fn (string $slug): ?int => $categoryBySlug->get($slug)?->id)
                ->filter()
                ->values()
                ->all();

            $this->line(sprintf(
                '%s #%02d %s -> %s golongan',
                $dryRun ? 'PREVIEW' : ($existingLocation ? 'UPDATE ' : 'SYNC   '),
                $no,
                $locationPayload['label'],
                count($categoryIds),
            ));

            if ($dryRun) {
                continue;
            }

            if ($existingLocation) {
                $existingLocation->fill($locationPayload)->save();
                $createdLocation = $existingLocation;
                $updated++;
            } else {
                $createdLocation = CompetitionLocation::query()->create($locationPayload);
                $created++;
            }

            $createdLocation->categories()->sync($categoryIds);
            $attached += count($categoryIds);
        }

        $this->newLine();
        $this->info(sprintf(
            'Sinkronisasi lokasi selesai. %d dibuat, %d diperbarui, %d relasi golongan dipasang.%s',
            $created,
            $updated,
            $attached,
            $dryRun ? ' (dry-run)' : '',
        ));

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function loadLocationRows(): Collection
    {
        $json = @file_get_contents(resource_path('data/lokasi-mtq.json'));
        $rows = json_decode((string) $json, true);

        if (! is_array($rows)) {
            return collect();
        }

        return collect($rows)
            ->filter(fn ($item): bool => is_array($item) && filled($item['no'] ?? null))
            ->values();
    }

    /**
     * @return list<string>
     */
    private function categorySlugsForLocation(int $no): array
    {
        return match ($no) {
            1 => [
                Str::slug('Seni Baca Al Qur`an Tilawah Kanak-kanak'),
                Str::slug('Seni Baca Al Qur`an Tilawah Anak Anak'),
            ],
            2 => [
                Str::slug('Seni Baca Al Qur`an Tilawah Remaja'),
                Str::slug('Seni Baca Al Qur`an Tilawah Dewasa'),
            ],
            3 => [
                Str::slug('Tartil Al Qur`an Gol. Dasar'),
                Str::slug('Tartil Al Qur`an Gol. Menengah'),
                Str::slug('Tartil Al Qur`an Gol. Umum'),
            ],
            4 => [
                Str::slug('Hafalan Al Qur`an Gol. 1 Juz Tilawah'),
                Str::slug('Hafalan Al Qur`an Gol. 5 Juz Tilawah'),
            ],
            5 => [
                Str::slug('Hafalan Al Qur`an Gol. 1 Juz Non Tilawah'),
                Str::slug('Hafalan Al Qur`an Gol. 5 Juz Non Tilawah'),
                Str::slug('Hafalan Al Qur`an Gol. 10 Juz'),
            ],
            6 => [
                Str::slug('Khutbah Jumat dan Adzan Khatib dan Muadzin'),
                Str::slug('Kitab Standar Kitab Standar'),
            ],
            7 => [
                Str::slug('Tafsir Al Qur`an Bahasa Indonesia'),
                Str::slug('Tafsir Al Qur`an Bahasa Arab'),
                Str::slug('Tafsir Al Qur`an Bahasa Inggris'),
            ],
            8 => [
                Str::slug('Syarhil Qur`an Golongan Putra'),
                Str::slug('Syarhil Qur`an Golongan Putri'),
            ],
            9 => [
                Str::slug('Fahmil Qur`an Golongan Putra'),
                Str::slug('Fahmil Qur`an Golongan Putri'),
            ],
            10 => [
                Str::slug('Seni Kaligrafi Al Qur`an Golongan Naskah'),
                Str::slug('Seni Kaligrafi Al Qur`an Golongan Hiasan Mushaf'),
            ],
            11 => [
                Str::slug('Seni Kaligrafi Al Qur`an Golongan Dekorasi'),
                Str::slug('Seni Kaligrafi Al Qur`an Golongan Kontemporer'),
            ],
            12 => [
                Str::slug('Karya Tulis Ilmiah Al Qur`an (KTIQ) KTIQ'),
            ],
            13 => [
                Str::slug('Hafalan Hadits Nabi Hafalan 50 Hadits dengan Sanad'),
                Str::slug('Hafalan Hadits Nabi Hafalan 250 Hadits dengan Sanad'),
            ],
            default => [],
        };
    }
}
