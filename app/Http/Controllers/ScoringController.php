<?php

namespace App\Http\Controllers;

use App\Events\ParticipantSelected;
use App\Events\ScoreUpdated;
use App\Models\CompetitionCategory;
use App\Models\Hakim;
use App\Models\ScoreCorrectionRequest;
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
            'step' => ['nullable', 'integer', 'min:1', 'max:3'],
            'judge_index' => ['nullable', 'integer', 'min:0', 'max:20'],
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

        $categoryUsageParticipants = Participant::query()
            ->select(['competition_category_id', 'verification_status'])
            ->where('verification_status', 'verified')
            ->when($restrictByCategory, fn ($query) => $query->whereIn('competition_category_id', $restrictedCategoryIds))
            ->get()
            ->groupBy('competition_category_id');

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

        \Log::info('Checking participant for broadcast', [
            'participant_id' => $selectedParticipant?->id,
            'category_id' => $selectedParticipant?->competition_category_id,
            'has_category' => $selectedParticipant?->competition_category_id ? 'yes' : 'no'
        ]);
        
        if ($selectedParticipant?->competition_category_id) {
            Cache::put(
                $this->currentParticipantCacheKey((int) $selectedParticipant->competition_category_id),
                (int) $selectedParticipant->id,
                now()->addHours(12)
            );

            // Broadcast to Big Screen - wrap with try-catch to handle Reverb unavailable
            $participantPhotoUrl = null;
            if ($selectedParticipant->document_photo) {
                $participantPhotoUrl = asset('storage/'.ltrim(str_replace('\\', '/', $selectedParticipant->document_photo), '/'));
            }

            try {
                ParticipantSelected::dispatch(
                    (int) $selectedParticipant->id,
                    (int) $selectedParticipant->competition_category_id,
                    $selectedParticipant->name,
                    $selectedParticipant->district?->name,
                    $selectedParticipant->lot_number,
                    $participantPhotoUrl
                );
            } catch (\Throwable $e) {
                \Log::warning('ParticipantSelected broadcast skipped: '.$e->getMessage());
            }
        }

        $selectedCategory = filled($filters['competition_category_id'] ?? null)
            ? CompetitionCategory::query()
                ->when($restrictByCategory, fn ($query) => $query->whereIn('id', $restrictedCategoryIds))
                ->find($filters['competition_category_id'])
            : $selectedParticipant?->category;
        $selectedCategoryIsMfq = (bool) ($selectedCategory && $this->isMfqCategory($selectedCategory));
        $selectedCategoryIsMsq = (bool) ($selectedCategory && filled($selectedCategory->maqra_system_type) && $selectedCategory->maqra_system_type === 'syarhil');
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
        $judgeIds = $activeRoundConfig['judge_ids'] ?? [];

        // Build judge ID to name mapping for this category (for Alpine modal)
        $availableJudges = $selectedCategory
            ? Hakim::byGolongan($selectedCategory->id)->get()
            : collect();
        $availableJudgeNames = $availableJudges->pluck('nama')->values()->all();
        $setupReady = $selectedCategory && $scoringSetting?->isReady($selectedJudgingRound);
        $setupCreated = (bool) $scoringSetting;
        $setupEditable = (bool) ($scoringSetting?->isEditable($selectedJudgingRound) ?? false);
        $setupRequested = (bool) ($scoringSetting?->isEditRequested($selectedJudgingRound) ?? false);

        // Get available judges from database for this category
        $availableJudgeNames = $availableJudges->pluck('nama')->values()->all();

        $roundSetupConfigs = $this->roundSetupConfigs(
            $selectedCategory?->branch,
            $scoringSetting,
            (string) auth()->user()?->name,
            $availableJudgeNames
        );
        $participantHasScores = (bool) ($selectedParticipant?->scores?->isNotEmpty() ?? false);
        $participantScoreRound = (string) ($selectedParticipant?->scores?->first()?->judging_round ?? $selectedJudgingRound);
        $participantScoreDraft = $this->scoreDraftForParticipant(
            $selectedParticipant,
            $participantScoreRound,
            $judgeNames,
            $criteria
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
        $categorySettings = ScoringSetting::query()
            ->whereIn('competition_category_id', $categories->pluck('id')->all())
            ->latest('id')
            ->get()
            ->keyBy('competition_category_id');
        $mfqCategories = $categories->filter(fn (CompetitionCategory $category): bool => $this->isMfqCategory($category))->values();
        $regularCategories = $categories->reject(fn (CompetitionCategory $category): bool => $this->isMfqCategory($category))->values();
        $categoryUsage = $categories->mapWithKeys(function (CompetitionCategory $category) use ($categoryUsageParticipants): array {
            $registered = (int) collect($categoryUsageParticipants->get($category->id, collect()))->count();
            $availableSlots = max((int) $category->quota, 0);

            return [
                $category->id => [
                    'available_slots' => $availableSlots,
                    'registered' => $registered,
                    'remaining_slots' => max($availableSlots - $registered, 0),
                ],
            ];
        });

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
            'setupCreated' => $setupCreated,
            'setupEditable' => $setupEditable,
            'setupRequested' => $setupRequested,
            'judgingRounds' => $judgingRounds,
            'selectedJudgingRound' => $selectedJudgingRound,
            'participantHasScores' => $participantHasScores,
            'participantScoreRound' => $participantScoreRound,
            'participantScoreDraft' => $participantScoreDraft,
            'judgeNames' => $judgeNames,
            'judgeIds' => $judgeIds,
            'criteria' => $criteria,
            'initialJudgeIndex' => (int) ($filters['judge_index'] ?? 0),
            'roundSetupConfigs' => $roundSetupConfigs,
            'recentScores' => $recentScores,
            'categories' => $categories,
            'regularCategories' => $regularCategories,
            'mfqCategories' => $mfqCategories,
            'categorySettings' => $categorySettings,
            'categoryUsage' => $categoryUsage,
            'branches' => $branches,
            'bigScreenUrl' => $bigScreenUrl,
            'selectedCategoryIsMfq' => $selectedCategoryIsMfq,
            'initialStep' => (int) ($selectedCategoryIsMfq ? 1 : ($filters['step'] ?? 1)),
            'availableJudges' => Hakim::query()
                ->orderBy('nama')
                ->get()
                ->map(fn ($h) => ['id' => $h->id, 'nama' => $h->nama, 'asal' => $h->asal])
                ->values()
                ->all(),
            'categoryJudgeIds' => $selectedCategory
                ? Hakim::byGolongan($selectedCategory->id)->pluck('id')->values()->all()
                : [],
            'availableJudgeNames' => $availableJudgeNames,
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

            // MSQ specific: group participants by district for lot-based scoring
            'selectedCategoryIsMsq' => $selectedCategoryIsMsq,
            'districtOptions' => $selectedCategoryIsMsq
                ? $this->buildDistrictOptionsForMsq($participants, $selectedJudgingRound)
                : [],
        ]);
    }

    /**
     * Build district/lot options for MSQ scoring.
     * Groups participants by district and calculates aggregate scores.
     */
    protected function buildDistrictOptionsForMsq($participants, string $selectedJudgingRound): array
    {
        if ($participants->isEmpty()) {
            return [];
        }

        // Group participants by district
        $byDistrict = $participants->groupBy(fn ($p) => (int) $p->district_id);

        return $byDistrict->map(function ($districtParticipants, $districtId) use ($selectedJudgingRound) {
            // Get representative participant (first with lot_number)
            $representative = $districtParticipants->firstWhere('lot_number', '!=', null)
                ?? $districtParticipants->firstWhere('lot_number', '!=', '')
                ?? $districtParticipants->first();

            // Aggregate scores from all participants in district
            $allScores = $districtParticipants->flatMap(fn ($p) => $p->scores ?? collect());
            $roundScores = $allScores->where('judging_round', $selectedJudgingRound);

            // Calculate aggregate metrics
            $scoreCount = $roundScores->count();
            $averageScore = $roundScores->avg('score') ?? 0;
            $latestScore = $roundScores->sortByDesc('submitted_at')->first()?->score ?? 0;

            // Count participants
            $participantCount = $districtParticipants->count();
            $scoredCount = $roundScores->pluck('participant_id')->unique()->count();

            return [
                'id' => 'district_'.$districtId,
                'district_id' => $districtId,
                'name' => $representative?->district?->name ?? 'Kecamatan '.$districtId,
                'lot_number' => $representative?->lot_number ?? '-',
                'participant_count' => $participantCount,
                'scored_count' => $scoredCount,
                'score_count' => $scoreCount,
                'average_score' => number_format((float) $averageScore, 2),
                'latest_score' => number_format((float) $latestScore, 2),
                'scoring_status' => $scoreCount > 0 ? 'Sudah Dinilai' : 'Belum Dinilai',
                'all_scored' => $scoredCount >= $participantCount,
                'photo' => $representative?->document_photo
                    ? asset('storage/'.$representative->document_photo)
                    : null,
            ];
        })->values()->all();
    }

    public function requestSettingEdit(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $restrictedCategoryIds = $this->accessibleCategoryIdsForUser($user);
        $restrictByCategory = $user?->role === 'panitia';
        $validated = $request->validate([
            'competition_category_id' => ['required', 'integer', 'exists:competition_categories,id'],
            'judging_round' => ['nullable', 'string', 'in:Penyisihan,Final'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $category = CompetitionCategory::query()
            ->when($restrictByCategory, fn ($query) => $query->whereIn('id', $restrictedCategoryIds))
            ->findOrFail((int) $validated['competition_category_id']);

        $this->ensureCategoryAccess((int) $category->id, 'competition_category_id');

        $scoringSetting = ScoringSetting::forCategory((int) $category->id);
        if (! $scoringSetting) {
            return back()->with('warning', 'Setting babak untuk golongan ini belum pernah dibuat.');
        }

        $roundLabel = $validated['judging_round'] ?? self::ALLOWED_JUDGING_ROUNDS[0];

        if ($scoringSetting->isEditable($roundLabel)) {
            return back()->with('status', "Setting babak {$roundLabel} sedang dibuka untuk diedit.");
        }

        $message = trim((string) ($validated['message'] ?? ''));
        $scoringSetting->requestEditRound($roundLabel);
        $scoringSetting->forceFill([
            ($roundLabel === 'Final' ? 'final' : 'penyisihan').'_edit_requested_at' => now(),
            ($roundLabel === 'Final' ? 'final' : 'penyisihan').'_edit_requested_by' => auth()->id(),
        ])->save();

        ActivityLogger::log(
            'scoring.settings.edit_requested',
            (auth()->user()?->name ?? 'Panitia')." meminta admin membuka/mengubah setting penilaian {$roundLabel} golongan {$category->name}.",
            $scoringSetting,
            array_filter([
                'competition_category_id' => $category->id,
                'competition_category_name' => $category->name,
                'judging_round' => $roundLabel,
                'message' => $message !== '' ? $message : null,
            ], static fn ($value) => $value !== null && $value !== ''),
            $request
        );

        return back()->with('status', "Request ke admin sudah dikirim untuk membuka atau mengubah setting {$roundLabel}.");
    }

    public function openSettingEdit(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'competition_category_id' => ['required', 'integer', 'exists:competition_categories,id'],
            'judging_round' => ['nullable', 'string', 'in:Penyisihan,Final'],
        ]);

        $category = CompetitionCategory::query()->findOrFail((int) $validated['competition_category_id']);
        $this->ensureCategoryAccess((int) $category->id, 'competition_category_id');

        $scoringSetting = ScoringSetting::forCategory((int) $category->id);
        if (! $scoringSetting) {
            return back()->with('warning', 'Setting babak untuk golongan ini belum pernah dibuat.');
        }

        $roundLabel = $validated['judging_round'] ?? self::ALLOWED_JUDGING_ROUNDS[0];
        $scoringSetting->openRound($roundLabel);
        $scoringSetting->forceFill([
            ($roundLabel === 'Final' ? 'final' : 'penyisihan').'_edit_opened_at' => now(),
            ($roundLabel === 'Final' ? 'final' : 'penyisihan').'_edit_opened_by' => auth()->id(),
        ])->save();

        ActivityLogger::log(
            'scoring.settings.edit_opened',
            (auth()->user()?->name ?? 'Admin')." membuka ulang setting penilaian {$roundLabel} golongan {$category->name}.",
            $scoringSetting,
            [
                'category_id' => $category->id,
                'category_label' => trim((string) $category->branch.' - '.(string) $category->name),
                'judging_round' => $roundLabel,
            ],
            $request
        );

        return back()->with('status', "Setting {$roundLabel} dibuka kembali. Silakan ubah lalu simpan ulang.");
    }

    public function storeSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'competition_category_id' => ['required', 'exists:competition_categories,id'],
            'judging_rounds_text' => ['required', 'string', 'max:1500'],
            'selected_judging_round' => ['nullable', 'string', 'in:Penyisihan,Final'],
            'rounds.penyisihan.judge_count' => ['required', 'integer', 'min:1', 'max:15'],
            'rounds.penyisihan.judge_names_text' => ['required', 'string', 'max:3000'],
            'rounds.penyisihan.judge_ids' => ['nullable'],
            'rounds.penyisihan.scoring_points_text' => ['required', 'string', 'max:3000'],
            'rounds.final.judge_count' => ['nullable', 'integer', 'min:1', 'max:15'],
            'rounds.final.judge_names_text' => ['nullable', 'string', 'max:3000'],
            'rounds.final.judge_ids' => ['nullable'],
            'rounds.final.scoring_points_text' => ['nullable', 'string', 'max:3000'],
        ]);

        $category = CompetitionCategory::query()->findOrFail((int) $validated['competition_category_id']);
        $this->ensureCategoryAccess((int) $category->id, 'competition_category_id');
        $judgingRounds = $this->normalizeLines($validated['judging_rounds_text']);
        if ($judgingRounds !== self::ALLOWED_JUDGING_ROUNDS) {
            throw ValidationException::withMessages([
                'judging_rounds_text' => 'Babak penilaian wajib tepat dua baris: Penyisihan dan Final.',
            ]);
        }

        $selectedRound = $validated['selected_judging_round'] ?? $judgingRounds[0] ?? 'Penyisihan';
        $roundSettings = [];
        $roundsToLock = [];

        // Build judge name to ID mapping from database
        $availableJudges = Hakim::byGolongan($category->id)->get()->keyBy('nama');
        $judgeNameToId = [];
        foreach ($availableJudges as $name => $h) {
            $judgeNameToId[mb_strtolower($name)] = (int) $h->id;
        }

        foreach (self::ROUND_KEYS as $roundKey => $roundLabel) {
            $isSelected = $roundLabel === $selectedRound;
            $judgeCount = (int) data_get($validated, 'rounds.'.$roundKey.'.judge_count');
            $judgeNames = $this->normalizeLines((string) data_get($validated, 'rounds.'.$roundKey.'.judge_names_text', ''));
            // Judge IDs might be sent as JSON string from form
            $judgeIdsRaw = data_get($validated, 'rounds.'.$roundKey.'.judge_ids', []);
            if (is_string($judgeIdsRaw)) {
                $judgeIdsRaw = json_decode($judgeIdsRaw, true) ?? [];
            }
            $judgeIdsRaw = (array) $judgeIdsRaw;
            // Convert to IDs based on names (authoritative)
            $judgeIds = [];
            foreach ($judgeNames as $idx => $name) {
                $id = $judgeIdsRaw[$idx] ?? $judgeNameToId[mb_strtolower($name)] ?? null;
                $judgeIds[] = $id;
            }
            $normalizedPointLabels = $this->normalizeLines((string) data_get($validated, 'rounds.'.$roundKey.'.scoring_points_text', ''));
            $scoringPoints = collect($normalizedPointLabels)
                ->mapWithKeys(function (string $label): array {
                    $key = Str::of($label)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();
                    $key = $key !== '' ? $key : 'poin_'.Str::random(4);

                    return [$key => $label];
                })
                ->all();

            if ($isSelected) {
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

                $roundsToLock[] = $roundLabel;
            }

            $roundSettings[$roundLabel] = [
                'judge_count' => $judgeCount,
                'judge_names' => array_values($judgeNames),
                'judge_ids' => $judgeIds,
                'scoring_points' => $scoringPoints,
                'scoring_priorities' => array_keys($scoringPoints),
            ];
        }

        $primaryRoundConfig = $roundSettings[$selectedRound] ?? reset($roundSettings) ?: [];
        $redirectJudgingRound = $selectedRound;

        // Build config per round
        $penyisihanConfig = $roundSettings['Penyisihan'] ?? [];
        $finalConfig = $roundSettings['Final'] ?? [];

        $setting = ScoringSetting::query()->updateOrCreate(
            ['competition_category_id' => $category->id],
            [
                // New prefix columns per round
                'penyisihan_judge_count' => (int) ($penyisihanConfig['judge_count'] ?? 0),
                'penyisihan_judge_names' => array_values($penyisihanConfig['judge_names'] ?? []),
                'penyisihan_judge_ids' => $penyisihanConfig['judge_ids'] ?? [],
                'penyisihan_scoring_points' => $penyisihanConfig['scoring_points'] ?? [],
                'penyisihan_edit_state' => in_array('Penyisihan', $roundsToLock) ? 'locked' : 'editable',
                'final_judge_count' => (int) ($finalConfig['judge_count'] ?? 0),
                'final_judge_names' => array_values($finalConfig['judge_names'] ?? []),
                'final_judge_ids' => $finalConfig['judge_ids'] ?? [],
                'final_scoring_points' => $finalConfig['scoring_points'] ?? [],
                'final_edit_state' => in_array('Final', $roundsToLock) ? 'locked' : 'editable',
                // Legacy columns (for backward compat)
                'judge_count' => (int) ($primaryRoundConfig['judge_count'] ?? 0),
                'judge_names' => array_values($primaryRoundConfig['judge_names'] ?? []),
                'judging_rounds' => array_values($judgingRounds),
                'scoring_points' => $primaryRoundConfig['scoring_points'] ?? [],
                'scoring_priorities' => $primaryRoundConfig['scoring_priorities'] ?? [],
                'round_settings' => $roundSettings,
                'configured_by' => auth()->id(),
                // Legacy edit state columns (cleared)
                'edit_state' => 'locked',
                'edit_requested_at' => null,
                'edit_requested_by' => null,
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
                'locked_rounds' => $roundsToLock,
            ]
        );

        return redirect()
            ->route('scoring', [
                'competition_category_id' => $category->id,
                'branch' => $category->branch,
                'judging_round' => $redirectJudgingRound,
                'step' => 3,
            ])
            ->with('status', 'Setting penilaian untuk golongan '.$category->name.' berhasil disimpan.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'participant_id' => ['required', 'exists:participants,id'],
            'judging_round' => ['required', 'string', 'max:100'],
            'active_judge_index' => ['nullable', 'integer', 'min:0', 'max:20'],
            'remarks' => ['nullable', 'array'],
        ]);

        $participant = Participant::query()
            ->with('category')
            ->whereKey($validated['participant_id'])
            ->where('verification_status', 'verified')
            ->firstOrFail();
        $this->ensureCategoryAccess((int) $participant->competition_category_id, 'participant_id');

        $scoringSetting = ScoringSetting::forCategory($participant->competition_category_id);

        if (! $scoringSetting || ! $scoringSetting->isReady($validated['judging_round'])) {
            throw ValidationException::withMessages([
                'participant_id' => 'Setting penilaian untuk golongan ini belum disiapkan. Simpan setting penilaian terlebih dahulu.',
            ]);
        }

        // Check if this is MSQ (Syarhil Quran) - district-based scoring
        $category = $participant->category;
        $isMsqCategory = filled($category?->maqra_system_type) && $category->maqra_system_type === 'syarhil';

        // For MSQ: get all participants in the same district that haven't been scored yet
        // For regular: just the selected participant
        if ($isMsqCategory) {
            $participantsToScore = Participant::query()
                ->where('competition_category_id', $participant->competition_category_id)
                ->where('district_id', $participant->district_id)
                ->where('verification_status', 'verified')
                ->whereDoesntHave('scores', function ($query) use ($validated) {
                    $query->where('judging_round', $validated['judging_round']);
                })
                ->get();

            if ($participantsToScore->isEmpty()) {
                throw ValidationException::withMessages([
                    'participant_id' => 'Semua peserta di kecamatan ini sudah dinilai untuk babak '.$validated['judging_round'].'.',
                ]);
            }
        } else {
            // Allow direct update if already scored (edit mode)
            $existingScore = $participant->scores()->where('judging_round', $validated['judging_round'])->first();
            if ($existingScore) {
                // Update existing score entry
                $participantsToScore = collect([$participant]);
                $updateExisting = true;
            } else {
                $participantsToScore = collect([$participant]);
                $updateExisting = false;
            }
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

        // Use config judge_ids as source of truth (handles judges not in hakims table)
        $configJudgeIds = $roundConfig['judge_ids'] ?? [];
        $judgeIds = [];
        foreach ($judgeNames as $index => $name) {
            $id = $configJudgeIds[$index] ?? $name;
            $judgeIds[$id] = $name;
        }

        // Build validation rules using judge IDs (scores can be empty, treated as 0)
        $scoreRules = [];
        foreach ($judgeIds as $judgeId => $judgeName) {
            $scoreRules['remarks.'.$judgeId] = ['nullable', 'string', 'max:1000'];
            foreach (array_keys($criteria) as $key) {
                $scoreRules['scores.'.$judgeId.'.'.$key] = ['nullable', 'numeric', 'min:0', 'max:100'];
            }
        }
        $scorePayload = $request->validate($scoreRules);

        // Normalize scores: treat empty/null as 0
        foreach ($scorePayload['scores'] ?? [] as $judgeId => &$pointScores) {
            foreach ($pointScores as $pointKey => &$value) {
                if ($value === '' || $value === null) {
                    $value = 0;
                } else {
                    $value = round((float) $value, 2);
                }
            }
        }

        // Collect all judge scores into JSON format
        // Perpoin: hitung rata-rata per poin dari hakim yang memberi nilai
        // Total Score = jumlah semua poin (setelah dirata-ratakan per poin)
        $allJudgeScores = [];
        $pointSums = [];    // Menyimpan jumlah per poin (untuk rata-rata)
        $pointCounts = [];  // Menyimpan jumlah hakim yang memberi nilai per poin

        // First pass: collect scores per judge
        foreach ($judgeIds as $judgeId => $judgeName) {
            $scores = collect(data_get($scorePayload, 'scores.'.$judgeId, []))
                ->map(fn ($value) => $value !== null && $value !== '' ? round((float) $value, 2) : null)
                ->all();

            // Accumulate point sums and counts (hanya yang tidak null)
            foreach ($scores as $pointKey => $pointValue) {
                if ($pointValue !== null) {
                    if (!isset($pointSums[$pointKey])) {
                        $pointSums[$pointKey] = 0;
                        $pointCounts[$pointKey] = 0;
                    }
                    $pointSums[$pointKey] += $pointValue;
                    $pointCounts[$pointKey]++;
                }
            }

            $allJudgeScores[$judgeName] = [
                'judge_id' => $judgeId,
                'scores' => $scores,
                'remarks' => data_get($scorePayload, 'remarks.'.$judgeId),
            ];
        }

        // Calculate point averages: jumlah / jumlah hakim yang memberi nilai
        $pointAverages = [];
        $pointTotals = [];
        foreach ($pointSums as $pointKey => $sum) {
            $count = $pointCounts[$pointKey] ?? 1;
            $pointAverages[$pointKey] = $count > 0 ? round($sum / $count, 2) : 0;
            $pointTotals[$pointKey] = $sum; // Untuk display/debugging
        }

        // Total Score = jumlah semua rata-rata poin
        $totalScore = count($pointAverages) > 0
            ? round(array_sum($pointAverages), 2)
            : 0;

        // Add point averages and final score to each judge entry
        foreach ($allJudgeScores as $judgeName => &$data) {
            $data['point_averages'] = $pointAverages;
            $data['point_totals'] = $pointTotals;
            $data['point_counts'] = $pointCounts;
            $data['score'] = $totalScore;
        }
        unset($data);

        // Total score is used for ranking (same as average_score for backward compatibility)
        $averageScore = $totalScore;

        \Log::info('SCORE_SAVED', [
            'participant_id' => $participant->id,
            'judging_round' => $validated['judging_round'],
            'judge_ids_used' => array_keys($allJudgeScores),
            'all_judge_scores' => $allJudgeScores,
            'point_sums' => $pointSums,
            'point_counts' => $pointCounts,
            'point_averages' => $pointAverages,
            'total_score' => $totalScore,
        ]);

        // Create score entries for all participants (for MSQ) or single participant (regular/update)
        $createdScoreEntries = [];
        $districtName = $participant->district?->name ?? 'Kecamatan '.$participant->district_id;

        foreach ($participantsToScore as $p) {
            // Check if update mode or create mode
            $existingEntry = isset($updateExisting) && $updateExisting
                ? ScoreEntry::where('participant_id', $p->id)
                    ->where('judging_round', $validated['judging_round'])
                    ->first()
                : null;

            if ($existingEntry) {
                // Update existing entry
                $existingEntry->update([
                    'scores' => $allJudgeScores,
                    'average_score' => $averageScore,
                    'submitted_at' => now(),
                ]);
                $scoreEntry = $existingEntry->fresh();
            } else {
                // Create new entry
                $scoreEntry = ScoreEntry::create([
                    'participant_id' => $p->id,
                    'judging_round' => $validated['judging_round'],
                    'scores' => $allJudgeScores,
                    'average_score' => $averageScore,
                    'submitted_at' => now(),
                ]);
            }
            $createdScoreEntries[] = $scoreEntry;

            // Log activity for each entry
            ActivityLogger::log(
                $existingEntry ? 'scoring.score.updated' : 'scoring.score.created',
                (auth()->user()?->name ?? 'Panitia').' '.($existingEntry ? 'memperbarui' : 'menginput').' nilai '.$validated['judging_round'].($isMsqCategory ? ' (MSQ)' : '').' untuk peserta '.$p->name.'.',
                $p,
                [
                    'participant_id' => $p->id,
                    'participant_name' => $p->name,
                    'registration_number' => $p->registration_number,
                    'lot_number' => $p->lot_number,
                    'category_id' => $p->competition_category_id,
                    'category_label' => trim((string) ($category?->branch ?? '').' - '.(string) ($category?->name ?? '')),
                    'district_id' => $isMsqCategory ? $p->district_id : null,
                    'district_name' => $isMsqCategory ? $districtName : null,
                    'judging_round' => $validated['judging_round'],
                    'judge_total' => count($allJudgeScores),
                    'total_score' => $averageScore,
                    'point_totals' => $pointTotals,
                    'judge_scores' => $allJudgeScores,
                    'submitted_at' => now()->toIso8601String(),
                    'input_by_user_id' => auth()->id(),
                    'input_by_user_name' => auth()->user()?->name,
                    'input_by_user_role' => auth()->user()?->role,
                    'ip_address' => $request->ip(),
                ]
            );
        }

        // Determine success message based on count
        $savedCount = count($createdScoreEntries);
        if ($isMsqCategory && $savedCount > 1) {
            $successMessage = "Nilai {$savedCount} peserta di {$districtName} berhasil disimpan untuk babak {$validated['judging_round']}.";
        } else {
            $successMessage = "Nomor lot {$participant->lot_number} · {$participant->name} · babak {$validated['judging_round']} berhasil disimpan.";
        }

        $redirectUrl = route('scoring', [
                'participant_id' => $participant->id,
                'competition_category_id' => $participant->competition_category_id,
                'branch' => $category?->branch,
                'judging_round' => $validated['judging_round'],
                'step' => 3,
                'judge_index' => (int) ($validated['active_judge_index'] ?? 0),
            ]).'#form-penilaian';

        return redirect()
            ->to($redirectUrl)
            ->with('toast', [
                'tone' => 'success',
                'title' => 'Nilai tersimpan',
                'message' => $successMessage,
            ]);
    }

    public function storeCorrectionRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'participant_id' => ['required', 'exists:participants,id'],
            'judging_round' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $participant = Participant::query()
            ->with(['category', 'scores' => fn ($query) => $query->orderByDesc('submitted_at')])
            ->whereKey($validated['participant_id'])
            ->where('verification_status', 'verified')
            ->firstOrFail();
        $this->ensureCategoryAccess((int) $participant->competition_category_id, 'participant_id');

        $scoringSetting = ScoringSetting::forCategory($participant->competition_category_id);
        if (! $scoringSetting || ! $scoringSetting->isReady()) {
            throw ValidationException::withMessages([
                'participant_id' => 'Setting penilaian untuk golongan ini belum disiapkan. Permintaan perbaikan belum bisa dikirim.',
            ]);
        }

        if ($participant->scores->isEmpty()) {
            throw ValidationException::withMessages([
                'participant_id' => 'Peserta ini belum punya nilai yang bisa diminta perbaikannya.',
            ]);
        }

        $judgingRounds = $this->judgingRoundsForSetting($scoringSetting);
        if (! in_array($validated['judging_round'], $judgingRounds, true)) {
            throw ValidationException::withMessages([
                'judging_round' => 'Babak perbaikan harus sesuai setting golongan ini.',
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

        // Use config judge_ids as source of truth (handles judges not in hakims table)
        $configJudgeIds = $roundConfig['judge_ids'] ?? [];
        $judgeIds = [];
        foreach ($judgeNames as $index => $name) {
            $id = $configJudgeIds[$index] ?? $name;
            $judgeIds[$id] = $name;
        }

        // Build validation rules using judge IDs
        $scoreRules = [];
        foreach ($judgeIds as $judgeId => $judgeName) {
            $scoreRules['remarks.'.$judgeId] = ['nullable', 'string', 'max:1000'];
            foreach (array_keys($criteria) as $key) {
                $scoreRules['scores.'.$judgeId.'.'.$key] = ['nullable', 'numeric', 'min:0', 'max:100'];
            }
        }
        $scorePayload = $request->validate($scoreRules);

        $requestedScores = [];
        $requestedRemarks = [];
        foreach ($judgeIds as $judgeId => $judgeName) {
            $scores = collect(data_get($scorePayload, 'scores.'.$judgeId, []))
                ->map(fn ($value) => round((float) $value, 2))
                ->all();

            $requestedScores[] = [
                'judge_id' => $judgeId,
                'judge_name' => $judgeName,
                'score' => round(collect($scores)->avg() ?? 0, 2),
                'score_breakdown' => $scores,
                'remarks' => data_get($scorePayload, 'remarks.'.$judgeId),
            ];
            $requestedRemarks[$judgeName] = data_get($scorePayload, 'remarks.'.$judgeId);
        }

        $note = trim((string) ($validated['note'] ?? ''));

        $correctionRequest = ScoreCorrectionRequest::query()->create([
            'participant_id' => $participant->id,
            'competition_category_id' => $participant->competition_category_id,
            'judging_round' => $validated['judging_round'],
            'requested_by' => auth()->id(),
            'status' => 'pending',
            'note' => $note !== '' ? $note : null,
            'requested_scores' => $requestedScores,
            'requested_remarks' => $requestedRemarks,
            'requested_at' => now(),
        ]);

        ActivityLogger::log(
            'scoring.correction.requested',
            (auth()->user()?->name ?? 'Panitia').' meminta perbaikan nilai untuk peserta '.$participant->name.'.',
            $participant,
            [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'lot_number' => $participant->lot_number,
                'category_id' => $participant->competition_category_id,
                'category_label' => trim((string) ($participant->category?->branch ?? '').' - '.(string) ($participant->category?->name ?? '')),
                'judging_round' => $validated['judging_round'],
                'status' => 'pending',
                'request_id' => $correctionRequest->id,
            ]
        );

        return redirect()
            ->to(route('scoring', [
                'participant_id' => $participant->id,
                'competition_category_id' => $participant->competition_category_id,
                'branch' => $participant->category?->branch,
                'judging_round' => $validated['judging_round'],
                'step' => 3,
            ]).'#form-penilaian')
            ->with('toast', [
                'tone' => 'success',
                'title' => 'Request terkirim',
                'message' => 'Permintaan perbaikan nilai untuk lot '.$participant->lot_number.' berhasil dikirim ke admin.',
            ]);
    }

    protected function criteriaForBranch(?string $branch): array
    {
        return config('scoring.criteria.'.$branch)
            ?? config('scoring.criteria.default', []);
    }

    protected function criteriaForContext(?string $branch, ?ScoringSetting $scoringSetting, string $roundLabel = 'Penyisihan'): array
    {
        // Try new prefix columns first
        if ($scoringSetting) {
            $config = $scoringSetting->roundConfig($roundLabel);
            if (! empty($config['scoring_points'])) {
                return $config['scoring_points'];
            }
            // Legacy fallback
            if (count($scoringSetting->scoring_points ?? []) > 0) {
                return $scoringSetting->scoring_points;
            }
        }

        return $this->criteriaForBranch($branch);
    }

    protected function judgeNamesForSetting(?ScoringSetting $scoringSetting, string $fallbackName, string $roundLabel = 'Penyisihan'): array
    {
        // Try new prefix columns first
        if ($scoringSetting) {
            $config = $scoringSetting->roundConfig($roundLabel);
            $names = array_values(array_filter($config['judge_names'] ?? []));
            if ($names !== []) {
                return $names;
            }
            // Legacy fallback
            $names = array_values(array_filter($scoringSetting?->judge_names ?? []));
            if ($names !== []) {
                return $names;
            }
        }

        return [$fallbackName];
    }

    protected function judgingRoundsForSetting(?ScoringSetting $scoringSetting): array
    {
        $rounds = array_values(array_filter($scoringSetting?->judging_rounds ?? []));

        if ($rounds !== []) {
            return array_values(array_intersect(self::ALLOWED_JUDGING_ROUNDS, $rounds));
        }

        return self::ALLOWED_JUDGING_ROUNDS;
    }

    protected function roundSetupConfigs(?string $branch, ?ScoringSetting $scoringSetting, string $fallbackName, array $availableJudgeNames = []): array
    {
        $configs = [];

        foreach (self::ROUND_KEYS as $roundLabel) {
            $configs[$roundLabel] = $this->roundConfigForSetting($branch, $scoringSetting, $roundLabel, $fallbackName, $availableJudgeNames);
        }

        return $configs;
    }

    protected function roundConfigForSetting(?string $branch, ?ScoringSetting $scoringSetting, string $roundLabel, string $fallbackName, array $availableJudgeNames = []): array
    {
        $defaultCriteria = $this->criteriaForBranch($branch);

        // New format: read from prefix columns
        if ($scoringSetting) {
            $config = $scoringSetting->roundConfig($roundLabel);
            if (! empty($config['judge_names']) && ! empty($config['scoring_points'])) {
                $judgeNames = array_values(array_filter($config['judge_names'] ?? []));
                $scoringPoints = $config['scoring_points'] ?? [];
                $scoringPriorities = array_values(array_filter($config['scoring_priorities'] ?? array_keys($scoringPoints)));

                return [
                    'judge_count' => (int) ($config['judge_count'] ?? count($judgeNames) ?: 1),
                    'judge_names' => $judgeNames !== [] ? $judgeNames : ($availableJudgeNames ?: [$fallbackName]),
                    'judge_ids' => $config['judge_ids'] ?? [],
                    'scoring_points' => $scoringPoints !== [] ? $scoringPoints : $defaultCriteria,
                    'scoring_priorities' => $scoringPriorities !== [] ? $scoringPriorities : array_keys($scoringPoints ?: $defaultCriteria),
                ];
            }

            // Fallback: read from legacy round_settings JSON
            $roundSettings = $scoringSetting->round_settings ?? [];
            $legacyConfig = is_array($roundSettings) ? ($roundSettings[$roundLabel] ?? null) : null;

            if (is_array($legacyConfig)) {
                $judgeNames = array_values(array_filter($legacyConfig['judge_names'] ?? []));
                $scoringPoints = $legacyConfig['scoring_points'] ?? [];
                $scoringPriorities = array_values(array_filter($legacyConfig['scoring_priorities'] ?? array_keys($scoringPoints)));

                return [
                    'judge_count' => (int) ($legacyConfig['judge_count'] ?? count($judgeNames) ?: 1),
                    'judge_names' => $judgeNames !== [] ? $judgeNames : ($availableJudgeNames ?: [$fallbackName]),
                    'judge_ids' => $legacyConfig['judge_ids'] ?? [],
                    'scoring_points' => $scoringPoints !== [] ? $scoringPoints : $defaultCriteria,
                    'scoring_priorities' => $scoringPriorities !== [] ? $scoringPriorities : array_keys($scoringPoints ?: $defaultCriteria),
                ];
            }
        }

        // Ultimate fallback
        $fallbackCriteria = $this->criteriaForContext($branch, $scoringSetting);
        $fallbackJudgeNames = $availableJudgeNames ?: $this->judgeNamesForSetting($scoringSetting, $fallbackName);

        return [
            'judge_count' => (int) ($scoringSetting?->judge_count ?? count($fallbackJudgeNames) ?: 1),
            'judge_names' => $fallbackJudgeNames,
            'scoring_points' => $fallbackCriteria,
            'scoring_priorities' => array_values(array_filter($scoringSetting?->scoring_priorities ?? array_keys($fallbackCriteria))),
        ];
    }

    protected function scoreInputRules(array $judgeNames, array $criteria): array
    {
        $scoreRules = [];

        foreach ($judgeNames as $judgeName) {
            $escapedJudgeName = $this->escapeDottedKey($judgeName);

            $scoreRules['remarks.'.$escapedJudgeName] = ['nullable', 'string', 'max:1000'];

            foreach (array_keys($criteria) as $key) {
                $escapedKey = $this->escapeDottedKey($key);
                $scoreRules['scores.'.$escapedJudgeName.'.'.$escapedKey] = ['required', 'numeric', 'min:0', 'max:100'];
            }
        }

        return $scoreRules;
    }

    protected function scoreDraftForParticipant(?Participant $participant, string $judgingRound, array $judgeNames, array $criteria): array
    {
        if (! $participant) {
            return [];
        }

        // Get the latest score entry for this round
        $scoreEntry = $participant->scores
            ->where('judging_round', $judgingRound)
            ->latest('submitted_at')
            ->first();

        $draft = [];

        // Check if using new format (scores JSON) or old format
        $isNewFormat = $scoreEntry && $scoreEntry->scores && is_array($scoreEntry->scores);

        if ($isNewFormat) {
            // New format: scores JSON with judge names as keys
            $allJudgeScores = $scoreEntry->scores;
        } else {
            // Old format or no scores: empty
            $allJudgeScores = [];
        }

        foreach ($judgeNames as $judgeName) {
            $judgeData = $allJudgeScores[$judgeName] ?? null;

            if ($isNewFormat && $judgeData) {
                // New format: get scores from judge's scores array
                $judgeScores = $judgeData['scores'] ?? [];
                $remarks = $judgeData['remarks'] ?? '';
            } else {
                // No score for this judge or old format
                $judgeScores = [];
                $remarks = '';
            }

            $draft[$judgeName] = [
                'scores' => [],
                'remarks' => (string) $remarks,
            ];

            foreach (array_keys($criteria) as $key) {
                // Try to get from new format first, then fallback to old format
                if ($isNewFormat && isset($judgeScores[$key])) {
                    $draft[$judgeName]['scores'][$key] = (string) $judgeScores[$key];
                } elseif ($scoreEntry && $scoreEntry->score_breakdown) {
                    $draft[$judgeName]['scores'][$key] = (string) data_get($scoreEntry->score_breakdown, $key, '');
                } else {
                    $draft[$judgeName]['scores'][$key] = '';
                }
            }
        }

        return $draft;
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

    protected function escapeDottedKey(string $key): string
    {
        return str_replace('.', '\\.', trim($key));
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

    protected function isMfqCategory(CompetitionCategory $category): bool
    {
        // Prioritas 1: cek kolom maqra_system_type
        if (filled($category->maqra_system_type)) {
            return $category->maqra_system_type === 'fahmil';
        }

        // Fallback: string matching (untuk data lama)
        $haystack = mb_strtolower(trim((string) $category->branch.' '.(string) $category->name.' '.(string) $category->slug));

        return str_contains($haystack, 'fahmil');
    }

    public function poll(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'competition_category_id' => ['nullable', 'integer'],
            'last_timestamp' => ['nullable', 'string'],
            'participant_id' => ['nullable', 'integer'],
        ]);

        $user = auth()->user();
        $restrictByCategory = $user?->role === 'panitia';
        $restrictedCategoryIds = $this->accessibleCategoryIdsForUser($user);

        $query = ScoreEntry::query()
            ->with('participant.category')
            ->when($restrictByCategory, fn ($q) => $q->whereIn('competition_category_id', $restrictedCategoryIds))
            ->when(filled($validated['competition_category_id'] ?? null), fn ($q) => $q->where('competition_category_id', $validated['competition_category_id']))
            ->when(filled($validated['participant_id'] ?? null), fn ($q) => $q->where('participant_id', $validated['participant_id']))
            ->orderByDesc('submitted_at')
            ->limit(20);

        if (! empty($validated['last_timestamp'])) {
            try {
                $lastTime = \Carbon\Carbon::parse($validated['last_timestamp']);
                $query->where('submitted_at', '>', $lastTime);
            } catch (\Exception $e) {
                // Ignore invalid timestamp, return all
            }
        }

        $scores = $query->get();
        $latestTimestamp = $scores->max('submitted_at');

        $data = $scores->map(function (ScoreEntry $score) {
            $participant = $score->participant;
            $scoresArray = $score->scores;
            $averageScore = $score->average_score;

            if ($scoresArray === null) {
                $scoresArray = [$score->judge_name => [
                    'score' => (float) $score->score,
                    'breakdown' => $score->score_breakdown ?? [],
                    'remarks' => $score->remarks,
                ]];
                $averageScore = (float) $score->score;
            }

            return [
                'id' => $score->id,
                'participant_id' => $score->participant_id,
                'participant_name' => $participant?->name,
                'category_name' => $participant?->category?->name,
                'branch' => $participant?->category?->branch,
                'district_name' => $participant?->district?->name,
                'lot_number' => $participant?->lot_number,
                'judging_round' => $score->judging_round,
                'average_score' => (float) $averageScore,
                'scores' => $scoresArray,
                'submitted_at' => $score->submitted_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'scores' => $data,
            'latest_timestamp' => $latestTimestamp?->toIso8601String(),
            'count' => $scores->count(),
            'realtime_available' => $this->isReverbAvailable(),
        ]);
    }

    protected function isReverbAvailable(): bool
    {
        try {
            $host = config('broadcasting.connections.reverb.options.host', '127.0.0.1');
            $port = (int) config('broadcasting.connections.reverb.options.port', 8080);

            $socket = @fsockopen($host, $port, $errno, $errstr, 1);
            if ($socket) {
                fclose($socket);
                return true;
            }
        } catch (\Exception $e) {
            // Fallback available
        }

        return false;
    }

    public function ranking(Request $request): View
    {
        $user = auth()->user();
        $restrictedCategoryIds = $this->accessibleCategoryIdsForUser($user);
        $restrictByCategory = $user?->role === 'panitia';

        $filters = $request->validate([
            'competition_category_id' => ['nullable', 'integer'],
            'judging_round' => ['nullable', 'string', 'in:Penyisihan,Final'],
            'appearance_day' => ['nullable', 'integer', 'min:0'],
        ]);

        $selectedCategory = filled($filters['competition_category_id'] ?? null)
            ? CompetitionCategory::query()
                ->when($restrictByCategory, fn ($query) => $query->whereIn('id', $restrictedCategoryIds))
                ->find($filters['competition_category_id'])
            : null;

        $selectedJudgingRound = $filters['judging_round'] ?? 'Penyisihan';
        $selectedAppearanceDay = $filters['appearance_day'] ?? null;
        $scoringSetting = ScoringSetting::forCategory($selectedCategory?->id);

        // Get appearance schedule for the category
        $appearanceSchedule = $selectedCategory
            ? \App\Models\AppearanceSchedule::where('competition_category_id', $selectedCategory->id)->first()
            : null;

        // Build lot numbers pool from appearance schedule
        $poolLotNumbers = [];
        $dayRanges = [];
        if ($appearanceSchedule) {
            $poolData = $appearanceSchedule->getPoolLotNumbers();
            $poolLotNumbers = $poolData['all'] ?? [];
            sort($poolLotNumbers);

            // Build day ranges for display
            $daySchedules = $appearanceSchedule->day_schedules ?? [];
            $offset = 0;
            foreach ($daySchedules as $dayIndex => $daySchedule) {
                $count = (int) ($daySchedule['count'] ?? 0);
                $dayLots = array_slice($poolLotNumbers, $offset, $count);
                $dayRanges[$dayIndex] = [
                    'day_index' => $dayIndex,
                    'name' => $daySchedule['name'] ?? ('Hari ' . ($dayIndex + 1)),
                    'date' => $daySchedule['date'] ?? null,
                    'time' => $daySchedule['time'] ?? null,
                    'count' => $count,
                    'lot_numbers' => $dayLots,
                    'lot_range' => !empty($dayLots)
                        ? str_pad((string) reset($dayLots), 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) end($dayLots), 2, '0', STR_PAD_LEFT)
                        : '-',
                ];
                $offset += $count;
            }
        }

        // Get participants with scores for this category and round
        $participantsQuery = Participant::query()
            ->with(['category', 'district', 'scores' => function ($query) use ($selectedJudgingRound) {
                $query->where('judging_round', $selectedJudgingRound);
            }])
            ->where('verification_status', 'verified')
            ->when($selectedCategory, fn ($query) => $query->where('competition_category_id', $selectedCategory->id))
            ->when($restrictByCategory, fn ($query) => $query->whereIn('competition_category_id', $restrictedCategoryIds));

        $participants = $participantsQuery->get();

        // Build rankings based on average score with gender and lot info
        $rankedParticipants = $participants
            ->map(function ($participant) use ($selectedJudgingRound) {
                $scores = $participant->scores ?? collect();
                $roundScores = $scores->where('judging_round', $selectedJudgingRound);
                $averageScore = $roundScores->avg('average_score') ?? $roundScores->avg('score') ?? 0;

                $lotNumber = $participant->lot_number ?? '';
                $parts = explode('-', $lotNumber);
                $lotSuffix = (int) end($parts);

                return [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'lot_number' => $lotNumber,
                    'lot_suffix' => $lotSuffix,
                    'gender' => $participant->gender ?? 'putra',
                    'district_name' => $participant->district?->name ?? '-',
                    'institution' => $participant->institution,
                    'photo_url' => $participant->document_photo
                        ? asset('storage/'.ltrim(str_replace('\\', '/', $participant->document_photo), '/'))
                        : null,
                    'average_score' => round((float) $averageScore, 2),
                    'score_count' => $roundScores->count(),
                    'has_score' => $roundScores->count() > 0,
                    'latest_score_entry' => $roundScores->sortByDesc('submitted_at')->first(),
                ];
            })
            ->sortByDesc(function ($item) {
                return [$item['average_score'], $item['lot_suffix'] * -1];
            })
            ->values()
            ->map(function ($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            });

        // Group by gender
        $putraRankings = $rankedParticipants->where('gender', 'putra')->values();
        $putriRankings = $rankedParticipants->where('gender', 'putri')->values();

        // Re-rank within each gender
        $putraRankings = $putraRankings->map(function ($item, $index) {
            $item['gender_rank'] = $index + 1;
            return $item;
        });
        $putriRankings = $putriRankings->map(function ($item, $index) {
            $item['gender_rank'] = $index + 1;
            return $item;
        });

        // Assign participants to appearance days based on lot suffix
        $participantsByDay = [];
        $allDays = $appearanceSchedule?->day_schedules ?? [];
        $workingPoolLots = $poolLotNumbers; // Copy for manipulation
        foreach ($allDays as $dayIndex => $daySchedule) {
            $count = (int) ($daySchedule['count'] ?? 0);
            $dayLotNumbers = array_slice($workingPoolLots, 0, $count);

            // Get participants for this day
            $dayParticipants = $rankedParticipants->filter(function ($p) use ($dayLotNumbers) {
                return in_array($p['lot_suffix'], $dayLotNumbers);
            })->values();

            // Separate by gender
            $dayPutra = $dayParticipants->filter(fn($p) => $p['gender'] === 'putra')->values()->map(fn($item, $idx) => array_merge($item, ['day_gender_rank' => $idx + 1]));
            $dayPutri = $dayParticipants->filter(fn($p) => $p['gender'] === 'putri')->values()->map(fn($item, $idx) => array_merge($item, ['day_gender_rank' => $idx + 1]));

            $participantsByDay[$dayIndex] = [
                'day_index' => $dayIndex,
                'name' => $daySchedule['name'] ?? ('Hari ' . ($dayIndex + 1)),
                'date' => $daySchedule['date'] ?? null,
                'time' => $daySchedule['time'] ?? null,
                'formatted_date' => isset($daySchedule['date'])
                    ? \Carbon\Carbon::parse($daySchedule['date'])->translatedFormat('d F Y')
                    : null,
                'lot_range' => !empty($dayLotNumbers)
                    ? str_pad((string) reset($dayLotNumbers), 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) end($dayLotNumbers), 2, '0', STR_PAD_LEFT)
                    : '-',
                'total_lots' => $count,
                'total_participants' => $dayParticipants->count(),
                'putra_count' => $dayPutra->count(),
                'putri_count' => $dayPutri->count(),
                'participants' => $dayParticipants,
                'putra' => $dayPutra,
                'putri' => $dayPutri,
                'scored_count' => $dayParticipants->filter(fn($p) => $p['has_score'])->count(),
            ];

            // Reset offset for next iteration
            array_splice($workingPoolLots, 0, $count);
        }

        // Stats
        $verifiedCount = Participant::query()
            ->where('verification_status', 'verified')
            ->when($selectedCategory, fn ($query) => $query->where('competition_category_id', $selectedCategory->id))
            ->when($restrictByCategory, fn ($query) => $query->whereIn('competition_category_id', $restrictedCategoryIds))
            ->count();

        $scoredCount = $rankedParticipants->where('has_score', true)->count();
        $putraCount = $rankedParticipants->where('gender', 'putra')->count();
        $putriCount = $rankedParticipants->where('gender', 'putri')->count();
        $totalParticipants = $rankedParticipants->count();

        $categoryLabel = $selectedCategory
            ? trim(($selectedCategory->branch ?? '').' - '.($selectedCategory->name ?? ''))
            : 'Semua Golongan';

        return view('pages/scoring-ranking', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'rankedParticipants' => $rankedParticipants,
            'putraRankings' => $putraRankings,
            'putriRankings' => $putriRankings,
            'participantsByDay' => $participantsByDay,
            'selectedCategory' => $selectedCategory,
            'selectedJudgingRound' => $selectedJudgingRound,
            'selectedAppearanceDay' => $selectedAppearanceDay,
            'categoryLabel' => $categoryLabel,
            'scoringSetting' => $scoringSetting,
            'appearanceSchedule' => $appearanceSchedule,
            'dayRanges' => $dayRanges,
            'restrictedCategoryIds' => $restrictedCategoryIds,
            'isRestricted' => $restrictByCategory,
            'stats' => [
                'verified_participants' => $verifiedCount,
                'scored_participants' => $scoredCount,
                'total_participants' => $totalParticipants,
                'putra_count' => $putraCount,
                'putri_count' => $putriCount,
            ],
            'filters' => $filters,
        ]);
    }
}
