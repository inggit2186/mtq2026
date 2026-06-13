<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaqraRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MaqraSchedule::class, 'round_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getActiveRounds(): array
    {
        return static::active()
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->all();
    }
}
