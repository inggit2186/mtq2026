<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hakim extends Model
{
    use HasFactory;

    protected $table = 'hakim';

    protected $fillable = [
        'nama',
        'asal',
    ];

    public function golongans(): BelongsToMany
    {
        return $this->belongsToMany(
            CompetitionCategory::class,
            'hakim_golongan',
            'hakim_id',
            'golongan_id'
        )->withTimestamps();
    }

    public function getGolonganNamesAttribute(): string
    {
        return $this->golongans->pluck('name')->join(', ');
    }

    public function scopeByGolongan($query, int $golonganId)
    {
        return $query->whereHas('golongans', function ($q) use ($golonganId) {
            $q->where('competition_categories.id', $golonganId);
        });
    }

    public function scopeSearch($query, ?string $search)
    {
        if (blank($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('nama', 'like', "%{$search}%")
              ->orWhere('asal', 'like', "%{$search}%");
        });
    }
}