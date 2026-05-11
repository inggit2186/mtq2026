<?php

namespace App\Events;

use App\Models\Announcement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementPublished implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Announcement $announcement)
    {
        $this->announcement->loadMissing('author');
    }

    public function broadcastOn(): array
    {
        return [new Channel('mtq-live')];
    }

    public function broadcastAs(): string
    {
        return 'announcement.published';
    }

    public function broadcastWith(): array
    {
        return [
            'title' => $this->announcement->title,
            'body' => $this->announcement->body,
            'priority' => $this->announcement->priority,
            'audience' => $this->announcement->audience ?? 'all',
            'published_at' => $this->announcement->published_at?->toIso8601String(),
        ];
    }
}
