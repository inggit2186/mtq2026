<?php

namespace App\Console\Commands;

use App\Models\District;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncSilatarDistricts extends Command
{
    protected $signature = 'districts:sync-silatar {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Sinkronkan data kecamatan dari API SILATAR ke tabel districts.';

    private const API_URL = 'https://ptsp.kemenagtanahdatar.cloud/api/v1/getKUA';

    /**
     * @var array<string, list<string>>
     */
    private array $slugAliases = [
        'tanjung-baru' => ['tanjuang-baru'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $apiDistricts = $this->fetchApiDistricts();

        if ($apiDistricts->isEmpty()) {
            $this->warn('API SILATAR tidak mengembalikan data kecamatan.');

            return self::FAILURE;
        }

        $existingDistricts = District::query()->orderBy('id')->get();
        $matchedIds = [];
        $created = 0;
        $updated = 0;

        foreach ($apiDistricts as $payload) {
            $district = $this->findExistingDistrict($existingDistricts, $payload, $matchedIds);
            $changes = [
                'silatar_id' => $payload['id'],
                'name' => $payload['name'],
                'slug' => $payload['slug'],
            ];

            if ($district) {
                $dirty = (int) ($district->silatar_id ?? 0) !== $changes['silatar_id']
                    || $district->name !== $changes['name']
                    || $district->slug !== $changes['slug'];
                $matchedIds[] = $district->id;

                if ($dirty) {
                    $updated++;
                    $this->line(sprintf(
                        'UPDATE #%d: %s [%s | SILATAR:%s] -> %s [%s | SILATAR:%s]',
                        $district->id,
                        $district->name,
                        $district->slug,
                        $district->silatar_id ?: '-',
                        $changes['name'],
                        $changes['slug'],
                        $changes['silatar_id'],
                    ));

                    if (! $dryRun) {
                        $district->fill($changes)->save();
                    }
                } else {
                    $this->line(sprintf(
                        'SKIP   #%d: %s [%s | SILATAR:%s]',
                        $district->id,
                        $district->name,
                        $district->slug,
                        $district->silatar_id ?: $changes['silatar_id'],
                    ));
                }

                continue;
            }

            $created++;
            $this->line(sprintf('CREATE: %s [%s | SILATAR:%s]', $changes['name'], $changes['slug'], $changes['silatar_id']));

            if (! $dryRun) {
                $createdDistrict = District::query()->create($changes);
                $matchedIds[] = $createdDistrict->id;
                $existingDistricts->push($createdDistrict);
            }
        }

        $unmatched = District::query()
            ->when($matchedIds !== [], fn ($query) => $query->whereNotIn('id', array_unique($matchedIds)))
            ->when($matchedIds === [], fn ($query) => $query)
            ->orderBy('name')
            ->get();

        if ($unmatched->isNotEmpty()) {
            $this->newLine();
            $this->warn('Data lokal yang tidak ada di API SILATAR dibiarkan apa adanya untuk menjaga relasi yang sudah ada:');
            foreach ($unmatched as $district) {
                $this->line(sprintf('- #%d %s [%s]', $district->id, $district->name, $district->slug));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Sinkronisasi selesai. %d dibuat, %d diperbarui, %d total dari API.%s',
            $created,
            $updated,
            $apiDistricts->count(),
            $dryRun ? ' (dry-run)' : '',
        ));

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, array{id:int, name:string, slug:string}>
     */
    private function fetchApiDistricts(): Collection
    {
        $response = null;

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->retry(2, 500)
                ->get(self::API_URL);
        } catch (ConnectionException) {
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->timeout(20)
                ->retry(2, 500)
                ->get(self::API_URL);
        }

        if (! $response->successful()) {
            $this->error('Gagal mengambil data dari API SILATAR: HTTP '.$response->status());

            return collect();
        }

        $data = $response->json('data');

        if (! is_array($data)) {
            $this->error('Respons API SILATAR tidak memiliki format data yang dikenali.');

            return collect();
        }

        return collect($data)
            ->map(function ($item): ?array {
                $id = (int) data_get($item, 'id', 0);
                $rawName = trim((string) data_get($item, 'kec', data_get($item, 'nama', '')));
                $name = preg_replace('/^kecamatan\s+/i', '', $rawName) ?: $rawName;
                $name = trim($name);
                $slug = Str::slug((string) data_get($item, 'keckode', $name));

                if ($id <= 0 || $name === '' || $slug === '') {
                    return null;
                }

                return [
                    'id' => $id,
                    'name' => Str::title($name),
                    'slug' => $slug,
                ];
            })
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * @param  Collection<int, District>  $existingDistricts
     * @param  array{id:int, name:string, slug:string}  $payload
     * @param  list<int>  $matchedIds
     */
    private function findExistingDistrict(Collection $existingDistricts, array $payload, array $matchedIds): ?District
    {
        $availableDistricts = $existingDistricts->reject(fn (District $district): bool => in_array($district->id, $matchedIds, true));

        $district = $availableDistricts->first(fn (District $item): bool => (int) ($item->silatar_id ?? 0) === $payload['id']);
        if ($district) {
            return $district;
        }

        $district = $availableDistricts->first(fn (District $item): bool => $item->slug === $payload['slug']);
        if ($district) {
            return $district;
        }

        $aliasSlugs = $this->slugAliases[$payload['slug']] ?? [];
        if ($aliasSlugs !== []) {
            $district = $availableDistricts->first(fn (District $item): bool => in_array($item->slug, $aliasSlugs, true));
            if ($district) {
                return $district;
            }
        }

        return $availableDistricts->first(function (District $item) use ($payload): bool {
            return $this->normalizeDistrictName($item->name) === $this->normalizeDistrictName($payload['name']);
        });
    }

    private function normalizeDistrictName(string $name): string
    {
        $normalized = Str::lower(trim($name));
        $normalized = str_replace(['kecamatan ', 'kec. ', 'kec '], '', $normalized);
        $normalized = str_replace('tanjuang', 'tanjung', $normalized);

        return preg_replace('/\s+/', ' ', $normalized) ?: $normalized;
    }
}
