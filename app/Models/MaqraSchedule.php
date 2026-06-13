<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class MaqraSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'round_id',
        'category_id',
        'open_at',
        'close_at',
        'lot_min',
        'lot_max',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'open_at' => 'datetime',
            'close_at' => 'datetime',
            'lot_min' => 'integer',
            'lot_max' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(MaqraRound::class, 'round_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeForRound($query, int $roundId)
    {
        return $query->where('round_id', $roundId);
    }

    public function scopeCurrentlyOpen($query)
    {
        $now = now();
        return $query->active()
            ->where(function ($q) use ($now) {
                $q->whereNull('open_at')
                    ->orWhere('open_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('close_at')
                    ->orWhere('close_at', '>=', $now);
            });
    }

    public function isCurrentlyOpen(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->open_at && $now->lt($this->open_at)) {
            return false;
        }

        if ($this->close_at && $now->gt($this->close_at)) {
            return false;
        }

        return true;
    }

    public function getStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'disabled';
        }

        $now = now();

        if ($this->open_at && $now->lt($this->open_at)) {
            return 'scheduled';
        }

        if ($this->close_at && $now->gt($this->close_at)) {
            return 'closed';
        }

        return 'open';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'disabled' => 'Ditutup',
            'scheduled' => 'Terjadwal',
            'closed' => 'Selesai',
            'open' => 'Sedang Buka',
            default => 'Unknown',
        };
    }

    public function getOpenAtIsoAttribute(): ?string
    {
        return $this->open_at?->toIsoString();
    }

    public function getCloseAtIsoAttribute(): ?string
    {
        return $this->close_at?->toIsoString();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'disabled' => 'slate',
            'scheduled' => 'amber',
            'closed' => 'gray',
            'open' => 'emerald',
            default => 'gray',
        };
    }

    public function isLotInRange(int $lotNumber): bool
    {
        if ($lotNumber < $this->lot_min) {
            return false;
        }

        if ($this->lot_max && $lotNumber > $this->lot_max) {
            return false;
        }

        return true;
    }

    public static function findActiveScheduleForParticipant(int $categoryId, int $lotNumber): ?self
    {
        return static::currentlyOpen()
            ->forCategory($categoryId)
            ->get()
            ->filter(fn ($schedule) => $schedule->isLotInRange($lotNumber))
            ->first();
    }

    public static function getSchedulesWithRelations()
    {
        return static::with(['round', 'category'])
            ->orderBy('open_at', 'desc')
            ->get();
    }

    public static function getSchedulesGroupedByRound()
    {
        return static::with(['round', 'category'])
            ->active()
            ->orderBy('open_at', 'desc')
            ->get()
            ->groupBy(fn ($schedule) => $schedule->round?->name ?? 'Tanpa Babak');
    }
}
