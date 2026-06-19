<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('inspire')->hourly();
Schedule::command('schedules:broadcast-due')->everyMinute()->withoutOverlapping();
Schedule::command('maqra:activate-schedules')->everyMinute()->withoutOverlapping();
