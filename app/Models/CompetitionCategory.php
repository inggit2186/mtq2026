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
    ];

    protected $casts = [
        'lot_number_min' => 'integer',
        'lot_number_max' => 'integer',
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
}
