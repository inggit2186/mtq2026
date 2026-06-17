<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\MaqraRound;
use App\Models\MaqraSchedule;
use App\Events\MaqraScheduleUpdated;
use App\Models\OfficialAccessSetting;
use App\Models\SessionSchedule;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminContentController extends Controller
{
    public function index(): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        SessionSchedule::syncAutomaticStatuses();

        $bigScreenUrl = route('big-screen');
        $projectorProtocolUrl = 'emtq-launch://bigscreen?url='.rawurlencode($bigScreenUrl);

        return view('pages/admin-content-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'bigScreenUrl' => $bigScreenUrl,
            'projectorProtocolUrl' => $projectorProtocolUrl,
            'districtCount' => District::query()->count(),
            'officialAccessReady' => Schema::hasTable('official_access_settings'),
            'officialAccessSetting' => OfficialAccessSetting::currentOrDefault(),
            'maqraCategories' => CompetitionCategory::query()
                ->orderBy('sort_order')
                ->orderBy('branch')
                ->orderBy('name')
                ->get(),
            'maqraRounds' => MaqraRound::active()->orderBy('sort_order')->get(),
            'maqraSchedules' => MaqraSchedule::with(['round', 'category'])
                ->orderBy('open_at', 'desc')
                ->get(),
            'announcements' => Announcement::query()
                ->with('author')
                ->latest('published_at')
                ->latest('created_at')
                ->limit(12)
                ->get(),
            'announcementAudienceLabels' => [
                'all' => 'Semua Dashboard',
                'official' => 'Official',
                'panitia' => 'Panitia',
                'official_panitia' => 'Official + Panitia',
            ],
            'schedules' => SessionSchedule::query()
                ->orderBy('starts_at')
                ->limit(12)
                ->get(),
        ]);
    }

    public function updateOfficialAccess(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        if (! Schema::hasTable('official_access_settings')) {
            return redirect()
                ->route('admin.content')
                ->with('status', 'Tabel official_access_settings belum tersedia. Jalankan migrate terlebih dahulu agar pengaturan akses official bisa disimpan.');
        }

        $request->validate([
            'participant_registration_open' => ['nullable', 'boolean'],
            'participant_edit_open' => ['nullable', 'boolean'],
            'participant_delete_open' => ['nullable', 'boolean'],
            'mandate_upload_open' => ['nullable', 'boolean'],
            'participant_documents_open' => ['nullable', 'boolean'],
            'participant_verification_open' => ['nullable', 'boolean'],
            'participant_lot_open' => ['nullable', 'boolean'],
            'participant_maqra_penyisihan_open' => ['nullable', 'boolean'],
            'participant_maqra_final_open' => ['nullable', 'boolean'],
            'participant_maqra_lot_min' => ['nullable', 'integer', 'min:1'],
            'participant_maqra_lot_max' => ['nullable', 'integer', 'min:1'],
            'participant_maqra_lot_ranges' => ['nullable', 'array'],
            'participant_maqra_lot_ranges.*' => ['nullable', 'array'],
            'participant_maqra_lot_ranges.*.min' => ['nullable', 'integer', 'min:1'],
            'participant_maqra_lot_ranges.*.max' => ['nullable', 'integer', 'min:1'],
            'participant_maqra_category_ids' => ['nullable', 'array'],
            'participant_maqra_category_ids.*' => ['integer', 'exists:competition_categories,id'],
        ]);

        $setting = OfficialAccessSetting::current() ?? new OfficialAccessSetting();
        $selectedMaqraCategoryIds = collect($request->input('participant_maqra_category_ids', []))
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();
        $maqraLotMin = filled($request->input('participant_maqra_lot_min'))
            ? (int) $request->input('participant_maqra_lot_min')
            : null;
        $maqraLotMax = filled($request->input('participant_maqra_lot_max'))
            ? (int) $request->input('participant_maqra_lot_max')
            : null;

        if (filled($maqraLotMin) && filled($maqraLotMax) && $maqraLotMin > $maqraLotMax) {
            [$maqraLotMin, $maqraLotMax] = [$maqraLotMax, $maqraLotMin];
        }

        $maqraPenyisihanOpen = $request->boolean('participant_maqra_penyisihan_open');
        $maqraFinalOpen = $request->boolean('participant_maqra_final_open');

        $maqraLotRangesInput = $request->input('participant_maqra_lot_ranges', []);
        $maqraLotRanges = [];
        if (is_array($maqraLotRangesInput)) {
            foreach ($maqraLotRangesInput as $categoryId => $range) {
                if (! is_array($range)) {
                    continue;
                }

                $rangeMin = filled($range['min'] ?? null) ? (int) $range['min'] : null;
                $rangeMax = filled($range['max'] ?? null) ? (int) $range['max'] : null;

                if (! filled($rangeMin) && ! filled($rangeMax)) {
                    continue;
                }

                if (! filled($rangeMin) || ! filled($rangeMax)) {
                    return redirect()
                        ->route('admin.content')
                        ->withErrors(['participant_maqra_lot_ranges' => 'Setiap rentang lot maqra per golongan harus diisi lengkap.'])
                        ->withInput();
                }

                if ($rangeMin > $rangeMax) {
                    [$rangeMin, $rangeMax] = [$rangeMax, $rangeMin];
                }

                $categoryId = (int) $categoryId;
                if ($categoryId > 0) {
                    $maqraLotRanges[$categoryId] = [
                        'min' => $rangeMin,
                        'max' => $rangeMax,
                    ];
                }
            }
        }

        $setting->fill([
            'participant_registration_open' => $request->boolean('participant_registration_open'),
            'participant_edit_open' => $request->boolean('participant_edit_open'),
            'participant_delete_open' => $request->boolean('participant_delete_open'),
            'mandate_upload_open' => $request->boolean('mandate_upload_open'),
            'participant_documents_open' => $request->boolean('participant_documents_open'),
            'participant_verification_open' => $request->boolean('participant_verification_open'),
            'participant_lot_open' => $request->boolean('participant_lot_open'),
            'participant_maqra_open' => $maqraPenyisihanOpen || $maqraFinalOpen,
            'participant_maqra_penyisihan_open' => $maqraPenyisihanOpen,
            'participant_maqra_final_open' => $maqraFinalOpen,
            'participant_maqra_lot_min' => $maqraLotMin,
            'participant_maqra_lot_max' => $maqraLotMax,
            'participant_maqra_lot_ranges' => $maqraLotRanges,
            'participant_maqra_category_ids' => $selectedMaqraCategoryIds,
        ]);
        $setting->save();

        ActivityLogger::log(
            'official.access.updated',
            (auth()->user()?->name ?? 'Admin').' memperbarui pengaturan akses official.',
            $setting,
            [
                'participant_registration_open' => $setting->participant_registration_open,
                'participant_edit_open' => $setting->participant_edit_open,
                'participant_delete_open' => $setting->participant_delete_open,
                'mandate_upload_open' => $setting->mandate_upload_open,
                'participant_documents_open' => $setting->participant_documents_open,
                'participant_verification_open' => $setting->participant_verification_open,
                'participant_lot_open' => $setting->participant_lot_open,
                'participant_maqra_open' => $setting->participant_maqra_open,
                'participant_maqra_penyisihan_open' => $setting->participant_maqra_penyisihan_open,
                'participant_maqra_final_open' => $setting->participant_maqra_final_open,
                'participant_maqra_lot_min' => $setting->participant_maqra_lot_min,
                'participant_maqra_lot_max' => $setting->participant_maqra_lot_max,
                'participant_maqra_lot_ranges' => $setting->participant_maqra_lot_ranges,
                'participant_maqra_category_ids' => $setting->maqraOpenCategoryIds(),
            ]
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Pengaturan akses official berhasil diperbarui.');
    }

    public function updateMaqraAccess(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        if (! Schema::hasTable('official_access_settings')) {
            return redirect()
                ->route('admin.content')
                ->with('status', 'Tabel official_access_settings belum tersedia. Jalankan migrate terlebih dahulu.');
        }

        $request->validate([
            'participant_maqra_penyisihan_open' => ['nullable', 'boolean'],
            'participant_maqra_final_open' => ['nullable', 'boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*.enabled' => ['nullable', 'boolean'],
            'categories.*.open_at' => ['nullable', 'date'],
            'categories.*.close_at' => ['nullable', 'date'],
            'categories.*.lot_min' => ['nullable', 'integer', 'min:1'],
            'categories.*.lot_max' => ['nullable', 'integer', 'min:1'],
        ]);

        $setting = OfficialAccessSetting::current() ?? new OfficialAccessSetting();

        // Process category schedules
        $categorySchedulesInput = $request->input('categories', []);
        $categorySchedules = [];
        if (is_array($categorySchedulesInput)) {
            foreach ($categorySchedulesInput as $categoryId => $schedule) {
                if (! is_array($schedule)) {
                    continue;
                }

                $categoryId = (int) $categoryId;
                if ($categoryId <= 0) {
                    continue;
                }

                $enabled = ! empty($schedule['enabled']);
                $openAt = filled($schedule['open_at'] ?? null)
                    ? \Carbon\Carbon::parse($schedule['open_at'])
                    : null;
                $closeAt = filled($schedule['close_at'] ?? null)
                    ? \Carbon\Carbon::parse($schedule['close_at'])
                    : null;
                $lotMin = filled($schedule['lot_min'] ?? null) ? (int) $schedule['lot_min'] : null;
                $lotMax = filled($schedule['lot_max'] ?? null) ? (int) $schedule['lot_max'] : null;

                // Validate lot range
                if (filled($lotMin) && filled($lotMax) && $lotMin > $lotMax) {
                    [$lotMin, $lotMax] = [$lotMax, $lotMin];
                }

                $categorySchedules[$categoryId] = [
                    'enabled' => $enabled,
                    'open_at' => $openAt?->toIso8601String(),
                    'close_at' => $closeAt?->toIso8601String(),
                    'lot_min' => $lotMin,
                    'lot_max' => $lotMax,
                ];
            }
        }

        $setting->fill([
            'participant_maqra_open' => $request->boolean('participant_maqra_penyisihan_open') || $request->boolean('participant_maqra_final_open'),
            'participant_maqra_penyisihan_open' => $request->boolean('participant_maqra_penyisihan_open'),
            'participant_maqra_final_open' => $request->boolean('participant_maqra_final_open'),
            'participant_maqra_category_schedules' => $categorySchedules,
        ]);
        $setting->save();

        ActivityLogger::log(
            'maqra.access.updated',
            (auth()->user()?->name ?? 'Admin').' memperbarui pengaturan akses maqra.',
            $setting,
            [
                'participant_maqra_penyisihan_open' => $setting->participant_maqra_penyisihan_open,
                'participant_maqra_final_open' => $setting->participant_maqra_final_open,
                'categories' => $categorySchedules,
            ]
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Pengaturan akses maqra berhasil diperbarui.');
    }

    public function syncDistricts(): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        Artisan::call('districts:sync-silatar');
        $output = trim(Artisan::output());
        preg_match('/(\d+) dibuat, (\d+) diperbarui, (\d+) total dari API/i', $output, $matches);

        $created = (int) ($matches[1] ?? 0);
        $updated = (int) ($matches[2] ?? 0);
        $total = (int) ($matches[3] ?? District::query()->count());

        return redirect()
            ->route('admin.content')
            ->with('status', sprintf(
                'Sinkronisasi kecamatan SILATAR selesai. %d data baru, %d data diperbarui, %d total data API diproses.',
                $created,
                $updated,
                $total,
            ));
    }

    public function projectorInstaller(): Response
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $defaultUrl = route('big-screen');
        $script = <<<'POWERSHELL'
param()

$baseDir = Join-Path $env:LOCALAPPDATA 'e-MTQ\ProjectorLauncher'
$launcherPath = Join-Path $baseDir 'Launch-EMTQProjector.ps1'
$defaultUrl = '__DEFAULT_URL__'

if (-not (Test-Path $baseDir)) {
    New-Item -ItemType Directory -Path $baseDir -Force | Out-Null
}

$launcherContent = @'
param(
    [string]$ProtocolUrl
)

$defaultUrl = '__DEFAULT_URL__'

function Get-ProtocolParameter {
    param(
        [string]$Source,
        [string]$Name
    )

    if ([string]::IsNullOrWhiteSpace($Source)) {
        return $null
    }

    $parts = $Source -split '\?', 2
    if ($parts.Count -lt 2) {
        return $null
    }

    foreach ($pair in ($parts[1] -split '&')) {
        if ($pair -like ($Name + '=*')) {
            return [System.Uri]::UnescapeDataString($pair.Substring($Name.Length + 1))
        }
    }

    return $null
}

$launchUrl = Get-ProtocolParameter -Source $ProtocolUrl -Name 'url'
if ([string]::IsNullOrWhiteSpace($launchUrl)) {
    $launchUrl = $defaultUrl
}

try {
    Start-Process -FilePath (Join-Path $env:WINDIR 'System32\DisplaySwitch.exe') -ArgumentList '/extend' -WindowStyle Hidden | Out-Null
    Start-Sleep -Seconds 2
} catch {
}

$browserCandidates = @(
    (Join-Path ${env:ProgramFiles(x86)} 'Microsoft\Edge\Application\msedge.exe'),
    (Join-Path $env:ProgramFiles 'Microsoft\Edge\Application\msedge.exe'),
    (Join-Path ${env:ProgramFiles(x86)} 'Google\Chrome\Application\chrome.exe'),
    (Join-Path $env:ProgramFiles 'Google\Chrome\Application\chrome.exe')
) | Where-Object { $_ -and (Test-Path $_) }

$browserPath = $browserCandidates | Select-Object -First 1

if ($browserPath) {
    Start-Process -FilePath $browserPath -ArgumentList @('--new-window', '--start-fullscreen', $launchUrl) | Out-Null
} else {
    Start-Process -FilePath $launchUrl | Out-Null
}
'@

$launcherContent = $launcherContent.Replace('__DEFAULT_URL__', $defaultUrl)
Set-Content -LiteralPath $launcherPath -Value $launcherContent -Encoding UTF8

$protocolRoot = 'HKCU:\Software\Classes\emtq-launch'
New-Item -Path $protocolRoot -Force | Out-Null
Set-Item -Path $protocolRoot -Value 'URL:e-MTQ Projector Launcher'
Set-ItemProperty -Path $protocolRoot -Name 'URL Protocol' -Value ''

$commandPath = Join-Path $protocolRoot 'shell\open\command'
New-Item -Path $commandPath -Force | Out-Null
$commandValue = 'powershell.exe -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + $launcherPath + '" "%1"'
Set-Item -Path $commandPath -Value $commandValue

Write-Host ''
Write-Host 'e-MTQ projector launcher berhasil diinstall.'
Write-Host ('Launcher: ' + $launcherPath)
Write-Host ('Default big screen: ' + $defaultUrl)
Write-Host ''
Write-Host 'Langkah berikutnya:'
Write-Host '1. Buka panel Kelola Konten.'
Write-Host '2. Klik tombol Aktifkan Projector.'
Write-Host '3. Windows akan pindah ke mode Extend lalu membuka big screen fullscreen.'
POWERSHELL;

        $script = str_replace('__DEFAULT_URL__', $defaultUrl, $script);

        return response($script, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="install-emtq-projector.ps1"',
        ]);
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
            'priority' => ['required', 'in:low,normal,high'],
            'audience' => ['required', 'in:all,official,panitia,official_panitia'],
            'published_at' => ['nullable', 'date'],
        ]);

        $announcement = Announcement::query()->create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'priority' => $validated['priority'],
            'audience' => $validated['audience'],
            'published_by' => auth()->id(),
            'published_at' => $validated['published_at'] ?? now(),
        ]);

        ActivityLogger::log(
            'announcement.created',
            (auth()->user()?->name ?? 'Panitia').' membuat pengumuman "'.$announcement->title.'".',
            $announcement,
            [
                'priority' => $announcement->priority,
                'audience' => $announcement->audience,
                'published_at' => optional($announcement->published_at)->toDateTimeString(),
            ]
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Pengumuman baru berhasil dibuat.');
    }

    public function destroyAnnouncement(Announcement $announcement): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $title = $announcement->title;
        $announcement->delete();

        ActivityLogger::log(
            'announcement.deleted',
            (auth()->user()?->name ?? 'Panitia').' menghapus pengumuman "'.$title.'".',
            $announcement,
            ['title' => $title]
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Pengumuman "'.$title.'" berhasil dihapus.');
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'stage' => ['required', 'string', 'max:255'],
            'venue' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:scheduled,postponed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['status'] = $validated['status'] === SessionSchedule::STATUS_POSTPONED
            ? SessionSchedule::STATUS_POSTPONED
            : SessionSchedule::STATUS_SCHEDULED;

        $schedule = SessionSchedule::query()->create($validated);
        $schedule->syncAutomaticStatus();

        ActivityLogger::log(
            'schedule.created',
            (auth()->user()?->name ?? 'Panitia').' membuat jadwal "'.$schedule->title.'".',
            $schedule,
            [
                'stage' => $schedule->stage,
                'venue' => $schedule->venue,
                'starts_at' => optional($schedule->starts_at)->toDateTimeString(),
                'ends_at' => optional($schedule->ends_at)->toDateTimeString(),
                'status' => $schedule->status,
            ]
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Jadwal baru berhasil ditambahkan.');
    }

    public function updateScheduleStatus(Request $request, SessionSchedule $schedule): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:scheduled,postponed'],
        ]);

        if ($validated['status'] === SessionSchedule::STATUS_POSTPONED) {
            $oldStatus = $schedule->status;
            $schedule->forceFill(['status' => SessionSchedule::STATUS_POSTPONED])->save();

            ActivityLogger::log(
                'schedule.status_updated',
                (auth()->user()?->name ?? 'Panitia').' menandai jadwal "'.$schedule->title.'" sebagai ditunda.',
                $schedule,
                [
                    'old_status' => $oldStatus,
                    'new_status' => $schedule->status,
                ]
            );

            return back()->with('status', 'Jadwal "'.$schedule->title.'" berhasil ditandai ditunda.');
        }

        $oldStatus = $schedule->status;
        $schedule->forceFill(['status' => SessionSchedule::STATUS_SCHEDULED])->save();
        $schedule->syncAutomaticStatus();

        ActivityLogger::log(
            'schedule.status_updated',
            (auth()->user()?->name ?? 'Panitia').' mengembalikan jadwal "'.$schedule->title.'" ke status otomatis.',
            $schedule,
            [
                'old_status' => $oldStatus,
                'new_status' => $schedule->status,
            ]
        );

        return back()->with('status', 'Status jadwal "'.$schedule->title.'" kembali otomatis mengikuti tanggal dan jam.');
    }

    public function destroySchedule(SessionSchedule $schedule): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $title = $schedule->title;
        $schedule->delete();

        ActivityLogger::log(
            'schedule.deleted',
            (auth()->user()?->name ?? 'Panitia').' menghapus jadwal "'.$title.'".',
            $schedule,
            ['title' => $title]
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Jadwal "'.$title.'" berhasil dihapus.');
    }

    // ======================
    // Maqra Round CRUD
    // ======================

    public function storeMaqraRound(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        // Check duplicate slug
        if (MaqraRound::where('slug', $validated['slug'])->exists()) {
            return redirect()
                ->route('admin.content')
                ->withErrors(['name' => 'Nama babak sudah ada. Gunakan nama lain.'])
                ->withInput();
        }

        $round = MaqraRound::create($validated);

        ActivityLogger::log(
            'maqra_round.created',
            (auth()->user()?->name ?? 'Admin').' membuat babak maqra baru "'.$round->name.'".',
            $round,
            ['name' => $round->name, 'slug' => $round->slug]
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Babak "'.$round->name.'" berhasil ditambahkan.');
    }

    public function updateMaqraRound(Request $request, MaqraRound $maqraRound): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $newSlug = \Illuminate\Support\Str::slug($validated['name']);
        if ($newSlug !== $maqraRound->slug && MaqraRound::where('slug', $newSlug)->exists()) {
            return redirect()
                ->route('admin.content')
                ->withErrors(['name' => 'Nama babak sudah ada. Gunakan nama lain.'])
                ->withInput();
        }

        $validated['slug'] = $newSlug;
        $validated['is_active'] = $request->boolean('is_active', true);

        $maqraRound->update($validated);

        ActivityLogger::log(
            'maqra_round.updated',
            (auth()->user()?->name ?? 'Admin').' memperbarui babak maqra "'.$maqraRound->name.'".',
            $maqraRound,
            ['name' => $maqraRound->name]
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Babak "'.$maqraRound->name.'" berhasil diperbarui.');
    }

    public function destroyMaqraRound(MaqraRound $maqraRound): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        // Check if round has schedules
        if ($maqraRound->schedules()->exists()) {
            return redirect()
                ->route('admin.content')
                ->withErrors(['round' => 'Babak tidak bisa dihapus karena masih memiliki jadwal. Hapus jadwal terlebih dahulu.']);
        }

        $name = $maqraRound->name;
        $maqraRound->delete();

        ActivityLogger::log(
            'maqra_round.deleted',
            (auth()->user()?->name ?? 'Admin').' menghapus babak maqra "'.$name.'".',
            null,
            ['name' => $name]
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Babak "'.$name.'" berhasil dihapus.');
    }

    // ======================
    // Maqra Schedule CRUD
    // ======================

    public function storeMaqraSchedule(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'round_id' => ['required', 'integer', 'exists:maqra_rounds,id'],
            'category_id' => ['required', 'integer', 'exists:competition_categories,id'],
            'open_at' => ['required', 'date'],
            'close_at' => ['required', 'date', 'after:open_at'],
            'lot_min' => ['required', 'integer', 'min:1'],
            'lot_max' => ['required', 'integer', 'min:1', 'gte:lot_min'],
            'is_active' => ['nullable', 'boolean'],
            'draw_access_by' => ['nullable', 'string', Rule::in(['panitia_only', 'official_only', 'both'])],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['draw_access_by'] = $validated['draw_access_by'] ?? 'official_only';

        $schedule = MaqraSchedule::create($validated);
        $schedule->load(['round', 'category']);

        ActivityLogger::log(
            'maqra_schedule.created',
            (auth()->user()?->name ?? 'Admin').' membuat jadwal maqra untuk '.$schedule->category?->name.' ('.$schedule->round?->name.').',
            $schedule,
            [
                'round_id' => $schedule->round_id,
                'category_id' => $schedule->category_id,
                'lot_range' => $schedule->lot_min.'-'.$schedule->lot_max,
            ]
        );

        // Broadcast schedule creation/update to all connected clients
        \Illuminate\Support\Facades\Log::info('[MaqraSchedule] Broadcasting created event', [
            'schedule_id' => $schedule->id,
            'action' => 'created',
            'category' => $schedule->category?->name,
        ]);

        MaqraScheduleUpdated::dispatch($schedule, 'created');

        return redirect()
            ->route('admin.content')
            ->with('status', 'Jadwal maqra berhasil ditambahkan.');
    }

    public function updateMaqraSchedule(Request $request, MaqraSchedule $maqraSchedule): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'round_id' => ['required', 'integer', 'exists:maqra_rounds,id'],
            'category_id' => ['required', 'integer', 'exists:competition_categories,id'],
            'open_at' => ['required', 'date'],
            'close_at' => ['required', 'date', 'after:open_at'],
            'lot_min' => ['required', 'integer', 'min:1'],
            'lot_max' => ['required', 'integer', 'min:1', 'gte:lot_min'],
            'is_active' => ['nullable', 'boolean'],
            'draw_access_by' => ['nullable', 'string', Rule::in(['panitia_only', 'official_only', 'both'])],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['draw_access_by'] = $validated['draw_access_by'] ?? 'official_only';

        $maqraSchedule->update($validated);
        $maqraSchedule->load(['round', 'category']);

        ActivityLogger::log(
            'maqra_schedule.updated',
            (auth()->user()?->name ?? 'Admin').' memperbarui jadwal maqra untuk '.$maqraSchedule->category?->name.' ('.$maqraSchedule->round?->name.').',
            $maqraSchedule,
            [
                'round_id' => $maqraSchedule->round_id,
                'category_id' => $maqraSchedule->category_id,
            ]
        );

        // Broadcast schedule update to all connected clients
        MaqraScheduleUpdated::dispatch(
            $maqraSchedule,
            $maqraSchedule->is_active ? 'opened' : 'closed'
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Jadwal maqra berhasil diperbarui.');
    }

    public function destroyMaqraSchedule(MaqraSchedule $maqraSchedule): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $info = $maqraSchedule->category?->name.' ('.$maqraSchedule->round?->name.')';

        // Broadcast before delete
        MaqraScheduleUpdated::dispatch($maqraSchedule, 'deleted');

        $maqraSchedule->delete();

        ActivityLogger::log(
            'maqra_schedule.deleted',
            (auth()->user()?->name ?? 'Admin').' menghapus jadwal maqra untuk '.$info.'.',
            null,
            ['info' => $info]
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Jadwal maqra berhasil dihapus.');
    }

    public function toggleMaqraSchedule(MaqraSchedule $maqraSchedule): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $maqraSchedule->update(['is_active' => ! $maqraSchedule->is_active]);
        $maqraSchedule->load(['round', 'category']);

        $status = $maqraSchedule->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $info = $maqraSchedule->category?->name.' ('.$maqraSchedule->round?->name.')';

        ActivityLogger::log(
            'maqra_schedule.toggled',
            (auth()->user()?->name ?? 'Admin').' '.$status.' jadwal maqra untuk '.$info.'.',
            $maqraSchedule,
            ['is_active' => $maqraSchedule->is_active]
        );

        // Broadcast schedule update to all connected clients
        \Illuminate\Support\Facades\Log::info('[MaqraSchedule] Broadcasting toggle event', [
            'schedule_id' => $maqraSchedule->id,
            'action' => $maqraSchedule->is_active ? 'opened' : 'closed',
            'category' => $info,
        ]);

        MaqraScheduleUpdated::dispatch(
            $maqraSchedule,
            $maqraSchedule->is_active ? 'opened' : 'closed'
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Jadwal maqra untuk '.$info.' berhasil '.$status.'.');
    }

    public function updateMaqraScheduleAccess(Request $request, MaqraSchedule $maqraSchedule): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'draw_access_by' => ['required', 'string', Rule::in(['panitia_only', 'official_only', 'both'])],
        ]);

        $maqraSchedule->update(['draw_access_by' => $validated['draw_access_by']]);
        $maqraSchedule->load(['round', 'category']);

        $accessLabel = $maqraSchedule->draw_access_by_label;
        $info = $maqraSchedule->category?->name.' ('.$maqraSchedule->round?->name.')';

        ActivityLogger::log(
            'maqra_schedule.access_updated',
            (auth()->user()?->name ?? 'Admin').' mengubah akses pengambilan maqra menjadi "'.$accessLabel.'" untuk '.$info.'.',
            $maqraSchedule,
            ['draw_access_by' => $maqraSchedule->draw_access_by]
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Akses pengambilan maqra untuk '.$info.' berhasil diubah menjadi "'.$accessLabel.'".');
    }
}
