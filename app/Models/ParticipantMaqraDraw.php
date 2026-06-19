<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ParticipantMaqraDraw extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'maqra_package_id',
        'msq_district_title_id',
        'round_label',
        'drawn_at',
    ];

    protected $casts = [
        'drawn_at' => 'datetime',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function maqraPackage(): BelongsTo
    {
        return $this->belongsTo(MaqraPackage::class);
    }

    public function msqDistrictTitle(): BelongsTo
    {
        return $this->belongsTo(MsqDistrictTitle::class);
    }
}
