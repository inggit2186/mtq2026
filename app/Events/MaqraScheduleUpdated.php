<?php

namespace App\Events;

use App\Models\MaqraSchedule;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaqraScheduleUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public MaqraSchedule $schedule,
        public string $action // 'opened', 'closed'
    ) {
        $this->schedule->load(['round', 'category']);
    }

    public function broadcastOn(): array
    {
        return [new Channel('mtq-live')];
    }

    public function broadcastAs(): string
    {
        return 'maqra.schedule.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'schedule_id' => $this->schedule->id,
            'category_id' => $this->schedule->category_id,
            'category_name' => $this->schedule->category?->name,
            'round_id' => $this->schedule->round_id,
            'round_name' => $this->schedule->round?->name,
            'action' => $this->action,
            'is_active' => $this->schedule->is_active,
            'open_at' => $this->schedule->open_at?->toIso8601String(),
            'close_at' => $this->schedule->close_at?->toIso8601String(),
            'lot_min' => $this->schedule->lot_min,
            'lot_max' => $this->schedule->lot_max,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
