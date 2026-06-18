<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class CompetitionCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'branch',
        'name',
        'slug',
        'quota',
        'age_requirement',
        'notes',
        'sort_order',
        'description',
        'round',
        'color',
        'lot_code',
        'lot_number_min',
        'lot_number_max',
        'maqra_system_type',
        'lot_group_type',
        'uses_district_quota',
    ];

    protected $casts = [
        'lot_number_min' => 'integer',
        'lot_number_max' => 'integer',
        'uses_district_quota' => 'boolean',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function scoringSetting(): HasOne
    {
        return $this->hasOne(ScoringSetting::class, 'competition_category_id');
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(CompetitionLocation::class, 'competition_category_location');
    }

    public function appearanceSchedule(): HasOne
    {
        return $this->hasOne(AppearanceSchedule::class, 'competition_category_id');
    }

    /**
     * Check if this is a Khutbah category (Khatib).
     * Uses name-based detection: name === 'Khatib' AND branch contains 'Khutbah'
     */
    public function isKhatibCategory(): bool
    {
        $name = trim((string) ($this->name ?? ''));
        $branch = mb_strtolower((string) ($this->branch ?? ''));

        return $name === 'Khatib' && str_contains($branch, 'khutbah');
    }

    /**
     * Check if this is an Adzan category (Muadzin).
     * Uses name-based detection: name === 'Adzan' AND branch contains 'Adzan'
     */
    public function isAdzanCategory(): bool
    {
        $name = trim((string) ($this->name ?? ''));
        $branch = mb_strtolower((string) ($this->branch ?? ''));

        return $name === 'Adzan' && str_contains($branch, 'adzan');
    }

    /**
     * Check if this category is related to Khutbah/Adzan.
     * Includes: Khatib, Adzan, and the old combined category.
     */
    public function isKhutbahAdzanRelated(): bool
    {
        $branch = mb_strtolower((string) ($this->branch ?? ''));

        // Check by branch (covers all khutbah/adzan related categories)
        return str_contains($branch, 'khutbah') || str_contains($branch, 'adzan');
    }

    /**
     * Check if this is a non-MFQ category that uses maqra system.
     */
    public function usesMaqraSystem(): bool
    {
        // Khutbah dan Adzan TIDAK menggunakan maqra
        if ($this->isKhatibCategory() || $this->isAdzanCategory()) {
            return false;
        }

        // Old combined category also doesn't use maqra
        $name = mb_strtolower((string) ($this->name ?? ''));
        if (str_contains($name, 'khatib') && str_contains($name, 'muadzin')) {
            return false;
        }

        return filled($this->maqra_system_type)
            && in_array($this->maqra_system_type, ['tilawah', 'tahfizh', 'tafsir']);
    }

    /**
     * Get the display name for this category.
     */
    public function getDisplayNameAttribute(): string
    {
        $branch = (string) ($this->branch ?? '');
        $name = (string) ($this->name ?? '');

        // For Khutbah/Adzan categories, combine branch and name
        if ($this->isKhatibCategory() || $this->isAdzanCategory()) {
            return trim($branch.' - '.$name);
        }

        return $branch ?: $name ?: 'Unknown';
    }
}
