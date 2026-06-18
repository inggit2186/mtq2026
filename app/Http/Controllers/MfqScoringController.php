<?php

namespace App\Http\Controllers;

use App\Events\ScoreUpdated;
use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\Hakim;
use App\Models\MfqDraft;
use App\Models\MfqResult;
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

    protected const SCORE_MAX = 50000;

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

            \Log::info($sessions->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category?->branch.' - '.$s->category?->name,
                'round' => $s->round,
                'district_ids' => $s->district_ids,
                'status' => $s->status,
            ]));
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

        // Get completed sessions and rankings for the selected category
        $completedSessionsByRound = ['Penyisihan' => collect(), 'Final' => collect()];
        $rankingsDataByRound = ['Penyisihan' => collect(), 'Final' => collect()];
        $displayedLotNumbers = []; // Lot numbers that have been displayed in previous sessions

        if ($categoryId) {
            $allCompletedSessions = MfqSession::with(['category', 'creator'])
                ->where('competition_category_id', $categoryId)
                ->where('status', 'completed')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            // Group by round
            foreach ($allCompletedSessions as $session) {
                $round = $session->round ?? 'Penyisihan';
                if (!isset($completedSessionsByRound[$round])) {
                    $completedSessionsByRound[$round] = collect();
                }
                $completedSessionsByRound[$round]->push($session);
            }

            // Get lot numbers that have been displayed in previous sessions of the same round
            // This is used to show "Sudah Tampil" badge on districts that have been in this round
            if ($activeSession && $activeSession->round && $categoryId) {
                $previousSessions = MfqSession::where('competition_category_id', $categoryId)
                    ->where('status', 'completed')
                    ->where('round', $activeSession->round)
                    ->where('id', '!=', $activeSession->id)
                    ->get();

                $previousDistrictIds = $previousSessions->pluck('district_ids')->flatten()->unique()->toArray();

                \Log::info('MFQ Index - Previous sessions in round ' . $activeSession->round . ': ' . $previousSessions->count());
                \Log::info('MFQ Index - Previous district IDs: ' . json_encode($previousDistrictIds));

                if (! empty($previousDistrictIds)) {
                    $displayedParticipants = Participant::whereIn('district_id', $previousDistrictIds)
                        ->whereNotNull('lot_number')
                        ->where('lot_number', '!=', '')
                        ->pluck('lot_number')
                        ->unique()
                        ->toArray();

                    $displayedLotNumbers = $displayedParticipants;
                    \Log::info('MFQ Index - Displayed lot numbers: ' . json_encode($displayedLotNumbers));
                }
            }
            
            // Build rankings by district/lot number for each round
            foreach ($completedSessionsByRound as $round => $roundSessions) {
                if ($roundSessions->isEmpty()) continue;

                $results = MfqResult::with(['participant.district', 'session'])
                    ->whereIn('mfq_session_id', $roundSessions->pluck('id'))
                    ->get();

                // Group by district
                $rankingsByDistrict = $results->groupBy(fn ($r) => $r->participant->district_id)->map(function ($districtResults) use ($round) {
                    $district = $districtResults->first()->participant->district;
                    $districtName = $district?->name ?? 'Tanpa Kecamatan';

                    // Get all lot numbers in this district across sessions
                    $lotNumbers = $districtResults->map(fn ($r) => $r->participant->lot_number)->filter()->unique()->values();

                    // Get session names for display
                    $sessionNames = [];
                    $sessionPoints = [];
                    foreach ($districtResults->groupBy('mfq_session_id') as $sessionId => $sessionResults) {
                        $session = $sessionResults->first()->session;
                        $sessionNames[$sessionId] = $session?->name ?? 'Sesi ' . $sessionId;

                        // Get the rank for this district in this session
                        // All participants in same district have same rank, so take the first one
                        $rank = $sessionResults->first()->rank ?? 999;

                        // Calculate points based on rank (once per district per session)
                        $point = match ($rank) {
                            1 => 3,
                            2 => 2,
                            3 => 1,
                            default => 0,
                        };
                        $sessionPoints[$sessionId] = $point;
                    }

                    // Get best scores per session (take first entry since all have same score)
                    $sessionScores = [];
                    foreach ($districtResults->groupBy('mfq_session_id') as $sessionId => $sessionResults) {
                        $sessionScores[$sessionId] = $sessionResults->first()->total_score ?? 0;
                    }

                    return [
                        'district_id' => $district->id ?? 0,
                        'district_name' => $districtName,
                        'lot_numbers' => $lotNumbers->toArray(),
                        'session_names' => $sessionNames,
                        'session_points' => $sessionPoints,
                        'total_points' => array_sum($sessionPoints),
                        'session_scores' => $sessionScores,
                        'total_score' => array_sum($sessionScores),
                        'participant_count' => $districtResults->pluck('participant_id')->unique()->count(),
                    ];
                })->filter()->values();

                $rankingsDataByRound[$round] = $rankingsByDistrict;
            }
            
        }

        $completedSessions = $completedSessionsByRound;
        $rankingsData = $rankingsDataByRound;

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
            'activeRound' => $activeSession?->round,
            'currentStep' => $currentStep,
            'summaryStats' => $summaryStats,
            'user' => $user,
            'availableJudges' => $availableJudges,
            'categoryJudgeIds' => $categoryJudgeIds,
            'defaultJudges' => $defaultJudges,
            'completedSessions' => $completedSessions,
            'rankingsData' => $rankingsData,
            'displayedLotNumbers' => $displayedLotNumbers,
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

            // Find participant with lot_number first, fallback to first participant
            $representative = $districtParticipants->firstWhere('lot_number', '!=', null)
                ?? $districtParticipants->firstWhere('lot_number', '!=', '')
                ?? $districtParticipants->first();
            $scores = $existingScores->get($representative->id, collect());

            // Get photo URL for representative
            $photoUrl = null;
            if ($representative->document_photo && file_exists(public_path('storage/' . $representative->document_photo))) {
                $photoUrl = asset('storage/' . $representative->document_photo);
            }

            return [
                'district_id' => $districtId,
                'district_name' => $district?->name ?? 'Tanpa Kecamatan',
                'participant_count' => $districtParticipants->count(),
                'representative' => [
                    'id' => $representative->id,
                    'name' => $representative->name,
                    'registration_number' => $representative->registration_number,
                    'lot_number' => $representative->lot_number,
                    'photo_url' => $photoUrl,
                ],
                'total_score' => $scores->sum('score'),
                'judge_count' => $scores->count(),
            ];
        })->filter()->values();

        // Build judges list
        $judges = $session->judges ?? [];

        // Group participants by district with photo URLs
        $participantsByDistrict = $participants->groupBy('district_id')->map(function ($group) {
            return $group->map(function ($p) {
                $photoUrl = null;
                if ($p->document_photo && file_exists(public_path('storage/' . $p->document_photo))) {
                    $photoUrl = asset('storage/' . $p->document_photo);
                }
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'lot_number' => $p->lot_number,
                    'photo_url' => $photoUrl,
                ];
            })->values()->toArray();
        })->toArray();

        return view('pages.scoring-mfq-scoring', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'navigation' => app(PageController::class)->consoleNavigation((string) auth()->user()?->role, 'scoring.mfq'),
            'session' => $session,
            'districtCards' => $districtCards,
            'judges' => $judges,
            'districts' => $districts,
            'participants' => $participants,
            'participantsByDistrictJson' => json_encode($participantsByDistrict),
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
        \Log::info('MFQ StoreScore - Called with sessionId: ' . $sessionId);
        \Log::info('MFQ StoreScore - Request data: ' . json_encode($request->except('_token')));

        $session = MfqSession::with('category')->findOrFail($sessionId);
        \Log::info('MFQ StoreScore - Session found: ' . $session->name);

        if ($session->status !== 'active') {
            throw ValidationException::withMessages([
                'session' => 'Sesi ini sudah tidak aktif.',
            ]);
        }

        // Decode questions if it's a JSON string
        $questionsInput = $request->input('questions');
        if (is_string($questionsInput)) {
            $decoded = json_decode($questionsInput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['questions' => $decoded]);
            }
        }

        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('MFQ StoreScore - Validation failed: ' . json_encode($e->errors()));
            throw $e;
        }

        \Log::info('MFQ StoreScore - Validation passed');

        $participant = Participant::with('category')->findOrFail($validated['participant_id']);
        \Log::info('MFQ StoreScore - Participant: ' . $participant->name);

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

        // IDEMPOTENCY: Find existing score entry for this session + participant + judge
        // This prevents duplicate entries if the user submits multiple times
        $existingEntry = ScoreEntry::where('participant_id', $participant->id)
            ->where('judge_name', $validated['judge_name'])
            ->get()
            ->first(function ($entry) use ($sessionId) {
                $scores = $entry->scores;
                return is_array($scores) && ($scores['session_id'] ?? null) == $sessionId;
            });

        $scoreData = [
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
        ];

        try {
            if ($existingEntry) {
                // UPDATE existing entry (idempotent - safe to retry)
                $existingEntry->update($scoreData);
                $scoreEntry = $existingEntry;
                \Log::info('MFQ StoreScore - ScoreEntry UPDATED with ID: ' . $scoreEntry->id);
            } else {
                // CREATE new entry
                $scoreEntry = ScoreEntry::create($scoreData);
                \Log::info('MFQ StoreScore - ScoreEntry CREATED with ID: ' . $scoreEntry->id);
            }
        } catch (\Exception $e) {
            \Log::error('MFQ StoreScore - Failed to save ScoreEntry: ' . $e->getMessage());
            throw $e;
        }

        // Delete any draft for this entry
        MfqDraft::forEntry($sessionId, $participant->id, $validated['judge_name'])->delete();

        // Broadcast disabled - hanya untuk MFQ
        // RealtimeBroadcaster::dispatch(new ScoreUpdated($scoreEntry));

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

    /**
     * Submit district-level scores (called from scoring view)
     * Creates/updates mfq_results for all participants in the district
     * MFQ scores are per group/district, not individual
     */
    public function submitDistrictScore(Request $request, int $sessionId): \Illuminate\Http\JsonResponse
    {
        \Log::info('MFQ SubmitDistrictScore - Called with sessionId: ' . $sessionId);
        \Log::info('MFQ SubmitDistrictScore - Request data: ' . json_encode($request->except('_token')));
        \Log::info('MFQ SubmitDistrictScore - district_id: ' . $request->input('district_id'));
        \Log::info('MFQ SubmitDistrictScore - participant_ids raw: ' . $request->input('participant_ids'));
        \Log::info('MFQ SubmitDistrictScore - total_score: ' . $request->input('total_score'));

        $session = MfqSession::with('category')->findOrFail($sessionId);

        if ($session->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Sesi ini sudah tidak aktif.',
            ], 400);
        }

        // Decode JSON fields if sent as strings
        $participantIdsInput = $request->input('participant_ids');
        $scoresDetailInput = $request->input('scores_detail');

        if (is_string($participantIdsInput)) {
            $decoded = json_decode($participantIdsInput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['participant_ids' => $decoded]);
            }
        }

        if (is_string($scoresDetailInput)) {
            $decoded = json_decode($scoresDetailInput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['scores_detail' => $decoded]);
            }
        }

        try {
            $validated = $request->validate([
                'district_id' => ['required', 'integer', 'exists:districts,id'],
                'participant_ids' => ['required', 'array', 'min:1'],
                'participant_ids.*' => ['required', 'integer', 'exists:participants,id'],
                'total_score' => ['required', 'numeric', 'min:0'],
                'scores_detail' => ['required', 'array'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('MFQ SubmitDistrictScore - Validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        }

        \Log::info('MFQ SubmitDistrictScore - Validation passed');

        // Verify district belongs to this session
        if (! in_array($validated['district_id'], $session->district_ids ?? [])) {
            return response()->json([
                'success' => false,
                'message' => 'Kecamatan tidak termasuk dalam sesi ini.',
            ], 400);
        }

        $district = District::find($validated['district_id']);
        $savedCount = 0;

        // MFQ scores are per district/group, not individual
        // Save mfq_result for each participant in this district with the same district score
        foreach ($validated['participant_ids'] as $participantId) {
            $participant = Participant::find($participantId);
            if (! $participant) {
                \Log::warning('MFQ SubmitDistrictScore - Participant not found: ' . $participantId);
                continue;
            }

            // Verify participant belongs to this district and session
            if ((int) $participant->district_id !== (int) $validated['district_id']) {
                \Log::warning('MFQ SubmitDistrictScore - Participant ' . $participantId . ' does not belong to district ' . $validated['district_id']);
                continue;
            }

            // Check if this is the representative (first participant gets the main scores)
            $isRepresentative = ((int) $participantId === (int) $validated['participant_ids'][0]);

            // Build scores_detail for this participant
            $participantScoresDetail = $validated['scores_detail'];

            // If not representative, we still use the same district scores
            // but mark it as a team score
            if (! $isRepresentative) {
                $participantScoresDetail['is_team_score'] = true;
                $participantScoresDetail['representative_id'] = $validated['participant_ids'][0];
            }

            // Upsert mfq_result (idempotent - same total_score for all participants in district)
            // Rank will be calculated in completeSession
            try {
                $existingResult = MfqResult::where('mfq_session_id', $session->id)
                    ->where('participant_id', $participantId)
                    ->first();

                if ($existingResult) {
                    // Update existing result
                    $existingResult->update([
                        'district_id' => $validated['district_id'],
                        'total_score' => $validated['total_score'],
                        'scores_detail' => $participantScoresDetail,
                        // Don't update rank - will be calculated in completeSession
                    ]);
                    \Log::info('MFQ SubmitDistrictScore - Updated result for participant: ' . $participant->name . ' (ID: ' . $participantId . ')');
                } else {
                    // Create new result (rank will be calculated in completeSession)
                    MfqResult::create([
                        'mfq_session_id' => $session->id,
                        'participant_id' => $participantId,
                        'district_id' => $validated['district_id'],
                        'round' => $session->round,
                        'rank' => 0, // Will be calculated in completeSession
                        'total_score' => $validated['total_score'],
                        'scores_detail' => $participantScoresDetail,
                    ]);
                    \Log::info('MFQ SubmitDistrictScore - Created result for participant: ' . $participant->name . ' (ID: ' . $participantId . ')');
                }

                $savedCount++;
            } catch (\Exception $e) {
                \Log::error('MFQ SubmitDistrictScore - Failed to save for participant ' . $participantId . ': ' . $e->getMessage());
            }
        }

        \Log::info('MFQ SubmitDistrictScore - Total saved: ' . $savedCount);

        // Log activity
        ActivityLogger::log(
            'scoring.mfq.district_score_submitted',
            auth()->user()?->name.' menyimpan nilai MFQ untuk kecamatan ' . ($district?->name ?? $validated['district_id']),
            null,
            [
                'session_id' => $session->id,
                'district_id' => $validated['district_id'],
                'district_name' => $district?->name,
                'participant_count' => count($validated['participant_ids']),
                'total_score' => $validated['total_score'],
            ]
        );

        return response()->json([
            'success' => $savedCount > 0,
            'message' => $savedCount > 0
                ? "Nilai untuk {$savedCount} peserta di kecamatan {$district?->name} berhasil disimpan."
                : "Gagal menyimpan nilai. Pastikan participant_ids valid.",
            'saved_count' => $savedCount,
            'expected_count' => count($validated['participant_ids']),
        ]);
    }

    /**
     * Auto-save draft to server (for recovery if connection fails)
     */
    public function saveDraft(Request $request, int $sessionId): \Illuminate\Http\JsonResponse
    {
        $session = MfqSession::findOrFail($sessionId);

        if ($session->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Sesi sudah tidak aktif'], 400);
        }

        $validated = $request->validate([
            'participant_id' => ['required', 'exists:participants,id'],
            'judge_name' => ['required', 'string', 'max:80'],
            'questions' => ['required', 'array'],
            'totals' => ['required', 'array'],
        ]);

        $participant = Participant::findOrFail($validated['participant_id']);

        // Verify participant belongs to this session's districts
        if (! in_array($participant->district_id, $session->district_ids ?? [])) {
            return response()->json(['success' => false, 'message' => 'Peserta tidak termasuk dalam sesi ini'], 400);
        }

        // Upsert draft - idempotent based on unique constraint
        $draft = MfqDraft::updateOrCreate(
            [
                'mfq_session_id' => $sessionId,
                'participant_id' => $participant->id,
                'judge_name' => $validated['judge_name'],
            ],
            [
                'questions_data' => $validated['questions'],
                'totals' => $validated['totals'],
            ]
        );

        \Log::info('MFQ SaveDraft - Draft saved for participant: ' . $participant->id . ', session: ' . $sessionId);

        return response()->json([
            'success' => true,
            'message' => 'Draft tersimpan',
            'draft_id' => $draft->id,
            'saved_at' => $draft->updated_at->toIso8601String(),
        ]);
    }

    /**
     * Get all drafts for a session (for recovery on page load)
     */
    public function getDrafts(int $sessionId): \Illuminate\Http\JsonResponse
    {
        $session = MfqSession::findOrFail($sessionId);

        $drafts = MfqDraft::with('participant:id,name,district_id')
            ->where('mfq_session_id', $sessionId)
            ->get()
            ->map(function ($draft) {
                return [
                    'id' => $draft->id,
                    'participant_id' => $draft->participant_id,
                    'participant_name' => $draft->participant?->name,
                    'judge_name' => $draft->judge_name,
                    'questions' => $draft->questions_data,
                    'totals' => $draft->totals,
                    'is_finalized' => $draft->isFinalized(),
                    'saved_at' => $draft->updated_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'drafts' => $drafts,
        ]);
    }

    /**
     * Delete a specific draft
     */
    public function deleteDraft(int $sessionId, int $draftId): \Illuminate\Http\JsonResponse
    {
        $draft = MfqDraft::where('id', $draftId)
            ->where('mfq_session_id', $sessionId)
            ->firstOrFail();

        $draft->delete();

        return response()->json(['success' => true, 'message' => 'Draft dihapus']);
    }

    public function completeSession(Request $request, int $sessionId): RedirectResponse
    {
        $session = MfqSession::findOrFail($sessionId);

        if ($session->status !== 'active') {
            throw ValidationException::withMessages([
                'session' => 'Sesi ini sudah tidak aktif.',
            ]);
        }

        // Debug: Log session info
        \Log::info('MFQ CompleteSession - Session ID: ' . $session->id);
        \Log::info('MFQ CompleteSession - District IDs: ' . json_encode($session->district_ids));

        // MFQ scores are per district/group
        // Read directly from mfq_results (saved by submitDistrictScore)
        // Only calculate and update the rankings
        $savedCount = 0;

        // Get all mfq_results for this session with scores > 0
        $results = MfqResult::with(['participant.district'])
            ->where('mfq_session_id', $session->id)
            ->where('total_score', '>', 0)
            ->get();

        \Log::info('MFQ CompleteSession - Results found: ' . $results->count());

        if ($results->isNotEmpty()) {
            // Group by district to get one ranking entry per district
            // Since all participants in same district have same score,
            // we only need one entry per district for ranking
            $districtResults = $results->groupBy('district_id')->map(function ($districtEntries) {
                return $districtEntries->first(); // Take first entry as representative
            })->values();

            // Sort by total_score descending
            $sortedResults = $districtResults->sortByDesc('total_score')->values();

            \Log::info('MFQ CompleteSession - Districts to rank: ' . $sortedResults->count());
            foreach ($sortedResults as $r) {
                \Log::info('MFQ CompleteSession - Sorted: district=' . $r->district_id . ', score=' . $r->total_score);
            }

            // Update ranks for all results
            // Track actual position in sorted list (0-based)
            $position = 0;
            $prevScore = null;
            foreach ($sortedResults as $result) {
                // If same score as previous, keep same rank (tie)
                // If lower score, rank = position + 1
                if ($prevScore !== null && $result->total_score < $prevScore) {
                    $position++;
                }

                $rank = $position + 1;

                // Update rank for ALL participants in this district
                MfqResult::where('mfq_session_id', $session->id)
                    ->where('district_id', $result->district_id)
                    ->update(['rank' => $rank]);

                $savedCount++;
                \Log::info('MFQ CompleteSession - Rank ' . $rank . ' for district ID: ' . $result->district_id . ' (Score: ' . $result->total_score . ', Position: ' . $position . ')');

                $prevScore = $result->total_score;
            }
        }

        $session->update(['status' => 'completed']);

        return redirect()
            ->route('scoring.mfq', ['competition_category_id' => $session->competition_category_id])
            ->with('status', "Sesi MFQ berhasil diselesaikan. {$savedCount} ranking tersimpan!");
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
            ->route('scoring.mfq', ['competition_category_id' => $session->competition_category_id])
            ->with('status', 'Sesi MFQ berhasil dihapus.');
    }

    // Helper methods

    protected function getMfqCategories($user): Collection
    {
        $query = CompetitionCategory::query()
            ->whereIn('id', [24, 25])
            ->orderBy('id');

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
