<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('mtq-live', function (): bool {
    return true;
});
