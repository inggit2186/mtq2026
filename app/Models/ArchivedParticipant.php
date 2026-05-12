<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ArchivedParticipant extends Model
{
    use HasFactory;

    protected $table = 'archived_participants';

    protected $fillable = [
        'source_participant_id',
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
        'archived_by',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'ktp_date' => 'date',
            'kk_date' => 'date',
            'lot_assigned_at' => 'datetime',
            'archived_at' => 'datetime',
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

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }
}
