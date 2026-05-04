<?php

namespace App\Console\Commands;

use App\Support\ScheduleRealtimeNotifier;
use Illuminate\Console\Command;

class BroadcastDueSchedules extends Command
{
    protected $signature = 'schedules:broadcast-due';

    protected $description = 'Broadcast jadwal yang mulai berlangsung melalui Reverb.';

    public function handle(): int
    {
        $sent = ScheduleRealtimeNotifier::broadcastDueStarts();

        $this->info($sent.' jadwal mulai disiarkan.');

        return self::SUCCESS;
    }
}
