<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MfqDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'mfq_session_id',
        'participant_id',
        'judge_name',
        'questions_data',
        'totals',
    ];

    protected function casts(): array
    {
        return [
            'questions_data' => 'array',
            'totals' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MfqSession::class, 'mfq_session_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Get unique key for this draft
     */
    public function getUniqueKey(): string
    {
        return "{$this->mfq_session_id}-{$this->participant_id}-{$this->judge_name}";
    }

    /**
     * Check if this draft has been finalized (converted to ScoreEntry)
     */
    public function isFinalized(): bool
    {
        return ScoreEntry::where('participant_id', $this->participant_id)
            ->whereJsonContains('scores->session_id', $this->mfq_session_id)
            ->where('judge_name', $this->judge_name)
            ->exists();
    }

    /**
     * Scope to find draft for specific session, participant, judge
     */
    public function scopeForEntry($query, int $sessionId, int $participantId, string $judgeName)
    {
        return $query->where('mfq_session_id', $sessionId)
            ->where('participant_id', $participantId)
            ->where('judge_name', $judgeName);
    }
}
