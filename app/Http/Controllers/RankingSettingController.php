<?php

namespace App\Http\Controllers;

use App\Models\AppearanceSchedule;
use App\Models\CompetitionCategory;
use App\Models\RankingSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RankingSettingController extends Controller
{
    /**
     * Display the ranking settings management page
     */
    public function index(): View
    {
        $rankingSettings = RankingSetting::with('category')
            ->orderBy('is_active', 'desc')
            ->orderBy('sort_order')
            ->get();

        $categories = CompetitionCategory::query()
            ->orderBy('branch')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'label' => trim(($cat->branch ?? '-') . ' | ' . $cat->name),
            ]);

        return view('pages.admin.ranking-settings', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel(auth()->user()?->role),
            'navigation' => app(PageController::class)->consoleNavigation((string) auth()->user()?->role, 'ranking.settings'),
            'rankingSettings' => $rankingSettings,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created ranking setting
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'competition_category_id' => ['nullable', 'integer', 'exists:competition_categories,id'],
            'gender' => ['required', 'in:putra,putri,all'],
            'appearance_day' => ['nullable', 'integer', 'min:0'],
            'judging_round' => ['required', 'in:Penyisihan,Final'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $maxSortOrder = RankingSetting::max('sort_order') ?? 0;
        $validated['sort_order'] = $validated['sort_order'] ?? ($maxSortOrder + 1);
        $validated['is_active'] = $validated['is_active'] ?? false;

        RankingSetting::create($validated);

        return redirect()
            ->route('ranking.settings.index')
            ->with('success', 'Pengaturan ranking berhasil ditambahkan.');
    }

    /**
     * Update the specified ranking setting
     */
    public function update(Request $request, RankingSetting $rankingSetting): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'competition_category_id' => ['nullable', 'integer', 'exists:competition_categories,id'],
            'gender' => ['required', 'in:putra,putri,all'],
            'appearance_day' => ['nullable', 'integer', 'min:0'],
            'judging_round' => ['required', 'in:Penyisihan,Final'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;

        $rankingSetting->update($validated);

        return redirect()
            ->route('ranking.settings.index')
            ->with('success', 'Pengaturan ranking berhasil diperbarui.');
    }

    /**
     * Toggle the active status of a ranking setting
     */
    public function toggle(RankingSetting $rankingSetting): RedirectResponse
    {
        $rankingSetting->update(['is_active' => !$rankingSetting->is_active]);

        $status = $rankingSetting->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()
            ->route('ranking.settings.index')
            ->with('success', "Ranking berhasil {$status}.");
    }

    /**
     * Remove the specified ranking setting
     */
    public function destroy(RankingSetting $rankingSetting): RedirectResponse
    {
        $rankingSetting->delete();

        return redirect()
            ->route('ranking.settings.index')
            ->with('success', 'Pengaturan ranking berhasil dihapus.');
    }

    /**
     * Get appearance schedule days for a category (AJAX)
     */
    public function getScheduleDays(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id');

        if (!$categoryId) {
            return response()->json(['days' => []]);
        }

        $schedule = AppearanceSchedule::where('competition_category_id', $categoryId)->first();

        if (!$schedule || empty($schedule->day_schedules)) {
            return response()->json(['days' => []]);
        }

        $days = collect($schedule->day_schedules)->map(function ($day, $index) {
            $name = $day['name'] ?? 'Hari ' . ($index + 1);
            $date = $day['date'] ?? null;
            $time = $day['time'] ?? null;
            $endTime = $day['end_time'] ?? null;

            // Format display string
            $displayParts = [$name];
            if ($date) {
                $displayParts[] = \Carbon\Carbon::parse($date)->translatedFormat('d M Y');
            }
            if ($time) {
                $timeStr = $time . ' WIB';
                if ($endTime) {
                    $timeStr .= ' - ' . $endTime . ' WIB';
                }
                $displayParts[] = $timeStr;
            }
            $display = implode(' · ', $displayParts);

            // Format lot range
            $lotRange = '-';
            if (isset($day['count']) && $day['count'] > 0) {
                $lotRange = 'Lot count: ' . $day['count'];
            }

            return [
                'index' => $index,
                'name' => $name,
                'date' => $date,
                'time' => $time,
                'end_time' => $endTime,
                'count' => $day['count'] ?? 0,
                'display' => $display,
                'lot_range' => $lotRange,
            ];
        })->values();

        return response()->json(['days' => $days]);
    }

    /**
     * Reorder ranking settings
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer', 'exists:ranking_settings,id'],
        ]);

        foreach ($validated['order'] as $index => $id) {
            RankingSetting::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
