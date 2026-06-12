<?php

namespace App\Http\Controllers;

use App\Events\ParticipantSelected;
use App\Events\ScoreUpdated;
use App\Models\CompetitionCategory;
use App\Models\Participant;
use App\Models\ScoreEntry;
use App\Support\ActivityLogger;
use App\Support\RealtimeBroadcaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MfqScoringController extends Controller
{
    protected const JUDGING_ROUNDS = ['Penyisihan', 'Final'];
    protected const SCORE_MIN = 1;
    protected const SCORE_MAX = 100;

    public function index(Request $request): View
    {
        $user = auth()->user();
        $restrictedCategoryIds = $this->accessibleCategoryIdsForUser($user);
        $filters = $request->validate([
            'session_name' => ['nullable', 'string', 'max:120'],
            'competition_category_id' => ['nullable', 'integer'],
            'participant_id' => ['nullable', 'integer'],
            'judging_round' => ['nullable', 'string', Rule::in(self::JUDGING_ROUNDS)],
        ]);
        $selection = $this->currentSelection();
        $sessionNameDraft = trim((string) ($filters['session_name'] ?? ''));
        if ($sessionNameDraft !== '') {
            session()->put('mfq.session_name_draft', $sessionNameDraft);
        }
        $selectionSessionName = trim((string) ($selection['session_name'] ?? ($sessionNameDraft !== '' ? $sessionNameDraft : session('mfq.session_name_draft', ''))));
        $selectionJudgeName = trim((string) ($selection['judge_name'] ?? ($filters['judge_name'] ?? (string) $user?->name)));
        $selectionJudgingRound = in_array((string) ($selection['judging_round'] ?? ($filters['judging_round'] ?? self::JUDGING_ROUNDS[0])), self::JUDGING_ROUNDS, true)
            ? (string) ($selection['judging_round'] ?? ($filters['judging_round'] ?? self::JUDGING_ROUNDS[0]))
            : self::JUDGING_ROUNDS[0];
        $selectionRemarks = trim((string) ($selection['remarks'] ?? ($filters['remarks'] ?? '')));
        $selectedCategoryId = filled($filters['competition_category_id'] ?? null)
            ? (int) $filters['competition_category_id']
            : (int) ($selection['competition_category_id'] ?? 0);
        $selectedDistrictIds = collect($selection['district_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $categoryQuery = CompetitionCategory::query()
            ->where(function ($query): void {
                $query
                    ->whereRaw('LOWER(branch) like ?', ['%fahmil%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%fahmil%'])
                    ->orWhereRaw('LOWER(slug) like ?', ['%fahmil%']);
            })
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name');

        if ($user?->role === 'panitia' && $restrictedCategoryIds !== []) {
            $categoryQuery->whereIn('id', $restrictedCategoryIds);
        }

        $mfqCategories = $categoryQuery->get();
        $mfqCategoryIds = $mfqCategories->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $participantsQuery = Participant::query()
            ->with(['category', 'district', 'scores'])
            ->where('verification_status', 'verified')
            ->whereIn('competition_category_id', $mfqCategoryIds ?: [0])
            ->when($user?->role === 'panitia' && $restrictedCategoryIds !== [], fn ($query) => $query->whereIn('competition_category_id', $restrictedCategoryIds))
            ->orderBy('name');

        if ($selectedCategoryId > 0) {
            $participantsQuery->where('competition_category_id', $selectedCategoryId);
        }

        $participants = $participantsQuery->get();
        $participantsByDistrict = $participants->groupBy(fn (Participant $participant) => (int) ($participant->district_id ?? 0));

        $selectedCategory = $selectedCategoryId > 0
            ? CompetitionCategory::query()
                ->whereIn('id', $mfqCategoryIds ?: [0])
                ->when($user?->role === 'panitia' && $restrictedCategoryIds !== [], fn ($query) => $query->whereIn('id', $restrictedCategoryIds))
                ->find($selectedCategoryId)
            : null;

        $selectedParticipants = $selectedDistrictIds->isNotEmpty()
            ? $selectedDistrictIds
                ->map(function (int $districtId) use ($participantsByDistrict) {
                    return $participantsByDistrict->get($districtId, collect())->first();
                })
                ->filter()
                ->values()
            : collect();
        $selectedParticipantScores = $selectedParticipants->isNotEmpty()
            ? ScoreEntry::query()
                ->whereIn('participant_id', $selectedParticipants->pluck('id')->all())
                ->where('judging_round', $selectionJudgingRound)
                ->orderByDesc('submitted_at')
                ->get()
                ->groupBy('participant_id')
                ->mapWithKeys(fn ($entries, $participantId) => [(int) $participantId => $entries->first()])
            : collect();
        $selectedDistrictCards = $selectedDistrictIds->isNotEmpty()
            ? $selectedDistrictIds
                ->map(function (int $districtId) use ($participantsByDistrict, $selectedParticipantScores) {
                    $districtParticipants = $participantsByDistrict->get($districtId, collect())->values();
                    $representative = $districtParticipants->first();
                    $latestScore = $selectedParticipantScores->get((int) ($representative?->id ?? 0));

                    if (! $representative) {
                        return null;
                    }

                    return [
                        'district_id' => $districtId,
                        'district_name' => (string) ($representative->district?->name ?? 'Tanpa Kecamatan'),
                        'participant_count' => $districtParticipants->count(),
                        'representative_id' => $representative->id,
                        'representative_name' => $representative->name,
                        'representative_registration_number' => $representative->registration_number,
                        'representative_lot_number' => $representative->lot_number ?? null,
                        'score_value' => $latestScore ? number_format((float) $latestScore->score, 2) : null,
                    ];
                })
                ->filter()
                ->values()
            : collect();
        $selectedDistrict = $selectedParticipants->first()?->district;

        if ($selectedParticipants->count() >= 2 && filled($selectionSessionName)) {
            $selectedParticipantId = filled($filters['participant_id'] ?? null)
                ? (int) $filters['participant_id']
                : (int) ($selectedParticipants->first()?->id ?? 0);
            $selectedParticipant = $selectedParticipants->firstWhere('id', $selectedParticipantId) ?? $selectedParticipants->first();
            $selectedDistrict = $selectedParticipant?->district ?? $selectedDistrict;
            $selectedJudgingRound = in_array(($filters['judging_round'] ?? null), self::JUDGING_ROUNDS, true)
                ? $filters['judging_round']
                : self::JUDGING_ROUNDS[0];
            $scoringColumns = $this->buildScoringColumns($selectedParticipants, $selectedParticipant);
            $recentScores = ScoreEntry::query()
                ->with('participant.category')
                ->when($selectedParticipant, fn ($query) => $query->where('participant_id', $selectedParticipant->id))
                ->orderByDesc('submitted_at')
                ->limit(8)
                ->get();

            return view('pages/scoring-mfq-step2', [
                'assets' => app(PageController::class)->viteAssets(),
                'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
                'navigation' => app(PageController::class)->consoleNavigation((string) auth()->user()?->role, 'scoring.mfq'),
                'selectedParticipants' => $selectedParticipants,
                'selectedParticipant' => $selectedParticipant,
                'selectedCategory' => $selectedCategory ?? $selectedParticipants->first()?->category,
                'selectedJudgingRound' => $selectedJudgingRound,
                'recentScores' => $recentScores,
                'scoringColumns' => $scoringColumns,
                'defaultQuestions' => $this->defaultQuestionRows($scoringColumns['opponents']->count()),
                'summaryStats' => [
                    'participant_total' => $selectedParticipants->count(),
                    'category_total' => $mfqCategories->count(),
                    'verified_total' => Participant::query()
                        ->where('verification_status', 'verified')
                        ->whereIn('competition_category_id', $mfqCategoryIds ?: [0])
                        ->when($user?->role === 'panitia' && $restrictedCategoryIds !== [], fn ($query) => $query->whereIn('competition_category_id', $restrictedCategoryIds))
                        ->count(),
                    'selected_average' => number_format((float) ($selectedParticipant?->scores?->avg('score') ?? 0), 2),
                    'selected_latest' => number_format((float) ($selectedParticipant?->scores?->first()?->score ?? 0), 2),
                ],
                'filters' => [
                    'session_name' => $selectionSessionName,
                    'competition_category_id' => $selectedCategoryId > 0 ? $selectedCategoryId : '',
                    'participant_id' => $selectedParticipant?->id ?? '',
                    'judging_round' => $selectedJudgingRound,
                ],
                'openInputModal' => $request->boolean('open_input_modal'),
                'judgeNameDefault' => (string) auth()->user()?->name,
                'selectionState' => $selection,
                'selectionSessionName' => $selectionSessionName,
                'selectionJudgeName' => $selectionJudgeName,
                'selectionJudgingRound' => $selectionJudgingRound,
                'selectionRemarks' => $selectionRemarks,
                'selectedDistrict' => $selectedDistrict,
                'selectedDistrictCards' => $selectedDistrictCards->all(),
                'selectedDistrictIds' => $selectedDistrictIds->all(),
                'mfqSheetSummary' => 'Format ini mengikuti lembar Excel MFQ: satu soal per kartu modal dengan kolom paket, lontaran, rebutan, dan jumlah per regu aktif.',
                'activeTeam' => $selectedParticipant,
            ]);
        }

        return view('pages/scoring-mfq-step1', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'navigation' => app(PageController::class)->consoleNavigation((string) auth()->user()?->role, 'scoring.mfq'),
            'participants' => $participants,
            'mfqCategories' => $mfqCategories,
            'selectedParticipants' => $selectedParticipants,
            'selectedCategory' => $selectedCategory,
            'participantsByDistrict' => $participantsByDistrict,
            'summaryStats' => [
                'participant_total' => $participants->count(),
                'category_total' => $mfqCategories->count(),
                'verified_total' => Participant::query()
                    ->where('verification_status', 'verified')
                    ->whereIn('competition_category_id', $mfqCategoryIds ?: [0])
                    ->when($user?->role === 'panitia' && $restrictedCategoryIds !== [], fn ($query) => $query->whereIn('competition_category_id', $restrictedCategoryIds))
                    ->count(),
                'selected_average' => number_format((float) ($selectedParticipants->avg(fn ($participant): float => (float) ($participant->scores?->avg('score') ?? 0)) ?? 0), 2),
                'selected_latest' => number_format((float) ($selectedParticipants->first()?->scores?->first()?->score ?? 0), 2),
            ],
            'filters' => [
                'session_name' => $selectionSessionName,
                'competition_category_id' => $filters['competition_category_id'] ?? '',
            ],
            'judgeNameDefault' => (string) auth()->user()?->name,
            'selectionState' => $selection,
            'selectionSessionName' => $selectionSessionName,
            'selectionJudgeName' => $selectionJudgeName,
            'selectionJudgingRound' => $selectionJudgingRound,
            'selectionRemarks' => $selectionRemarks,
            'selectedDistrictIds' => $selectedDistrictIds->all(),
        ]);
    }

    public function storeSelection(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_name' => ['required', 'string', 'max:120'],
            'competition_category_id' => ['required', 'integer'],
            'judge_name' => ['required', 'string', 'max:120'],
            'judging_round' => ['required', 'string', Rule::in(self::JUDGING_ROUNDS)],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'district_ids' => ['required', 'array', 'min:2', 'max:5'],
            'district_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $category = CompetitionCategory::query()
            ->whereKey($validated['competition_category_id'])
            ->firstOrFail();
        $this->ensureCategoryAccess((int) $category->id, 'competition_category_id');
        $this->ensureMfqCategoryByCategory($category);

        $districtIds = collect($validated['district_ids'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $participants = Participant::query()
            ->with('category')
            ->whereIn('district_id', $districtIds->all())
            ->where('competition_category_id', (int) $category->id)
            ->get();

        if ($participants->isEmpty()) {
            throw ValidationException::withMessages([
                'district_ids' => 'Ada kecamatan yang dipilih tidak ditemukan.',
            ]);
        }

        if ($participants->contains(fn (Participant $participant): bool => $participant->verification_status !== 'verified')) {
            throw ValidationException::withMessages([
                'district_ids' => 'Semua kecamatan harus berstatus terverifikasi.',
            ]);
        }

        if ($participants->contains(fn (Participant $participant): bool => ! $this->isMfqParticipant($participant))) {
            throw ValidationException::withMessages([
                'district_ids' => 'Semua kecamatan harus berasal dari cabang MFQ.',
            ]);
        }

        $districtIds = $participants->pluck('district_id')
            ->filter()
            ->unique()
            ->values();

        session()->put('mfq.selection', [
            'session_name' => trim((string) $validated['session_name']),
            'competition_category_id' => (int) $category->id,
            'district_ids' => $districtIds->all(),
            'judge_name' => trim((string) $validated['judge_name']),
            'judging_round' => (string) $validated['judging_round'],
            'remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : '',
        ]);
        session()->put('mfq.session_name_draft', trim((string) $validated['session_name']));

        return redirect()
            ->route('scoring.mfq', ['competition_category_id' => $category->id])
            ->with('status', 'Kecamatan MFQ berhasil dipilih. Berikutnya kita lanjut ke tahap penilaian per soal.');
    }

    public function clearSelection(): RedirectResponse
    {
        session()->forget('mfq.selection');

        return redirect()
            ->route('scoring.mfq')
            ->with('status', 'Pilihan kecamatan MFQ sudah dihapus.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'participant_id' => ['required', 'exists:participants,id'],
            'judge_name' => ['required', 'string', 'max:120'],
            'judging_round' => ['required', 'string', Rule::in(self::JUDGING_ROUNDS)],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'questions' => ['required', 'array', 'min:1'],
        ]);

        $participant = Participant::query()
            ->with('category')
            ->whereKey($validated['participant_id'])
            ->where('verification_status', 'verified')
            ->firstOrFail();

        $selection = $this->currentSelection();
        $sessionName = trim((string) ($selection['session_name'] ?? ''));
        $selectedDistrictIds = collect($selection['district_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($selectedDistrictIds->count() < 2) {
            throw ValidationException::withMessages([
                'participant_id' => 'Pilih kecamatan MFQ terlebih dahulu pada tahap awal.',
            ]);
        }

        if (! $selectedDistrictIds->contains((int) $participant->district_id)) {
            throw ValidationException::withMessages([
                'participant_id' => 'Peserta yang dinilai harus berasal dari kecamatan yang sudah dipilih pada tahap awal.',
            ]);
        }

        $this->ensureCategoryAccess((int) $participant->competition_category_id, 'participant_id');
        $this->ensureMfqCategory($participant);

        $selectedParticipants = Participant::query()
            ->with('category')
            ->whereIn('district_id', $selectedDistrictIds->all())
            ->where('competition_category_id', (int) $participant->competition_category_id)
            ->where('verification_status', 'verified')
            ->orderBy('district_id')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Participant $selectedParticipant): int => (int) ($selectedParticipant->district_id ?? 0))
            ->map(fn ($districtParticipants) => $districtParticipants->first())
            ->values();

        $scoringColumns = $this->buildScoringColumns(
            $selectedParticipants,
            $participant
        );

        $questionRows = [];
        $columnTotals = [
            'package_score' => 0,
            'throw_scores' => array_fill(0, max(0, $scoringColumns['opponents']->count()), 0),
            'rebuttal_score' => 0,
            'row_total' => 0,
        ];
        $totalScore = 0;

        foreach (array_values($validated['questions']) as $index => $row) {
            $rowValidated = validator($row, [
                'label' => ['required', 'string', 'max:120'],
                'package_score' => ['nullable', 'integer', 'between:'.self::SCORE_MIN.','.self::SCORE_MAX],
                'throw_scores' => ['nullable', 'array'],
                'throw_scores.*' => ['nullable', 'integer', 'between:'.self::SCORE_MIN.','.self::SCORE_MAX],
                'rebuttal_score' => ['nullable', 'integer', 'between:'.self::SCORE_MIN.','.self::SCORE_MAX],
                'notes' => ['nullable', 'string', 'max:500'],
            ])->validate();

            $packageScore = $this->normalizeScore($rowValidated['package_score'] ?? null);
            $throwScores = collect($rowValidated['throw_scores'] ?? [])
                ->map(fn ($score) => $this->normalizeScore($score))
                ->values();
            $rebuttalScore = $this->normalizeScore($rowValidated['rebuttal_score'] ?? null);
            $rowTotal = (int) $packageScore
                + (int) $throwScores->sum()
                + (int) $rebuttalScore;

            $totalScore += $rowTotal;
            $columnTotals['package_score'] += (int) $packageScore;
            foreach ($throwScores as $throwIndex => $throwScore) {
                $columnTotals['throw_scores'][$throwIndex] = (int) ($columnTotals['throw_scores'][$throwIndex] ?? 0) + (int) $throwScore;
            }
            $columnTotals['rebuttal_score'] += (int) $rebuttalScore;
            $columnTotals['row_total'] += (int) $rowTotal;

            $questionRows[] = [
                'order' => $index + 1,
                'label' => $rowValidated['label'],
                'package_score' => $packageScore,
                'throw_scores' => $throwScores->all(),
                'rebuttal_score' => $rebuttalScore,
                'score' => $rowTotal,
                'notes' => $rowValidated['notes'] ?? null,
            ];
        }

        $scoreEntry = ScoreEntry::query()->updateOrCreate(
            [
                'participant_id' => $participant->id,
                'judging_round' => $validated['judging_round'],
            ],
            [
                'judge_name' => $validated['judge_name'],
                'score' => round((float) $totalScore, 2),
                'score_breakdown' => [
                    'type' => 'MFQ',
                    'session_name' => $sessionName !== '' ? $sessionName : null,
                    'judge_name' => $validated['judge_name'],
                    'judging_round' => $validated['judging_round'],
                    'participant_label' => trim((string) ($participant->category?->branch ?? '').' - '.(string) ($participant->category?->name ?? '')),
                    'sheet_style' => [
                        'question_count' => count($questionRows),
                        'selected_regu_count' => $selectedDistrictIds->count(),
                        'active_regu_id' => $participant->id,
                        'active_regu_name' => $participant->name,
                        'opponents' => $scoringColumns['opponents']->map(fn ($item) => [
                            'id' => $item['id'],
                            'name' => $item['name'],
                            'registration_number' => $item['registration_number'],
                        ])->values()->all(),
                    ],
                    'summary' => [
                        'total_questions' => count($questionRows),
                        'total_score' => round((float) $totalScore, 2),
                        'column_totals' => [
                            'package_score' => round((float) ($columnTotals['package_score'] ?? 0), 2),
                            'throw_scores' => collect($columnTotals['throw_scores'] ?? [])->map(fn ($value) => round((float) $value, 2))->values()->all(),
                            'rebuttal_score' => round((float) ($columnTotals['rebuttal_score'] ?? 0), 2),
                        ],
                    ],
                    'questions' => $questionRows,
                ],
                'remarks' => filled($validated['remarks'] ?? null) ? $validated['remarks'] : null,
                'submitted_at' => Carbon::now(),
            ]
        );

        RealtimeBroadcaster::dispatch(new ScoreUpdated($scoreEntry));

        ActivityLogger::log(
            $scoreEntry->wasRecentlyCreated ? 'scoring.mfq.created' : 'scoring.mfq.updated',
            (auth()->user()?->name ?? 'Panitia').' menyimpan penilaian MFQ untuk peserta '.$participant->name.'.',
            $participant,
            [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'category_id' => $participant->competition_category_id,
                'category_label' => trim((string) ($participant->category?->branch ?? '').' - '.(string) ($participant->category?->name ?? '')),
                'judging_round' => $validated['judging_round'],
                'question_total' => count($questionRows),
                'total_score' => round($totalScore, 2),
                'action' => $scoreEntry->wasRecentlyCreated ? 'created' : 'updated',
            ]
        );

        return redirect()
            ->to(route('scoring.mfq', [
                'participant_id' => $participant->id,
                'competition_category_id' => $participant->competition_category_id,
                'judging_round' => $validated['judging_round'],
            ]).'#form-penilaian')
            ->with('status', 'Penilaian MFQ untuk '.$participant->name.' berhasil disimpan.');
    }

    protected function buildScoringColumns($selectedParticipants, Participant $activeParticipant): array
    {
        $opponents = collect($selectedParticipants)
            ->reject(fn (Participant $participant): bool => (int) $participant->id === (int) $activeParticipant->id)
            ->values()
            ->map(fn (Participant $participant, int $index): array => [
                'index' => $index,
                'id' => $participant->id,
                'name' => (string) ($participant->district?->name ?? $participant->name),
                'district_name' => (string) ($participant->district?->name ?? $participant->name),
                'representative_name' => $participant->name,
                'registration_number' => $participant->registration_number,
            ]);

        return [
            'active' => [
                'id' => $activeParticipant->id,
                'name' => $activeParticipant->name,
                'registration_number' => $activeParticipant->registration_number,
            ],
            'opponents' => $opponents,
        ];
    }

    protected function normalizeScore(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round((float) $value);
    }

    protected function defaultQuestionRows(int $opponentCount = 0): array
    {
        return array_map(
            fn (int $index): array => [
                'id' => Str::uuid()->toString(),
                'label' => 'Soal '.($index + 1),
                'package_score' => '',
                'throw_scores' => array_fill(0, max(0, $opponentCount), ''),
                'rebuttal_score' => '',
                'notes' => '',
            ],
            range(0, 2)
        );
    }

    protected function accessibleCategoryIdsForUser($user): array
    {
        if (! $user || $user->role !== 'panitia') {
            return [];
        }

        return $user->accessibleCategoryIds();
    }

    protected function ensureCategoryAccess(?int $categoryId, string $errorKey = 'participant_id'): void
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'panitia') {
            return;
        }

        $accessibleCategoryIds = $this->accessibleCategoryIdsForUser($user);

        if (! $categoryId || ! in_array($categoryId, $accessibleCategoryIds, true)) {
            throw ValidationException::withMessages([
                $errorKey => 'Akun panitia ini tidak memiliki hak akses untuk golongan tersebut.',
            ]);
        }
    }

    protected function ensureMfqCategory(Participant $participant): void
    {
        if (! $this->isMfqParticipant($participant)) {
            throw ValidationException::withMessages([
                'participant_id' => 'Form ini hanya untuk peserta pada cabang MFQ.',
            ]);
        }
    }

    protected function ensureMfqCategoryByCategory(CompetitionCategory $category): void
    {
        $haystack = mb_strtolower(trim((string) $category->branch.' '.(string) $category->name.' '.(string) $category->slug));

        if (! str_contains($haystack, 'fahmil')) {
            throw ValidationException::withMessages([
                'competition_category_id' => 'Golongan yang dipilih bukan cabang MFQ.',
            ]);
        }
    }

    protected function isMfqParticipant(Participant $participant): bool
    {
        $haystack = mb_strtolower(trim((string) ($participant->category?->branch ?? '').' '.(string) ($participant->category?->name ?? '').' '.(string) ($participant->category?->slug ?? '')));

        return str_contains($haystack, 'fahmil');
    }

    protected function storeCurrentParticipant(int $categoryId, int $participantId): void
    {
        cache()->put(
            'mtq:bigscreen:category:'.$categoryId.':current_participant_id',
            $participantId,
            now()->addHours(12)
        );

        // Broadcast to Big Screen
        $participant = Participant::with('district')->find($participantId);
        if ($participant) {
            $participantPhotoUrl = null;
            if ($participant->document_photo) {
                $participantPhotoUrl = asset('storage/'.ltrim(str_replace('\\', '/', $participant->document_photo), '/'));
            }

            try {
                ParticipantSelected::dispatch(
                    (int) $participant->id,
                    (int) $categoryId,
                    $participant->name,
                    $participant->district?->name,
                    $participant->lot_number,
                    $participantPhotoUrl
                );
            } catch (\Throwable $e) {
                \Log::warning('MFQ ParticipantSelected broadcast skipped: '.$e->getMessage());
            }
        }
    }

    protected function currentSelection(): array
    {
        $selection = session('mfq.selection', []);
        $districtIds = array_values(array_filter(array_map('intval', $selection['district_ids'] ?? [])));

        return [
            'session_name' => (string) ($selection['session_name'] ?? ''),
            'competition_category_id' => (int) ($selection['competition_category_id'] ?? 0),
            'district_ids' => $districtIds,
            'judge_name' => (string) ($selection['judge_name'] ?? ''),
            'judging_round' => (string) ($selection['judging_round'] ?? ''),
            'remarks' => (string) ($selection['remarks'] ?? ''),
        ];
    }
}
