<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SessionSchedule extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_POSTPONED = 'postponed';

    protected $fillable = [
        'title',
        'stage',
        'venue',
        'starts_at',
        'ends_at',
        'status',
        'started_broadcast_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'started_broadcast_at' => 'datetime',
        ];
    }

    public static function automaticStatusFor(?Carbon $startsAt, ?Carbon $endsAt, ?Carbon $now = null): string
    {
        $now ??= now();

        if (! $startsAt || $now->lt($startsAt)) {
            return self::STATUS_SCHEDULED;
        }

        if ($endsAt && $now->gt($endsAt)) {
            return self::STATUS_COMPLETED;
        }

        return self::STATUS_ONGOING;
    }

    public function automaticStatus(?Carbon $now = null): string
    {
        return self::automaticStatusFor($this->starts_at, $this->ends_at, $now);
    }

    public function syncAutomaticStatus(?Carbon $now = null): bool
    {
        if ($this->status === self::STATUS_POSTPONED) {
            return false;
        }

        $status = $this->automaticStatus($now);

        if ($this->status === $status) {
            return false;
        }

        $this->forceFill(['status' => $status])->save();

        return true;
    }

    public static function syncAutomaticStatuses(?Carbon $now = null): int
    {
        $now ??= now();
        $updatedAt = ['updated_at' => $now];
        $normalStatuses = [
            self::STATUS_SCHEDULED,
            self::STATUS_ONGOING,
            self::STATUS_COMPLETED,
        ];

        $updated = self::query()
            ->whereIn('status', $normalStatuses)
            ->where('starts_at', '>', $now)
            ->where('status', '!=', self::STATUS_SCHEDULED)
            ->update(['status' => self::STATUS_SCHEDULED] + $updatedAt);

        $updated += self::query()
            ->whereIn('status', $normalStatuses)
            ->where('starts_at', '<=', $now)
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->where('status', '!=', self::STATUS_ONGOING)
            ->update(['status' => self::STATUS_ONGOING] + $updatedAt);

        $updated += self::query()
            ->whereIn('status', $normalStatuses)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->where('status', '!=', self::STATUS_COMPLETED)
            ->update(['status' => self::STATUS_COMPLETED] + $updatedAt);

        return $updated;
    }
}
