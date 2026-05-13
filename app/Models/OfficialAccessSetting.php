<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class OfficialAccessSetting extends Model
{
    protected $fillable = [
        'participant_registration_open',
        'participant_edit_open',
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
    ];

    protected function casts(): array
    {
        return [
            'participant_registration_open' => 'boolean',
            'participant_edit_open' => 'boolean',
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
        ];
    }

    public static function defaults(): array
    {
        return [
            'participant_registration_open' => true,
            'participant_edit_open' => true,
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
        $defaults = static::defaults();

        return (bool) ($this->{$feature} ?? ($defaults[$feature] ?? true));
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
}
