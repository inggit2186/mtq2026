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
     * Check if this is a Khutbah Jumat category.
     */
    public function isKhatibCategory(): bool
    {
        return $this->slug === 'khutbah-jumat-khatib';
    }

    /**
     * Check if this is an Adzan category.
     */
    public function isAdzanCategory(): bool
    {
        return $this->slug === 'adzan-muadzin';
    }

    /**
     * Check if this category is related to Khutbah/Adzan (either new separate or old combined).
     */
    public function isKhutbahAdzanRelated(): bool
    {
        $slug = $this->slug ?? '';
        $branch = mb_strtolower($this->branch ?? '');

        return $this->isKhatibCategory()
            || $this->isAdzanCategory()
            || str_contains($slug, 'khutbah-jumat-dan-adzan')
            || str_contains($branch, 'khutbah');
    }

    /**
     * Check if this is a non-MFQ category that uses maqra system.
     */
    public function usesMaqraSystem(): bool
    {
        // Khutbah Jumat and Adzan do NOT use maqra
        if ($this->isKhatibCategory() || $this->isAdzanCategory()) {
            return false;
        }

        // Old combined category also doesn't use maqra
        $slug = $this->slug ?? '';
        if (str_contains($slug, 'khutbah-jumat-dan-adzan')) {
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
        if ($this->isKhatibCategory()) {
            return 'Khutbah Jumat';
        }
        if ($this->isAdzanCategory()) {
            return 'Adzan';
        }

        return $this->branch ?? $this->name ?? 'Unknown';
    }
}
