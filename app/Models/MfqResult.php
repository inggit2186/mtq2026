<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MfqResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'mfq_session_id',
        'participant_id',
        'district_id',
        'round',
        'rank',
        'total_score',
        'scores_detail',
    ];

    protected function casts(): array
    {
        return [
            'scores_detail' => 'array',
            'total_score' => 'decimal:2',
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

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
