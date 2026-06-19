<?php

namespace App\Services;

use App\Models\MaqraSchedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MaqraScheduleCacheService
{
    /**
     * Cache TTL in seconds (30 seconds - balances freshness and performance)
     */
    protected const CACHE_TTL = 30;

    /**
     * Cache key for open schedules by category
     */
    protected const CACHE_KEY_OPEN_BY_CATEGORY = 'maqra:open_by_category';

    /**
     * Cache key for all schedules status
     */
    protected const CACHE_KEY_ALL_STATUS = 'maqra:all_status';

    /**
     * Get all currently open schedules (cached)
     * Returns collection of schedules grouped by category_id
     */
    public static function getOpenSchedulesByCategory(): Collection
    {
        return Cache::remember(self::CACHE_KEY_OPEN_BY_CATEGORY, self::CACHE_TTL, function () {
            return self::buildOpenSchedulesCache();
        });
    }

    /**
     * Build the open schedules cache data
     */
    protected static function buildOpenSchedulesCache(): Collection
    {
        $now = now();

        return MaqraSchedule::query()
            ->select(['id', 'round_id', 'category_id', 'open_at', 'close_at', 'lot_min', 'lot_max', 'is_active', 'draw_access_by'])
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('open_at')
                    ->orWhere('open_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('close_at')
                    ->orWhere('close_at', '>=', $now);
            })
            ->get()
            ->groupBy('category_id');
    }

    /**
     * Check if a category has any open schedule (cached)
     */
    public static function hasOpenScheduleForCategory(int $categoryId): bool
    {
        $schedules = self::getOpenSchedulesByCategory();

        return $schedules->has($categoryId);
    }

    /**
     * Check if a lot number is within any open schedule for a category (cached)
     */
    public static function isLotInOpenSchedule(int $categoryId, int $lotNumber): bool
    {
        $schedules = self::getOpenSchedulesByCategory();

        $categorySchedules = $schedules->get($categoryId, collect());

        if ($categorySchedules->isEmpty()) {
            return false;
        }

        foreach ($categorySchedules as $schedule) {
            if ($lotNumber >= $schedule->lot_min && ($schedule->lot_max === null || $lotNumber <= $schedule->lot_max)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the first open schedule for a category (cached)
     */
    public static function getFirstOpenScheduleForCategory(int $categoryId): ?MaqraSchedule
    {
        $schedules = self::getOpenSchedulesByCategory();

        return $schedules->get($categoryId, collect())->first();
    }

    /**
     * Check if a role can draw maqra for a category based on open schedule settings (cached)
     */
    public static function canRoleDrawForCategory(int $categoryId, ?string $role): bool
    {
        $schedule = self::getFirstOpenScheduleForCategory($categoryId);

        if (!$schedule) {
            return false;
        }

        return $schedule->canRoleDrawMaqra($role);
    }

    /**
     * Get all schedules with their status (cached)
     * Returns array with schedule ID as key
     */
    public static function getAllSchedulesStatus(): array
    {
        return Cache::remember(self::CACHE_KEY_ALL_STATUS, self::CACHE_TTL, function () {
            return self::buildAllSchedulesStatusCache();
        });
    }

    /**
     * Build all schedules status cache
     */
    protected static function buildAllSchedulesStatusCache(): array
    {
        $now = now();

        return MaqraSchedule::query()
            ->select(['id', 'round_id', 'category_id', 'open_at', 'close_at', 'lot_min', 'lot_max', 'is_active', 'draw_access_by'])
            ->get()
            ->mapWithKeys(function ($schedule) use ($now) {
                // Determine status
                $status = 'disabled';
                if ($schedule->is_active) {
                    if ($schedule->open_at && $now->lt($schedule->open_at)) {
                        $status = 'scheduled';
                    } elseif ($schedule->close_at && $now->gt($schedule->close_at)) {
                        $status = 'closed';
                    } else {
                        $status = 'open';
                    }
                }

                return [
                    $schedule->id => [
                        'id' => $schedule->id,
                        'category_id' => $schedule->category_id,
                        'round_id' => $schedule->round_id,
                        'status' => $status,
                        'is_active' => $schedule->is_active,
                        'is_open' => $status === 'open',
                        'draw_access_by' => $schedule->draw_access_by,
                        'allowed_roles' => $schedule->allowedRolesForDraw(),
                        'lot_min' => $schedule->lot_min,
                        'lot_max' => $schedule->lot_max,
                        'open_at' => $schedule->open_at?->toIsoString(),
                        'close_at' => $schedule->close_at?->toIsoString(),
                    ]
                ];
            })
            ->all();
    }

    /**
     * Check if a schedule is currently open (cached)
     */
    public static function isScheduleOpen(int $scheduleId): bool
    {
        $allStatus = self::getAllSchedulesStatus();

        $schedule = $allStatus[$scheduleId] ?? null;

        return $schedule && $schedule['is_open'] === true;
    }

    /**
     * Get allowed roles for a schedule (cached)
     */
    public static function getAllowedRolesForSchedule(int $scheduleId): array
    {
        $allStatus = self::getAllSchedulesStatus();

        $schedule = $allStatus[$scheduleId] ?? null;

        return $schedule ? $schedule['allowed_roles'] : [];
    }

    /**
     * Invalidate all maqra schedule caches
     * Call this when:
     * - Schedule is created, updated, or deleted
     * - Schedule status changes (scheduled -> active -> closed)
     */
    public static function invalidate(): void
    {
        Cache::forget(self::CACHE_KEY_OPEN_BY_CATEGORY);
        Cache::forget(self::CACHE_KEY_ALL_STATUS);
    }

    /**
     * Invalidate and refresh caches immediately
     */
    public static function refresh(): void
    {
        self::invalidate();
        // Pre-warm the cache
        self::getOpenSchedulesByCategory();
        self::getAllSchedulesStatus();
    }

    /**
     * Warm up cache (call this after deploy or cache clear)
     */
    public static function warmUp(): void
    {
        self::refresh();
    }
}
