<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\CompetitionCategory;
use App\Models\Participant;
use App\Models\SessionSchedule;
use App\Models\ScoreEntry;
use Carbon\Carbon;
use Livewire\Component;

class DashboardPage extends Component
{
    public array $stats = [];

    public array $leaders = [];

    public array $schedules = [];

    public array $announcements = [];

    public function mount(): void
    {
        $this->loadDashboard();
    }

    public function refreshDashboard(): void
    {
        $this->loadDashboard();
    }

    protected function loadDashboard(): void
    {
        $today = Carbon::today();

        $this->stats = [
            'participants' => Participant::count(),
            'categories' => CompetitionCategory::count(),
            'todaySessions' => SessionSchedule::whereDate('starts_at', $today)->count(),
            'averageScore' => round((float) (ScoreEntry::avg('score') ?? 0), 2),
        ];

        $this->leaders = Participant::query()
            ->with(['category', 'scores'])
            ->get()
            ->map(function (Participant $participant): array {
                $latestScore = $participant->scores->sortByDesc('submitted_at')->first();
                $average = round((float) ($participant->scores->avg('score') ?? 0), 2);

                return [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'institution' => $participant->institution,
                    'category' => $participant->category?->name ?? '-',
                    'score' => $latestScore ? (float) $latestScore->score : 0,
                    'average' => $average,
                    'status' => $participant->status,
                ];
            })
            ->sortByDesc('average')
            ->values()
            ->take(6)
            ->all();

        $this->schedules = SessionSchedule::query()
            ->orderBy('starts_at')
            ->limit(5)
            ->get()
            ->map(fn (SessionSchedule $schedule): array => [
                'title' => $schedule->title,
                'stage' => $schedule->stage,
                'venue' => $schedule->venue,
                'starts_at' => $schedule->starts_at?->format('H:i'),
                'ends_at' => $schedule->ends_at?->format('H:i'),
                'status' => $schedule->status,
            ])
            ->all();

        $this->announcements = Announcement::query()
            ->latest('published_at')
            ->limit(4)
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'title' => $announcement->title,
                'body' => $announcement->body,
                'priority' => $announcement->priority,
                'published_at' => $announcement->published_at?->diffForHumans(),
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.dashboard-page');
    }
}
