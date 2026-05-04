<?php

namespace App\Http\Controllers;

use App\Events\ScoreUpdated;
use App\Models\CompetitionCategory;
use App\Models\Participant;
use App\Models\ScoringSetting;
use App\Models\ScoreEntry;
use App\Support\ActivityLogger;
use App\Support\RealtimeBroadcaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScoringController extends Controller
{
    protected const ALLOWED_JUDGING_ROUNDS = ['Penyisihan', 'Final'];
    protected const ROUND_KEYS = [
        'penyisihan' => 'Penyisihan',
        'final' => 'Final',
    ];

    public function index(Request $request): View
    {
        $user = auth()->user();
        $restrictedCategoryIds = $this->accessibleCategoryIdsForUser($user);
        $restrictByCategory = $user?->role === 'panitia';
        $filters = $request->validate([
            'participant_id' => ['nullable', 'integer'],
            'competition_category_id' => ['nullable', 'integer'],
            'branch' => ['nullable', 'string', 'max:255'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'judging_round' => ['nullable', 'string', 'in:Penyisihan,Final'],
        ]);

        $participants = Participant::query()
            ->with(['category', 'district', 'scores'])
            ->where('verification_status', 'verified')
            ->when($restrictByCategory, fn ($query) => $query->whereIn('competition_category_id', $restrictedCategoryIds))
            ->when(filled($filters['competition_category_id'] ?? null), fn ($query) => $query->where('competition_category_id', $filters['competition_category_id']))
            ->when(filled($filters['branch'] ?? null), fn ($query) => $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('branch', $filters['branch'])))
            ->when(filled($filters['keyword'] ?? null), function ($query) use ($filters): void {
                $keyword = trim((string) $filters['keyword']);

                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('registration_number', 'like', '%'.$keyword.'%')
                        ->orWhere('institution', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy('name')
            ->get();

        $selectedParticipant = null;
        if (filled($filters['participant_id'] ?? null)) {
            $selectedParticipant = Participant::query()
                ->with(['category', 'district', 'scores' => fn ($query) => $query->orderByDesc('submitted_at')])
                ->whereKey($filters['participant_id'])
                ->where('verification_status', 'verified')
                ->when($restrictByCategory, fn ($query) => $query->whereIn('competition_category_id', $restrictedCategoryIds))
                ->first();
        }

        if (! $selectedParticipant && $participants->isNotEmpty()) {
            $selectedParticipant = Participant::query()
                ->with(['category', 'district', 'scores' => fn ($query) => $query->orderByDesc('submitted_at')])
                ->find($participants->first()->id);
        }

        if ($selectedParticipant?->competition_category_id) {
            Cache::put(
                $this->currentParticipantCacheKey((int) $selectedParticipant->competition_category_id),
                (int) $selectedParticipant->id,
                now()->addHours(12)
            );
        }

        $selectedCategory = filled($filters['competition_category_id'] ?? null)
            ? CompetitionCategory::query()
                ->when($restrictByCategory, fn ($query) => $query->whereIn('id', $restrictedCategoryIds))
                ->find($filters['competition_category_id'])
            : $selectedParticipant?->category;
        $scoringSetting = ScoringSetting::forCategory($selectedCategory?->id);
        $judgingRounds = $this->judgingRoundsForSetting($scoringSetting);
        $selectedJudgingRound = in_array(($filters['judging_round'] ?? null), $judgingRounds, true)
            ? $filters['judging_round']
            : ($judgingRounds[0] ?? self::ALLOWED_JUDGING_ROUNDS[0]);
        $activeRoundConfig = $this->roundConfigForSetting(
            $selectedCategory?->branch,
            $scoringSetting,
            $selectedJudgingRound,
            (string) auth()->user()?->name
        );
        $criteria = $activeRoundConfig['scoring_points'];
        $judgeNames = $activeRoundConfig['judge_names'];
        $setupReady = $selectedCategory && $scoringSetting?->isReady();
        $roundSetupConfigs = $this->roundSetupConfigs(
            $selectedCategory?->branch,
            $scoringSetting,
            (string) auth()->user()?->name
        );

        $recentScores = ScoreEntry::query()
            ->with('participant.category')
            ->when($selectedParticipant, fn ($query) => $query->where('participant_id', $selectedParticipant->id))
            ->orderByDesc('submitted_at')
            ->limit(8)
            ->get();

        $categories = CompetitionCategory::query()
            ->when($restrictByCategory, fn ($query) => $query->whereIn('id', $restrictedCategoryIds))
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->get();

        $branches = CompetitionCategory::query()
            ->select('branch')
            ->distinct()
            ->when($restrictByCategory, fn ($query) => $query->whereIn('id', $restrictedCategoryIds))
            ->orderBy('branch')
            ->pluck('branch');

        $bigScreenUrl = route('big-screen', array_filter([
            'competition_category_id' => $selectedCategory?->id,
            'participant_id' => $selectedParticipant?->id,
        ]));

        return view('pages/scoring-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'restrictedCategories' => $categories
                ->map(fn (CompetitionCategory $category): string => trim(($category->branch ?? '-').' - '.($category->name ?? '-')))
                ->values()
                ->all(),
            'participants' => $participants,
            'selectedParticipant' => $selectedParticipant,
            'selectedCategory' => $selectedCategory,
            'scoringSetting' => $scoringSetting,
            'setupReady' => (bool) $setupReady,
            'judgingRounds' => $judgingRounds,
            'selectedJudgingRound' => $selectedJudgingRound,
            'judgeNames' => $judgeNames,
            'criteria' => $criteria,
            'roundSetupConfigs' => $roundSetupConfigs,
            'recentScores' => $recentScores,
            'categories' => $categories,
            'branches' => $branches,
            'bigScreenUrl' => $bigScreenUrl,
            'filters' => [
                'participant_id' => $selectedParticipant?->id ?: ($filters['participant_id'] ?? ''),
                'competition_category_id' => $filters['competition_category_id'] ?? '',
                'branch' => $filters['branch'] ?? '',
                'keyword' => $filters['keyword'] ?? '',
                'judging_round' => $selectedJudgingRound,
            ],
            'scoreStats' => [
                'participant_total' => $participants->count(),
                'verified_total' => Participant::query()
                    ->where('verification_status', 'verified')
                    ->when($restrictByCategory, fn ($query) => $query->whereIn('competition_category_id', $restrictedCategoryIds))
                    ->count(),
                'selected_average' => number_format((float) ($selectedParticipant?->scores?->avg('score') ?? 0), 2),
                'selected_latest' => number_format((float) ($selectedParticipant?->scores?->first()?->score ?? 0), 2),
                'judge_total' => count($judgeNames),
                'criteria_total' => count($criteria),
            ],
        ]);
    }

    public function storeSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'competition_category_id' => ['required', 'exists:competition_categories,id'],
            'judging_rounds_text' => ['required', 'string', 'max:1500'],
            'rounds.penyisihan.judge_count' => ['required', 'integer', 'min:1', 'max:15'],
            'rounds.penyisihan.judge_names_text' => ['required', 'string', 'max:3000'],
            'rounds.penyisihan.scoring_points_text' => ['required', 'string', 'max:3000'],
            'rounds.final.judge_count' => ['required', 'integer', 'min:1', 'max:15'],
            'rounds.final.judge_names_text' => ['required', 'string', 'max:3000'],
            'rounds.final.scoring_points_text' => ['required', 'string', 'max:3000'],
        ]);

        $category = CompetitionCategory::query()->findOrFail((int) $validated['competition_category_id']);
        $this->ensureCategoryAccess((int) $category->id, 'competition_category_id');
        $judgingRounds = $this->normalizeLines($validated['judging_rounds_text']);
        if ($judgingRounds !== self::ALLOWED_JUDGING_ROUNDS) {
            throw ValidationException::withMessages([
                'judging_rounds_text' => 'Babak penilaian wajib tepat dua baris: Penyisihan dan Final.',
            ]);
        }

        $roundSettings = [];
        foreach (self::ROUND_KEYS as $roundKey => $roundLabel) {
            $judgeCount = (int) data_get($validated, 'rounds.'.$roundKey.'.judge_count');
            $judgeNames = $this->normalizeLines((string) data_get($validated, 'rounds.'.$roundKey.'.judge_names_text', ''));
            $normalizedPointLabels = $this->normalizeLines((string) data_get($validated, 'rounds.'.$roundKey.'.scoring_points_text', ''));
            $scoringPoints = collect($normalizedPointLabels)
                ->mapWithKeys(function (string $label): array {
                    $key = Str::of($label)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();
                    $key = $key !== '' ? $key : 'poin_'.Str::random(4);

                    return [$key => $label];
                })
                ->all();

            if ($judgeNames === [] || $scoringPoints === []) {
                throw ValidationException::withMessages([
                    'rounds.'.$roundKey.'.judge_names_text' => 'Nama hakim dan poin penilaian untuk babak '.$roundLabel.' wajib diisi.',
                ]);
            }

            if ($judgeCount !== count($judgeNames)) {
                throw ValidationException::withMessages([
                    'rounds.'.$roundKey.'.judge_count' => 'Jumlah hakim babak '.$roundLabel.' harus sama dengan jumlah nama hakim yang ditulis.',
                ]);
            }

            if (count($judgeNames) !== count(array_unique(array_map('mb_strtolower', $judgeNames)))) {
                throw ValidationException::withMessages([
                    'rounds.'.$roundKey.'.judge_names_text' => 'Nama hakim babak '.$roundLabel.' tidak boleh duplikat.',
                ]);
            }

            $roundSettings[$roundLabel] = [
                'judge_count' => $judgeCount,
                'judge_names' => array_values($judgeNames),
                'scoring_points' => $scoringPoints,
                'scoring_priorities' => array_keys($scoringPoints),
            ];
        }

        $primaryRoundConfig = $roundSettings['Final'] ?? $roundSettings['Penyisihan'] ?? reset($roundSettings) ?: [];

        $setting = ScoringSetting::query()->updateOrCreate(
            ['competition_category_id' => $category->id],
            [
                'judge_count' => (int) ($primaryRoundConfig['judge_count'] ?? 0),
                'judge_names' => array_values($primaryRoundConfig['judge_names'] ?? []),
                'judging_rounds' => array_values($judgingRounds),
                'scoring_points' => $primaryRoundConfig['scoring_points'] ?? [],
                'scoring_priorities' => $primaryRoundConfig['scoring_priorities'] ?? [],
                'round_settings' => $roundSettings,
                'configured_by' => auth()->id(),
            ]
        );

        ActivityLogger::log(
            'scoring.settings.updated',
            (auth()->user()?->name ?? 'Panitia').' memperbarui setting penilaian golongan '.$category->name.'.',
            $setting,
            [
                'category_id' => $category->id,
                'category_label' => trim((string) $category->branch.' - '.(string) $category->name),
                'judging_rounds' => $judgingRounds,
                'judge_total' => (int) ($primaryRoundConfig['judge_count'] ?? 0),
            ]
        );

        return redirect()
            ->route('scoring', ['competition_category_id' => $category->id, 'branch' => $category->branch])
            ->with('status', 'Setting penilaian untuk golongan '.$category->name.' berhasil disimpan.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'participant_id' => ['required', 'exists:participants,id'],
            'judging_round' => ['required', 'string', 'max:100'],
            'remarks' => ['nullable', 'array'],
        ]);

        $participant = Participant::query()
            ->with('category')
            ->whereKey($validated['participant_id'])
            ->where('verification_status', 'verified')
            ->firstOrFail();
        $this->ensureCategoryAccess((int) $participant->competition_category_id, 'participant_id');

        $scoringSetting = ScoringSetting::forCategory($participant->competition_category_id);

        if (! $scoringSetting || ! $scoringSetting->isReady()) {
            throw ValidationException::withMessages([
                'participant_id' => 'Setting penilaian untuk golongan ini belum disiapkan. Simpan setting penilaian terlebih dahulu.',
            ]);
        }

        $judgingRounds = $this->judgingRoundsForSetting($scoringSetting);

        if (! in_array($validated['judging_round'], $judgingRounds, true)) {
            throw ValidationException::withMessages([
                'judging_round' => 'Babak penilaian harus sesuai setting golongan ini.',
            ]);
        }

        $roundConfig = $this->roundConfigForSetting(
            $participant->category?->branch,
            $scoringSetting,
            $validated['judging_round'],
            (string) auth()->user()?->name
        );
        $judgeNames = $roundConfig['judge_names'];
        $criteria = $roundConfig['scoring_points'];
        $scoreRules = [];
        foreach ($judgeNames as $judgeName) {
            $scoreRules['remarks.'.$judgeName] = ['nullable', 'string', 'max:1000'];

            foreach (array_keys($criteria) as $key) {
                $scoreRules['scores.'.$judgeName.'.'.$key] = ['required', 'numeric', 'min:0', 'max:100'];
            }
        }

        $scorePayload = $request->validate($scoreRules);
        $createdEntries = [];
        foreach ($judgeNames as $judgeName) {
            $scores = collect(data_get($scorePayload, 'scores.'.$judgeName, []))
                ->map(fn ($value) => round((float) $value, 2))
                ->all();
            $totalScore = round(collect($scores)->avg() ?? 0, 2);

            $createdEntries[] = ScoreEntry::query()->create([
                'participant_id' => $participant->id,
                'judge_name' => $judgeName,
                'judging_round' => $validated['judging_round'],
                'score' => $totalScore,
                'score_breakdown' => $scores,
                'remarks' => data_get($scorePayload, 'remarks.'.$judgeName),
                'submitted_at' => now(),
            ]);
        }

        foreach ($createdEntries as $scoreEntry) {
            RealtimeBroadcaster::dispatch(new ScoreUpdated($scoreEntry));
        }

        ActivityLogger::log(
            'scoring.score.created',
            (auth()->user()?->name ?? 'Panitia').' menginput nilai '.$validated['judging_round'].' untuk peserta '.$participant->name.'.',
            $participant,
            [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'registration_number' => $participant->registration_number,
                'category_id' => $participant->competition_category_id,
                'category_label' => trim((string) ($participant->category?->branch ?? '').' - '.(string) ($participant->category?->name ?? '')),
                'judging_round' => $validated['judging_round'],
                'judge_total' => count($createdEntries),
                'average_score' => round(collect($createdEntries)->avg(fn (ScoreEntry $entry): float => (float) $entry->score) ?? 0, 2),
            ]
        );

        $redirectUrl = route('scoring', [
                'participant_id' => $participant->id,
                'competition_category_id' => $participant->competition_category_id,
                'branch' => $participant->category?->branch,
                'judging_round' => $validated['judging_round'],
            ]).'#form-penilaian';

        return redirect()
            ->to($redirectUrl)
            ->with('status', 'Nilai semua hakim untuk peserta '.$participant->name.' berhasil disimpan sekaligus.');
    }

    protected function criteriaForBranch(?string $branch): array
    {
        return config('scoring.criteria.'.$branch)
            ?? config('scoring.criteria.default', []);
    }

    protected function criteriaForContext(?string $branch, ?ScoringSetting $scoringSetting): array
    {
        if ($scoringSetting && count($scoringSetting->scoring_points ?? []) > 0) {
            return $scoringSetting->scoring_points;
        }

        return $this->criteriaForBranch($branch);
    }

    protected function judgeNamesForSetting(?ScoringSetting $scoringSetting, string $fallbackName): array
    {
        $names = array_values(array_filter($scoringSetting?->judge_names ?? []));

        return $names !== [] ? $names : [$fallbackName];
    }

    protected function judgingRoundsForSetting(?ScoringSetting $scoringSetting): array
    {
        $rounds = array_values(array_filter($scoringSetting?->judging_rounds ?? []));

        if ($rounds !== []) {
            return array_values(array_intersect(self::ALLOWED_JUDGING_ROUNDS, $rounds));
        }

        return self::ALLOWED_JUDGING_ROUNDS;
    }

    protected function roundSetupConfigs(?string $branch, ?ScoringSetting $scoringSetting, string $fallbackName): array
    {
        $configs = [];

        foreach (self::ROUND_KEYS as $roundLabel) {
            $configs[$roundLabel] = $this->roundConfigForSetting($branch, $scoringSetting, $roundLabel, $fallbackName);
        }

        return $configs;
    }

    protected function roundConfigForSetting(?string $branch, ?ScoringSetting $scoringSetting, string $roundLabel, string $fallbackName): array
    {
        $defaultCriteria = $this->criteriaForBranch($branch);
        $roundSettings = $scoringSetting?->round_settings ?? [];
        $config = is_array($roundSettings) ? ($roundSettings[$roundLabel] ?? null) : null;

        if (is_array($config)) {
            $judgeNames = array_values(array_filter($config['judge_names'] ?? []));
            $scoringPoints = $config['scoring_points'] ?? [];
            $scoringPriorities = array_values(array_filter($config['scoring_priorities'] ?? array_keys($scoringPoints)));

            return [
                'judge_count' => (int) ($config['judge_count'] ?? count($judgeNames) ?: 1),
                'judge_names' => $judgeNames !== [] ? $judgeNames : [$fallbackName],
                'scoring_points' => $scoringPoints !== [] ? $scoringPoints : $defaultCriteria,
                'scoring_priorities' => $scoringPriorities !== [] ? $scoringPriorities : array_keys($scoringPoints ?: $defaultCriteria),
            ];
        }

        $fallbackCriteria = $this->criteriaForContext($branch, $scoringSetting);
        $fallbackJudgeNames = $this->judgeNamesForSetting($scoringSetting, $fallbackName);

        return [
            'judge_count' => (int) ($scoringSetting?->judge_count ?? count($fallbackJudgeNames) ?: 1),
            'judge_names' => $fallbackJudgeNames,
            'scoring_points' => $fallbackCriteria,
            'scoring_priorities' => array_values(array_filter($scoringSetting?->scoring_priorities ?? array_keys($fallbackCriteria))),
        ];
    }

    protected function normalizeLines(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function currentParticipantCacheKey(int $categoryId): string
    {
        return 'mtq:bigscreen:category:'.$categoryId.':current_participant_id';
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
}
