<?php

namespace App\Events;

use App\Models\MaqraPackage;
use App\Models\Participant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaqraAssigned implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Participant $participant,
        public MaqraPackage $maqraPackage,
        public string $roundLabel,
        public string $assignedBy,
        public bool $shared = false
    ) {
        $this->participant->loadMissing(['category', 'district']);
        $this->maqraPackage->loadMissing('competitionCategory');
    }

    public function broadcastOn(): array
    {
        return [new Channel('mtq-live')];
    }

    public function broadcastAs(): string
    {
        return 'maqra.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->participant->id,
            'participant_name' => $this->participant->name,
            'category_id' => $this->participant->competition_category_id,
            'category_name' => $this->participant->category?->name,
            'district_id' => $this->participant->district_id,
            'district_name' => $this->participant->district?->name,
            'round_label' => $this->roundLabel,
            'maqra_package_id' => $this->maqraPackage->id,
            'maqra_code' => $this->maqraPackage->maqra_code,
            'maqra_title' => $this->maqraPackage->title,
            'maqra_content' => $this->maqraPackage->content,
            'assigned_by' => $this->assignedBy,
            'shared' => $this->shared,
            'assigned_at' => now()->toIso8601String(),
        ];
    }
}
