<?php

namespace App\Http\Controllers;

use App\Models\CompetitionCategory;
use App\Models\Finalist;
use App\Models\Participant;
use App\Models\ScoreEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinalistController extends Controller
{
    /**
     * Display the finalists management page.
     */
    public function index(Request $request)
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $categoryId = $request->get('category_id');

        // Get all categories (excluding MFQ which uses different system)
        $categories = CompetitionCategory::query()
            ->whereNotIn('id', [24, 25]) // Exclude MFQ categories
            ->orderBy('branch')
            ->orderBy('name')
            ->get();

        // Get finalists with optional filtering
        $finalistsQuery = Finalist::with(['participant.district', 'competitionCategory'])
            ->orderBy('competition_category_id')
            ->orderBy('gender')
            ->orderBy('finalist_rank');

        if ($categoryId) {
            $finalistsQuery->where('competition_category_id', $categoryId);
        }

        $finalists = $finalistsQuery->get();

        // Group finalists by category and gender
        $groupedFinalists = $finalists->groupBy(fn ($f) => $f->competition_category_id)
            ->map(fn ($catFinalists) => $catFinalists->groupBy('gender'));

        // Get existing finalist info
        $existingFinalists = Finalist::select('competition_category_id', 'gender')
            ->distinct()
            ->get()
            ->groupBy('competition_category_id')
            ->map(fn ($items) => $items->pluck('gender')->toArray());

        return view('pages.finalists', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'categories' => $categories,
            'groupedFinalists' => $groupedFinalists,
            'existingFinalists' => $existingFinalists,
            'selectedCategoryId' => $categoryId,
        ]);
    }

    /**
     * Print/Download finalists as PDF (HTML Print Friendly).
     */
    public function print(): \Illuminate\Http\Response
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        // Get all categories with finalists
        $categories = CompetitionCategory::query()
            ->whereNotIn('id', [24, 25])
            ->orderBy('id') // Sort by category ID
            ->get();

        $finalists = Finalist::with(['participant.district', 'competitionCategory'])
            ->orderBy('competition_category_id')
            ->orderBy('gender')
            ->orderBy('finalist_rank')
            ->get();

        // Group by category
        $groupedFinalists = $finalists->groupBy('competition_category_id')
            ->map(fn ($catFinalists) => $catFinalists->groupBy('gender'));

        $html = view('pages.finalists-print', [
            'categories' => $categories,
            'groupedFinalists' => $groupedFinalists,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ])->render();

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Generate finalists for a specific category.
     * Takes top 3 from each gender (putra and putri).
     */
    public function generate(Request $request, int $categoryId): JsonResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $category = CompetitionCategory::findOrFail($categoryId);

        // Exclude MFQ categories
        if (in_array($categoryId, [24, 25])) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori MFQ tidak menggunakan sistem finalis yang sama.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Get all verified participants for this category with their scores
            $participants = Participant::with(['scores', 'district'])
                ->where('competition_category_id', $categoryId)
                ->where('verification_status', 'verified')
                ->get();

            if ($participants->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada peserta terverifikasi untuk golongan ini.',
                ], 400);
            }

            // Clear existing finalists for this category
            Finalist::where('competition_category_id', $categoryId)->delete();

            $createdFinalists = [];

            // Process each gender separately
            foreach ([Finalist::GENDER_MALE, Finalist::GENDER_FEMALE] as $gender) {
                $genderParticipants = $participants->filter(
                    fn ($p) => $p->gender === $gender
                );

                if ($genderParticipants->isEmpty()) {
                    continue;
                }

                // Calculate rankings for this gender
                $rankings = $this->calculateRankings($genderParticipants);

                // Take top 3
                $topThree = $rankings->take(3);

                foreach ($topThree as $rank => $data) {
                    $finalist = Finalist::create([
                        'participant_id' => $data['participant_id'],
                        'competition_category_id' => $categoryId,
                        'gender' => $gender,
                        'finalist_rank' => $rank + 1,
                        'score' => $data['score'],
                        'round' => $data['round'],
                        'status' => Finalist::STATUS_PENDING,
                    ]);

                    $createdFinalists[] = [
                        'id' => $finalist->id,
                        'participant_id' => $finalist->participant_id,
                        'participant_name' => $data['name'],
                        'district' => $data['district'],
                        'gender' => $gender,
                        'rank' => $rank + 1,
                        'score' => $data['score'],
                        'round' => $data['round'],
                    ];
                }
            }

            DB::commit();

            Log::info('Finalists generated', [
                'category_id' => $categoryId,
                'category_name' => $category->name,
                'finalist_count' => count($createdFinalists),
                'generated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => count($createdFinalists) . ' finalis berhasil dibuat untuk ' . $category->name,
                'data' => $createdFinalists,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to generate finalists', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat finalis: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate finalists for ALL categories at once.
     */
    public function generateAll(Request $request): JsonResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        try {
            DB::beginTransaction();

            // Get all non-MFQ categories
            $categories = CompetitionCategory::query()
                ->whereNotIn('id', [24, 25])
                ->orderBy('branch')
                ->orderBy('name')
                ->get();

            $allResults = [];
            $totalFinalists = 0;

            foreach ($categories as $category) {
                // Get all verified participants for this category with their scores
                $participants = Participant::with(['scores', 'district'])
                    ->where('competition_category_id', $category->id)
                    ->where('verification_status', 'verified')
                    ->get();

                if ($participants->isEmpty()) {
                    continue;
                }

                // Clear existing finalists for this category
                Finalist::where('competition_category_id', $category->id)->delete();

                $categoryResults = [];

                foreach ([Finalist::GENDER_MALE, Finalist::GENDER_FEMALE] as $gender) {
                    $genderParticipants = $participants->filter(
                        fn ($p) => $p->gender === $gender
                    );

                    if ($genderParticipants->isEmpty()) {
                        continue;
                    }

                    $rankings = $this->calculateRankings($genderParticipants);
                    $topThree = $rankings->take(3);

                    foreach ($topThree as $rank => $data) {
                        $finalist = Finalist::create([
                            'participant_id' => $data['participant_id'],
                            'competition_category_id' => $category->id,
                            'gender' => $gender,
                            'finalist_rank' => $rank + 1,
                            'score' => $data['score'],
                            'round' => $data['round'],
                            'status' => Finalist::STATUS_PENDING,
                        ]);

                        $categoryResults[] = [
                            'participant_id' => $finalist->participant_id,
                            'participant_name' => $data['name'],
                            'gender' => $gender,
                            'rank' => $rank + 1,
                        ];
                    }
                }

                if (!empty($categoryResults)) {
                    $allResults[$category->id] = [
                        'category_name' => $category->name,
                        'branch' => $category->branch,
                        'finalists' => $categoryResults,
                    ];
                    $totalFinalists += count($categoryResults);
                }
            }

            DB::commit();

            Log::info('All finalists generated', [
                'category_count' => count($allResults),
                'total_finalists' => $totalFinalists,
                'generated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $totalFinalists . ' finalis berhasil dibuat untuk ' . count($allResults) . ' golongan',
                'data' => $allResults,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to generate all finalists', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat finalis: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update finalist status (e.g., scratch).
     */
    public function updateStatus(Request $request, Finalist $finalist): JsonResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $validated = $request->validate([
            'status' => 'required|in:pending,active,completed,scratched',
            'notes' => 'nullable|string|max:500',
        ]);

        $finalist->update($validated);

        Log::info('Finalist status updated', [
            'finalist_id' => $finalist->id,
            'new_status' => $validated['status'],
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status finalis berhasil diperbarui',
        ]);
    }

    /**
     * Delete all finalists for a category.
     */
    public function destroy(int $categoryId): JsonResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $category = CompetitionCategory::findOrFail($categoryId);
        $count = Finalist::where('competition_category_id', $categoryId)->count();

        Finalist::where('competition_category_id', $categoryId)->delete();

        Log::info('Finalists deleted', [
            'category_id' => $categoryId,
            'category_name' => $category->name,
            'count_deleted' => $count,
            'deleted_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $count . ' finalis berhasil dihapus untuk ' . $category->name,
        ]);
    }

    /**
     * Get finalists for a specific category (API endpoint).
     */
    public function getByCategory(int $categoryId): JsonResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $finalists = Finalist::with(['participant.district', 'competitionCategory'])
            ->where('competition_category_id', $categoryId)
            ->orderBy('gender')
            ->orderBy('finalist_rank')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $finalists,
        ]);
    }

    /**
     * Calculate rankings for a collection of participants.
     * Uses the same logic as leaderboard: Final scores > Penyisihan scores.
     * Tie-breaker: average score, then latest score, then name.
     */
    private function calculateRankings($participants): \Illuminate\Support\Collection
    {
        $rows = collect($participants)
            ->map(function (Participant $participant): ?array {
                $penyisihanScores = $participant->scores->filter(
                    fn (ScoreEntry $entry) => (string) $entry->judging_round === 'Penyisihan'
                );
                $finalScores = $participant->scores->filter(
                    fn (ScoreEntry $entry) => (string) $entry->judging_round === 'Final'
                );

                $hasFinal = $finalScores->isNotEmpty();
                $hasPenyisihan = $penyisihanScores->isNotEmpty();

                if (!$hasFinal && !$hasPenyisihan) {
                    return null;
                }

                // Get score based on availability (Final preferred)
                $scoreValue = 0;
                $round = 'Penyisihan';

                if ($hasFinal) {
                    $latestFinal = $finalScores->sortByDesc('submitted_at')->first();
                    $scoreValue = (float) ($latestFinal->score ?? 0);
                    $round = 'Final';
                } elseif ($hasPenyisihan) {
                    $latestPenyisihan = $penyisihanScores->sortByDesc('submitted_at')->first();
                    $scoreValue = (float) ($latestPenyisihan->score ?? 0);
                }

                return [
                    'participant_id' => $participant->id,
                    'name' => $participant->name,
                    'district' => $participant->district?->name ?? '-',
                    'score' => $scoreValue,
                    'round' => $round,
                    'has_final' => $hasFinal,
                    'latest_score' => $scoreValue,
                ];
            })
            ->filter()
            ->values()
            ->all();

        // Sort: Final participants first (by score), then Penyisihan-only (by score)
        usort($rows, function (array $left, array $right): int {
            // Final participants always above Penyisihan-only
            if ($left['has_final'] && !$right['has_final']) {
                return -1;
            }
            if (!$left['has_final'] && $right['has_final']) {
                return 1;
            }

            // Both have same status, sort by score
            $scoreComparison = $right['score'] <=> $left['score'];
            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }

            return strcmp((string) $left['name'], (string) $right['name']);
        });

        return collect($rows);
    }
}
