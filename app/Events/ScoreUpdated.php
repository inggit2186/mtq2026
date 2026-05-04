<?php

namespace App\Events;

use App\Models\ScoreEntry;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScoreUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public ScoreEntry $score)
    {
        $this->score->loadMissing('participant.category');
    }

    public function broadcastOn(): array
    {
        return [new Channel('mtq-live')];
    }

    public function broadcastAs(): string
    {
        return 'score.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->score->participant_id,
            'participant' => $this->score->participant?->name,
            'category' => $this->score->participant?->category?->name,
            'branch' => $this->score->participant?->category?->branch,
            'judging_round' => $this->score->judging_round,
            'score' => (float) $this->score->score,
            'score_breakdown' => $this->score->score_breakdown,
            'submitted_at' => $this->score->submitted_at?->toIso8601String(),
            'remarks' => $this->score->remarks,
        ];
    }
}
