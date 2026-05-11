<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'priority',
        'audience',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function scopeVisibleToRole(Builder $query, ?string $role = null): Builder
    {
        $audiences = match ($role) {
            'official', 'pendamping' => ['all', 'official', 'official_panitia'],
            'panitia' => ['all', 'panitia', 'official_panitia'],
            default => ['all'],
        };

        return $query->whereIn('audience', $audiences);
    }

    public function scopeForDashboardRole(Builder $query, ?string $role = null): Builder
    {
        return $query->visibleToRole($role);
    }

    public function audienceLabel(): string
    {
        return match ($this->audience ?? 'all') {
            'official' => 'Official',
            'panitia' => 'Panitia',
            'official_panitia' => 'Official + Panitia',
            default => 'Semua',
        };
    }
}
