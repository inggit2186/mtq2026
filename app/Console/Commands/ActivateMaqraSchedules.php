<?php

namespace App\Console\Commands;

use App\Models\MaqraSchedule;
use App\Services\MaqraScheduleCacheService;
use App\Events\MaqraScheduleUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ActivateMaqraSchedules extends Command
{
    protected $signature = 'maqra:activate-schedules';

    protected $description = 'Aktifkan jadwal maqra yang sudah waktunya dibuka berdasarkan open_at.';

    public function handle(): int
    {
        $now = now();
        $this->info("Memeriksa jadwal maqra yang perlu diaktifkan pada {$now->format('Y-m-d H:i:s')}...");

        try {
            // Find schedules that:
            // 1. is_active is false
            // 2. open_at is set and <= now
            $schedulesToActivate = MaqraSchedule::query()
                ->where('is_active', false)
                ->whereNotNull('open_at')
                ->where('open_at', '<=', $now)
                ->where('close_at', '>=', $now) // Only activate if not already past close_at
                ->get();

            $activatedCount = 0;
            foreach ($schedulesToActivate as $schedule) {
                $oldStatus = $schedule->getAttributes();
                $schedule->is_active = true;
                $schedule->save();

                // Invalidate cache
                MaqraScheduleCacheService::invalidate();

                // Broadcast event for realtime updates
                try {
                    MaqraScheduleUpdated::dispatch($schedule);
                } catch (\Throwable $e) {
                    Log::warning('Failed to broadcast MaqraScheduleUpdated: ' . $e->getMessage());
                }

                $categoryName = $schedule->category?->name ?? "Golongan #{$schedule->category_id}";
                $roundName = $schedule->round?->name ?? "Babak #{$schedule->round_id}";

                $this->info("  ✓ Diaktifkan: {$roundName} - {$categoryName} (Schedule #{$schedule->id})");
                $activatedCount++;

                Log::info('MaqraSchedule auto-activated', [
                    'schedule_id' => $schedule->id,
                    'category_id' => $schedule->category_id,
                    'round_id' => $schedule->round_id,
                    'open_at' => $schedule->open_at?->toIsoString(),
                    'close_at' => $schedule->close_at?->toIsoString(),
                ]);
            }

            // Also close schedules that have passed their close_at time
            $schedulesToClose = MaqraSchedule::query()
                ->where('is_active', true)
                ->whereNotNull('close_at')
                ->where('close_at', '<', $now)
                ->get();

            $closedCount = 0;
            foreach ($schedulesToClose as $schedule) {
                $schedule->is_active = false;
                $schedule->save();

                // Invalidate cache
                MaqraScheduleCacheService::invalidate();

                // Broadcast event for realtime updates
                try {
                    MaqraScheduleUpdated::dispatch($schedule);
                } catch (\Throwable $e) {
                    Log::warning('Failed to broadcast MaqraScheduleUpdated (close): ' . $e->getMessage());
                }

                $categoryName = $schedule->category?->name ?? "Golongan #{$schedule->category_id}";
                $roundName = $schedule->round?->name ?? "Babak #{$schedule->round_id}";

                $this->warn("  ✗ Ditutup: {$roundName} - {$categoryName} (Schedule #{$schedule->id})");
                $closedCount++;

                Log::info('MaqraSchedule auto-closed (past close_at)', [
                    'schedule_id' => $schedule->id,
                    'category_id' => $schedule->category_id,
                    'round_id' => $schedule->round_id,
                    'close_at' => $schedule->close_at?->toIsoString(),
                ]);
            }

            // Pre-warm the cache after changes
            if ($activatedCount > 0 || $closedCount > 0) {
                MaqraScheduleCacheService::warmUp();
            }

            $totalChanged = $activatedCount + $closedCount;
            if ($totalChanged > 0) {
                $this->info("Selesai. {$activatedCount} jadwal diaktifkan, {$closedCount} jadwal ditutup.");
            } else {
                $this->line('Tidak ada jadwal yang perlu diubah statusnya.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Error in ActivateMaqraSchedules command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error('Terjadi kesalahan: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
