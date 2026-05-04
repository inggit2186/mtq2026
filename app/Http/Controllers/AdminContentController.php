<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\District;
use App\Models\SessionSchedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminContentController extends Controller
{
    public function index(): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $bigScreenUrl = route('big-screen');
        $projectorProtocolUrl = 'emtq-launch://bigscreen?url='.rawurlencode($bigScreenUrl);

        return view('pages/admin-content-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'bigScreenUrl' => $bigScreenUrl,
            'projectorProtocolUrl' => $projectorProtocolUrl,
            'districtCount' => District::query()->count(),
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

        Announcement::query()->create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'priority' => $validated['priority'],
            'published_by' => auth()->id(),
            'published_at' => $validated['published_at'] ?? now(),
        ]);

        return redirect()
            ->route('admin.content')
            ->with('status', 'Pengumuman baru berhasil dibuat.');
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
            'status' => ['required', 'in:scheduled,ongoing,completed,postponed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        SessionSchedule::query()->create($validated);

        return redirect()
            ->route('admin.content')
            ->with('status', 'Jadwal baru berhasil ditambahkan.');
    }
}
