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
        $photoUrl = null;
        $participant = $this->score->participant;
        if ($participant && $participant->document_photo) {
            $photoUrl = asset('storage/'.ltrim(str_replace('\\', '/', $participant->document_photo), '/'));
        }

        // Use new aggregated format if available
        $scores = $this->score->scores;
        $averageScore = $this->score->average_score;

        // Fallback to legacy format for backward compatibility
        if ($scores === null) {
            $scoreValue = $this->score->score;
            $scoreFloat = is_string($scoreValue) ? (float) $scoreValue : $scoreValue;
            $judgeName = $this->score->judge_name;
            $breakdown = $this->score->score_breakdown ?? [];

            $scores = [
                $judgeName => [
                    'score' => $scoreFloat,
                    'breakdown' => $breakdown,
                    'remarks' => $this->score->remarks,
                ]
            ];
            $averageScore = $scoreFloat;
        }

        return [
            'participant_id' => $this->score->participant_id,
            'participant' => $participant?->name,
            'category' => $participant?->category?->name,
            'branch' => $participant?->category?->branch,
            'district_name' => $participant?->district?->name,
            'lot_number' => $participant?->lot_number,
            'institution' => $participant?->institution,
            'judging_round' => $this->score->judging_round,
            'average_score' => (float) $averageScore,
            'scores' => $scores, // All judge scores in JSON format
            'submitted_at' => $this->score->submitted_at?->toIso8601String(),
            'photo_url' => $photoUrl,
            'remarks' => $this->score->remarks,
        ];
    }
}
