<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class OfficialAccessSetting extends Model
{
    protected $fillable = [
        'participant_registration_open',
        'participant_edit_open',
        'participant_delete_open',
        'mandate_upload_open',
        'participant_documents_open',
        'participant_verification_open',
        'participant_lot_open',
        'participant_maqra_open',
        'participant_maqra_penyisihan_open',
        'participant_maqra_final_open',
        'participant_maqra_lot_min',
        'participant_maqra_lot_max',
        'participant_maqra_lot_ranges',
        'participant_maqra_category_ids',
        'participant_maqra_category_schedules',
    ];

    protected function casts(): array
    {
        return [
            'participant_registration_open' => 'boolean',
            'participant_edit_open' => 'boolean',
            'participant_delete_open' => 'boolean',
            'mandate_upload_open' => 'boolean',
            'participant_documents_open' => 'boolean',
            'participant_verification_open' => 'boolean',
            'participant_lot_open' => 'boolean',
            'participant_maqra_open' => 'boolean',
            'participant_maqra_penyisihan_open' => 'boolean',
            'participant_maqra_final_open' => 'boolean',
            'participant_maqra_lot_min' => 'integer',
            'participant_maqra_lot_max' => 'integer',
            'participant_maqra_lot_ranges' => 'array',
            'participant_maqra_category_ids' => 'array',
            'participant_maqra_category_schedules' => 'array',
        ];
    }

    public static function defaults(): array
    {
        return [
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'participant_delete_open' => true,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
            'participant_verification_open' => true,
            'participant_lot_open' => true,
            'participant_maqra_open' => true,
            'participant_maqra_penyisihan_open' => true,
            'participant_maqra_final_open' => true,
            'participant_maqra_lot_min' => null,
            'participant_maqra_lot_max' => null,
            'participant_maqra_lot_ranges' => [],
            'participant_maqra_category_ids' => [],
            'participant_maqra_open_at' => null,
            'participant_maqra_close_at' => null,
        ];
    }

    public static function current(): ?self
    {
        if (! Schema::hasTable('official_access_settings')) {
            return null;
        }

        return static::query()->latest('id')->first();
    }

    public static function currentOrDefault(): self
    {
        return static::current() ?? new self(static::defaults());
    }

    public function isEnabled(string $feature): bool
    {
        $rawValue = (bool) ($this->getRawOriginal($feature) ?? (static::defaults()[$feature] ?? true));
        $role = (string) auth()->user()?->role;

        if ($role === 'admin') {
            return true;
        }

        return $rawValue;
    }

    public function getParticipantRegistrationOpenAttribute(): bool
    {
        return $this->isEnabled('participant_registration_open');
    }

    public function getParticipantEditOpenAttribute(): bool
    {
        return $this->isEnabled('participant_edit_open');
    }

    public function getParticipantDeleteOpenAttribute(): bool
    {
        return $this->isEnabled('participant_delete_open');
    }

    public function getMandateUploadOpenAttribute(): bool
    {
        return $this->isEnabled('mandate_upload_open');
    }

    public function getParticipantDocumentsOpenAttribute(): bool
    {
        return $this->isEnabled('participant_documents_open');
    }

    public function getParticipantVerificationOpenAttribute(): bool
    {
        return $this->isEnabled('participant_verification_open');
    }

    public function getParticipantLotOpenAttribute(): bool
    {
        return $this->isEnabled('participant_lot_open');
    }

    public function getParticipantMaqraOpenAttribute(): bool
    {
        return $this->isEnabled('participant_maqra_open');
    }

    public function getParticipantMaqraPenyisihanOpenAttribute(): bool
    {
        return $this->isEnabled('participant_maqra_penyisihan_open');
    }

    public function getParticipantMaqraFinalOpenAttribute(): bool
    {
        return $this->isEnabled('participant_maqra_final_open');
    }

    public function maqraOpenCategoryIds(): array
    {
        $ids = $this->participant_maqra_category_ids ?? [];

        if (! is_array($ids)) {
            return [];
        }

        return collect($ids)
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function maqraOpenLotRange(): ?array
    {
        $min = $this->participant_maqra_lot_min;
        $max = $this->participant_maqra_lot_max;

        if (! filled($min) || ! filled($max)) {
            return null;
        }

        $min = (int) $min;
        $max = (int) $max;

        if ($min <= 0 || $max <= 0) {
            return null;
        }

        if ($max < $min) {
            [$min, $max] = [$max, $min];
        }

        return [$min, $max];
    }

    public function maqraOpenLotRanges(): array
    {
        $ranges = $this->participant_maqra_lot_ranges ?? [];

        if (! is_array($ranges)) {
            return [];
        }

        return collect($ranges)
            ->filter(fn ($range): bool => is_array($range))
            ->mapWithKeys(function (array $range, $categoryId): array {
                $min = filled($range['min'] ?? null) ? (int) $range['min'] : null;
                $max = filled($range['max'] ?? null) ? (int) $range['max'] : null;

                if (! filled($min) || ! filled($max) || $min <= 0 || $max <= 0) {
                    return [];
                }

                if ($max < $min) {
                    [$min, $max] = [$max, $min];
                }

                return [(int) $categoryId => ['min' => $min, 'max' => $max]];
            })
            ->all();
    }

    public function maqraOpenLotRangeForCategory(?int $categoryId): ?array
    {
        if ($categoryId) {
            $ranges = $this->maqraOpenLotRanges();

            if (isset($ranges[$categoryId])) {
                return $ranges[$categoryId];
            }
        }

        return $this->maqraOpenLotRange();
    }

    public function maqraRoundFeatureKey(string $round): string
    {
        return match ($round) {
            'Final' => 'participant_maqra_final_open',
            default => 'participant_maqra_penyisihan_open',
        };
    }

    public function maqraRoundEnabled(string $round): bool
    {
        return $this->isEnabled('participant_maqra_open')
            && $this->isEnabled($this->maqraRoundFeatureKey($round));
    }

    public function maqraAnyRoundEnabled(): bool
    {
        return $this->maqraRoundEnabled('Penyisihan') || $this->maqraRoundEnabled('Final');
    }

    public function getMaqraScheduleForCategory(?int $categoryId): ?array
    {
        $schedules = $this->getAttribute('participant_maqra_category_schedules') ?? [];
        if (! is_array($schedules) || ! $categoryId) {
            return null;
        }
        return $schedules[$categoryId] ?? null;
    }

    public function isMaqraCategoryEnabled(?int $categoryId): bool
    {
        $schedule = $this->getMaqraScheduleForCategory($categoryId);
        return (bool) ($schedule['enabled'] ?? false);
    }

    public function isMaqraCategoryScheduleActive(?int $categoryId): bool
    {
        $schedule = $this->getMaqraScheduleForCategory($categoryId);
        if (! $schedule) {
            return false;
        }

        $enabled = (bool) ($schedule['enabled'] ?? false);
        if (! $enabled) {
            return false;
        }

        $now = now();
        $openAt = $schedule['open_at'] ?? null;
        $closeAt = $schedule['close_at'] ?? null;

        // If no schedule set, return true (always active while enabled)
        if (! $openAt && ! $closeAt) {
            return true;
        }

        // Check open time if set
        if ($openAt) {
            $openTime = \Carbon\Carbon::parse($openAt);
            if ($now->lt($openTime)) {
                return false;
            }
        }

        // Check close time if set
        if ($closeAt) {
            $closeTime = \Carbon\Carbon::parse($closeAt);
            if ($now->gt($closeTime)) {
                return false;
            }
        }

        return true;
    }

    public function getMaqraLotRangeForCategory(?int $categoryId): ?array
    {
        $schedule = $this->getMaqraScheduleForCategory($categoryId);
        if (! $schedule) {
            return null;
        }

        $lotMin = (int) ($schedule['lot_min'] ?? 0);
        $lotMax = (int) ($schedule['lot_max'] ?? 0);

        if ($lotMin <= 0 || $lotMax <= 0) {
            return null;
        }

        if ($lotMax < $lotMin) {
            [$lotMin, $lotMax] = [$lotMax, $lotMin];
        }

        return ['min' => $lotMin, 'max' => $lotMax];
    }

    public function getMaqraScheduleStatusForCategory(?int $categoryId): array
    {
        $schedule = $this->getMaqraScheduleForCategory($categoryId);

        if (! $schedule) {
            return [
                'enabled' => false,
                'status' => 'not_configured',
                'label' => 'Belum Diatur',
                'color' => 'slate',
            ];
        }

        $enabled = (bool) ($schedule['enabled'] ?? false);

        if (! $enabled) {
            return [
                'enabled' => false,
                'status' => 'disabled',
                'label' => 'Ditutup',
                'color' => 'slate',
            ];
        }

        $now = now();
        $openAt = $schedule['open_at'] ?? null;
        $closeAt = $schedule['close_at'] ?? null;

        if (! $openAt && ! $closeAt) {
            return [
                'enabled' => true,
                'status' => 'always_open',
                'label' => 'Selalu Buka',
                'color' => 'emerald',
                'open_at' => null,
                'close_at' => null,
            ];
        }

        if ($openAt && $closeAt) {
            $openTime = \Carbon\Carbon::parse($openAt);
            $closeTime = \Carbon\Carbon::parse($closeAt);

            if ($now->lt($openTime)) {
                return [
                    'enabled' => true,
                    'status' => 'scheduled',
                    'label' => 'Terjadwal',
                    'color' => 'amber',
                    'open_at' => $openAt,
                    'close_at' => $closeAt,
                ];
            }
            if ($now->between($openTime, $closeTime)) {
                return [
                    'enabled' => true,
                    'status' => 'open',
                    'label' => 'Sedang Buka',
                    'color' => 'emerald',
                    'open_at' => $openAt,
                    'close_at' => $closeAt,
                ];
            }
            return [
                'enabled' => true,
                'status' => 'closed',
                'label' => 'Sudah Tutup',
                'color' => 'slate',
                'open_at' => $openAt,
                'close_at' => $closeAt,
            ];
        }

        if ($openAt) {
            $openTime = \Carbon\Carbon::parse($openAt);
            if ($now->lt($openTime)) {
                return [
                    'enabled' => true,
                    'status' => 'scheduled',
                    'label' => 'Terjadwal',
                    'color' => 'amber',
                    'open_at' => $openAt,
                    'close_at' => null,
                ];
            }
            return [
                'enabled' => true,
                'status' => 'open',
                'label' => 'Buka (tanpa batas)',
                'color' => 'emerald',
                'open_at' => $openAt,
                'close_at' => null,
            ];
        }

        if ($closeAt) {
            $closeTime = \Carbon\Carbon::parse($closeAt);
            if ($now->gt($closeTime)) {
                return [
                    'enabled' => true,
                    'status' => 'closed',
                    'label' => 'Sudah Tutup',
                    'color' => 'slate',
                    'open_at' => null,
                    'close_at' => $closeAt,
                ];
            }
            return [
                'enabled' => true,
                'status' => 'open',
                'label' => 'Buka sampai '.$closeTime->format('H:i'),
                'color' => 'emerald',
                'open_at' => null,
                'close_at' => $closeAt,
            ];
        }

        return [
            'enabled' => true,
            'status' => 'always_open',
            'label' => 'Selalu Buka',
            'color' => 'emerald',
        ];
    }
}
