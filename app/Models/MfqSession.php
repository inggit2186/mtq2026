<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MfqSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'competition_category_id',
        'round',
        'judges',
        'district_ids',
        'status',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'judges' => 'array',
            'district_ids' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(MfqResult::class);
    }

    public function getJudgesList(): array
    {
        return $this->judges ?? [];
    }

    public function getDistrictsList(): array
    {
        return $this->district_ids ?? [];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRound($query, string $round)
    {
        return $query->where('round', $round);
    }
}
