<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MsqDistrictTitle extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_id',
        'gender',
        'title',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDistrict($query, int $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    public function scopeForGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }
}
