<?php

namespace App\Http\Controllers;

use App\Models\AppearanceSchedule;
use App\Models\CompetitionCategory;
use App\Models\Participant;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AppearanceScheduleController extends Controller
{
    /**
     * Categories where 1 district = 1 lot number
     */
    private function isLotPerDistrictCategory(string $branch): bool
    {
        $lotPerDistrictBranches = [
            'Fahmil Qur`an',
            'Syarhil Qur`an',
            'Khutbah Jumat dan Adzan',
        ];

        return in_array($branch, $lotPerDistrictBranches);
    }

    public function index(): View
    {
        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->get();

        $schedules = AppearanceSchedule::query()
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get()
            ->keyBy('competition_category_id');

        $categoryStats = $categories->mapWithKeys(function (CompetitionCategory $category) use ($schedules) {
            $totalParticipants = Participant::query()
                ->where('competition_category_id', $category->id)
                ->where('verification_status', 'verified')
                ->count();

            // For lot-per-district categories (Fahmil, Syarhil, Khutbah), count unique districts
            $isLotPerDistrict = $this->isLotPerDistrictCategory($category->branch);
            $totalDistricts = 0;
            if ($isLotPerDistrict) {
                $totalDistricts = Participant::query()
                    ->where('competition_category_id', $category->id)
                    ->where('verification_status', 'verified')
                    ->distinct('district_id')
                    ->count('district_id');
            }

            $schedule = $schedules->get($category->id);

            $categoryMaxLot = (int) ($category->lot_number_max ?? 99);
            $categoryMinLot = (int) ($category->lot_number_min ?? 1);
            $totalConfiguredLots = max(0, $categoryMaxLot - $categoryMinLot + 1);

            return [
                $category->id => [
                    'total_lots' => $totalConfiguredLots,
                    'total_participants' => $totalParticipants,
                    'total_districts' => $totalDistricts,
                    'is_lot_per_district' => $isLotPerDistrict,
                    'min_lot' => $categoryMinLot,
                    'max_lot' => $categoryMaxLot,
                    'has_schedule' => $schedule !== null,
                    'schedule_days' => $schedule?->number_of_days,
                    'schedule_total' => $schedule?->getTotalParticipants(),
                    'is_balanced' => $schedule ? ($totalParticipants === $schedule->getTotalParticipants()) : false,
                ],
            ];
        });

        return view('pages.admin.appearance-schedules', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel('admin'),
            'navigation' => app(PageController::class)->consoleNavigation('admin', 'appearance.schedules'),
            'categories' => $categories,
            'schedules' => $schedules,
            'categoryStats' => $categoryStats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'competition_category_id' => ['required', 'integer', 'exists:competition_categories,id'],
            'number_of_days' => ['required', 'integer', 'min:1'],
            'day_names' => ['required', 'array', 'min:1'],
            'day_names.*' => ['nullable', 'string', 'max:255'],
            'day_dates' => ['required', 'array', 'min:1'],
            'day_dates.*' => ['nullable', 'date'],
            'day_times' => ['required', 'array', 'min:1'],
            'day_times.*' => ['nullable', 'date_format:H:i'],
            'day_end_times' => ['nullable', 'array', 'min:1'],
            'day_end_times.*' => ['nullable', 'date_format:H:i'],
            'day_counts' => ['required', 'array', 'min:1'],
            'day_counts.*' => ['required', 'integer', 'min:1'],
        ]);

        $categoryId = (int) $validated['competition_category_id'];
        $category = CompetitionCategory::query()->find($categoryId);

        // Check if this is a lot-per-district category
        $isLotPerDistrict = $this->isLotPerDistrictCategory($category->branch);

        if ($isLotPerDistrict) {
            // For lot-per-district categories, use total districts
            $totalExpected = Participant::query()
                ->where('competition_category_id', $categoryId)
                ->where('verification_status', 'verified')
                ->distinct('district_id')
                ->count('district_id');
            $expectedLabel = 'total kecamatan';
        } else {
            // For regular categories, use total participants
            $totalExpected = Participant::query()
                ->where('competition_category_id', $categoryId)
                ->where('verification_status', 'verified')
                ->count();
            $expectedLabel = 'total peserta';
        }

        $numberOfDays = (int) $validated['number_of_days'];
        $dayCounts = array_values($validated['day_counts']);
        $dayNames = $validated['day_names'] ?? [];
        $dayDates = $validated['day_dates'] ?? [];
        $dayTimes = $validated['day_times'] ?? [];
        $dayEndTimes = $validated['day_end_times'] ?? [];

        $totalDayCounts = array_sum($dayCounts);

        if ($totalDayCounts !== $totalExpected) {
            return back()
                ->withInput()
                ->withErrors([
                    'day_counts' => "Total per hari ({$totalDayCounts}) harus sama dengan {$expectedLabel} ({$totalExpected}).",
                ]);
        }

        $daySchedules = [];
        for ($i = 0; $i < $numberOfDays; $i++) {
            $daySchedules[] = [
                'name' => $dayNames[$i] ?? null,
                'date' => $dayDates[$i] ?? null,
                'time' => $dayTimes[$i] ?? null,
                'end_time' => $dayEndTimes[$i] ?? null,
                'count' => (int) ($dayCounts[$i] ?? 0),
            ];
        }

        AppearanceSchedule::updateOrCreate(
            ['competition_category_id' => $categoryId],
            [
                'number_of_days' => $numberOfDays,
                'day_schedules' => $daySchedules,
                'is_active' => true,
            ]
        );

        return redirect()
            ->route('appearance.schedules')
            ->with('status', 'Jadwal penampilan berhasil disimpan.');
    }

    public function destroy(AppearanceSchedule $schedule): RedirectResponse
    {
        $categoryName = $schedule->category?->name ?? 'golongan';

        $schedule->delete();

        return redirect()
            ->route('appearance.schedules')
            ->with('status', "Jadwal penampilan {$categoryName} berhasil dihapus.");
    }

    public function results(int $categoryId): View
    {
        $category = CompetitionCategory::query()->findOrFail($categoryId);
        $schedule = AppearanceSchedule::query()
            ->where('competition_category_id', $categoryId)
            ->firstOrFail();

        $dayData = [];
        for ($i = 0; $i < $schedule->number_of_days; $i++) {
            $dayData[$i] = $schedule->getDayParticipants($i);
        }

        return view('pages.admin.appearance-results', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel('admin'),
            'navigation' => app(PageController::class)->consoleNavigation('admin', 'appearance.schedules'),
            'category' => $category,
            'schedule' => $schedule,
            'dayData' => $dayData,
        ]);
    }

    public function exportAllPdf()
    {
        $categories = CompetitionCategory::query()
            ->with(['appearanceSchedule', 'locations'])
            ->whereHas('appearanceSchedule')
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->get();

        if ($categories->isEmpty()) {
            return back()->with('error', 'Belum ada jadwal yang diisi.');
        }

        $dataByCategory = [];
        $totalPoolLots = 0;
        $totalScheduledLots = 0;

        foreach ($categories as $category) {
            $schedule = $category->appearanceSchedule;
            $dayData = [];

            for ($i = 0; $i < $schedule->number_of_days; $i++) {
                $dayData[$i] = $schedule->getDayParticipants($i);
            }

            $categoryTotalLots = $schedule->getTotalLots();
            $categoryScheduledLots = 0;
            foreach ($dayData as $day) {
                $categoryScheduledLots += ($day['range']['count'] ?? 0);
            }

            $totalPoolLots += $categoryTotalLots;
            $totalScheduledLots += $categoryScheduledLots;

            // Get location for this category
            $location = $category->locations->first();
            $locationName = $location?->venue_name ?? config('juknis.host', 'Lokasi Belum Ditentukan');
            $locationMapUrl = $location?->map_url ?? '';

            $dataByCategory[] = [
                'category' => $category,
                'schedule' => $schedule,
                'dayData' => $dayData,
                'totalLots' => $categoryTotalLots,
                'scheduledLots' => $categoryScheduledLots,
                'location_name' => $locationName,
                'location_map_url' => $locationMapUrl,
            ];
        }

        // Group by date AND time - each date+time becomes one row with all categories
        $dateGroups = [];
        foreach ($dataByCategory as $item) {
            $category = $item['category'];
            $schedule = $item['schedule'];
            $dayData = $item['dayData'];
            $lotCode = $category->lot_code ?? 'XX';
            $isLotPerDistrict = $this->isLotPerDistrictCategory($category->branch);

            foreach ($dayData as $dayIndex => $data) {
                $daySchedule = $data['schedule'] ?? [];
                $sessionDate = $daySchedule['date'] ?? null;
                $sessionTime = $daySchedule['time'] ?? null;
                $sessionEndTime = $daySchedule['end_time'] ?? null;
                $dayLotNumbers = $data['range']['lot_numbers'] ?? [];

                // Format lot badges
                $lotBadges = [];
                foreach ($dayLotNumbers as $lot) {
                    $lotStr = str_pad((string) $lot, 2, '0', STR_PAD_LEFT);
                    $lotBadges[] = $lotCode . '.' . $lotStr;
                }

                // Use date + time as key to group by date AND time range
                $dateKey = $sessionDate ?? 'no-date';
                $timeKey = ($sessionTime ?? '') . '|' . ($sessionEndTime ?? '');
                $groupKey = $dateKey . '___' . $timeKey;

                if (!isset($dateGroups[$groupKey])) {
                    $dateGroups[$groupKey] = [
                        'date' => $sessionDate,
                        'time' => $sessionTime,
                        'end_time' => $sessionEndTime,
                        'categories' => [],
                    ];
                }

                $dateGroups[$groupKey]['categories'][] = [
                    'day_number' => $dayIndex + 1,
                    'category_name' => $category->name,
                    'category_branch' => $category->branch,
                    'session_name' => $daySchedule['name'] ?? '-',
                    'lot_badges' => $lotBadges,
                    'lot_count' => count($lotBadges),
                    'is_lot_per_district' => $isLotPerDistrict,
                    'location_name' => $item['location_name'],
                    'location_map_url' => $item['location_map_url'],
                ];
            }
        }

        // Sort by date then time
        uksort($dateGroups, function ($a, $b) {
            if ($a === $b) return 0;
            // Handle 'no-date*' keys - put them at the end
            if (str_starts_with($a, 'no-date')) return 1;
            if (str_starts_with($b, 'no-date')) return -1;
            return strcmp($a, $b);
        });

        $html = view('pages.admin.appearance-results-pdf', [
                'dateGroups' => array_values($dateGroups),
                'totalCategories' => count($dataByCategory),
                'totalPoolLots' => $totalPoolLots,
                'totalScheduledLots' => $totalScheduledLots,
                'eventName' => config('mtq.event_name', 'MTQ Kabupaten'),
            ])->render();

        // Preview mode - show HTML directly (disable PDF for now)
        return response($html, 200, ['Content-Type' => 'text/html']);

        /* Uncomment below to enable PDF generation:

        // Try Snappy first, fallback to HTML auto-download
        try {
            $pdf = SnappyPdf::loadHTML($html)
                ->setOrientation('landscape')
                ->setPaper('A4')
                ->setOption('enable-local-file-access', true);

            $filename = 'Jadwal_Penampilan_Semua_' . now()->format('Ymd_His') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            // Fallback to HTML auto-download with html2pdf.js
            return view('pages.admin.appearance-results-pdf-html', [
                'htmlContent' => $html,
                'filename' => 'Jadwal_Penampilan_Semua_' . now()->format('Ymd_His') . '.pdf',
            ]);
        }

        */
    }

    public function exportPdf(int $categoryId)
    {
        $category = CompetitionCategory::query()
            ->with('locations')
            ->findOrFail($categoryId);
        $schedule = AppearanceSchedule::query()
            ->where('competition_category_id', $categoryId)
            ->firstOrFail();

        $dayData = [];
        for ($i = 0; $i < $schedule->number_of_days; $i++) {
            $dayData[$i] = $schedule->getDayParticipants($i);
        }

        $totalPoolLots = $schedule->getTotalLots();
        $totalScheduledLots = 0;

        // Get location for this category
        $location = $category->locations->first();
        $locationName = $location?->venue_name ?? config('juknis.host', 'Lokasi Belum Ditentukan');
        $locationMapUrl = $location?->map_url ?? '';

        // Group by date AND time - each date+time becomes one row with all categories
        $dateGroups = [];
        $lotCode = $category->lot_code ?? 'XX';
        $isLotPerDistrict = $this->isLotPerDistrictCategory($category->branch);

        foreach ($dayData as $dayIndex => $data) {
            $daySchedule = $data['schedule'] ?? [];
            $sessionDate = $daySchedule['date'] ?? null;
            $sessionTime = $daySchedule['time'] ?? null;
            $sessionEndTime = $daySchedule['end_time'] ?? null;
            $dayLotNumbers = $data['range']['lot_numbers'] ?? [];

            $totalScheduledLots += ($day['range']['count'] ?? 0);

            // Format lot badges
            $lotBadges = [];
            foreach ($dayLotNumbers as $lot) {
                $lotStr = str_pad((string) $lot, 2, '0', STR_PAD_LEFT);
                $lotBadges[] = $lotCode . '.' . $lotStr;
            }

            // Use date + time as key to group by date AND time range
            $dateKey = $sessionDate ?? 'no-date';
            $timeKey = ($sessionTime ?? '') . '|' . ($sessionEndTime ?? '');
            $groupKey = $dateKey . '___' . $timeKey;

            if (!isset($dateGroups[$groupKey])) {
                $dateGroups[$groupKey] = [
                    'date' => $sessionDate,
                    'time' => $sessionTime,
                    'end_time' => $sessionEndTime,
                    'categories' => [],
                ];
            }

            $dateGroups[$groupKey]['categories'][] = [
                'day_number' => $dayIndex + 1,
                'category_name' => $category->name,
                'category_branch' => $category->branch,
                'session_name' => $daySchedule['name'] ?? '-',
                'lot_badges' => $lotBadges,
                'lot_count' => count($lotBadges),
                'is_lot_per_district' => $isLotPerDistrict,
                'location_name' => $locationName,
                'location_map_url' => $locationMapUrl,
            ];
        }

        // Sort by date then time
        uksort($dateGroups, function ($a, $b) {
            // Helper to normalize Indonesian date format
            $normalizeDate = function($date) {
                if (preg_match('/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})/i', $date, $matches)) {
                    $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $monthNames = ['januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04', 'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08', 'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12'];
                    $month = strtolower($matches[2]);
                    $month = $monthNames[$month] ?? '01';
                    $year = $matches[3];
                    return "{$year}-{$month}-{$day}";
                }
                return $date;
            };

            if ($a === $b) return 0;
            if (str_starts_with($a, 'no-date')) return 1;
            if (str_starts_with($b, 'no-date')) return -1;
            // Extract date and time parts for proper numeric comparison
            $partsA = explode('___', $a, 2);
            $partsB = explode('___', $b, 2);
            $dateA = $normalizeDate($partsA[0] ?? '');
            $dateB = $normalizeDate($partsB[0] ?? '');
            if ($dateA !== $dateB) {
                return strcmp($dateA, $dateB);
            }
            // Same date - compare times (handle both HH:MM and HH.MM formats)
            $timeA = $partsA[1] ?? '';
            $timeB = $partsB[1] ?? '';
            preg_match('/^(\d{2})[.:](\d{2})/', $timeA, $matchA);
            preg_match('/^(\d{2})[.:](\d{2})/', $timeB, $matchB);
            $timeANum = isset($matchA[1]) ? (int)($matchA[1] . $matchA[2]) : 9999;
            $timeBNum = isset($matchB[1]) ? (int)($matchB[1] . $matchB[2]) : 9999;
            return $timeANum - $timeBNum;
        });

        $html = view('pages.admin.appearance-results-pdf', [
            'dateGroups' => array_values($dateGroups),
            'totalCategories' => 1,
            'totalPoolLots' => $totalPoolLots,
            'totalScheduledLots' => $totalScheduledLots,
            'eventName' => config('mtq.event_name', 'MTQ Kabupaten'),
        ])->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setOrientation('landscape')
            ->setPaper('A4')
            ->setOption('enable-local-file-access', true);

        $filename = 'Jadwal_Penampilan_' . Str::slug((string) $category->name) . '_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportFullSchedulePdf()
    {
        // Get event schedule from juknis
        $eventSchedule = config('juknis.event_schedule', []);
        $eventTitle = config('juknis.title', 'Juknis MTQ');
        $eventLocation = config('juknis.host', 'Lokasi MTQ');
        $ageReference = config('juknis.age_reference_date', '');

        // Get appearance schedules
        $categories = CompetitionCategory::query()
            ->with(['appearanceSchedule', 'locations'])
            ->whereHas('appearanceSchedule')
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->get();

        $dataByCategory = [];
        $totalPoolLots = 0;
        $totalScheduledLots = 0;
        $totalVerifiedParticipants = 0;

        foreach ($categories as $category) {
            $schedule = $category->appearanceSchedule;
            $dayData = [];
            for ($i = 0; $i < $schedule->number_of_days; $i++) {
                $dayData[$i] = $schedule->getDayParticipants($i);
            }

            $categoryTotalLots = $schedule->getTotalLots();
            $categoryScheduledLots = 0;
            foreach ($dayData as $day) {
                $categoryScheduledLots += ($day['range']['count'] ?? 0);
            }

            // Count verified participants for this category
            $categoryVerified = Participant::query()
                ->where('competition_category_id', $category->id)
                ->where('verification_status', 'verified')
                ->count();

            $totalPoolLots += $categoryTotalLots;
            $totalScheduledLots += $categoryScheduledLots;
            $totalVerifiedParticipants += $categoryVerified;

            $location = $category->locations->first();
            $locationName = $location?->venue_name ?? $eventLocation;
            $locationMapUrl = $location?->map_url ?? '';

            $dataByCategory[] = [
                'category' => $category,
                'schedule' => $schedule,
                'dayData' => $dayData,
                'totalLots' => $categoryTotalLots,
                'scheduledLots' => $categoryScheduledLots,
                'location_name' => $locationName,
                'location_map_url' => $locationMapUrl,
            ];
        }

        // Group by date AND time
        $dateGroups = [];
        foreach ($dataByCategory as $item) {
            $category = $item['category'];
            $schedule = $item['schedule'];
            $dayData = $item['dayData'];
            $lotCode = $category->lot_code ?? 'XX';
            $isLotPerDistrict = $this->isLotPerDistrictCategory($category->branch);
            $locationName = $item['location_name'];
            $locationMapUrl = $item['location_map_url'];

            foreach ($dayData as $dayIndex => $data) {
                $daySchedule = $data['schedule'] ?? [];
                $sessionDate = $daySchedule['date'] ?? null;
                $sessionTime = $daySchedule['time'] ?? null;
                $sessionEndTime = $daySchedule['end_time'] ?? null;
                $dayLotNumbers = $data['range']['lot_numbers'] ?? [];

                $lotBadges = [];
                foreach ($dayLotNumbers as $lot) {
                    $lotStr = str_pad((string) $lot, 2, '0', STR_PAD_LEFT);
                    $lotBadges[] = $lotCode . '.' . $lotStr;
                }

                $dateKey = $sessionDate ?? 'no-date';
                $timeKey = ($sessionTime ?? '') . '|' . ($sessionEndTime ?? '');
                $groupKey = $dateKey . '___' . $timeKey;

                if (!isset($dateGroups[$groupKey])) {
                    $dateGroups[$groupKey] = [
                        'date' => $sessionDate,
                        'time' => $sessionTime,
                        'end_time' => $sessionEndTime,
                        'categories' => [],
                    ];
                }

                $dateGroups[$groupKey]['categories'][] = [
                    'day_number' => $dayIndex + 1,
                    'category_name' => $category->name,
                    'category_branch' => $category->branch,
                    'session_name' => $daySchedule['name'] ?? '-',
                    'lot_badges' => $lotBadges,
                    'lot_count' => count($lotBadges),
                    'is_lot_per_district' => $isLotPerDistrict,
                    'location_name' => $locationName,
                    'location_map_url' => $locationMapUrl,
                ];
            }
        }

        uksort($dateGroups, function ($a, $b) {
            // Helper to normalize Indonesian date format
            $normalizeDate = function($date) {
                if (preg_match('/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})/i', $date, $matches)) {
                    $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $monthNames = ['januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04', 'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08', 'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12'];
                    $month = strtolower($matches[2]);
                    $month = $monthNames[$month] ?? '01';
                    $year = $matches[3];
                    return "{$year}-{$month}-{$day}";
                }
                return $date;
            };

            if ($a === $b) return 0;
            if (str_starts_with($a, 'no-date')) return 1;
            if (str_starts_with($b, 'no-date')) return -1;
            // Extract date and time parts for proper numeric comparison
            $partsA = explode('___', $a, 2);
            $partsB = explode('___', $b, 2);
            $dateA = $normalizeDate($partsA[0] ?? '');
            $dateB = $normalizeDate($partsB[0] ?? '');
            if ($dateA !== $dateB) {
                return strcmp($dateA, $dateB);
            }
            // Same date - compare times (handle both HH:MM and HH.MM formats)
            $timeA = $partsA[1] ?? '';
            $timeB = $partsB[1] ?? '';
            preg_match('/^(\d{2})[.:](\d{2})/', $timeA, $matchA);
            preg_match('/^(\d{2})[.:](\d{2})/', $timeB, $matchB);
            $timeANum = isset($matchA[1]) ? (int)($matchA[1] . $matchA[2]) : 9999;
            $timeBNum = isset($matchB[1]) ? (int)($matchB[1] . $matchB[2]) : 9999;
            return $timeANum - $timeBNum;
        });

        // Prepare event schedule with sortable date/time
        $normalizeDate = function($date) {
            if (preg_match('/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})/i', $date, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $monthNames = ['januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04', 'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08', 'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12'];
                $month = strtolower($matches[2]);
                $month = $monthNames[$month] ?? '01';
                $year = $matches[3];
                return "{$year}-{$month}-{$day}";
            }
            return $date;
        };

        $eventSchedulePrepared = collect($eventSchedule)->map(function ($event) use ($normalizeDate) {
            $time = $event['time'] ?? '';
            // Extract start time (before first dash) - normalize format
            $startTime = trim(explode('-', $time)[0] ?? '99:99');
            // Convert HH.MM to HH:MM for consistent sorting
            $startTime = str_replace('.', ':', $startTime);
            return [
                'date_sort' => $normalizeDate($event['date'] ?? 'zzz'),
                'time_sort' => $startTime,
                'original' => $event,
            ];
        })->sortBy(function ($item) {
            // Sort by date first, then by time numerically
            $date = $item['date_sort'];
            $time = $item['time_sort'];
            // Extract HH:MM for numeric comparison
            preg_match('/^(\d{2})[.:](\d{2})/', $time, $match);
            $timeNum = isset($match[1]) ? (int)($match[1] . $match[2]) : 9999;
            return [$date, $timeNum];
        })->values()->pluck('original')->all();

        $html = view('pages.admin.full-schedule-pdf', [
            'eventSchedule' => $eventSchedulePrepared,
            'eventTitle' => $eventTitle,
            'eventLocation' => $eventLocation,
            'ageReference' => $ageReference,
            'dateGroups' => array_values($dateGroups),
            'totalCategories' => count($dataByCategory),
            'totalPoolLots' => $totalPoolLots,
            'totalScheduledLots' => $totalScheduledLots,
            'totalVerifiedParticipants' => $totalVerifiedParticipants,
        ])->render();

        // Preview mode - show HTML directly
        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
