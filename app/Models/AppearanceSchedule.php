<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AppearanceSchedule extends Model
{
    use HasFactory;

    /**
     * Categories where 1 district = 1 lot number
     */
    private const LOT_PER_DISTRICT_BRANCHES = [
        'Fahmil Qur`an',
        'Syarhil Qur`an',
        'Khutbah Jumat dan Adzan',
    ];

    protected $fillable = [
        'competition_category_id',
        'number_of_days',
        'day_schedules',
        'is_active',
    ];

    protected $casts = [
        'day_schedules' => 'array',
        'number_of_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function isLotPerDistrictCategory(): bool
    {
        $branch = $this->category?->branch ?? '';
        return in_array($branch, self::LOT_PER_DISTRICT_BRANCHES);
    }

    public function getConfiguredLotRange(): array
    {
        $category = $this->category;
        $minLot = (int) ($category?->lot_number_min ?? 1);
        $maxLot = (int) ($category?->lot_number_max ?? 99);

        return [
            'min' => $minLot,
            'max' => $maxLot,
        ];
    }

    /**
     * Get all valid pool lot numbers based on actual participants or districts.
     * For lot-per-district categories: uses district count (1 district = 1 lot).
     * For regular categories: uses participant count (with Putra/Putri splitting).
     */
    public function getPoolLotNumbers(): array
    {
        $category = $this->category;

        // Check if this is a lot-per-district category
        if ($this->isLotPerDistrictCategory()) {
            // For lot-per-district categories, each district gets 1 lot
            $totalDistricts = Participant::query()
                ->where('competition_category_id', $category->id)
                ->where('verification_status', 'verified')
                ->distinct('district_id')
                ->count('district_id');

            // All lots go to Putra (or single gender)
            $lotNumbers = range(1, $totalDistricts * 2 - ($totalDistricts > 0 ? 1 : 0), 2);
            if ($totalDistricts === 0) {
                $lotNumbers = [];
            }

            return [
                'putra' => $lotNumbers,
                'putri' => [],
                'all' => $lotNumbers,
            ];
        }

        // Regular categories - use participant count with Putra/Putri splitting
        $putraCount = Participant::query()
            ->where('competition_category_id', $category->id)
            ->where('gender', 'putra')
            ->where('verification_status', 'verified')
            ->count();

        $putriCount = Participant::query()
            ->where('competition_category_id', $category->id)
            ->where('gender', 'putri')
            ->where('verification_status', 'verified')
            ->count();

        $groupSize = (int) ($category->group_size ?? 1);
        $putraSharedCount = $groupSize > 1 ? (int) ceil($putraCount / $groupSize) : $putraCount;
        $putriSharedCount = $groupSize > 1 ? (int) ceil($putriCount / $groupSize) : $putriCount;

        $putraPoolMax = $putraSharedCount * 2;
        $putriPoolMax = $putriSharedCount * 2 - 1;

        $putraLots = range(2, $putraPoolMax, 2);
        $putriLots = range(1, max(1, $putriPoolMax), 2);

        return [
            'putra' => $putraLots,
            'putri' => $putriLots,
            'all' => array_unique(array_merge($putraLots, $putriLots)),
        ];
    }

    public function getTotalLots(): int
    {
        $poolLots = $this->getPoolLotNumbers();
        return count($poolLots['all']);
    }

    public function getTotalParticipants(): int
    {
        $schedules = $this->day_schedules ?? [];
        $total = 0;
        foreach ($schedules as $day) {
            $total += (int) ($day['count'] ?? 0);
        }
        return $total;
    }

    /**
     * Get total expected based on category type.
     * Returns participant count for regular categories, district count for lot-per-district.
     */
    public function getTotalExpected(): int
    {
        if ($this->isLotPerDistrictCategory()) {
            return Participant::query()
                ->where('competition_category_id', $this->competition_category_id)
                ->where('verification_status', 'verified')
                ->distinct('district_id')
                ->count('district_id');
        }

        return Participant::query()
            ->where('competition_category_id', $this->competition_category_id)
            ->where('verification_status', 'verified')
            ->count();
    }

    public function getDaySchedule(int $dayIndex): ?array
    {
        $schedules = $this->day_schedules ?? [];
        return $schedules[$dayIndex] ?? null;
    }

    /**
     * Get day range using actual pool lot numbers.
     */
    public function getDayRange(int $dayIndex): array
    {
        $daySchedule = $this->getDaySchedule($dayIndex);
        $count = (int) ($daySchedule['count'] ?? 0);

        if ($count === 0) {
            return [
                'start' => 0,
                'end' => 0,
                'count' => 0,
                'lot_numbers' => [],
            ];
        }

        $poolLots = $this->getPoolLotNumbers();
        $allLots = $poolLots['all'];
        sort($allLots);

        $offset = 0;
        for ($i = 0; $i < $dayIndex; $i++) {
            $prevSchedule = $this->getDaySchedule($i);
            $offset += (int) ($prevSchedule['count'] ?? 0);
        }

        $dayLots = array_slice($allLots, $offset, $count);

        return [
            'start' => $dayLots[0] ?? 0,
            'end' => $dayLots[count($dayLots) - 1] ?? 0,
            'count' => $count,
            'lot_numbers' => $dayLots,
        ];
    }

    public function getFormattedDate(int $dayIndex): ?string
    {
        $daySchedule = $this->getDaySchedule($dayIndex);
        if (empty($daySchedule['date'])) {
            return null;
        }

        $date = \Carbon\Carbon::parse($daySchedule['date'])->translatedFormat('d F Y');
        $time = $daySchedule['time'] ?? '';
        $name = $daySchedule['name'] ?? '';

        $parts = [];
        if ($name) $parts[] = $name;
        if ($date) $parts[] = $date;
        if ($time) $parts[] = $time . ' WIB';

        return implode(' - ', $parts) ?: null;
    }

    public function getDayParticipants(int $dayIndex): array
    {
        $dayRange = $this->getDayRange($dayIndex);
        $daySchedule = $this->getDaySchedule($dayIndex);
        $lotNumbers = $dayRange['lot_numbers'] ?? [];

        if (empty($lotNumbers)) {
            return [
                'participants' => collect(),
                'total' => 0,
                'displayed' => 0,
                'remaining' => 0,
                'range' => $dayRange,
                'schedule' => $daySchedule,
            ];
        }

        // Sanitize lot numbers to ensure they are integers (prevent SQL injection)
        $sanitizedLotNumbers = array_map('intval', $lotNumbers);

        $participants = Participant::query()
            ->where('competition_category_id', $this->competition_category_id)
            ->whereNotNull('lot_number')
            ->where('verification_status', 'verified')
            ->whereRaw(
                "CAST(SUBSTRING_INDEX(lot_number, '-', -1) AS UNSIGNED) IN (" . implode(',', array_fill(0, count($sanitizedLotNumbers), '?')) . ")",
                $sanitizedLotNumbers
            )
            ->orderByRaw("CAST(SUBSTRING_INDEX(lot_number, '-', -1) AS UNSIGNED) ASC")
            ->get();

        $totalParticipants = Participant::query()
            ->where('competition_category_id', $this->competition_category_id)
            ->whereNotNull('lot_number')
            ->where('verification_status', 'verified')
            ->count();

        $totalPoolLots = count($this->getPoolLotNumbers()['all']);
        $remaining = max(0, $totalPoolLots - ($dayRange['end'] ?? 0));

        return [
            'participants' => $participants,
            'total' => $totalParticipants,
            'displayed' => $participants->count(),
            'remaining' => $remaining,
            'range' => $dayRange,
            'schedule' => $daySchedule,
        ];
    }
}
