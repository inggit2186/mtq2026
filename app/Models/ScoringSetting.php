<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoringSetting extends Model
{
    protected $fillable = [
        'competition_category_id',
        'configured_by',
        // Penyisihan
        'penyisihan_judge_count',
        'penyisihan_judge_names',
        'penyisihan_judge_ids',
        'penyisihan_scoring_points',
        'penyisihan_edit_state',
        'penyisihan_edit_requested_at',
        'penyisihan_edit_requested_by',
        'penyisihan_edit_opened_at',
        'penyisihan_edit_opened_by',
        'penyisihan_finalized_at',
        // Final
        'final_judge_count',
        'final_judge_names',
        'final_judge_ids',
        'final_scoring_points',
        'final_edit_state',
        'final_edit_requested_at',
        'final_edit_requested_by',
        'final_edit_opened_at',
        'final_edit_opened_by',
        'final_finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'penyisihan_judge_names' => 'array',
            'penyisihan_judge_ids' => 'array',
            'penyisihan_scoring_points' => 'array',
            'penyisihan_edit_requested_at' => 'datetime',
            'penyisihan_edit_opened_at' => 'datetime',
            'penyisihan_finalized_at' => 'datetime',
            'final_judge_names' => 'array',
            'final_judge_ids' => 'array',
            'final_scoring_points' => 'array',
            'final_edit_requested_at' => 'datetime',
            'final_edit_opened_at' => 'datetime',
            'final_finalized_at' => 'datetime',
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
        if ($categoryId === null) {
            return null;
        }

        return static::where('competition_category_id', $categoryId)->first();
    }

    public function penyisihanEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penyisihan_edit_requested_by');
    }

    public function finalEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_edit_requested_by');
    }

    public function penyisihanOpener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penyisihan_edit_opened_by');
    }

    public function finalOpener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_edit_opened_by');
    }

    public function roundConfig(string $roundLabel): array
    {
        $prefix = $this->roundPrefix($roundLabel);

        $judgeNames = $this->{$prefix.'_judge_names'} ?? [];
        $judgeIds = $this->{$prefix.'_judge_ids'} ?? [];
        $scoringPoints = $this->{$prefix.'_scoring_points'} ?? [];

        // Derive scoring_priorities from scoring_points keys
        $scoringPriorities = array_keys($scoringPoints);

        return [
            'judge_count' => (int) ($this->{$prefix.'_judge_count'} ?? 0),
            'judge_names' => $judgeNames,
            'judge_ids' => $judgeIds,
            'scoring_points' => $scoringPoints,
            'scoring_priorities' => $scoringPriorities,
        ];
    }

    public function isReady(string $roundLabel = 'Penyisihan'): bool
    {
        $config = $this->roundConfig($roundLabel);

        return $config['judge_count'] > 0
            && count($config['judge_names']) > 0
            && count($config['scoring_points']) > 0;
    }

    public function isEditable(string $roundLabel): bool
    {
        $state = $this->{$this->roundPrefix($roundLabel).'_edit_state'} ?? 'locked';

        return $state === 'open' || $state === 'editable';
    }

    public function isEditRequested(string $roundLabel): bool
    {
        $state = $this->{$this->roundPrefix($roundLabel).'_edit_state'} ?? 'locked';

        return $state === 'requested';
    }

    public function isFinalized(string $roundLabel): bool
    {
        $prefix = $this->roundPrefix($roundLabel);

        return filled($this->{$prefix.'_finalized_at'});
    }

    public function finalizeRound(string $roundLabel): void
    {
        $prefix = $this->roundPrefix($roundLabel);
        $this->forceFill([$prefix.'_finalized_at' => now()])->save();
    }

    public function unfinalizeRound(string $roundLabel): void
    {
        $prefix = $this->roundPrefix($roundLabel);
        $this->forceFill([$prefix.'_finalized_at' => null])->save();
    }

    public function lockRound(string $roundLabel): void
    {
        $prefix = $this->roundPrefix($roundLabel);
        $this->forceFill([$prefix.'_edit_state' => 'locked'])->save();
    }

    public function openRound(string $roundLabel): void
    {
        $prefix = $this->roundPrefix($roundLabel);
        $this->forceFill([
            $prefix.'_edit_state' => 'open',
            $prefix.'_edit_opened_at' => now(),
        ])->save();
    }

    public function requestEditRound(string $roundLabel): void
    {
        $prefix = $this->roundPrefix($roundLabel);
        $this->forceFill([$prefix.'_edit_state' => 'requested'])->save();
    }

    protected function roundPrefix(string $roundLabel): string
    {
        return match (mb_strtolower($roundLabel)) {
            'final' => 'final',
            default => 'penyisihan',
        };
    }
}
