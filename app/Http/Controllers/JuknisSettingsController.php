<?php

namespace App\Http\Controllers;

use App\Models\JuknisSetting;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class JuknisSettingsController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $juknisConfig = app(PageController::class)->juknisConfig();

        return view('pages/juknis-settings-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'juknisConfig' => $juknisConfig,
            'registrationWindows' => old('registration_windows', $juknisConfig['registration_windows'] ?? []),
            'juknisSettingsReady' => Schema::hasTable('juknis_settings'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        if (! Schema::hasTable('juknis_settings')) {
            return redirect()
                ->route('admin.juknis')
                ->with('status', 'Tabel juknis_settings belum tersedia. Jalankan migrate terlebih dahulu agar juknis dapat disimpan.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'age_reference_date' => ['required', 'string', 'max:255'],
            'app.name' => ['required', 'string', 'max:255'],
            'app.homepage_title' => ['required', 'string', 'max:255'],
            'app.homepage_tagline' => ['required', 'string', 'max:500'],
            'app.homepage_description' => ['required', 'string', 'max:2000'],
            'footer.headline' => ['required', 'string', 'max:255'],
            'footer.description' => ['required', 'string', 'max:2000'],
            'footer.contact_label' => ['required', 'string', 'max:255'],
            'footer.contact_number' => ['required', 'string', 'max:50'],
            'footer.attribution_name' => ['required', 'string', 'max:255'],
            'footer.attribution_role' => ['required', 'string', 'max:255'],
            'footer.note' => ['required', 'string', 'max:1000'],
            'registration.open' => ['required', 'string', 'max:255'],
            'registration.close' => ['required', 'string', 'max:255'],
            'registration.official_edit_start' => ['required', 'string', 'max:255'],
            'registration.official_edit_end' => ['required', 'string', 'max:255'],
            'registration.verification_start' => ['required', 'string', 'max:255'],
            'registration.verification_end' => ['required', 'string', 'max:255'],
            'registration.announcement' => ['required', 'string', 'max:255'],
            'registration.objection_start' => ['required', 'string', 'max:255'],
            'registration.objection_end' => ['required', 'string', 'max:255'],
            'registration_windows' => ['nullable', 'array'],
            'registration_windows.*.label' => ['nullable', 'string', 'max:255'],
            'registration_windows.*.start_at' => ['nullable', 'string', 'max:255'],
            'registration_windows.*.end_at' => ['nullable', 'string', 'max:255'],
            'registration_windows.*.official' => ['nullable', 'array'],
            'registration_windows.*.panitia' => ['nullable', 'array'],
            'event_schedule' => ['nullable', 'array'],
            'event_schedule.*.date' => ['nullable', 'string', 'max:255'],
            'event_schedule.*.time' => ['nullable', 'string', 'max:255'],
            'event_schedule.*.activity' => ['nullable', 'string', 'max:255'],
            'event_schedule.*.notes' => ['nullable', 'string', 'max:2000'],
            'administration_requirements_text' => ['nullable', 'string', 'max:8000'],
            'participant_rules_text' => ['nullable', 'string', 'max:8000'],
            'performance_rules_text' => ['nullable', 'string', 'max:8000'],
            'competition_system_text' => ['nullable', 'string', 'max:8000'],
            'objection_rules_text' => ['nullable', 'string', 'max:8000'],
            'official.can_view_score_detail' => ['nullable', 'boolean'],
        ]);

        $content = [
            'title' => trim((string) $validated['title']),
            'host' => trim((string) $validated['host']),
            'age_reference_date' => trim((string) $validated['age_reference_date']),
            'app' => [
                'name' => trim((string) $validated['app']['name']),
                'homepage_title' => trim((string) $validated['app']['homepage_title']),
                'homepage_tagline' => trim((string) $validated['app']['homepage_tagline']),
                'homepage_description' => trim((string) $validated['app']['homepage_description']),
            ],
            'footer' => [
                'headline' => trim((string) $validated['footer']['headline']),
                'description' => trim((string) $validated['footer']['description']),
                'contact_label' => trim((string) $validated['footer']['contact_label']),
                'contact_number' => trim((string) $validated['footer']['contact_number']),
                'attribution_name' => trim((string) $validated['footer']['attribution_name']),
                'attribution_role' => trim((string) $validated['footer']['attribution_role']),
                'note' => trim((string) $validated['footer']['note']),
            ],
            'registration' => [
                'open' => trim((string) $validated['registration']['open']),
                'close' => trim((string) $validated['registration']['close']),
                'official_edit_start' => trim((string) $validated['registration']['official_edit_start']),
                'official_edit_end' => trim((string) $validated['registration']['official_edit_end']),
                'verification_start' => trim((string) $validated['registration']['verification_start']),
                'verification_end' => trim((string) $validated['registration']['verification_end']),
                'announcement' => trim((string) $validated['registration']['announcement']),
                'objection_start' => trim((string) $validated['registration']['objection_start']),
                'objection_end' => trim((string) $validated['registration']['objection_end']),
            ],
            'registration_windows' => collect($validated['registration_windows'] ?? [])
                ->map(function (array $row): array {
                    $official = [];
                    foreach ([
                        'participant_registration_open',
                        'participant_edit_open',
                        'participant_verification_open',
                        'participant_delete_open',
                        'mandate_upload_open',
                        'participant_documents_open',
                        'participant_lot_open',
                        'participant_maqra_open',
                        'participant_maqra_penyisihan_open',
                        'participant_maqra_final_open',
                    ] as $feature) {
                        $official[$feature] = ! empty($row['official'][$feature] ?? null);
                    }

                    $panitia = [];
                    foreach ([
                        'participant_registration_open',
                        'participant_edit_open',
                        'participant_verification_open',
                        'participant_delete_open',
                        'mandate_upload_open',
                        'participant_documents_open',
                        'participant_lot_open',
                        'participant_maqra_open',
                        'participant_maqra_penyisihan_open',
                        'participant_maqra_final_open',
                    ] as $feature) {
                        $panitia[$feature] = ! empty($row['panitia'][$feature] ?? null);
                    }

                    return [
                        'label' => trim((string) ($row['label'] ?? '')),
                        'start_at' => trim((string) ($row['start_at'] ?? '')),
                        'end_at' => trim((string) ($row['end_at'] ?? '')),
                        'official' => $official,
                        'panitia' => $panitia,
                    ];
                })
                ->filter(function (array $row): bool {
                    return filled($row['label']) || filled($row['start_at']) || filled($row['end_at']);
                })
                ->values()
                ->all(),
            'event_schedule' => collect($validated['event_schedule'] ?? [])
                ->map(function (array $row): array {
                    return [
                        'date' => trim((string) ($row['date'] ?? '')),
                        'time' => trim((string) ($row['time'] ?? '')),
                        'activity' => trim((string) ($row['activity'] ?? '')),
                        'notes' => trim((string) ($row['notes'] ?? '')),
                    ];
                })
                ->filter(fn (array $row): bool => collect($row)->filter(fn ($value): bool => filled($value))->isNotEmpty())
                ->values()
                ->all(),
            'administration_requirements' => $this->textLines($validated['administration_requirements_text'] ?? null),
            'participant_rules' => $this->textLines($validated['participant_rules_text'] ?? null),
            'performance_rules' => $this->textLines($validated['performance_rules_text'] ?? null),
            'competition_system' => $this->textLines($validated['competition_system_text'] ?? null),
            'objection_rules' => $this->textLines($validated['objection_rules_text'] ?? null),
            'official' => [
                'can_view_score_detail' => ! empty($validated['official']['can_view_score_detail'] ?? null),
            ],
        ];

        $setting = JuknisSetting::current() ?? new JuknisSetting();
        $setting->fill(['content' => $content]);
        $setting->save();

        ActivityLogger::log(
            'juknis.settings.updated',
            (auth()->user()?->name ?? 'Admin').' memperbarui juknis aplikasi.',
            $setting,
            [
                'title' => $content['title'],
                'host' => $content['host'],
                'app_name' => $content['app']['name'] ?? null,
                'registration' => $content['registration'],
                'registration_windows_count' => count($content['registration_windows']),
                'event_schedule_count' => count($content['event_schedule']),
            ]
        );

        return redirect()
            ->route('admin.juknis')
            ->with('status', 'Juknis aplikasi berhasil diperbarui.');
    }

    protected function textLines(?string $value): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value) ?: [];

        return collect($lines)
            ->map(fn (string $line): string => trim($line))
            ->filter(fn (string $line): bool => $line !== '')
            ->values()
            ->all();
    }
}
