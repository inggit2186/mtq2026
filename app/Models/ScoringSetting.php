<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class ScoringSetting extends Model
{
    protected $fillable = [
        'competition_category_id',
        'judge_count',
        'judge_names',
        'judging_rounds',
        'scoring_points',
        'scoring_priorities',
        'round_settings',
        'configured_by',
    ];

    protected function casts(): array
    {
        return [
            'judge_names' => 'array',
            'judging_rounds' => 'array',
            'scoring_points' => 'array',
            'scoring_priorities' => 'array',
            'round_settings' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'configured_by');
    }

    public static function forCategory(?int $categoryId): ?self
    {
        if (! $categoryId || ! Schema::hasTable('scoring_settings')) {
            return null;
        }

        return static::query()
            ->where('competition_category_id', $categoryId)
            ->latest('id')
            ->first();
    }

    public function isReady(): bool
    {
        $roundSettings = $this->round_settings ?? [];
        if (is_array($roundSettings) && $roundSettings !== []) {
            $rounds = array_values(array_filter($this->judging_rounds ?? []));

            foreach ($rounds as $round) {
                $config = $roundSettings[$round] ?? [];
                $judgeNames = array_values(array_filter($config['judge_names'] ?? []));
                $scoringPoints = $config['scoring_points'] ?? [];

                if ((int) ($config['judge_count'] ?? 0) <= 0) {
                    return false;
                }

                if (count($judgeNames) !== (int) ($config['judge_count'] ?? 0)) {
                    return false;
                }

                if (count($scoringPoints) === 0) {
                    return false;
                }
            }

            return count($rounds) > 0;
        }

        return (int) $this->judge_count > 0
            && count(array_filter($this->judge_names ?? [])) > 0
            && count(array_filter($this->judging_rounds ?? [])) > 0
            && count(array_filter($this->scoring_points ?? [])) > 0;
    }
}
