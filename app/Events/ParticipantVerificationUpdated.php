<?php

namespace App\Events;

use App\Models\Participant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantVerificationUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Participant $participant)
    {
        $this->participant->loadMissing(['category', 'district']);
    }

    public function broadcastOn(): array
    {
        return [new Channel('mtq-live')];
    }

    public function broadcastAs(): string
    {
        return 'participant.verification-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->participant->id,
            'participant' => $this->participant->name,
            'category' => $this->participant->category?->name,
            'district' => $this->participant->district?->name,
            'verification_status' => $this->participant->verification_status,
            'verification_notes' => $this->participant->verification_notes,
        ];
    }
}
