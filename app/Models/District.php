<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'silatar_id',
        'mandate_document_path',
        'mandate_uploaded_at',
        'mandate_status',
        'mandate_verification_notes',
        'mandate_verified_by',
        'mandate_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'silatar_id' => 'integer',
            'mandate_uploaded_at' => 'datetime',
            'mandate_verified_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function mandateVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mandate_verified_by');
    }
}
