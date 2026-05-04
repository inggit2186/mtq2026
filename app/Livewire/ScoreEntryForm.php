<?php

namespace App\Livewire;

use App\Events\ScoreUpdated;
use App\Models\Participant;
use App\Models\ScoreEntry;
use App\Support\RealtimeBroadcaster;
use Illuminate\Support\Carbon;
use Livewire\Component;

class ScoreEntryForm extends Component
{
    public ?int $participant_id = null;

    public string $judge_name = '';

    public string $score = '';

    public string $remarks = '';

    public function save(): void
    {
        $validated = $this->validate([
            'participant_id' => ['required', 'exists:participants,id'],
            'judge_name' => ['required', 'string', 'max:120'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $score = ScoreEntry::create([
            'participant_id' => $validated['participant_id'],
            'judge_name' => $validated['judge_name'],
            'score' => $validated['score'],
            'remarks' => $validated['remarks'] ?? null,
            'submitted_at' => Carbon::now(),
        ]);

        RealtimeBroadcaster::dispatch(new ScoreUpdated($score));

        $this->reset(['participant_id', 'judge_name', 'score', 'remarks']);
        $this->dispatch('score-entry-saved');
        session()->flash('success', 'Nilai berhasil disimpan dan realtime dashboard langsung diperbarui.');
    }

    public function render()
    {
        return view('livewire.score-entry-form', [
            'participants' => Participant::query()
                ->with('category')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
