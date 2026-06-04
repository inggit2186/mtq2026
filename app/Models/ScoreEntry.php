<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ScoreEntry extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'participant_id',
        'judging_round',
        'scores',
        'average_score',
        'submitted_at',
        // Legacy fields - kept for reference only (not used in new aggregated format)
        // 'judge_name',
        // 'score',
        // 'score_breakdown',
        // 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'average_score' => 'decimal:2',
            // Legacy casts for backward compatibility
            'score' => 'decimal:2',
            'score_breakdown' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Backward compatibility accessor: Get judge name from JSON scores
     * Returns first judge name if using new format, or the stored judge_name
     */
    public function getJudgeNameAttribute(): ?string
    {
        // If using new JSON format (scores field), return first key
        if ($this->scores && is_array($this->scores) && count($this->scores) > 0) {
            return array_key_first($this->scores);
        }
        // Fallback to old field
        return $this->getAttributes()['judge_name'] ?? null;
    }

    /**
     * Backward compatibility accessor: Get score from average_score
     * Returns average_score if using new format, or the stored score
     */
    public function getScoreAttribute(): ?float
    {
        // If using new format (average_score field), return it
        if ($this->average_score !== null) {
            return (float) $this->average_score;
        }
        // Fallback to old field
        return isset($this->getAttributes()['score']) ? (float) $this->getAttributes()['score'] : null;
    }

    /**
     * Backward compatibility accessor: Get score breakdown from JSON
     * Returns first judge's breakdown if using new format
     */
    public function getScoreBreakdownAttribute(): ?array
    {
        // If using new JSON format, return first judge's breakdown
        if ($this->scores && is_array($this->scores) && count($this->scores) > 0) {
            $firstJudge = array_key_first($this->scores);
            return $this->scores[$firstJudge]['breakdown'] ?? null;
        }
        // Fallback to old field
        return $this->getAttributes()['score_breakdown'] ?? null;
    }

    /**
     * Check if this entry uses the new aggregated JSON format
     */
    public function isAggregatedFormat(): bool
    {
        return $this->scores !== null && is_array($this->scores);
    }

    /**
     * Get all judge scores from JSON format
     */
    public function getAllJudgeScores(): array
    {
        if ($this->scores && is_array($this->scores)) {
            return $this->scores;
        }
        // Fallback to old format - single entry
        $judgeName = $this->getAttributes()['judge_name'] ?? 'Unknown';
        $score = $this->getAttributes()['score'] ?? 0;
        $breakdown = $this->getAttributes()['score_breakdown'] ?? [];
        $remarks = $this->getAttributes()['remarks'] ?? null;

        return [
            $judgeName => [
                'score' => (float) $score,
                'breakdown' => $breakdown,
                'remarks' => $remarks,
            ]
        ];
    }

    /**
     * Get remarks from new JSON format or old field
     */
    public function getRemarksForJudge(string $judgeName): ?string
    {
        if ($this->scores && is_array($this->scores) && isset($this->scores[$judgeName])) {
            return $this->scores[$judgeName]['remarks'] ?? null;
        }
        // Fallback to old field (only works for single judge entries)
        return $this->getAttributes()['remarks'] ?? null;
    }
}
