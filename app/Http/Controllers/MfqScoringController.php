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

            // Get lot numbers that have been displayed in previous sessions
            // (same category, same round, but different session)
            if ($activeSession && $activeSession->round) {
                $previousSessions = MfqSession::where('competition_category_id', $categoryId)
                    ->where('status', 'completed')
                    ->where('round', $activeSession->round)
                    ->where('id', '!=', $activeSession->id)
                    ->get();

                $previousDistrictIds = $previousSessions->pluck('district_ids')->flatten()->unique()->toArray();

                if (! empty($previousDistrictIds)) {
                    $displayedParticipants = Participant::whereIn('district_id', $previousDistrictIds)
                        ->whereNotNull('lot_number')
                        ->where('lot_number', '!=', '')
                        ->pluck('lot_number')
                        ->unique()
                        ->toArray();

                    $displayedLotNumbers = $displayedParticipants;
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

                        $sessionResults->sortBy('rank');
                        foreach ($sessionResults as $rank) {
                            $point = match ($rank->rank) {
                                1 => 3,
                                2 => 2,
                                3 => 1,
                                default => 0,
                            };
                            $sessionPoints[$sessionId] = ($sessionPoints[$sessionId] ?? 0) + $point;
                        }
                    }

                    // Get best scores per session
                    $sessionScores = [];
                    foreach ($districtResults->groupBy('mfq_session_id') as $sessionId => $sessionResults) {
                        $sessionScores[$sessionId] = $sessionResults->sortByDesc('total_score')->first()->total_score ?? 0;
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

        // Calculate rankings and save results
        $districtIds = $session->district_ids ?? [];
        $savedCount = 0;

        if (! empty($districtIds)) {
            // Get participants from selected districts
            $participantIds = Participant::where('competition_category_id', $session->competition_category_id)
                ->whereIn('district_id', $districtIds)
                ->where('verification_status', 'verified')
                ->pluck('id')
                ->toArray();

            \Log::info('MFQ CompleteSession - Participant IDs: ' . json_encode($participantIds));

            // Get all score entries for these participants
            $allScoreEntries = ScoreEntry::whereIn('participant_id', $participantIds)->get();
            \Log::info('MFQ CompleteSession - Total ScoreEntries found: ' . $allScoreEntries->count());

            // Filter only entries with score > 0
            $scoreEntries = $allScoreEntries->filter(function ($entry) {
                return $entry->score > 0;
            })->groupBy('participant_id');

            \Log::info('MFQ CompleteSession - ScoreEntries with score > 0: ' . $scoreEntries->count());

            // Calculate total scores per participant
            $participantScores = [];
            foreach ($scoreEntries as $participantId => $entries) {
                $totalScore = $entries->sum('score');

                $participant = Participant::with('district')->find($participantId);
                if (! $participant) {
                    continue;
                }

                \Log::info('MFQ CompleteSession - Processing participant: ' . $participant->name . ', Score: ' . $totalScore);

                // Build scores detail
                $scoresDetail = [];
                foreach ($entries as $entry) {
                    $judgeName = $entry->judge_name ?? 'Unknown';
                    $scoresDetail[$judgeName] = [
                        'score' => (float) $entry->score,
                        'breakdown' => $entry->score_breakdown ?? [],
                    ];
                }

                $participantScores[] = [
                    'participant' => $participant,
                    'total_score' => $totalScore,
                    'scores_detail' => $scoresDetail,
                ];
            }

            // Sort by total score (descending)
            usort($participantScores, fn ($a, $b) => $b['total_score'] <=> $a['total_score']);

            \Log::info('MFQ CompleteSession - Participants to save: ' . count($participantScores));

            // Delete old results for this session
            MfqResult::where('mfq_session_id', $session->id)->delete();

            // Save rankings to mfq_results
            foreach ($participantScores as $index => $data) {
                MfqResult::create([
                    'mfq_session_id' => $session->id,
                    'participant_id' => $data['participant']->id,
                    'round' => $session->round,
                    'rank' => $index + 1,
                    'total_score' => $data['total_score'],
                    'scores_detail' => $data['scores_detail'],
                ]);
                $savedCount++;
                \Log::info('MFQ CompleteSession - Saved rank ' . ($index + 1) . ' for ' . $data['participant']->name);
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
