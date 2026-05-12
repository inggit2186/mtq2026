<?php

namespace App\Http\Controllers;

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
    protected const QUESTION_COUNT = 15;
    protected const SCORE_MIN = 1;
    protected const SCORE_MAX = 100;

    public function index(Request $request): View
    {
        $user = auth()->user();
        $restrictedCategoryIds = $this->accessibleCategoryIdsForUser($user);
        $filters = $request->validate([
            'competition_category_id' => ['nullable', 'integer'],
            'participant_id' => ['nullable', 'integer'],
            'judging_round' => ['nullable', 'string', Rule::in(self::JUDGING_ROUNDS)],
        ]);
        $selection = $this->currentSelection();
        $selectedCategoryId = filled($filters['competition_category_id'] ?? null)
            ? (int) $filters['competition_category_id']
            : (int) ($selection['competition_category_id'] ?? 0);
        $selectedParticipantIds = collect($selection['participant_ids'] ?? [])
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

        $selectedCategory = $selectedCategoryId > 0
            ? CompetitionCategory::query()
                ->whereIn('id', $mfqCategoryIds ?: [0])
                ->when($user?->role === 'panitia' && $restrictedCategoryIds !== [], fn ($query) => $query->whereIn('id', $restrictedCategoryIds))
                ->find($selectedCategoryId)
            : null;

        $selectedParticipants = $selectedParticipantIds->isNotEmpty()
            ? Participant::query()
                ->with(['category', 'district', 'scores' => fn ($query) => $query->orderByDesc('submitted_at')])
                ->whereIn('id', $selectedParticipantIds->all())
                ->orderBy('name')
                ->get()
            : collect();

        if ($selectedParticipants->count() >= 2) {
            $selectedParticipantId = filled($filters['participant_id'] ?? null)
                ? (int) $filters['participant_id']
                : (int) ($selectedParticipants->first()?->id ?? 0);
            $selectedParticipant = $selectedParticipants->firstWhere('id', $selectedParticipantId) ?? $selectedParticipants->first();
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
                    'competition_category_id' => $selectedCategoryId > 0 ? $selectedCategoryId : '',
                    'participant_id' => $selectedParticipant?->id ?? '',
                    'judging_round' => $selectedJudgingRound,
                ],
                'judgeNameDefault' => (string) auth()->user()?->name,
                'selectionState' => $selection,
                'mfqSheetSummary' => 'Format ini mengikuti lembar Excel MFQ: 15 baris soal dengan kolom paket, lontaran, rebutan, dan jumlah per regu aktif.',
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
                'competition_category_id' => $filters['competition_category_id'] ?? '',
            ],
            'judgeNameDefault' => (string) auth()->user()?->name,
            'selectionState' => $selection,
        ]);
    }

    public function storeSelection(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'competition_category_id' => ['required', 'integer'],
            'participant_ids' => ['required', 'array', 'min:2', 'max:5'],
            'participant_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $category = CompetitionCategory::query()
            ->whereKey($validated['competition_category_id'])
            ->firstOrFail();
        $this->ensureCategoryAccess((int) $category->id, 'competition_category_id');
        $this->ensureMfqCategoryByCategory($category);

        $participantIds = collect($validated['participant_ids'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $participants = Participant::query()
            ->with('category')
            ->whereIn('id', $participantIds->all())
            ->get();

        if ($participants->count() !== $participantIds->count()) {
            throw ValidationException::withMessages([
                'participant_ids' => 'Ada regu yang dipilih tidak ditemukan.',
            ]);
        }

        if ($participants->contains(fn (Participant $participant): bool => (int) $participant->competition_category_id !== (int) $category->id)) {
            throw ValidationException::withMessages([
                'participant_ids' => 'Semua regu harus berasal dari golongan yang sama.',
            ]);
        }

        if ($participants->contains(fn (Participant $participant): bool => $participant->verification_status !== 'verified')) {
            throw ValidationException::withMessages([
                'participant_ids' => 'Semua regu harus berstatus terverifikasi.',
            ]);
        }

        if ($participants->contains(fn (Participant $participant): bool => ! $this->isMfqParticipant($participant))) {
            throw ValidationException::withMessages([
                'participant_ids' => 'Semua regu harus berasal dari cabang MFQ.',
            ]);
        }

        session()->put('mfq.selection', [
            'competition_category_id' => (int) $category->id,
            'participant_ids' => $participantIds->all(),
        ]);

        return redirect()
            ->route('scoring.mfq', ['competition_category_id' => $category->id])
            ->with('status', 'Regu MFQ berhasil dipilih. Berikutnya kita lanjut ke tahap penilaian per soal.');
    }

    public function clearSelection(): RedirectResponse
    {
        session()->forget('mfq.selection');

        return redirect()
            ->route('scoring.mfq')
            ->with('status', 'Pilihan regu MFQ sudah dihapus.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'participant_id' => ['required', 'exists:participants,id'],
            'judge_name' => ['required', 'string', 'max:120'],
            'judging_round' => ['required', 'string', Rule::in(self::JUDGING_ROUNDS)],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'questions' => ['required', 'array', 'size:15'],
        ]);

        $participant = Participant::query()
            ->with('category')
            ->whereKey($validated['participant_id'])
            ->where('verification_status', 'verified')
            ->firstOrFail();

        $selection = $this->currentSelection();
        $selectedParticipantIds = collect($selection['participant_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($selectedParticipantIds->count() < 2) {
            throw ValidationException::withMessages([
                'participant_id' => 'Pilih regu MFQ terlebih dahulu pada tahap awal.',
            ]);
        }

        if (! $selectedParticipantIds->contains((int) $participant->id)) {
            throw ValidationException::withMessages([
                'participant_id' => 'Peserta yang dinilai harus berasal dari regu yang sudah dipilih pada tahap awal.',
            ]);
        }

        $this->ensureCategoryAccess((int) $participant->competition_category_id, 'participant_id');
        $this->ensureMfqCategory($participant);

        $scoringColumns = $this->buildScoringColumns(
            collect($selection['participant_ids'] ?? [])
                ->map(fn ($id): int => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->pipe(fn ($ids) => Participant::query()->with('category')->whereIn('id', $ids->all())->orderBy('name')->get()),
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

        $scoreEntry = ScoreEntry::query()->create([
            'participant_id' => $participant->id,
            'judge_name' => $validated['judge_name'],
            'judging_round' => $validated['judging_round'],
            'score' => (int) $totalScore,
            'score_breakdown' => [
                'type' => 'MFQ',
                'judge_name' => $validated['judge_name'],
                'judging_round' => $validated['judging_round'],
                'participant_label' => trim((string) ($participant->category?->branch ?? '').' - '.(string) ($participant->category?->name ?? '')),
                'sheet_style' => [
                    'question_count' => self::QUESTION_COUNT,
                    'selected_regu_count' => $selectedParticipantIds->count(),
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
                    'total_score' => (int) $totalScore,
                    'column_totals' => [
                        'package_score' => (int) $columnTotals['package_score'],
                        'throw_scores' => collect($columnTotals['throw_scores'])->map(fn ($value) => (int) $value)->values()->all(),
                        'rebuttal_score' => (int) $columnTotals['rebuttal_score'],
                    ],
                ],
                'questions' => $questionRows,
            ],
            'remarks' => filled($validated['remarks'] ?? null) ? $validated['remarks'] : null,
            'submitted_at' => Carbon::now(),
        ]);

        RealtimeBroadcaster::dispatch(new ScoreUpdated($scoreEntry));

        ActivityLogger::log(
            'scoring.mfq.created',
            (auth()->user()?->name ?? 'Panitia').' menginput penilaian MFQ untuk peserta '.$participant->name.'.',
            $participant,
            [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'category_id' => $participant->competition_category_id,
                'category_label' => trim((string) ($participant->category?->branch ?? '').' - '.(string) ($participant->category?->name ?? '')),
                'judging_round' => $validated['judging_round'],
                'question_total' => count($questionRows),
                'total_score' => round($totalScore, 2),
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
                'name' => $participant->name,
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
        return collect(range(1, self::QUESTION_COUNT))
            ->map(function (int $number) use ($opponentCount): array {
                return [
                    'id' => Str::uuid()->toString(),
                    'label' => 'Soal '.$number,
                    'package_score' => '',
                    'throw_scores' => array_fill(0, max(0, $opponentCount), ''),
                    'rebuttal_score' => '',
                    'notes' => '',
                ];
            })
            ->values()
            ->all();
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
    }

    protected function currentSelection(): array
    {
        $selection = session('mfq.selection', []);

        return [
            'competition_category_id' => (int) ($selection['competition_category_id'] ?? 0),
            'participant_ids' => array_values(array_filter(array_map('intval', $selection['participant_ids'] ?? []))),
        ];
    }
}
