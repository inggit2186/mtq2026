<?php

namespace App\Console\Commands;

use App\Models\Participant;
use App\Models\ParticipantMaqraDraw;
use App\Models\ParticipantVerificationLog;
use App\Models\ScoreEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DedupeParticipantNiks extends Command
{
    protected $signature = 'participants:dedupe-nik {--apply : Jalankan perbaikan dan simpan perubahan} {--dry-run : Hanya tampilkan preview tanpa menyimpan}';

    protected $description = 'Gabungkan peserta duplikat berdasarkan NIK, pindahkan relasi, dan soft-delete data duplikat.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply') && ! (bool) $this->option('dry-run');

        $participants = Participant::query()
            ->withCount(['scores', 'maqraDraws', 'verificationLogs'])
            ->whereNotNull('nik')
            ->where('nik', '!=', '')
            ->orderBy('nik')
            ->orderBy('id')
            ->get();

        $duplicateGroups = $participants
            ->groupBy(fn (Participant $participant): string => $this->normalizeNik((string) $participant->nik))
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->values();

        if ($duplicateGroups->isEmpty()) {
            $this->info('Tidak ada NIK duplikat pada data peserta aktif.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d grup NIK duplikat ditemukan.%s',
            $duplicateGroups->count(),
            $apply ? ' Perbaikan sedang dijalankan.' : ' Jalankan kembali dengan --apply jika hasil preview sudah sesuai.'
        ));

        $mergedCount = 0;
        $deletedCount = 0;

        foreach ($duplicateGroups as $group) {
            $canonical = $this->pickCanonical($group);
            $duplicates = $group->reject(fn (Participant $participant): bool => $participant->id === $canonical->id)->values();
            $nik = $this->normalizeNik((string) $canonical->nik);

            $this->line(sprintf(
                'NIK %s -> simpan #%d %s, duplikat: %s',
                $nik,
                $canonical->id,
                $canonical->name,
                $duplicates->map(fn (Participant $participant): string => '#'.$participant->id.' '.$participant->name)->implode(', ')
            ));

            if (! $apply) {
                continue;
            }

            DB::transaction(function () use ($canonical, $duplicates, &$mergedCount, &$deletedCount): void {
                foreach ($duplicates as $duplicate) {
                    $changed = $this->mergeMissingAttributes($canonical, $duplicate);

                    $scoreEntriesMoved = ScoreEntry::query()
                        ->where('participant_id', $duplicate->id)
                        ->update(['participant_id' => $canonical->id]);

                    $maqraDrawsMoved = ParticipantMaqraDraw::query()
                        ->where('participant_id', $duplicate->id)
                        ->update(['participant_id' => $canonical->id]);

                    $verificationLogsMoved = ParticipantVerificationLog::query()
                        ->where('participant_id', $duplicate->id)
                        ->update(['participant_id' => $canonical->id]);

                    if ($changed !== []) {
                        $canonical->fill($changed)->save();
                    }

                    $duplicate->delete();

                    $mergedCount++;
                    $deletedCount++;

                    $this->line(sprintf(
                        '  - #%d digabung ke #%d | skor:%d, undian:%d, log:%d, field:%d',
                        $duplicate->id,
                        $canonical->id,
                        $scoreEntriesMoved,
                        $maqraDrawsMoved,
                        $verificationLogsMoved,
                        count($changed)
                    ));
                }
            });
        }

        if ($apply) {
            $this->newLine();
            $this->info(sprintf(
                'Selesai. %d data duplikat digabung dan %d data duplikat di-soft delete.',
                $mergedCount,
                $deletedCount
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Participant>  $group
     */
    private function pickCanonical(Collection $group): Participant
    {
        return $group
            ->sort(function (Participant $left, Participant $right): int {
                $scoreDiff = $this->participantScore($right) <=> $this->participantScore($left);

                return $scoreDiff !== 0 ? $scoreDiff : ($left->id <=> $right->id);
            })
            ->firstOrFail();
    }

    private function participantScore(Participant $participant): int
    {
        $score = match ((string) $participant->verification_status) {
            'verified' => 300,
            'submitted' => 200,
            'draft' => 100,
            default => 0,
        };

        $filledFields = [
            $participant->district_id,
            $participant->competition_category_id,
            $participant->participant_role,
            $participant->name,
            $participant->gender,
            $participant->nik,
            $participant->ktp_date,
            $participant->place_of_birth,
            $participant->date_of_birth,
            $participant->kk_number,
            $participant->kk_date,
            $participant->phone,
            $participant->institution,
            $participant->last_education,
            $participant->bank_name,
            $participant->bank_account_number,
            $participant->bank_account_name,
            $participant->current_address,
            $participant->ktp_address,
            $participant->ktp_district,
            $participant->ktp_regency,
            $participant->region,
            $participant->avatar,
            $participant->document_kk,
            $participant->document_ktp,
            $participant->document_birth_certificate,
            $participant->document_photo,
            $participant->document_last_diploma,
            $participant->document_bank_book,
        ];

        $score += collect($filledFields)->filter(fn ($value): bool => filled($value))->count() * 2;
        $score += (int) ($participant->scores_count ?? 0) * 15;
        $score += (int) ($participant->maqra_draws_count ?? 0) * 10;
        $score += (int) ($participant->verification_logs_count ?? 0) * 5;
        $score += $participant->updated_at ? 1 : 0;

        return $score;
    }

    /**
     * @return array<string, mixed>
     */
    private function mergeMissingAttributes(Participant $canonical, Participant $duplicate): array
    {
        $changes = [];

        $copyIfEmpty = [
            'district_id',
            'competition_category_id',
            'participant_role',
            'name',
            'gender',
            'nik',
            'ktp_date',
            'place_of_birth',
            'date_of_birth',
            'kk_number',
            'kk_date',
            'phone',
            'institution',
            'last_education',
            'bank_name',
            'bank_account_number',
            'bank_account_name',
            'current_address',
            'ktp_address',
            'ktp_district',
            'ktp_regency',
            'region',
            'avatar',
            'document_kk',
            'document_ktp',
            'document_birth_certificate',
            'document_photo',
            'document_last_diploma',
            'document_bank_book',
        ];

        foreach ($copyIfEmpty as $field) {
            if (blank($canonical->{$field}) && filled($duplicate->{$field})) {
                $changes[$field] = $duplicate->{$field};
            }
        }

        foreach (['document_certificates', 'document_other_files', 'document_revision_notes'] as $field) {
            $merged = $this->mergeArrayField($canonical->{$field}, $duplicate->{$field});

            if ($merged !== null) {
                $changes[$field] = $merged;
            }
        }

        if (blank($canonical->verification_notes) && filled($duplicate->verification_notes)) {
            $changes['verification_notes'] = $duplicate->verification_notes;
        }

        return $changes;
    }

    /**
     * @param  mixed  $current
     * @param  mixed  $incoming
     * @return array<int, mixed>|null
     */
    private function mergeArrayField(mixed $current, mixed $incoming): ?array
    {
        $currentItems = is_array($current) ? $current : [];
        $incomingItems = is_array($incoming) ? $incoming : [];

        $merged = array_values(array_unique(array_filter(array_merge($currentItems, $incomingItems), fn ($item): bool => filled($item))));

        if ($merged === $currentItems) {
            return null;
        }

        return $merged;
    }

    private function normalizeNik(string $nik): string
    {
        return preg_replace('/\s+/', '', trim($nik)) ?: '';
    }
}
