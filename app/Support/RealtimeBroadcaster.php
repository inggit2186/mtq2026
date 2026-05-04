<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

class RealtimeBroadcaster
{
    public static function dispatch(object $event): bool
    {
        try {
            event($event);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Realtime broadcast skipped because the broadcaster is unavailable.', [
                'event' => $event::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
