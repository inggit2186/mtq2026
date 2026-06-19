<?php

namespace App\Http\Controllers;

use App\Models\CompetitionCategory;
use App\Models\Participant;
use App\Models\ScoreCorrectionRequest;
use App\Models\ScoringSetting;
use App\Models\ScoreEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ScoreCorrectionController extends Controller
{
    /**
     * Display list of score correction requests
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'competition_category_id' => ['nullable', 'integer'],
            'judging_round' => ['nullable', 'string'],
        ]);

        $query = ScoreCorrectionRequest::with(['participant', 'category', 'requestedBy'])
            ->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['competition_category_id'])) {
            $query->where('competition_category_id', $filters['competition_category_id']);
        }

        if (!empty($filters['judging_round'])) {
            $query->where('judging_round', $filters['judging_round']);
        }

        $requests = $query->get();
        $categories = CompetitionCategory::query()
            ->orderBy('branch')
            ->orderBy('sort_order')
            ->get();

        $stats = [
            'pending' => ScoreCorrectionRequest::where('status', 'pending')->count(),
            'approved' => ScoreCorrectionRequest::where('status', 'approved')->count(),
            'rejected' => ScoreCorrectionRequest::where('status', 'rejected')->count(),
        ];

        return view('pages.admin.score-corrections', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel(auth()->user()?->role),
            'requests' => $requests,
            'categories' => $categories,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    /**
     * Show details of a correction request
     */
    public function show(ScoreCorrectionRequest $correction): View
    {
        $correction->load(['participant', 'category', 'requestedBy']);

        // Get current scores for comparison
        $currentScores = $correction->participant->scores
            ->where('judging_round', $correction->judging_round)
            ->first();

        // Get scoring criteria
        $scoringSetting = ScoringSetting::forCategory($correction->competition_category_id);
        $criteria = $scoringSetting?->scoring_points
            ?? config('scoring.criteria.'.($correction->category?->branch ?? ''), []);

        return view('pages.admin.score-correction-detail', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel(auth()->user()?->role),
            'correction' => $correction,
            'currentScores' => $currentScores,
            'criteria' => $criteria,
        ]);
    }

    /**
     * Approve a correction request and update the score
     */
    public function approve(ScoreCorrectionRequest $correction): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        if ($correction->status !== 'pending') {
            return redirect()
                ->route('admin.score-corrections.index')
                ->with('error', 'Request ini sudah diproses sebelumnya.');
        }

        $correction->load('participant');

        // Get existing score entry or create new one
        $scoreEntry = ScoreEntry::where('participant_id', $correction->participant_id)
            ->where('judging_round', $correction->judging_round)
            ->first();

        if ($scoreEntry) {
            // Update existing entry
            $requestedScores = $correction->requested_scores ?? [];
            $judgeName = $correction->requestedBy?->name ?? 'Admin';
            $judgeId = $correction->requestedBy?->id;

            // Build new scores structure with single judge
            $newScores = [
                $judgeName => [
                    'judge_id' => $judgeId,
                    'breakdown' => $requestedScores,
                    'score' => array_sum($requestedScores),
                    'submitted_at' => now()->toIso8601String(),
                ],
            ];

            // Calculate new average
            $totalScore = 0;
            $judgeCount = 0;
            foreach ($newScores as $judgeData) {
                $totalScore += $judgeData['score'];
                $judgeCount++;
            }
            $averageScore = $judgeCount > 0 ? $totalScore / $judgeCount : 0;

            $scoreEntry->update([
                'scores' => $newScores,
                'score_breakdown' => $requestedScores,
                'score' => array_sum($requestedScores),
                'average_score' => $averageScore,
                'remarks' => $correction->requested_remarks,
                'submitted_at' => now(),
            ]);
        }

        $correction->update(['status' => 'approved']);

        return redirect()
            ->route('admin.score-corrections.index')
            ->with('success', 'Perbaikan nilai berhasil disetujui dan diterapkan.');
    }

    /**
     * Reject a correction request
     */
    public function reject(ScoreCorrectionRequest $correction): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        if ($correction->status !== 'pending') {
            return redirect()
                ->route('admin.score-corrections.index')
                ->with('error', 'Request ini sudah diproses sebelumnya.');
        }

        $correction->update(['status' => 'rejected']);

        return redirect()
            ->route('admin.score-corrections.index')
            ->with('success', 'Request perbaikan nilai ditolak.');
    }

    /**
     * Reset correction request back to pending
     */
    public function reset(ScoreCorrectionRequest $correction): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $correction->update(['status' => 'pending']);

        return redirect()
            ->route('admin.score-corrections.index')
            ->with('success', 'Request dikembalikan ke status pending.');
    }
}
