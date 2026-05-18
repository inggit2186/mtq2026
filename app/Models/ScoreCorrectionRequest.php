<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'competition_category_id',
        'judging_round',
        'requested_by',
        'status',
        'note',
        'requested_scores',
        'requested_remarks',
        'requested_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_scores' => 'array',
            'requested_remarks' => 'array',
            'requested_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
