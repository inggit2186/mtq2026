<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\District;
use App\Models\OfficialAccessSetting;
use App\Models\SessionSchedule;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            'announcements' => Announcement::query()
                ->with('author')
                ->latest('published_at')
                ->latest('created_at')
                ->limit(12)
                ->get(),
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
            'mandate_upload_open' => ['nullable', 'boolean'],
            'participant_documents_open' => ['nullable', 'boolean'],
            'participant_verification_open' => ['nullable', 'boolean'],
            'participant_lot_open' => ['nullable', 'boolean'],
            'participant_maqra_open' => ['nullable', 'boolean'],
        ]);

        $setting = OfficialAccessSetting::current() ?? new OfficialAccessSetting();
        $setting->fill([
            'participant_registration_open' => $request->boolean('participant_registration_open'),
            'participant_edit_open' => $request->boolean('participant_edit_open'),
            'mandate_upload_open' => $request->boolean('mandate_upload_open'),
            'participant_documents_open' => $request->boolean('participant_documents_open'),
            'participant_verification_open' => $request->boolean('participant_verification_open'),
            'participant_lot_open' => $request->boolean('participant_lot_open'),
            'participant_maqra_open' => $request->boolean('participant_maqra_open'),
        ]);
        $setting->save();

        ActivityLogger::log(
            'official.access.updated',
            (auth()->user()?->name ?? 'Admin').' memperbarui pengaturan akses official.',
            $setting,
            [
                'participant_registration_open' => $setting->participant_registration_open,
                'participant_edit_open' => $setting->participant_edit_open,
                'mandate_upload_open' => $setting->mandate_upload_open,
                'participant_documents_open' => $setting->participant_documents_open,
                'participant_verification_open' => $setting->participant_verification_open,
                'participant_lot_open' => $setting->participant_lot_open,
                'participant_maqra_open' => $setting->participant_maqra_open,
            ]
        );

        return redirect()
            ->route('admin.content')
            ->with('status', 'Pengaturan akses official berhasil diperbarui.');
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
            'published_at' => ['nullable', 'date'],
        ]);

        $announcement = Announcement::query()->create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'priority' => $validated['priority'],
            'published_by' => auth()->id(),
            'published_at' => $validated['published_at'] ?? now(),
        ]);

        ActivityLogger::log(
            'announcement.created',
            (auth()->user()?->name ?? 'Panitia').' membuat pengumuman "'.$announcement->title.'".',
            $announcement,
            [
                'priority' => $announcement->priority,
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
}
