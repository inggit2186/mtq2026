<?php

namespace App\Support;

use App\Events\SessionScheduleUpdated;
use App\Models\SessionSchedule;
use Illuminate\Support\Facades\Schema;

class ScheduleRealtimeNotifier
{
    public static function broadcastDueStarts(): int
    {
        SessionSchedule::syncAutomaticStatuses();

        if (! Schema::hasColumn('session_schedules', 'started_broadcast_at')) {
            return 0;
        }

        $now = now();
        $sent = 0;

        SessionSchedule::query()
            ->where('status', SessionSchedule::STATUS_ONGOING)
            ->whereNull('started_broadcast_at')
            ->where('starts_at', '<=', $now)
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('starts_at')
            ->each(function (SessionSchedule $schedule) use (&$sent, $now): void {
                if (! RealtimeBroadcaster::dispatch(new SessionScheduleUpdated($schedule, 'auto-start'))) {
                    return;
                }

                $schedule->forceFill(['started_broadcast_at' => $now])->save();
                $sent++;
            });

        return $sent;
    }

    public static function ongoingPayloads(int $limit = 3): array
    {
        self::broadcastDueStarts();

        $now = now();

        return SessionSchedule::query()
            ->where('status', SessionSchedule::STATUS_ONGOING)
            ->where('starts_at', '<=', $now)
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('starts_at')
            ->limit($limit)
            ->get()
            ->map(fn (SessionSchedule $schedule): array => [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'stage' => $schedule->stage,
                'venue' => $schedule->venue,
                'status' => $schedule->status,
                'starts_at' => $schedule->starts_at?->toIso8601String(),
                'ends_at' => $schedule->ends_at?->toIso8601String(),
                'notes' => $schedule->notes,
                'source' => 'page-load',
            ])
            ->all();
    }
}
