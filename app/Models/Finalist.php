<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Finalist extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'competition_category_id',
        'gender',
        'finalist_rank',
        'score',
        'round',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'finalist_rank' => 'integer',
            'score' => 'decimal:2',
        ];
    }

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SCRATCHED = 'scratched';

    /**
     * Gender constants (matching participant gender field)
     */
    public const GENDER_MALE = 'putra';
    public const GENDER_FEMALE = 'putri';

    /**
     * Get the participant for this finalist.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Get the competition category for this finalist.
     */
    public function competitionCategory(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    /**
     * Get the rank label (e.g., "Juara 1", "Juara 2", "Juara 3")
     */
    public function getRankLabelAttribute(): string
    {
        return match ($this->finalist_rank) {
            1 => 'Juara 1',
            2 => 'Juara 2',
            3 => 'Juara 3',
            default => "Peringkat {$this->finalist_rank}",
        };
    }

    /**
     * Get the gender label in Indonesian
     */
    public function getGenderLabelAttribute(): string
    {
        return match ($this->gender) {
            self::GENDER_MALE => 'Putra',
            self::GENDER_FEMALE => 'Putri',
            default => $this->gender,
        };
    }

    /**
     * Check if finalist is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if finalist has been scratched
     */
    public function isScratched(): bool
    {
        return $this->status === self::STATUS_SCRATCHED;
    }

    /**
     * Scope to get finalists by category and gender
     */
    public function scopeByCategoryAndGender($query, int $categoryId, string $gender)
    {
        return $query->where('competition_category_id', $categoryId)
            ->where('gender', $gender);
    }

    /**
     * Scope to get active finalists only
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_SCRATCHED);
    }

    /**
     * Scope to order by finalist rank
     */
    public function scopeOrderByRank($query)
    {
        return $query->orderBy('finalist_rank');
    }
}
