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
}
