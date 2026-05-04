<?php

namespace App\Http\Controllers;

use App\Models\CompetitionCategory;
use App\Models\Participant;
use App\Models\ScoreEntry;
use App\Models\ScoringSetting;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;

class ParticipantResultController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $isParticipant = $user?->role === 'peserta';

        [$filters, $participants, $selectedParticipant, $scoreTimeline, $branchCriteria] = $this->resultContext($request, $isParticipant);
        $scores = $selectedParticipant?->scores ?? collect();

        return view('pages/results-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) $user?->role),
            'participants' => $participants,
            'selectedParticipant' => $selectedParticipant,
            'categories' => CompetitionCategory::query()->orderBy('sort_order')->orderBy('branch')->get(),
            'filters' => [
                'participant_id' => $selectedParticipant?->id ?: ($filters['participant_id'] ?? ''),
                'competition_category_id' => $filters['competition_category_id'] ?? '',
                'keyword' => $filters['keyword'] ?? '',
            ],
            'resultStats' => [
                'entries' => $scores->count(),
                'latest' => number_format((float) ($scoreTimeline->first()?->score ?? 0), 2),
                'best' => number_format((float) ($scores->max('score') ?? 0), 2),
                'average' => number_format((float) ($scores->avg('score') ?? 0), 2),
            ],
            'scoreTimeline' => $scoreTimeline,
            'branchCriteria' => $branchCriteria,
            'isParticipant' => $isParticipant,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia', 'official', 'pendamping'], true), 403);

        [, , $selectedParticipant, $scoreTimeline, $branchCriteria] = $this->resultContext($request, false);

        abort_unless($selectedParticipant, 404);

        $filename = 'hasil-nilai-'.strtolower(str_replace(' ', '-', $selectedParticipant->registration_number ?? 'peserta')).'.csv';

        return response()->streamDownload(function () use ($selectedParticipant, $scoreTimeline, $branchCriteria): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Nama Peserta', $selectedParticipant->name]);
            fputcsv($handle, ['No. Registrasi', $selectedParticipant->registration_number]);
            fputcsv($handle, ['Kecamatan', $selectedParticipant->district?->name ?? '-']);
            fputcsv($handle, ['Cabang / Golongan', trim(($selectedParticipant->category?->branch ?? '-').' - '.($selectedParticipant->category?->name ?? '-'))]);
            fputcsv($handle, []);
            fputcsv($handle, ['Tanggal', 'Hakim / Operator', 'Babak', 'Total Nilai', 'Breakdown', 'Catatan']);

            foreach ($scoreTimeline as $entry) {
                $breakdown = collect($entry->score_breakdown ?? [])
                    ->map(function ($value, $key) use ($branchCriteria): string {
                        $label = $branchCriteria[$key] ?? ucwords(str_replace('_', ' ', (string) $key));

                        return $label.': '.number_format((float) $value, 2);
                    })
                    ->implode(' | ');

                fputcsv($handle, [
                    optional($entry->submitted_at)->format('d/m/Y H:i'),
                    $entry->judge_name,
                    $entry->judging_round,
                    number_format((float) $entry->score, 2),
                    $breakdown,
                    $entry->remarks,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request): View
    {
        $isParticipant = auth()->user()?->role === 'peserta';
        [$filters, $participants, $selectedParticipant, $scoreTimeline, $branchCriteria] = $this->resultContext($request, $isParticipant);
        $scores = $selectedParticipant?->scores ?? collect();

        return view('pages/results-print', [
            'selectedParticipant' => $selectedParticipant,
            'scoreTimeline' => $scoreTimeline,
            'branchCriteria' => $branchCriteria,
            'documentConfig' => app(PageController::class)->documentConfig(),
            'resultStats' => [
                'entries' => $scores->count(),
                'latest' => number_format((float) ($scoreTimeline->first()?->score ?? 0), 2),
                'best' => number_format((float) ($scores->max('score') ?? 0), 2),
                'average' => number_format((float) ($scores->avg('score') ?? 0), 2),
            ],
            'generatedAt' => now(),
            'filters' => $filters,
            'participants' => $participants,
        ]);
    }

    public function recap(Request $request): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia', 'official', 'pendamping'], true), 403);

        [$filters, $rows, $categories, $branches] = $this->recapContext($request);
        $selectedCategory = filled($filters['competition_category_id'] ?? null)
            ? $categories->firstWhere('id', (int) $filters['competition_category_id'])
            : null;

        return view('pages/results-recap-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'filters' => $filters,
            'rows' => $rows,
            'categories' => $categories,
            'branches' => $branches,
            'selectedCategory' => $selectedCategory,
            'rankingPriorityContext' => app(PageController::class)->rankingPriorityContext(
                $selectedCategory?->id,
                $selectedCategory?->branch ?? ($filters['branch'] ?? null),
                (bool) $selectedCategory
            ),
            'recapStats' => [
                'participants' => $rows->count(),
                'categories' => $rows->pluck('category_name')->unique()->count(),
                'highest_score' => number_format((float) ($rows->max('best_score') ?? 0), 2),
                'average_score' => number_format((float) ($rows->avg('average_score_value') ?? 0), 2),
            ],
        ]);
    }

    public function recapPrint(Request $request): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia', 'official', 'pendamping'], true), 403);

        [$filters, $rows, $categories] = $this->recapContext($request);

        $selectedCategory = filled($filters['competition_category_id'] ?? null)
            ? $categories->firstWhere('id', (int) $filters['competition_category_id'])
            : null;

        return view('pages/results-recap-print', [
            'filters' => $filters,
            'rows' => $rows,
            'generatedAt' => now(),
            'selectedCategoryLabel' => $selectedCategory
                ? trim(($selectedCategory->branch ?? '-').' - '.($selectedCategory->name ?? '-'))
                : 'Semua',
            'rankingPriorityContext' => app(PageController::class)->rankingPriorityContext(
                $selectedCategory?->id,
                $selectedCategory?->branch ?? ($filters['branch'] ?? null),
                (bool) $selectedCategory
            ),
            'documentConfig' => app(PageController::class)->documentConfig(),
        ]);
    }

    public function recapExportExcel(Request $request): StreamedResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia', 'official', 'pendamping'], true), 403);

        [$filters, $categoryBlocks, $summary, $selectedCategory] = $this->recapExportContext($request);

        $filenameSlug = $selectedCategory
            ? Str::slug((string) $selectedCategory->branch.' '.$selectedCategory->name)
            : 'semua-golongan';
        $filename = 'rekap-penilaian-detail-'.$filenameSlug.'-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($filters, $categoryBlocks, $summary, $selectedCategory): void {
            echo view('excel.recap-detail', [
                'filters' => $filters,
                'categoryBlocks' => $categoryBlocks,
                'summary' => $summary,
                'selectedCategory' => $selectedCategory,
                'generatedAt' => now(),
                'documentConfig' => app(PageController::class)->documentConfig(),
                'rankingPriorityContext' => app(PageController::class)->rankingPriorityContext(
                    $selectedCategory?->id,
                    $selectedCategory?->branch,
                    (bool) $selectedCategory
                ),
            ])->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function recapCategoryPdf(Request $request, CompetitionCategory $category): Response
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia', 'official', 'pendamping'], true), 403);

        $participants = Participant::query()
            ->with(['category', 'district', 'scores' => fn ($query) => $query->orderByDesc('submitted_at')])
            ->where('verification_status', 'verified')
            ->where('competition_category_id', $category->id)
            ->orderBy('name')
            ->get();

        abort_if($participants->isEmpty(), 404);

        $priorityContext = app(PageController::class)->rankingPriorityContext($category->id, $category->branch, true);
        $categoryBlock = $this->buildCategoryBlock($category, $participants);
        $payload = [
            'categoryBlock' => $categoryBlock,
            'generatedAt' => now(),
            'documentConfig' => app(PageController::class)->documentConfig(),
            'priorityContext' => $priorityContext,
        ];
        $html = view('pdf.recap-category-detail', $payload)->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'rekap-golongan-'.Str::slug((string) $category->branch.' '.$category->name).'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function recapPenyisihanPdf(Request $request): Response
    {
        return $this->renderRoundWinnersPdf($request, 'Penyisihan');
    }

    public function recapFinalPdf(Request $request): Response
    {
        return $this->renderRoundWinnersPdf($request, 'Final');
    }

    protected function loadParticipant(int $participantId): ?Participant
    {
        return Participant::query()
            ->with(['category', 'district', 'scores' => fn ($query) => $query->orderByDesc('submitted_at')])
            ->whereKey($participantId)
            ->where('verification_status', 'verified')
            ->first();
    }

    protected function resolveParticipantProfile(?string $nomorInduk): ?Participant
    {
        if (! filled($nomorInduk)) {
            return null;
        }

        return Participant::query()
            ->with(['category', 'district', 'scores' => fn ($query) => $query->orderByDesc('submitted_at')])
            ->where('nik', $nomorInduk)
            ->first();
    }

    protected function resultContext(Request $request, bool $isParticipant): array
    {
        $filters = $request->validate([
            'participant_id' => ['nullable', 'integer'],
            'competition_category_id' => ['nullable', 'integer'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ]);

        $participants = Participant::query()
            ->with('category')
            ->where('verification_status', 'verified')
            ->when(filled($filters['competition_category_id'] ?? null), fn ($query) => $query->where('competition_category_id', $filters['competition_category_id']))
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

        $selectedParticipant = $isParticipant
            ? $this->resolveParticipantProfile(auth()->user()?->nomor_induk)
            : null;

        if (! $isParticipant && filled($filters['participant_id'] ?? null)) {
            $selectedParticipant = $this->loadParticipant((int) $filters['participant_id']);
        }

        if (! $selectedParticipant && ! $isParticipant && $participants->isNotEmpty()) {
            $selectedParticipant = $this->loadParticipant((int) $participants->first()->id);
        }

        $scores = $selectedParticipant?->scores ?? collect();
        $branchCriteria = config('scoring.criteria.'.($selectedParticipant?->category?->branch ?? ''))
            ?? config('scoring.criteria.default', []);

        $scoreTimeline = $scores
            ->sortByDesc('submitted_at')
            ->values();

        return [$filters, $participants, $selectedParticipant, $scoreTimeline, $branchCriteria];
    }

    protected function recapContext(Request $request): array
    {
        $filters = $request->validate([
            'branch' => ['nullable', 'string', 'max:255'],
            'competition_category_id' => ['nullable', 'integer'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ]);

        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->get();

        $branches = CompetitionCategory::query()
            ->select('branch')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch');

        $participants = Participant::query()
            ->with(['category', 'district', 'scores'])
            ->where('verification_status', 'verified')
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
            ->get();

        $rows = $participants
            ->map(function (Participant $participant): array {
                $scores = $participant->scores;
                $latest = $scores->sortByDesc('submitted_at')->first();
                $average = (float) ($scores->avg('score') ?? 0);
                $best = (float) ($scores->max('score') ?? 0);
                $priorityValues = $this->participantPriorityValues($participant);

                return [
                    'participant_name' => $participant->name,
                    'registration_number' => $participant->registration_number,
                    'district' => $participant->district?->name ?? '-',
                    'branch' => $participant->category?->branch ?? '-',
                    'category_name' => $participant->category?->name ?? '-',
                    'institution' => $participant->institution,
                    'latest_score' => number_format((float) ($latest->score ?? 0), 2),
                    'average_score' => number_format($average, 2),
                    'average_score_value' => $average,
                    'best_score' => number_format($best, 2),
                    'best_score_value' => $best,
                    'entry_count' => $scores->count(),
                    'priority_values' => $priorityValues,
                ];
            })
            ->sort(function (array $left, array $right): int {
                $averageComparison = $right['average_score_value'] <=> $left['average_score_value'];
                if ($averageComparison !== 0) {
                    return $averageComparison;
                }

                $maxPriorityCount = max(count($left['priority_values']), count($right['priority_values']));
                for ($index = 0; $index < $maxPriorityCount; $index++) {
                    $leftValue = (float) ($left['priority_values'][$index] ?? 0);
                    $rightValue = (float) ($right['priority_values'][$index] ?? 0);
                    $priorityComparison = $rightValue <=> $leftValue;

                    if ($priorityComparison !== 0) {
                        return $priorityComparison;
                    }
                }

                $bestComparison = $right['best_score_value'] <=> $left['best_score_value'];
                if ($bestComparison !== 0) {
                    return $bestComparison;
                }

                return strcmp((string) $left['participant_name'], (string) $right['participant_name']);
            })
            ->map(function (array $row): array {
                unset($row['priority_values']);

                return $row;
            })
            ->values();

        return [$filters, $rows, $categories, $branches];
    }

    protected function recapExportContext(Request $request): array
    {
        $filters = $request->validate([
            'branch' => ['nullable', 'string', 'max:255'],
            'competition_category_id' => ['nullable', 'integer'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ]);

        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name')
            ->get();

        $participants = Participant::query()
            ->with(['category', 'district', 'scores' => fn ($query) => $query->orderByDesc('submitted_at')])
            ->where('verification_status', 'verified')
            ->when(filled($filters['branch'] ?? null), fn ($query) => $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('branch', $filters['branch'])))
            ->when(filled($filters['competition_category_id'] ?? null), fn ($query) => $query->where('competition_category_id', (int) $filters['competition_category_id']))
            ->when(filled($filters['keyword'] ?? null), function ($query) use ($filters): void {
                $keyword = trim((string) $filters['keyword']);

                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('registration_number', 'like', '%'.$keyword.'%')
                        ->orWhere('institution', 'like', '%'.$keyword.'%');
                });
            })
            ->get();

        $selectedCategory = filled($filters['competition_category_id'] ?? null)
            ? $categories->firstWhere('id', (int) $filters['competition_category_id'])
            : null;

        $categoryBlocks = $categories
            ->map(fn (CompetitionCategory $category): ?array => $this->buildCategoryBlock(
                $category,
                $participants->filter(fn (Participant $participant): bool => (int) $participant->competition_category_id === (int) $category->id)
            ))
            ->filter()
            ->values();

        $summary = [
            'participants' => $participants->count(),
            'categories' => $categoryBlocks->count(),
            'branches' => $categoryBlocks->pluck('branch')->unique()->count(),
            'score_entries' => $participants->flatMap->scores->count(),
            'highest_score' => number_format((float) ($participants->flatMap->scores->max('score') ?? 0), 2),
            'average_score' => number_format((float) ($participants->flatMap->scores->avg('score') ?? 0), 2),
        ];

        return [$filters, $categoryBlocks, $summary, $selectedCategory];
    }

    protected function recapWinnersContext(Request $request, string $roundLabel): array
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
        ]);

        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name')
            ->get();

        $participants = Participant::query()
            ->with(['category', 'district', 'scores' => fn ($query) => $query->orderByDesc('submitted_at')])
            ->where('verification_status', 'verified')
            ->when(filled($filters['keyword'] ?? null), function ($query) use ($filters): void {
                $keyword = trim((string) $filters['keyword']);

                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('registration_number', 'like', '%'.$keyword.'%')
                        ->orWhere('institution', 'like', '%'.$keyword.'%');
                });
            })
            ->get();

        $categoryBlocks = $categories
            ->map(fn (CompetitionCategory $category): ?array => $this->buildCategoryRoundBlock(
                $category,
                $participants->filter(fn (Participant $participant): bool => (int) $participant->competition_category_id === (int) $category->id),
                $roundLabel
            ))
            ->filter()
            ->values();

        $summary = [
            'participants' => $participants->count(),
            'categories' => $categoryBlocks->count(),
            'branches' => $categoryBlocks->pluck('branch')->unique()->count(),
            'score_entries' => $participants->flatMap->scores->count(),
            'highest_score' => number_format((float) ($participants->flatMap->scores->max('score') ?? 0), 2),
            'average_score' => number_format((float) ($participants->flatMap->scores->avg('score') ?? 0), 2),
        ];

        return [$filters, $categoryBlocks, $summary];
    }

    protected function renderRoundWinnersPdf(Request $request, string $roundLabel): Response
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia', 'official', 'pendamping'], true), 403);

        [$filters, $categoryBlocks, $summary] = $this->recapWinnersContext($request, $roundLabel);
        $html = view('pdf.recap-winners-summary', [
            'filters' => $filters,
            'categoryBlocks' => $categoryBlocks,
            'summary' => $summary,
            'generatedAt' => now(),
            'documentConfig' => app(PageController::class)->documentConfig(),
            'roundLabel' => $roundLabel,
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'rekap-juara-'.Str::slug(strtolower($roundLabel)).'-semua-golongan.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function buildCategoryBlock(CompetitionCategory $category, Collection $participants): ?array
    {
        if ($participants->isEmpty()) {
            return null;
        }

        $rankingRows = $this->buildExportRankingRows($category, $participants);
        $labels = app(PageController::class)->priorityLabelsForCategory($category->id, $category->branch);
        $priorityContext = app(PageController::class)->rankingPriorityContext($category->id, $category->branch, true);

        return [
            'category' => $category,
            'category_id' => $category->id,
            'branch' => $category->branch,
            'category_name' => $category->name,
            'participant_total' => $participants->count(),
            'score_entries' => $participants->flatMap->scores->count(),
            'priority_labels' => $labels,
            'priority_context' => $priorityContext,
            'ranking_rows' => $rankingRows,
            'winners' => $rankingRows->take(6)->values(),
        ];
    }

    protected function buildCategoryRoundBlock(CompetitionCategory $category, Collection $participants, string $roundLabel): ?array
    {
        if ($participants->isEmpty()) {
            return null;
        }

        $roundRows = $this->buildRoundRankingRows($category, $participants, $roundLabel);
        $labels = app(PageController::class)->priorityLabelsForCategory($category->id, $category->branch);
        $priorityContext = app(PageController::class)->rankingPriorityContext($category->id, $category->branch, true);

        return [
            'category' => $category,
            'category_id' => $category->id,
            'branch' => $category->branch,
            'category_name' => $category->name,
            'participant_total' => $participants->count(),
            'score_entries' => $participants->flatMap->scores->filter(fn (ScoreEntry $entry): bool => (string) ($entry->judging_round ?? '') === $roundLabel)->count(),
            'priority_labels' => $labels,
            'priority_context' => $priorityContext,
            'round_rows' => $roundRows,
            'winners' => $roundRows->take(6)->values(),
            'round_label' => $roundLabel,
        ];
    }

    protected function buildExportRankingRows(CompetitionCategory $category, Collection $participants): Collection
    {
        $rows = $participants
            ->map(function (Participant $participant) use ($category): array {
                $scores = $participant->scores;
                $latest = $scores->sortByDesc('submitted_at')->first();
                $average = (float) ($scores->avg('score') ?? 0);
                $best = (float) ($scores->max('score') ?? 0);
                $priorityValues = $this->participantPriorityValues($participant);

                return [
                    'participant_id' => $participant->id,
                    'name' => $participant->name,
                    'registration_number' => $participant->registration_number,
                    'district' => $participant->district?->name ?? '-',
                    'institution' => $participant->institution ?? '-',
                    'branch' => $category->branch,
                    'category_name' => $category->name,
                    'latest_score' => number_format((float) ($latest->score ?? 0), 2),
                    'average_score' => number_format($average, 2),
                    'average_score_value' => $average,
                    'best_score' => number_format($best, 2),
                    'best_score_value' => $best,
                    'entry_count' => $scores->count(),
                    'priority_values' => $priorityValues,
                    'priority_label_values' => $this->priorityLabelValues($category, $priorityValues),
                    'score_entries' => $this->scoreEntryDetails($participant, $category),
                ];
            })
            ->values()
            ->all();

        usort($rows, function (array $left, array $right): int {
            $averageComparison = $right['average_score_value'] <=> $left['average_score_value'];
            if ($averageComparison !== 0) {
                return $averageComparison;
            }

            $maxPriorityCount = max(count($left['priority_values']), count($right['priority_values']));
            for ($index = 0; $index < $maxPriorityCount; $index++) {
                $leftValue = (float) ($left['priority_values'][$index] ?? 0);
                $rightValue = (float) ($right['priority_values'][$index] ?? 0);
                $priorityComparison = $rightValue <=> $leftValue;

                if ($priorityComparison !== 0) {
                    return $priorityComparison;
                }
            }

            $bestComparison = $right['best_score_value'] <=> $left['best_score_value'];
            if ($bestComparison !== 0) {
                return $bestComparison;
            }

            return strcmp((string) $left['name'], (string) $right['name']);
        });

        return collect($rows)
            ->values()
            ->map(function (array $row, int $index): array {
                $row['rank'] = $index + 1;

                unset($row['average_score_value'], $row['best_score_value'], $row['priority_values']);

                return $row;
            });
    }

    protected function buildRoundRankingRows(CompetitionCategory $category, Collection $participants, string $roundLabel): Collection
    {
        $rows = $participants
            ->map(function (Participant $participant) use ($category, $roundLabel): ?array {
                $roundScores = $participant->scores->filter(fn (ScoreEntry $entry): bool => (string) ($entry->judging_round ?? '') === $roundLabel);

                if ($roundScores->isEmpty()) {
                    return null;
                }

                $latest = $roundScores->sortByDesc('submitted_at')->first();
                $average = (float) ($roundScores->avg('score') ?? 0);
                $best = (float) ($roundScores->max('score') ?? 0);
                $priorityValues = $this->participantPriorityValuesFromScores($participant, $roundScores);

                return [
                    'participant_id' => $participant->id,
                    'name' => $participant->name,
                    'registration_number' => $participant->registration_number,
                    'district' => $participant->district?->name ?? '-',
                    'institution' => $participant->institution ?? '-',
                    'branch' => $category->branch,
                    'category_name' => $category->name,
                    'latest_score' => number_format((float) ($latest->score ?? 0), 2),
                    'average_score' => number_format($average, 2),
                    'average_score_value' => $average,
                    'best_score' => number_format($best, 2),
                    'best_score_value' => $best,
                    'entry_count' => $roundScores->count(),
                    'priority_values' => $priorityValues,
                    'priority_label_values' => $this->priorityLabelValues($category, $priorityValues),
                    'score_entries' => $this->scoreEntryDetailsForRound($participant, $category, $roundLabel),
                ];
            })
            ->filter()
            ->values()
            ->all();

        usort($rows, function (array $left, array $right): int {
            $averageComparison = $right['average_score_value'] <=> $left['average_score_value'];
            if ($averageComparison !== 0) {
                return $averageComparison;
            }

            $maxPriorityCount = max(count($left['priority_values']), count($right['priority_values']));
            for ($index = 0; $index < $maxPriorityCount; $index++) {
                $leftValue = (float) ($left['priority_values'][$index] ?? 0);
                $rightValue = (float) ($right['priority_values'][$index] ?? 0);
                $priorityComparison = $rightValue <=> $leftValue;

                if ($priorityComparison !== 0) {
                    return $priorityComparison;
                }
            }

            $bestComparison = $right['best_score_value'] <=> $left['best_score_value'];
            if ($bestComparison !== 0) {
                return $bestComparison;
            }

            return strcmp((string) $left['name'], (string) $right['name']);
        });

        return collect($rows)
            ->values()
            ->map(function (array $row, int $index): array {
                $row['rank'] = $index + 1;

                unset($row['average_score_value'], $row['best_score_value'], $row['priority_values']);

                return $row;
            });
    }

    protected function priorityLabelValues(CompetitionCategory $category, array $priorityValues): array
    {
        $labels = app(PageController::class)->priorityLabelsForCategory($category->id, $category->branch);

        return collect($labels)
            ->mapWithKeys(function (string $label, int $index) use ($priorityValues): array {
                return [$label => number_format((float) ($priorityValues[$index] ?? 0), 2)];
            })
            ->all();
    }

    protected function scoreEntryDetails(Participant $participant, CompetitionCategory $category): array
    {
        $labels = app(PageController::class)->priorityLabelsForCategory($category->id, $category->branch);
        $priorityKeys = $this->priorityKeysForCategory($category->id, $category->branch);
        $scoreEntries = $participant->scores->sortByDesc('submitted_at')->values();

        return $scoreEntries
            ->map(function (ScoreEntry $entry) use ($labels, $priorityKeys): array {
                $breakdownValues = $entry->score_breakdown ?? [];
                $breakdown = collect($labels)
                    ->mapWithKeys(function (string $label, int $index) use ($breakdownValues, $priorityKeys): array {
                        $key = $priorityKeys[$index] ?? null;

                        return [$label => number_format((float) ($key ? ($breakdownValues[$key] ?? 0) : 0), 2)];
                    })
                    ->all();

                return [
                    'submitted_at' => optional($entry->submitted_at)->format('d/m/Y H:i'),
                    'judge_name' => $entry->judge_name,
                    'judging_round' => $entry->judging_round ?? '-',
                    'score' => number_format((float) $entry->score, 2),
                    'breakdown' => $breakdown,
                ];
            })
            ->all();
    }

    protected function scoreEntryDetailsForRound(Participant $participant, CompetitionCategory $category, string $roundLabel): array
    {
        $labels = app(PageController::class)->priorityLabelsForCategory($category->id, $category->branch);
        $priorityKeys = $this->priorityKeysForCategory($category->id, $category->branch);
        $scoreEntries = $participant->scores
            ->filter(fn (ScoreEntry $entry): bool => (string) ($entry->judging_round ?? '') === $roundLabel)
            ->sortByDesc('submitted_at')
            ->values();

        return $scoreEntries
            ->map(function (ScoreEntry $entry) use ($labels, $priorityKeys): array {
                $breakdownValues = $entry->score_breakdown ?? [];
                $breakdown = collect($labels)
                    ->mapWithKeys(function (string $label, int $index) use ($breakdownValues, $priorityKeys): array {
                        $key = $priorityKeys[$index] ?? null;

                        return [$label => number_format((float) ($key ? ($breakdownValues[$key] ?? 0) : 0), 2)];
                    })
                    ->all();

                return [
                    'submitted_at' => optional($entry->submitted_at)->format('d/m/Y H:i'),
                    'judge_name' => $entry->judge_name,
                    'judging_round' => $entry->judging_round ?? '-',
                    'score' => number_format((float) $entry->score, 2),
                    'breakdown' => $breakdown,
                ];
            })
            ->all();
    }

    protected function participantPriorityValues(Participant $participant): array
    {
        $priorityKeys = $this->priorityKeysForCategory($participant->competition_category_id, $participant->category?->branch);
        $scores = collect($participant->scores);

        return collect($priorityKeys)
            ->map(function (string $key) use ($scores): float {
                return (float) ($scores->avg(fn ($entry) => (float) (($entry->score_breakdown[$key] ?? 0))) ?? 0);
            })
            ->values()
            ->all();
    }

    protected function participantPriorityValuesFromScores(Participant $participant, $scores): array
    {
        $priorityKeys = $this->priorityKeysForCategory($participant->competition_category_id, $participant->category?->branch);
        $scoreCollection = collect($scores);

        return collect($priorityKeys)
            ->map(function (string $key) use ($scoreCollection): float {
                return (float) ($scoreCollection->avg(fn ($entry) => (float) (($entry->score_breakdown[$key] ?? 0))) ?? 0);
            })
            ->values()
            ->all();
    }

    protected function priorityKeysForCategory(?int $categoryId, ?string $branch = null): array
    {
        $setting = ScoringSetting::forCategory($categoryId);
        $priorityKeys = array_values(array_filter($setting?->scoring_priorities ?? []));

        if ($priorityKeys !== []) {
            return $priorityKeys;
        }

        $criteria = $setting?->scoring_points
            ?? config('scoring.criteria.'.($branch ?? ''))
            ?? config('scoring.criteria.default', []);

        return array_keys($criteria);
    }
}
