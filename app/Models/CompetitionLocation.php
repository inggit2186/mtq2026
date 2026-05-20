<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompetitionLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'venue_name',
        'map_url',
        'photo_path',
        'photo_thumb_path',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CompetitionCategory::class, 'competition_category_location');
    }
}
