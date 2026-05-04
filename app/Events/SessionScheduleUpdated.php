<?php

namespace App\Events;

use App\Models\SessionSchedule;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionScheduleUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public SessionSchedule $schedule, public string $source = 'manual')
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('mtq-live')];
    }

    public function broadcastAs(): string
    {
        return 'schedule.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->schedule->id,
            'title' => $this->schedule->title,
            'stage' => $this->schedule->stage,
            'venue' => $this->schedule->venue,
            'status' => $this->schedule->status,
            'starts_at' => $this->schedule->starts_at?->toIso8601String(),
            'ends_at' => $this->schedule->ends_at?->toIso8601String(),
            'notes' => $this->schedule->notes,
            'source' => $this->source,
        ];
    }
}
