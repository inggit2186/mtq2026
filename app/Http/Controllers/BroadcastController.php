<?php

namespace App\Http\Controllers;

use App\Events\AnnouncementPublished;
use App\Events\SessionScheduleUpdated;
use App\Models\Announcement;
use App\Models\SessionSchedule;
use App\Support\ActivityLogger;
use App\Support\RealtimeBroadcaster;
use Illuminate\Http\RedirectResponse;

class BroadcastController extends Controller
{
    public function announcement(Announcement $announcement): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        RealtimeBroadcaster::dispatch(new AnnouncementPublished($announcement));

        ActivityLogger::log(
            'announcement.broadcasted',
            (auth()->user()?->name ?? 'Panitia').' menyiarkan pengumuman "'.$announcement->title.'" secara realtime.',
            $announcement,
            ['priority' => $announcement->priority]
        );

        return back()->with('status', 'Pengumuman "'.$announcement->title.'" berhasil disiarkan realtime.');
    }

    public function schedule(SessionSchedule $schedule): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $schedule->syncAutomaticStatus();

        RealtimeBroadcaster::dispatch(new SessionScheduleUpdated($schedule, 'manual'));

        ActivityLogger::log(
            'schedule.broadcasted',
            (auth()->user()?->name ?? 'Panitia').' menyiarkan jadwal "'.$schedule->title.'" secara realtime.',
            $schedule,
            [
                'status' => $schedule->status,
                'starts_at' => optional($schedule->starts_at)->toDateTimeString(),
            ]
        );

        return back()->with('status', 'Jadwal "'.$schedule->title.'" berhasil disiarkan realtime.');
    }
}
