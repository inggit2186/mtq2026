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
        'judge_name',
        'judging_round',
        'score',
        'score_breakdown',
        'remarks',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'score_breakdown' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
