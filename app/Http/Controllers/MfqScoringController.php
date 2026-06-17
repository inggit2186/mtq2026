<?php

namespace App\Http\Controllers;

use App\Events\ScoreUpdated;
use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\Hakim;
use App\Models\MfqSession;
use App\Models\Participant;
use App\Models\ScoreEntry;
use App\Support\ActivityLogger;
use App\Support\RealtimeBroadcaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MfqScoringController extends Controller
{
    protected const JUDGING_ROUNDS = ['Penyisihan', 'Final'];

    protected const SCORE_MIN = 0;

    protected const SCORE_MAX = 100;

    protected const MIN_DISTRICTS = 2;

    protected const MAX_DISTRICTS = 5;

    public function index(Request $request): View
    {
        $user = auth()->user();
        $mfqCategories = $this->getMfqCategories($user);
        $mfqCategoryIds = $mfqCategories->pluck('id')->toArray();

        // Get active session from URL or session
        $sessionId = $request->query('session_id');
        $activeSession = null;
        $currentStep = 1;

        if ($sessionId) {
            $activeSession = MfqSession::with('category')
                ->find($sessionId);
            if ($activeSession && $activeSession->status === 'active') {
                // Determine step based on session state
                if (! empty($activeSession->district_ids)) {
                    $currentStep = 3; // Step 3: Scoring
                } else {
                    $currentStep = 2; // Step 2: Select Districts
                }
            }
        }

        // Get sessions for sidebar
        $sessions = MfqSession::with(['category', 'creator'])
            ->whereIn('competition_category_id', $mfqCategoryIds ?: [0])
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Get participants grouped by district
        $categoryId = $request->query('competition_category_id')
            ?? ($activeSession?->competition_category_id);
        $selectedCategory = null;
        $participantsByDistrict = collect();
        $districts = collect();

        if ($categoryId) {
            $selectedCategory = CompetitionCategory::find($categoryId);
            $participants = Participant::with(['district', 'category'])
                ->where('competition_category_id', $categoryId)
                ->where('verification_status', 'verified')
                ->orderBy('district_id')
                ->orderBy('name')
                ->get();

            $participantsByDistrict = $participants->groupBy(fn ($p) => (int) $p->district_id);
            $districts = District::whereIn('id', $participantsByDistrict->keys())
                ->orderBy('name')
                ->get()
                ->keyBy('id');
        }

        $summaryStats = [
            'participant_total' => $categoryId
                ? $participants->count() ?? 0
                : Participant::whereIn('competition_category_id', $mfqCategoryIds ?: [0])
                    ->where('verification_status', 'verified')
                    ->count(),
            'category_total' => $mfqCategories->count(),
            'session_active' => $sessions->count(),
        ];

        // Get available judges for the modal
        $availableJudges = Hakim::all()->map(fn ($h) => [
            'id' => $h->id,
            'nama' => $h->nama,
            'asal' => $h->asal,
        ])->values()->all();

        // Get category-specific judge IDs and names for default selection
        $categoryJudgeIds = [];
        $defaultJudges = [];
        if ($selectedCategory) {
            $categoryJudges = Hakim::byGolongan($selectedCategory->id)->get();
            $categoryJudgeIds = $categoryJudges->pluck('id')->toArray();
            $defaultJudges = $categoryJudges->pluck('nama')->values()->toArray();
        }

        // Fallback to current user name if no category judges
        if (empty($defaultJudges)) {
            $defaultJudges = [$user?->name ?? ''];
        }

        return view('pages.scoring-mfq-new', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'navigation' => app(PageController::class)->consoleNavigation((string) auth()->user()?->role, 'scoring.mfq'),
            'mfqCategories' => $mfqCategories,
            'selectedCategory' => $selectedCategory,
            'participantsByDistrict' => $participantsByDistrict,
            'districts' => $districts,
            'sessions' => $sessions,
            'activeSession' => $activeSession,
            'currentStep' => $currentStep,
            'summaryStats' => $summaryStats,
            'user' => $user,
            'availableJudges' => $availableJudges,
            'categoryJudgeIds' => $categoryJudgeIds,
            'defaultJudges' => $defaultJudges,
        ]);
    }

    public function storeSession(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'competition_category_id' => ['required', 'exists:competition_categories,id'],
            'round' => ['required', 'string', Rule::in(self::JUDGING_ROUNDS)],
            'judges' => ['required', 'array', 'min:1'],
            'judges.*' => ['nullable', 'string', 'max:80'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        // Validate judges array - filter empty values
        $judges = array_values(array_filter(array_map('trim', $validated['judges'])));
        if (count($judges) < 1) {
            throw ValidationException::withMessages([
                'judges' => 'Minimal harus ada 1 hakim.',
            ]);
        }

        $category = CompetitionCategory::findOrFail($validated['competition_category_id']);
        $this->ensureMfqCategory($category);

        $session = MfqSession::create([
            'name' => trim($validated['name']),
            'competition_category_id' => $validated['competition_category_id'],
            'round' => $validated['round'],
            'judges' => $judges,
            'district_ids' => [],
            'status' => 'active',
            'remarks' => filled($validated['remarks'] ?? null) ? trim($validated['remarks']) : null,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('scoring.mfq', [
                'session_id' => $session->id,
                'competition_category_id' => $validated['competition_category_id'],
            ])
            ->with('status', 'Sesi MFQ berhasil dibuat. Lanjut ke pemilihan kecamatan.');
    }

    public function selectDistricts(Request $request, int $sessionId): RedirectResponse
    {
        $session = MfqSession::findOrFail($sessionId);

        if ($session->status !== 'active') {
            throw ValidationException::withMessages([
                'session' => 'Sesi ini sudah tidak aktif.',
            ]);
        }

        $validated = $request->validate([
            'district_ids' => ['required', 'array', 'min:'.self::MIN_DISTRICTS, 'max:'.self::MAX_DISTRICTS],
            'district_ids.*' => ['required', 'integer', 'exists:districts,id'],
        ]);

        // Verify all districts have verified participants in this category
        $participants = Participant::where('competition_category_id', $session->competition_category_id)
            ->whereIn('district_id', $validated['district_ids'])
            ->where('verification_status', 'verified')
            ->get();

        $districtsWithParticipants = $participants->pluck('district_id')->unique()->count();

        if ($districtsWithParticipants < self::MIN_DISTRICTS) {
            throw ValidationException::withMessages([
                'district_ids' => 'Setiap kecamatan yang dipilih harus memiliki minimal 1 peserta terverifikasi.',
            ]);
        }

        $session->update([
            'district_ids' => $validated['district_ids'],
        ]);

        return redirect()
            ->route('scoring.mfq', [
                'session_id' => $session->id,
                'step' => 'scoring',
                'competition_category_id' => $session->competition_category_id,
            ])
            ->with('status', 'Kecamatan berhasil dipilih. Lanjut ke input nilai.');
    }

    public function scoring(Request $request, int $sessionId): View
    {
        $session = MfqSession::with(['category', 'creator'])->findOrFail($sessionId);
        $user = auth()->user();

        if ($session->status !== 'active') {
            return redirect()
                ->route('scoring.mfq')
                ->with('error', 'Sesi ini sudah tidak aktif.');
        }

        $districtIds = $session->district_ids ?? [];
        if (empty($districtIds)) {
            return redirect()
                ->route('scoring.mfq', ['session_id' => $session->id])
                ->with('error', 'Pilih kecamatan terlebih dahulu.');
        }

        $participants = Participant::with(['district', 'category'])
            ->where('competition_category_id', $session->competition_category_id)
            ->whereIn('district_id', $districtIds)
            ->where('verification_status', 'verified')
            ->get();

        $districts = District::whereIn('id', $districtIds)->get()->keyBy('id');

        // Get all scores for this session
        $existingScores = ScoreEntry::whereIn('participant_id', $participants->pluck('id'))
            ->get()
            ->groupBy('participant_id');

        // Build district cards with scores
        $districtCards = collect($districtIds)->map(function ($districtId) use ($districts, $participants, $existingScores) {
            $district = $districts->get($districtId);
            $districtParticipants = $participants->where('district_id', $districtId);

            if ($districtParticipants->isEmpty()) {
                return null;
            }

            $representative = $districtParticipants->first();
            $scores = $existingScores->get($representative->id, collect());

            return [
                'district_id' => $districtId,
                'district_name' => $district?->name ?? 'Tanpa Kecamatan',
                'participant_count' => $districtParticipants->count(),
                'representative' => [
                    'id' => $representative->id,
                    'name' => $representative->name,
                    'registration_number' => $representative->registration_number,
                    'lot_number' => $representative->lot_number,
                ],
                'total_score' => $scores->sum('score'),
                'judge_count' => $scores->count(),
            ];
        })->filter()->values();

        // Build judges list
        $judges = $session->judges ?? [];

        return view('pages.scoring-mfq-scoring', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'navigation' => app(PageController::class)->consoleNavigation((string) auth()->user()?->role, 'scoring.mfq'),
            'session' => $session,
            'districtCards' => $districtCards,
            'judges' => $judges,
            'districts' => $districts,
            'participants' => $participants,
            'summaryStats' => [
                'total_districts' => count($districtIds),
                'total_participants' => $participants->count(),
                'total_score_entries' => $existingScores->flatten()->count(),
            ],
            'user' => $user,
        ]);
    }

    public function storeScore(Request $request, int $sessionId): RedirectResponse
    {
        $session = MfqSession::with('category')->findOrFail($sessionId);

        if ($session->status !== 'active') {
            throw ValidationException::withMessages([
                'session' => 'Sesi ini sudah tidak aktif.',
            ]);
        }

        $validated = $request->validate([
            'participant_id' => ['required', 'exists:participants,id'],
            'judge_name' => ['required', 'string', 'max:80'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.label' => ['required', 'string'],
            'questions.*.package_score' => ['nullable', 'integer', 'min:'.self::SCORE_MIN, 'max:'.self::SCORE_MAX],
            'questions.*.throw_scores' => ['nullable', 'array'],
            'questions.*.throw_scores.*' => ['nullable', 'integer', 'min:'.self::SCORE_MIN, 'max:'.self::SCORE_MAX],
            'questions.*.rebuttal_score' => ['nullable', 'integer', 'min:'.self::SCORE_MIN, 'max:'.self::SCORE_MAX],
            'questions.*.notes' => ['nullable', 'string', 'max:200'],
        ]);

        $participant = Participant::with('category')->findOrFail($validated['participant_id']);

        // Verify participant belongs to this session's districts
        if (! in_array($participant->district_id, $session->district_ids ?? [])) {
            throw ValidationException::withMessages([
                'participant_id' => 'Peserta tidak termasuk dalam sesi ini.',
            ]);
        }

        // Calculate totals
        $questions = [];
        $totalScore = 0;
        $packageTotal = 0;
        $throwTotals = [0, 0];
        $rebuttalTotal = 0;

        foreach ($validated['questions'] as $index => $q) {
            $package = (int) ($q['package_score'] ?? 0);
            $throwScores = array_values(array_map('intval', $q['throw_scores'] ?? []));
            $rebuttal = (int) ($q['rebuttal_score'] ?? 0);

            // Ensure 2 throw columns
            while (count($throwScores) < 2) {
                $throwScores[] = 0;
            }
            $throwScores = array_slice($throwScores, 0, 2);

            $rowTotal = $package + array_sum($throwScores) + $rebuttal;
            $totalScore += $rowTotal;
            $packageTotal += $package;
            foreach ($throwScores as $i => $t) {
                $throwTotals[$i] += $t;
            }
            $rebuttalTotal += $rebuttal;

            $questions[] = [
                'order' => $index + 1,
                'label' => $q['label'],
                'package_score' => $package,
                'throw_scores' => $throwScores,
                'rebuttal_score' => $rebuttal,
                'row_total' => $rowTotal,
                'notes' => $q['notes'] ?? null,
            ];
        }

        // Store score entry
        $scoreEntry = ScoreEntry::create([
            'participant_id' => $participant->id,
            'judge_name' => $validated['judge_name'],
            'score' => $totalScore,
            'scores' => [
                'session_id' => $session->id,
                'session_name' => $session->name,
                'round' => $session->round,
                'category' => $session->category?->branch.' - '.$session->category?->name,
                'participant_name' => $participant->name,
                'district_name' => $participant->district?->name,
            ],
            'score_breakdown' => [
                'type' => 'MFQ',
                'summary' => [
                    'total_questions' => count($questions),
                    'total_score' => $totalScore,
                    'package_total' => $packageTotal,
                    'throw_totals' => $throwTotals,
                    'rebuttal_total' => $rebuttalTotal,
                ],
                'questions' => $questions,
            ],
            'submitted_at' => Carbon::now(),
        ]);

        RealtimeBroadcaster::dispatch(new ScoreUpdated($scoreEntry));

        ActivityLogger::log(
            'scoring.mfq.score_created',
            auth()->user()?->name.' menyimpan nilai MFQ untuk '.$participant->name,
            $participant,
            [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'session_id' => $session->id,
                'total_score' => $totalScore,
            ]
        );

        return redirect()
            ->back()
            ->with('status', 'Nilai untuk '.$participant->name.' berhasil disimpan.');
    }

    public function completeSession(Request $request, int $sessionId): RedirectResponse
    {
        $session = MfqSession::findOrFail($sessionId);

        if ($session->status !== 'active') {
            throw ValidationException::withMessages([
                'session' => 'Sesi ini sudah tidak aktif.',
            ]);
        }

        $session->update(['status' => 'completed']);

        return redirect()
            ->route('scoring.mfq')
            ->with('status', 'Sesi MFQ berhasil diselesaikan.');
    }

    public function destroySession(int $sessionId): RedirectResponse
    {
        $session = MfqSession::findOrFail($sessionId);

        if ($session->status === 'completed') {
            throw ValidationException::withMessages([
                'session' => 'Sesi yang sudah selesai tidak dapat dihapus.',
            ]);
        }

        // Delete related scores
        $districtIds = $session->district_ids ?? [];
        if (! empty($districtIds)) {
            $participantIds = Participant::where('competition_category_id', $session->competition_category_id)
                ->whereIn('district_id', $districtIds)
                ->pluck('id');

            ScoreEntry::whereIn('participant_id', $participantIds)
                ->whereJsonContains('scores->session_id', $session->id)
                ->delete();
        }

        $session->delete();

        return redirect()
            ->route('scoring.mfq')
            ->with('status', 'Sesi MFQ berhasil dihapus.');
    }

    // Helper methods

    protected function getMfqCategories($user): Collection
    {
        $query = CompetitionCategory::query()
            ->where(function ($q): void {
                $q->whereRaw('LOWER(branch) like ?', ['%fahmil%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%fahmil%'])
                    ->orWhere('maqra_system_type', 'fahmil');
            })
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name');

        if ($user?->role === 'panitia') {
            $restrictedIds = $user->accessibleCategoryIds();
            if (! empty($restrictedIds)) {
                $query->whereIn('id', $restrictedIds);
            }
        }

        return $query->get();
    }

    protected function ensureMfqCategory(CompetitionCategory $category): void
    {
        // Check maqra_system_type first
        if (filled($category->maqra_system_type)) {
            if ($category->maqra_system_type === 'fahmil') {
                return;
            }
            throw ValidationException::withMessages([
                'competition_category_id' => 'Golongan yang dipilih bukan cabang MFQ.',
            ]);
        }

        // Fallback: string matching
        $haystack = mb_strtolower(trim($category->branch.' '.$category->name.' '.$category->slug));
        if (! str_contains($haystack, 'fahmil')) {
            throw ValidationException::withMessages([
                'competition_category_id' => 'Golongan yang dipilih bukan cabang MFQ.',
            ]);
        }
    }
}
