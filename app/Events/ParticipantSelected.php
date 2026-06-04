<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ParticipantSelected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $participantId,
        public int $categoryId,
        public ?string $participantName = null,
        public ?string $districtName = null,
        public ?string $lotNumber = null,
        public ?string $photoUrl = null,
    ) {
        Log::info('ParticipantSelected event created', [
            'participant_id' => $this->participantId,
            'category_id' => $this->categoryId,
            'name' => $this->participantName,
        ]);
    }

    public function broadcastOn(): array
    {
        Log::info('ParticipantSelected broadcasting on mtq-live channel');
        return [
            new Channel('mtq-live'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'participant.selected';
    }

    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->participantId,
            'category_id' => $this->categoryId,
            'participant_name' => $this->participantName,
            'district_name' => $this->districtName,
            'lot_number' => $this->lotNumber,
            'photo_url' => $this->photoUrl,
        ];
    }
}
