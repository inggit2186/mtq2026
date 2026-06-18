<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Participant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'competition_category_id',
        'district_id',
        'registration_number',
        'participant_role',
        'competition_role',
        'name',
        'gender',
        'nik',
        'ktp_date',
        'place_of_birth',
        'date_of_birth',
        'kk_number',
        'kk_date',
        'phone',
        'institution',
        'last_education',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'current_address',
        'ktp_address',
        'ktp_district',
        'ktp_regency',
        'region',
        'avatar',
        'document_kk',
        'document_ktp',
        'document_birth_certificate',
        'document_photo',
        'document_last_diploma',
        'document_bank_book',
        'document_certificates',
        'document_other_files',
        'status',
        'verification_status',
        'lot_number',
        'lot_assigned_at',
        'verification_notes',
        'document_revision_notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'ktp_date' => 'date',
            'kk_date' => 'date',
            'lot_assigned_at' => 'datetime',
            'document_certificates' => 'array',
            'document_other_files' => 'array',
            'document_revision_notes' => 'array',
            'district_id' => 'integer',
            'competition_category_id' => 'integer',
        ];
    }

    public function getNameAttribute($value): ?string
    {
        $name = trim((string) $value);

        return $name === '' ? null : Str::upper($name);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ScoreEntry::class);
    }

    public function maqraDraws(): HasMany
    {
        return $this->hasMany(ParticipantMaqraDraw::class);
    }

    public function latestMaqraDraw(): HasOne
    {
        return $this->hasOne(ParticipantMaqraDraw::class)->latestOfMany('drawn_at');
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(ParticipantVerificationLog::class)->latest();
    }

    public function latestScore(): HasMany
    {
        return $this->hasMany(ScoreEntry::class)->latestOfMany('submitted_at');
    }

    /**
     * Alias for category() relationship for consistency.
     */
    public function competitionCategory(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    /**
     * Check if this participant is a Khatib (Khutbah Jumat).
     */
    public function isKhatib(): bool
    {
        return $this->competition_role === 'khatib'
            || $this->category?->isKhatibCategory();
    }

    /**
     * Check if this participant is a Muadzin (Adzan).
     */
    public function isMuadzin(): bool
    {
        return $this->competition_role === 'muadzin'
            || $this->category?->isAdzanCategory();
    }

    /**
     * Get the role label for display.
     */
    public function getRoleLabelAttribute(): ?string
    {
        return match ($this->competition_role) {
            'khatib' => 'Khatib',
            'muadzin' => 'Muadzin',
            default => null,
        };
    }

    /**
     * Check if this participant needs to select competition role before lot assignment.
     * Only applies to the old combined "Khutbah Jumat dan Adzan" category.
     * (Name contains both "Khatib" and "Muadzin" - the pair category)
     *
     * Note: This handles soft-deleted categories by checking the category ID directly.
     */
    public function needsCompetitionRoleSelection(): bool
    {
        $category = $this->category;

        // Must have a competition_category_id
        if (!filled($this->competition_category_id)) {
            return false;
        }

        // If category relationship is loaded, check by name
        if ($category) {
            $name = mb_strtolower((string) ($category->name ?? ''));
            return str_contains($name, 'khatib') && str_contains($name, 'muadzin');
        }

        // If category relationship is null (possibly soft-deleted), check by category ID
        // Old combined category ID: 28 (khutbah-jumat-dan-adzan-khatib-dan-muadzin)
        // We need to check by ID since the soft-deleted category won't load
        $oldCategoryId = 28;

        if ((int) $this->competition_category_id === $oldCategoryId) {
            return true;
        }

        return false;
    }

    /**
     * Get the paired participant from the same district and gender who is still in the old combined category.
     * Used for Khatib/Muadzin lot sharing (1 kecamatan = 2 peserta).
     *
     * @param int|null $oldCategoryId The old category ID to search in. If null, uses $this->competition_category_id.
     */
    public function getPairedParticipant(?int $oldCategoryId = null): ?self
    {
        if (! filled($this->district_id) || ! filled($this->gender)) {
            return null;
        }

        // Only for participants in the old combined category
        if (! $this->needsCompetitionRoleSelection()) {
            return null;
        }

        $categoryId = $oldCategoryId ?? $this->competition_category_id;

        // Find another verified participant from same district, same gender, still in old category, no lot number
        return self::query()
            ->where('id', '!=', $this->id)
            ->where('district_id', $this->district_id)
            ->where('gender', $this->gender)
            ->where('verification_status', 'verified')
            ->where('competition_category_id', $categoryId)
            ->whereNull('lot_number')
            ->first();
    }

    /**
     * Find paired participant from same district and gender (without lot_number requirement).
     * Used when the first participant has already been assigned a lot.
     *
     * @param int|null $oldCategoryId The old category ID to search in. If null, uses $this->competition_category_id.
     */
    public function findPairedForMuadzin(?int $oldCategoryId = null): ?self
    {
        if (! filled($this->district_id) || ! filled($this->gender)) {
            return null;
        }

        $categoryId = $oldCategoryId ?? $this->competition_category_id;

        // Find verified participant from same district, same gender, still in old category, no competition role yet
        return self::query()
            ->where('id', '!=', $this->id)
            ->where('district_id', $this->district_id)
            ->where('gender', $this->gender)
            ->where('verification_status', 'verified')
            ->where('competition_category_id', $categoryId)
            ->whereNull('competition_role')
            ->first();
    }
}
