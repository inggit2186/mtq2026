<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingSetting extends Model
{
    protected $fillable = [
        'name',
        'competition_category_id',
        'gender',
        'appearance_day',
        'judging_round',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'appearance_day' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    /**
     * Scope to get only active rankings ordered by sort_order
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Scope to get rankings for a specific category or global ones
     */
    public function scopeForCategory($query, ?int $categoryId)
    {
        return $query->where(function ($q) use ($categoryId) {
            $q->whereNull('competition_category_id')
              ->orWhere('competition_category_id', $categoryId);
        });
    }

    /**
     * Check if this ranking is for a specific gender
     */
    public function isForGender(string $gender): bool
    {
        return $this->gender === 'all' || $this->gender === $gender;
    }

    /**
     * Check if this ranking is for overall (no specific day)
     */
    public function isOverall(): bool
    {
        return $this->appearance_day === null;
    }

    /**
     * Get the display label for this ranking
     */
    public function getDisplayLabelAttribute(): string
    {
        $parts = [];

        // Gender label
        $genderLabel = match ($this->gender) {
            'putra' => 'Putra',
            'putri' => 'Putri',
            default => 'Putra & Putri',
        };
        $parts[] = $genderLabel;

        // Category label
        if ($this->category) {
            $parts[] = $this->category->name;
        }

        // Day label
        if ($this->appearance_day !== null) {
            $parts[] = 'Hari ' . ($this->appearance_day + 1);
        } else {
            $parts[] = 'Keseluruhan';
        }

        // Round label
        $parts[] = $this->judging_round;

        return implode(' - ', $parts);
    }

    /**
     * Get all active rankings with their computed rankings
     */
    public static function getActiveRankings(?int $categoryId = null): \Illuminate\Support\Collection
    {
        return static::active()
            ->forCategory($categoryId)
            ->with('category')
            ->get();
    }
}
