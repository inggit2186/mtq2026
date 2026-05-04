<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class MaqraPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'competition_category_id',
        'round_label',
        'maqra_code',
        'title',
        'content',
        'notes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function draws(): HasMany
    {
        return $this->hasMany(ParticipantMaqraDraw::class);
    }
}
