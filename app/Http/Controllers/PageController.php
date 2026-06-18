<?php

namespace App\Http\Controllers;

use App\Models\ActivityDocumentation;
use App\Models\Announcement;
use App\Models\CompetitionCategory;
use App\Models\CompetitionLocation;
use App\Models\District;
use App\Models\DocumentSetting;
use App\Models\JuknisSetting;
use App\Models\MaqraPackage;
use App\Models\Participant;
use App\Models\ScoringSetting;
use App\Models\SessionSchedule;
use App\Models\ScoreEntry;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\ScheduleRealtimeNotifier;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PageController extends Controller
{
    private const SILATAR_NIP_API = 'https://ptsp.kemenagtanahdatar.cloud/api/v1/nip/';

    public function home(Request $request): View
    {
        SessionSchedule::syncAutomaticStatuses();

        $documentConfig = $this->documentConfig();
        $coverSlides = collect();
        $galleryImages = null;

        if (Schema::hasTable('activity_documentations')) {
            $coverSlides = ActivityDocumentation::query()
                ->with('uploader')
                ->where('is_active', true)
                ->where('is_cover_homepage', true)
                ->orderBy('sort_order')
                ->orderBy('created_at')
                ->orderBy('id')
                ->limit(5)
                ->get()
                ->map(function (ActivityDocumentation $item): array {
                    $caption = trim((string) $item->caption);
                    $uploadedBy = trim((string) ($item->uploader?->name ?? 'Panitia'));
                    $uploadedAt = optional($item->created_at)->translatedFormat('d M Y');

                    return [
                        'src' => $item->imageUrl(),
                        'full_src' => $item->imageUrl(),
                        'label' => Str::limit($caption !== '' ? $caption : 'Dokumentasi MTQ', 24),
                        'caption' => $caption !== '' ? $caption : 'Dokumentasi kegiatan e-MTQ.',
                        'meta' => trim($uploadedBy.($uploadedAt ? ' • '.$uploadedAt : '')),
                    ];
                })
                ->filter(fn (array $item): bool => filled($item['src'] ?? null))
                ->values();

            $galleryImages = ActivityDocumentation::query()
                ->with('uploader')
                ->select([
                    'id',
                    'caption',
                    'image_path',
                    'thumbnail_path',
                    'uploaded_by',
                    'is_active',
                    'is_cover_homepage',
                    'sort_order',
                    'created_at',
                ])
                ->where('is_active', true)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(9, ['*'], 'gallery_page')
                ->withQueryString();

            $galleryImages->setCollection(
                $galleryImages->getCollection()
                ->map(function (ActivityDocumentation $item): array {
                    $caption = trim((string) $item->caption);
                    $uploadedBy = trim((string) ($item->uploader?->name ?? 'Panitia'));
                    $uploadedAt = optional($item->created_at)->translatedFormat('d M Y');

                    return [
                        'src' => $item->thumbnailUrl(),
                        'full_src' => $item->imageUrl(),
                        'label' => Str::limit($caption !== '' ? $caption : 'Dokumentasi MTQ', 24),
                        'caption' => $caption !== '' ? $caption : 'Dokumentasi kegiatan e-MTQ.',
                        'meta' => trim($uploadedBy.($uploadedAt ? ' | '.$uploadedAt : '')),
                    ];
                })
                ->filter(fn (array $item): bool => filled($item['src'] ?? null))
                    ->values()
            );
        }
        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name')
            ->get();
        $announcements = Announcement::query()
            ->visibleToRole(null)
            ->latest('published_at')
            ->limit(3)
            ->get();
        $featuredSchedules = SessionSchedule::query()
            ->orderByRaw("case when status = 'ongoing' then 0 when starts_at >= ? then 1 else 2 end", [now()])
            ->orderBy('starts_at')
            ->limit(2)
            ->get();
        $timeline = collect($this->juknisConfig()['event_schedule'] ?? [])
            ->take(2)
            ->values();
        $competitionVenues = collect();

        if (Schema::hasTable('competition_locations') && Schema::hasTable('competition_category_location')) {
            $competitionVenues = CompetitionLocation::query()
                ->with(['categories' => function ($query): void {
                    $query->orderBy('sort_order')->orderBy('branch')->orderBy('name');
                }])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(function (CompetitionLocation $location): array {
                    $venueName = trim((string) $location->venue_name);
                    $kind = match (true) {
                        str_contains(mb_strtolower($venueName), 'masjid') => 'Masjid',
                        str_contains(mb_strtolower($venueName), 'sma')
                            || str_contains(mb_strtolower($venueName), 'smp')
                            || str_contains(mb_strtolower($venueName), 'mts')
                            || str_contains(mb_strtolower($venueName), 'sdn') => 'Sekolah',
                        default => 'Lapangan / Komunitas',
                    };

                    $categoryLabels = $location->categories
                        ->map(fn (CompetitionCategory $category): string => trim((string) $category->branch.' - '.(string) $category->name))
                        ->filter()
                        ->values();

                    return [
                        'no' => (int) $location->sort_order,
                        'cabang' => $location->label,
                        'venue' => $venueName !== '' ? $venueName : $location->label,
                        'map_url' => (string) ($location->map_url ?? ''),
                        'kind' => $kind,
                        'photo_url' => filled($location->photo_path) ? asset($location->photo_path) : '',
                        'photo_thumb_url' => filled($location->photo_thumb_path)
                            ? asset($location->photo_thumb_path)
                            : (filled($location->photo_path) ? asset($location->photo_path) : ''),
                        'category_labels' => $categoryLabels->values()->all(),
                        'category_count' => $categoryLabels->count(),
                    ];
                })
                ->filter(fn (array $venue): bool => filled($venue['venue'] ?? null))
                ->values();
        }
        $preferredVenueOrder = (int) $request->integer('venue', 0);
        $initialVenueIndex = $competitionVenues->search(function (array $venue) use ($preferredVenueOrder): bool {
            return (int) ($venue['no'] ?? 0) === $preferredVenueOrder;
        });

        if ($initialVenueIndex === false) {
            $initialVenueIndex = $competitionVenues->search(fn (array $venue): bool => (int) ($venue['no'] ?? 0) === 2);
        }

        if ($initialVenueIndex === false) {
            $initialVenueIndex = 0;
        }
        $featuredParticipants = Participant::query()
            ->with(['category', 'district'])
            ->where('verification_status', 'verified')
            ->where(function ($query): void {
                $query->whereNotNull('document_photo')
                    ->orWhereNotNull('avatar');
            })
            ->orderBy('competition_category_id')
            ->orderBy('district_id')
            ->orderBy('name')
            ->get()
            ->map(function (Participant $participant): array {
                $categoryLabel = trim((string) ($participant->category?->branch ? $participant->category->branch.' - ' : '').(string) ($participant->category?->name ?? '-'));
                $originLabel = trim((string) ($participant->district?->name ?? $participant->institution ?? '-'));
                $ageLabel = '-';

                if ($participant->date_of_birth) {
                    try {
                        $birthDate = Carbon::parse($participant->date_of_birth)->startOfDay();
                        $age = $birthDate->diff(Carbon::now()->startOfDay());
                        $parts = array_filter([
                            $age->y > 0 ? $age->y.' tahun' : null,
                            $age->m > 0 ? $age->m.' bulan' : null,
                            $age->d > 0 ? $age->d.' hari' : null,
                        ]);
                        $ageLabel = $parts !== [] ? implode(' ', $parts) : '0 hari';
                    } catch (\Throwable) {
                        $ageLabel = '-';
                    }
                }

                return [
                    'participant_id' => $participant->id,
                    'name' => $participant->name,
                    'origin' => $originLabel !== '' ? $originLabel : '-',
                    'category_label' => $categoryLabel !== '' ? $categoryLabel : '-',
                    'branch' => $participant->category?->branch ?? '-',
                    'photo_url' => $this->publicParticipantPhotoUrl($participant),
                    'age_label' => $ageLabel,
                ];
            })
            ->values();

        return view('pages.home', [
            'assets' => $this->viteAssets(),
            'documentConfig' => $documentConfig,
            'stats' => [
                'branches' => $categories->pluck('branch')->filter()->unique()->count(),
                'categories' => $categories->count(),
                'participants' => Participant::query()->count(),
                'announcements' => Announcement::query()->visibleToRole(null)->count(),
            ],
            'featuredBranches' => $categories
            ->groupBy('branch')
            ->map(fn ($items, $branch): array => [
                    'name' => $branch,
                    'category_total' => $items->count(),
                    'quota_total' => (int) $items->sum('quota'),
                    'highlight' => $this->featuredBranchHighlight($items, (string) $branch),
                ])
                ->take(4)
                ->values(),
            'announcements' => $announcements,
            'featuredSchedules' => $featuredSchedules,
            'timeline' => $timeline,
            'competitionVenues' => $competitionVenues,
            'initialVenueIndex' => $initialVenueIndex,
            'galleryImages' => $galleryImages,
            'coverSlides' => $coverSlides,
            'featuredParticipants' => $featuredParticipants,
        ]);
    }

    public function bigScreen(Request $request): View
    {
        $filters = $request->validate([
            'competition_category_id' => ['nullable', 'integer'],
            'participant_id' => ['nullable', 'integer'],
        ]);

        $documentConfig = $this->documentConfig();
        $requestedParticipantId = (int) ($filters['participant_id'] ?? 0);
        $selectedCategory = null;

        if (filled($filters['competition_category_id'] ?? null)) {
            $selectedCategory = CompetitionCategory::query()->find($filters['competition_category_id']);
        } elseif ($requestedParticipantId) {
            $selectedCategory = CompetitionCategory::query()
                ->whereHas('participants', fn ($query) => $query->whereKey($requestedParticipantId))
                ->first();
        }

        $participants = Participant::query()
            ->with(['category', 'district', 'scores' => fn ($query) => $query->orderByDesc('submitted_at')])
            ->where('verification_status', 'verified')
            ->when($selectedCategory, fn ($query) => $query->where('competition_category_id', $selectedCategory->id))
            ->orderBy('name')
            ->get();
        $announcements = Announcement::query()
            ->visibleToRole(null)
            ->with('author')
            ->latest('published_at')
            ->latest('created_at')
            ->limit(2)
            ->get();
        $recentScores = ScoreEntry::query()
            ->with(['participant.category', 'participant.district'])
            ->whereHas('participant', function ($query) use ($selectedCategory): void {
                $query->where('verification_status', 'verified');

                if ($selectedCategory) {
                    $query->where('competition_category_id', $selectedCategory->id);
                }
            })
            ->orderByDesc('submitted_at')
            ->limit(6)
            ->get();
        $cachedParticipantId = $selectedCategory
            ? (int) Cache::get($this->currentParticipantCacheKey($selectedCategory->id), 0)
            : 0;
        $seedParticipantId = $cachedParticipantId ?: $requestedParticipantId;
        $currentParticipant = $seedParticipantId
            ? $participants->firstWhere('id', $seedParticipantId)
            : null;

        if (! $currentParticipant) {
            $currentParticipant = $participants->first();
        }

        $latestScoredEntry = $recentScores->first();
        $leaders = $this->buildLeaders(
            $participants->filter(fn (Participant $participant): bool => $participant->scores->isNotEmpty())
        );

        $queueParticipants = $participants
            ->values()
            ->reject(fn (Participant $participant): bool => $participant->id === $currentParticipant?->id)
            ->take(5)
            ->values();
        $rankingPriorityContext = $this->rankingPriorityContext($selectedCategory?->id, $selectedCategory?->branch, true);

        return view('pages/big-screen', [
            'assets' => $this->viteAssets(),
            'documentConfig' => $documentConfig,
            'selectedCategory' => $selectedCategory,
            'currentParticipant' => $currentParticipant,
            'latestScoredEntry' => $latestScoredEntry,
            'recentScores' => $recentScores,
            'queueParticipants' => $queueParticipants,
            'stats' => [
                'verified_participants' => $participants->count(),
                'score_entries' => $recentScores->count(),
                'leaders' => count($leaders),
                'announcements' => $announcements->count(),
            ],
            'leaders' => array_slice($leaders, 0, 8),
            'rankingPriorityContext' => $rankingPriorityContext,
            'announcements' => $announcements,
            'schedules' => collect(),
            'todaySchedules' => collect(),
            'generatedAt' => now(),
        ]);
    }

    public function profileSettings(): View
    {
        $user = auth()->user();

        return view('pages/profile-settings', [
            'assets' => $this->viteAssets(),
            'user' => $user,
        ]);
    }

    public function updateProfilePhoto(Request $request): RedirectResponse
    {
        \Log::info('updateProfilePhoto called', [
            'user_id' => auth()->id(),
            'method' => $request->method(),
            'uri' => $request->uri(),
            'has_file' => $request->hasFile('photo'),
        ]);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $user = auth()->user();
        $file = $request->file('photo');

        // Delete old photo if exists
        if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        // Store new photo: users/{user_id}/profile.{extension}
        $extension = $file->getClientOriginalExtension();
        $path = $file->storeAs('users/'.$user->id, 'profile.'.$extension, 'public');

        $user->update(['profile_photo_path' => $path]);

        return redirect()->back()->with('success', 'Foto profil berhasil diupdate.');
    }

    public function dashboard(): View
    {
        SessionSchedule::syncAutomaticStatuses();

        $user = auth()->user();
        $isAdminOps = in_array($user?->role, ['admin', 'panitia'], true);
        $isOfficial = in_array($user?->role, ['official', 'pendamping'], true);
        $isParticipant = $user?->role === 'peserta';
        $mustChangePassword = User::supportsMustChangePasswordFlag()
            && in_array($user?->role, ['official', 'panitia'], true)
            && (bool) $user?->must_change_password;
        $districtId = $isOfficial ? $user?->district_id : null;
        $participantProfile = $this->resolveParticipantProfile($user?->nomor_induk);

        $participantQuery = Participant::query()
            ->with(['category', 'district', 'scores'])
            ->when($districtId, fn ($query) => $query->where('district_id', $districtId));

        $leaders = $this->buildLeaders((clone $participantQuery)->get());

        $participants = (clone $participantQuery)->get();
        $needsAttentionParticipants = $participants
            ->filter(fn (Participant $participant): bool => in_array($participant->verification_status, ['submitted', 'rejected'], true))
            ->sortBy([
                fn (Participant $participant): int => $participant->verification_status === 'rejected' ? 0 : 1,
                fn (Participant $participant): string => $participant->name,
            ])
            ->take(6)
            ->values();

        $district = $districtId ? District::query()->find($districtId) : null;
        $mandateAlert = $isOfficial ? $this->buildOfficialMandateAlert($district) : null;
        $participantAlerts = $isOfficial
            ? $participants
                ->filter(fn (Participant $participant): bool => $participant->verification_status === 'rejected')
                ->map(function (Participant $participant): array {
                    return [
                        'participant_id' => $participant->id,
                        'name' => $participant->name,
                        'category' => $participant->category?->name ?? '-',
                        'message' => $participant->verification_notes ?: 'Data peserta perlu diperbaiki dan dikirim ulang.',
                        'href' => route('participants.show', $participant),
                    ];
                })
                ->take(5)
                ->values()
            : collect();
        $dashboardNotices = $isOfficial || $isAdminOps
            ? Announcement::query()
                ->visibleToRole((string) $user?->role)
                ->latest('published_at')
                ->latest('created_at')
                ->limit(3)
                ->get()
            : collect();
        $statusBreakdown = [
            'draft' => $participants->where('verification_status', 'draft')->count(),
            'submitted' => $participants->where('verification_status', 'submitted')->count(),
            'verified' => $participants->where('verification_status', 'verified')->count(),
            'rejected' => $participants->where('verification_status', 'rejected')->count(),
        ];

        $stats = $isOfficial
            ? [
                'participants' => $participants->count(),
                'categories' => $participants->pluck('competition_category_id')->filter()->unique()->count(),
                'today_sessions' => SessionSchedule::query()->whereDate('starts_at', today())->count(),
                'average_score' => number_format((float) ($participants->flatMap->scores->avg('score') ?? 0), 2),
            ]
            : ($isParticipant
                ? [
                    'participants' => $participantProfile ? 1 : 0,
                    'categories' => $participantProfile?->competition_category_id ? 1 : 0,
                    'today_sessions' => SessionSchedule::query()->whereDate('starts_at', today())->count(),
                    'average_score' => number_format((float) ($participantProfile?->scores?->avg('score') ?? 0), 2),
                ]
            : [
                'participants' => Participant::count(),
                'categories' => CompetitionCategory::count(),
                'today_sessions' => SessionSchedule::query()->whereDate('starts_at', today())->count(),
                'average_score' => number_format((float) (ScoreEntry::avg('score') ?? 0), 2),
            ]);

        $nextSchedule = SessionSchedule::query()
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->first();
        $verificationSummary = $this->verificationSummaryPayload();
        $verificationDistrictCounts = $this->verificationDistrictCounts();

        $branchRecap = $isAdminOps
            ? CompetitionCategory::query()
                ->orderBy('sort_order')
                ->orderBy('branch')
                ->get()
                ->groupBy('branch')
                ->map(function ($categories, string $branch): array {
                    $participants = Participant::query()
                        ->with('scores')
                        ->whereIn('competition_category_id', $categories->pluck('id'))
                        ->where('verification_status', 'verified')
                        ->get();

                    $scores = $participants->flatMap->scores;

                    return [
                        'branch' => $branch,
                        'category_total' => $categories->count(),
                        'participant_total' => $participants->count(),
                        'score_entries' => $scores->count(),
                        'average_score' => number_format((float) ($scores->avg('score') ?? 0), 2),
                    ];
                })
                ->sortByDesc(fn (array $row): float => (float) $row['average_score'])
                ->take(6)
                ->values()
            : collect();
        return view('pages.dashboard-v2', [
            'assets' => $this->viteAssets(),
            'rolePanel' => $this->rolePanel((string) $user?->role),
            'stats' => $stats,
            'leaders' => $leaders,
            'verificationSummary' => $verificationSummary,
            'verificationDistrictCounts' => $verificationDistrictCounts,
            'schedules' => SessionSchedule::query()
                ->orderBy('starts_at')
                ->limit(5)
                ->get(),
            'announcements' => Announcement::query()
                ->visibleToRole($user?->role)
                ->latest('published_at')
                ->limit(4)
                ->get(),
            'officialDashboard' => [
                'enabled' => $isOfficial,
                'district' => $district,
                'mandate_alert' => $mandateAlert,
                'participant_alerts' => $participantAlerts,
                'status_breakdown' => $statusBreakdown,
                'needs_attention' => $needsAttentionParticipants,
            ],
            'dashboardNotices' => $dashboardNotices,
            'mustChangePassword' => $mustChangePassword,
            'participantDashboard' => [
                'enabled' => $isParticipant,
                'profile' => $participantProfile,
                'latest_score' => number_format((float) ($participantProfile?->scores?->sortByDesc('submitted_at')->first()?->score ?? 0), 2),
                'average_score' => number_format((float) ($participantProfile?->scores?->avg('score') ?? 0), 2),
                'next_schedule' => $nextSchedule,
                'cv_url' => ($isParticipant && $participantProfile?->verification_status === 'verified')
                    ? route('participants.cv', $participantProfile)
                    : null,
            ],
            'adminDashboard' => [
                'enabled' => $isAdminOps,
                'branch_recap' => $branchRecap,
                'quick_exports' => [
                    ['label' => 'Rekap Penilaian', 'href' => route('results.recap')],
                    ['label' => 'Hasil Nilai Peserta', 'href' => route('results.index')],
                    ['label' => 'Kelola Konten', 'href' => route('admin.content')],
                    ['label' => 'Panitia Golongan', 'href' => route('committees.index')],
                    ['label' => 'Modul Penilaian', 'href' => route('scoring')],
                    ['label' => 'Pendaftaran Peserta', 'href' => route('participants.index')],
                ],
                'ops_stats' => [
                    'announcements' => Announcement::query()->visibleToRole(null)->count(),
                    'schedules' => SessionSchedule::query()->count(),
                    'verified_participants' => Participant::query()->where('verification_status', 'verified')->count(),
                    'score_entries' => ScoreEntry::query()->count(),
                ],
            ],
        ]);
    }

    public function updateDashboardPassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user, 403);

        $validated = $request->validate([
            'password' => ['required', 'string', PasswordRule::min(8), 'confirmed'],
        ]);

        $payload = [
            'password' => Hash::make((string) $validated['password']),
        ];

        if (User::supportsMustChangePasswordFlag()) {
            $payload['must_change_password'] = false;
        }

        $user->forceFill($payload)->save();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Password berhasil diperbarui. Silakan lanjutkan ke dashboard.');
    }

    public function syncDashboardUser(): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $nomorInduk = preg_replace('/\D+/', '', (string) ($user->nomor_induk ?? '')) ?: '';

        if ($nomorInduk === '') {
            return redirect()
                ->route('dashboard')
                ->with('warning', 'Sinkronisasi SILATAR belum bisa dijalankan karena akun ini belum memiliki NIP atau nomor induk.');
        }

        $employee = $this->fetchSilatarEmployee($nomorInduk);

        if (! $employee) {
            return redirect()
                ->route('dashboard')
                ->with('warning', 'Data user di SILATAR untuk NIP tersebut tidak ditemukan atau belum dapat diakses.');
        }

        $profilePhotoPath = $this->syncSilatarProfilePhoto($employee, $user->profile_photo_path);
        $resolvedEmail = $this->resolveDashboardUserEmail($user, $employee);
        $district = $this->resolveDistrictFromEmployee($employee);

        $payload = [
            'name' => (string) data_get($employee, 'name', $user->name),
            'email' => $resolvedEmail,
            'phone' => $this->normalizePhoneNumber((string) data_get($employee, 'telp', '')),
            'silatar_user_id' => ($silatarUserId = (int) data_get($employee, 'id', 0)) > 0 ? $silatarUserId : $user->silatar_user_id,
        ];

        if ($district) {
            $payload['district_id'] = $district->id;
        }

        if ($profilePhotoPath) {
            $payload['profile_photo_path'] = $profilePhotoPath;
        }

        $user->fill($payload)->save();

        $status = 'Data user berhasil disinkronkan dari SILATAR.';

        if (! $district) {
            $status .= ' Kecamatan e-MTQ dipertahankan karena dept_id SILATAR tidak cocok dengan silatar_id kecamatan.';
        }

        if ($resolvedEmail !== trim((string) data_get($employee, 'email', ''))) {
            $status .= ' Email akun dipertahankan karena email SILATAR kosong atau sedang dipakai akun lain.';
        }

        return redirect()
            ->route('dashboard')
            ->with('status', $status);
    }

    public function dashboardRealtimeSummary(Request $request): JsonResponse
    {
        SessionSchedule::syncAutomaticStatuses();

        $user = auth()->user();
        $isOfficial = in_array($user?->role, ['official', 'pendamping'], true);
        $districtId = $isOfficial ? $user?->district_id : null;
        $participantId = $request->integer('participant_id') ?: null;

        $participants = Participant::query()
            ->with(['category', 'district', 'scores'])
            ->when($districtId, fn ($query) => $query->where('district_id', $districtId))
            ->get();

        $leaders = $this->buildLeaders($participants);
        $participantSummary = null;
        $verificationSummary = $this->verificationSummaryPayload();
        $verificationDistrictCounts = $this->verificationDistrictCounts();

        if ($participantId) {
            $participant = $participants->firstWhere('id', $participantId);

            if ($participant) {
                $latestScore = $participant->scores->sortByDesc('submitted_at')->first();
                $participantSummary = [
                    'participant_id' => $participant->id,
                    'latest_score' => number_format((float) ($latestScore->score ?? 0), 2),
                    'average_score' => number_format((float) ($participant->scores->avg('score') ?? 0), 2),
                ];
            }
        }

        return response()->json([
            'leaders' => $leaders,
            'participant_summary' => $participantSummary,
            'verification_summary' => $verificationSummary,
            'verification_district_counts' => $verificationDistrictCounts,
            'registration_summary' => $verificationSummary,
            'registration_district_counts' => $verificationDistrictCounts,
        ]);
    }

    public function ongoingSchedules(): JsonResponse
    {
        return response()->json([
            'schedules' => ScheduleRealtimeNotifier::ongoingPayloads(5),
        ]);
    }

    public function scoring(): View
    {
        return view('pages.scoring-v2', [
            'assets' => $this->viteAssets(),
            'rolePanel' => $this->rolePanel((string) auth()->user()?->role),
            'participants' => Participant::query()
                ->with('category')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function categories(): View
    {
        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name')
            ->get()
            ->groupBy('branch');

        $districts = District::query()
            ->with(['users' => fn ($query) => $query->where('role', 'official')->orderBy('name')])
            ->orderBy('name')
            ->get();

        $districtBasedCategories = $categories
            ->flatten(1)
            ->filter(fn (CompetitionCategory $category): bool => $category->uses_district_quota || str_contains(mb_strtolower((string) $category->notes), 'kk'));

        $districtSlotTotal = $districtBasedCategories->sum(function (CompetitionCategory $category) use ($districts): int {
            $baseQuota = (int) $category->quota;

            return $districts->sum(fn (District $district): int => $baseQuota);
        });

        return view('pages/categories-v2', [
            'assets' => $this->viteAssets(),
            'rolePanel' => $this->rolePanel((string) auth()->user()?->role),
            'categoryGroups' => $categories,
            'districts' => $districts,
            'categoryStats' => [
                'branches' => $categories->count(),
                'categories' => $categories->flatten(1)->count(),
                'quota_total' => $categories->flatten(1)->sum('quota'),
                'district_count' => $districts->count(),
                'official_count' => User::query()->where('role', 'official')->whereNotNull('district_id')->count(),
                'district_slot_total' => $districtSlotTotal,
            ],
            ]);
    }

    public function updateCategoryLotSettings(Request $request, CompetitionCategory $category): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $data = $request->validate([
            'lot_code' => ['nullable', 'string', 'max:10'],
            'lot_number_min' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'lot_number_max' => ['nullable', 'integer', 'min:1', 'max:999999'],
        ]);

        $min = filled($data['lot_number_min'] ?? null) ? (int) $data['lot_number_min'] : null;
        $max = filled($data['lot_number_max'] ?? null) ? (int) $data['lot_number_max'] : null;

        if ($min !== null && $max !== null && $min > $max) {
            return back()
                ->withInput()
                ->withErrors(['lot_number_max' => 'Nomor maksimum harus lebih besar atau sama dengan nomor minimum.']);
        }

        $category->update([
            'lot_code' => strtoupper(trim((string) ($data['lot_code'] ?? ''))) ?: null,
            'lot_number_min' => $min,
            'lot_number_max' => $max,
        ]);

        ActivityLogger::log(
            'category.lot_settings.updated',
            (auth()->user()?->name ?? 'Admin').' memperbarui pengaturan nomor lot golongan '.$category->name.'.',
            $category,
            [
                'category_id' => $category->id,
                'category_label' => trim((string) $category->branch.' - '.(string) $category->name),
                'lot_code' => $category->lot_code,
                'lot_number_min' => $category->lot_number_min,
                'lot_number_max' => $category->lot_number_max,
            ]
        );

        return back()->with('status', 'Pengaturan nomor lot untuk golongan '.$category->name.' berhasil disimpan.');
    }

    public function leaderboard(Request $request): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $filters = $request->validate([
            'competition_category_id' => ['nullable', 'integer'],
            'show_juara_umum' => ['nullable', 'boolean'],
        ]);

        // ========== REGULAR MTQ CATEGORIES ==========
        // Exclude MFQ categories (id 24 and 25) - they are handled separately
        $categories = CompetitionCategory::query()
            ->whereNotIn('id', [24, 25])
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name')
            ->get();

        $participants = Participant::query()
            ->with(['category', 'district', 'scores' => fn ($query) => $query->orderByDesc('submitted_at')])
            ->where('verification_status', 'verified')
            ->get();

        $categoryGroups = $categories
            ->map(function (CompetitionCategory $category) use ($participants): ?array {
                $categoryParticipants = $participants->filter(fn (Participant $participant): bool => (int) $participant->competition_category_id === (int) $category->id);

                if ($categoryParticipants->isEmpty()) {
                    return null;
                }

                $rounds = [];

                // Count score entries per round for status display
                $penyisihanEntries = $categoryParticipants->flatMap->scores
                    ->filter(fn (ScoreEntry $entry): bool => (string) $entry->judging_round === 'Penyisihan')
                    ->count();
                $finalEntries = $categoryParticipants->flatMap->scores
                    ->filter(fn (ScoreEntry $entry): bool => (string) $entry->judging_round === 'Final')
                    ->count();

                // Build Penyisihan and Final rankings
                foreach (['Penyisihan', 'Final'] as $roundLabel) {
                    $roundLeaders = $this->buildRoundLeaders($categoryParticipants, $roundLabel);
                    $rounds[$roundLabel] = [
                        'label' => $roundLabel,
                        'leaders' => $roundLeaders,
                        'leader_count' => count($roundLeaders),
                        'top_score' => $roundLeaders[0]['average_score'] ?? '0.00',
                    ];
                }

                // Build "Semua" ranking (Final above Penyisihan-only)
                $semuaLeaders = $this->buildSemuaRanking($categoryParticipants);
                $rounds['Semua'] = [
                    'label' => 'Semua',
                    'leaders' => $semuaLeaders,
                    'leader_count' => count($semuaLeaders),
                    'top_score' => $semuaLeaders[0]['average_score'] ?? '0.00',
                ];

                // Overall leaders (top 3) from "Semua" ranking
                $overallLeaders = collect($semuaLeaders)
                    ->take(3)
                    ->values()
                    ->all();

                // Separate by gender
                $putraLeaders = collect($semuaLeaders)
                    ->filter(fn ($leader) => ($leader['gender'] ?? null) === 'male')
                    ->take(3)
                    ->values()
                    ->all();

                $putriLeaders = collect($semuaLeaders)
                    ->filter(fn ($leader) => ($leader['gender'] ?? null) === 'female')
                    ->take(3)
                    ->values()
                    ->all();

                return [
                    'category_id' => $category->id,
                    'branch' => $category->branch,
                    'category_name' => $category->name,
                    'participant_total' => $categoryParticipants->count(),
                    'score_entries' => $categoryParticipants->flatMap->scores->count(),
                    'score_entry_status' => [
                        'penyisihan' => $penyisihanEntries > 0,
                        'final' => $finalEntries > 0,
                        'penyisihan_count' => $penyisihanEntries,
                        'final_count' => $finalEntries,
                    ],
                    'overall_leaders' => $overallLeaders,
                    'putra_leaders' => $putraLeaders,
                    'putri_leaders' => $putriLeaders,
                    'rounds' => $rounds,
                ];
            })
            ->filter()
            ->values()
            ->sortByDesc(fn (array $category): int => $category['participant_total'])
            ->values();

        // Group by branch
        $branchGroups = $categoryGroups
            ->groupBy('branch')
            ->map(function ($branchCategories, string $branch): array {
                // Calculate score entry status from all categories in this branch
                $penyisihanCount = $branchCategories->sum(fn ($cat) => $cat['score_entry_status']['penyisihan_count'] ?? 0);
                $finalCount = $branchCategories->sum(fn ($cat) => $cat['score_entry_status']['final_count'] ?? 0);

                return [
                    'branch' => $branch,
                    'is_mfq' => false,
                    'category_total' => $branchCategories->count(),
                    'participant_total' => $branchCategories->sum('participant_total'),
                    'score_entries' => $branchCategories->sum('score_entries'),
                    'score_entry_status' => [
                        'penyisihan' => $penyisihanCount > 0,
                        'final' => $finalCount > 0,
                        'penyisihan_count' => $penyisihanCount,
                        'final_count' => $finalCount,
                    ],
                    'categories' => $branchCategories->values()->all(),
                ];
            })
            ->values();

        // ========== MFQ DATA ==========
        // Get MFQ categories specifically id 24 and 25 (Fahmil Qur'an Golongan Putra & Putri)
        $mfqCategories = CompetitionCategory::query()
            ->whereIn('id', [24, 25])
            ->orderBy('id')
            ->get();

        $mfqSessions = \App\Models\MfqSession::query()
            ->with(['category', 'results.participant.district'])
            ->where('status', 'completed')
            ->get();

        // Debug: Log MFQ sessions count
        \Log::info('MFQ Leaderboard Debug', [
            'mfq_categories_count' => $mfqCategories->count(),
            'mfq_categories' => $mfqCategories->pluck('id', 'name')->toArray(),
            'mfq_sessions_count' => $mfqSessions->count(),
            'mfq_sessions_by_category' => $mfqSessions->groupBy('competition_category_id')->map->count()->toArray(),
        ]);

        $mfqRankings = $this->buildMfqRankingsData($mfqSessions);

        // Count MFQ sessions per round for status display
        $mfqSessionCounts = [
            'penyisihan' => $mfqSessions->where('round', 'Penyisihan')->count(),
            'final' => $mfqSessions->where('round', 'Final')->count(),
        ];

        // Add "Fahmil Qur'an" as a special branch
        $mfqBranchName = $mfqCategories->first()?->branch ?? 'Fahmil Qur\'an';
        $mfqBranch = [
            'branch' => $mfqBranchName,
            'is_mfq' => true,
            'category_total' => $mfqCategories->count(),
            'participant_total' => $mfqRankings['participant_count'] ?? 0,
            'score_entries' => $mfqRankings['session_count'] ?? 0,
            'score_entry_status' => [
                'penyisihan' => $mfqSessionCounts['penyisihan'] > 0,
                'final' => $mfqSessionCounts['final'] > 0,
                'penyisihan_count' => $mfqSessionCounts['penyisihan'],
                'final_count' => $mfqSessionCounts['final'],
            ],
            'completed_sessions' => $mfqSessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'name' => $session->name,
                    'round' => $session->round,
                    'district_ids' => $session->district_ids ?? [],
                    'created_at' => $session->created_at?->format('d M Y, H:i'),
                ];
            })->values()->all(),
            'categories' => $mfqCategories->map(function ($cat) use ($mfqRankings, $mfqSessions) {
                $catSessions = $mfqSessions->where('competition_category_id', $cat->id);
                return [
                    'category_id' => $cat->id,
                    'category_name' => $cat->name,
                    'branch' => $cat->branch, // Use actual branch from DB
                    'is_mfq' => true,
                    'rankings' => $mfqRankings[$cat->id] ?? [],
                    'completed_sessions' => $catSessions->map(function ($session) {
                        return [
                            'id' => $session->id,
                            'name' => $session->name,
                            'round' => $session->round,
                            'district_ids' => $session->district_ids ?? [],
                            'created_at' => $session->created_at?->format('d M Y, H:i'),
                        ];
                    })->values()->all(),
                    'score_entry_status' => [
                        'penyisihan' => $catSessions->where('round', 'Penyisihan')->count() > 0,
                        'final' => $catSessions->where('round', 'Final')->count() > 0,
                        'penyisihan_count' => $catSessions->where('round', 'Penyisihan')->count(),
                        'final_count' => $catSessions->where('round', 'Final')->count(),
                    ],
                ];
            })->values()->all(),
        ];

        // ========== JUARA UMUM DATA ==========
        $juaraUmumData = $this->buildChampionData();

        // ========== SELECTED STATE ==========
        $firstBranch = $branchGroups->first();
        $mfqFirstCategory = $mfqBranch['categories'][0] ?? null;

        $selectedCategoryId = filled($filters['competition_category_id'] ?? null)
            ? (int) $filters['competition_category_id']
            : (int) ($firstBranch['categories'][0]['category_id'] ?? ($mfqFirstCategory['category_id'] ?? 0));

        $selectedCategoryData = $branchGroups->isNotEmpty()
            ? collect($branchGroups)->flatMap(fn ($bg) => $bg['categories'] ?? [])->first(fn ($cat) => $cat['category_id'] === $selectedCategoryId)
            : null;

        $selectedBranch = $selectedCategoryData['branch'] ?? ($firstBranch['branch'] ?? ($mfqBranch['branch'] ?? null));
        $showJuaraUmum = !empty($filters['show_juara_umum']);

        return view('pages.leaderboard-v2', [
            'assets' => $this->viteAssets(),
            'rolePanel' => $this->rolePanel((string) auth()->user()?->role),
            'categoryGroups' => $categoryGroups,
            'branchGroups' => $branchGroups,
            'mfqBranch' => $mfqBranch,
            'mfqRankings' => $mfqRankings,
            'juaraUmumData' => $juaraUmumData,
            'selectedCategoryId' => $selectedCategoryId,
            'selectedCategoryData' => $selectedCategoryData,
            'selectedBranch' => $selectedBranch,
            'showJuaraUmum' => $showJuaraUmum,
            'leaderboardStats' => [
                'categories' => $categoryGroups->count() + 1, // +1 for MFQ
                'verified_participants' => $participants->count(),
                'branches' => $categories->pluck('branch')->filter()->unique()->count() + 1, // +1 for MFQ
                'score_entries' => $participants->flatMap->scores->count(),
            ],
        ]);
    }

    /**
     * Build MFQ rankings data for all categories
     * Format: ranking per district (sesuai dengan scoring-mfq-new.php)
     */
    protected function buildMfqRankingsData($sessions): array
    {
        $result = [];
        $allParticipants = collect();
        $categoryIds = $sessions->pluck('competition_category_id')->unique();

        foreach ($categoryIds as $catId) {
            $catSessions = $sessions->where('competition_category_id', $catId);
            $catResults = $catSessions->map(fn ($s) => $s->results)->flatten();

            // Count participants
            foreach ($catResults as $r) {
                if ($r->participant) {
                    $allParticipants->push($r->participant->id);
                }
            }

            $catData = [
                'participant_count' => $catResults->pluck('participant_id')->unique()->count(),
                'session_count' => $catSessions->count(),
                'rounds' => [],
            ];

            // Build rounds per category - grouping by district
            foreach (['Penyisihan', 'Final'] as $round) {
                $roundSessions = $catSessions->where('round', $round);
                $roundResults = collect();

                foreach ($roundSessions as $session) {
                    $roundResults = $roundResults->merge($session->results);
                }

                // Group by district
                $rankingsByDistrict = $roundResults->groupBy(fn ($r) => $r->participant->district_id ?? 0)->map(function ($districtResults) use ($round) {
                    $district = $districtResults->first()->participant->district;
                    $districtName = $district?->name ?? 'Tanpa Kecamatan';

                    // Get all lot numbers in this district
                    $lotNumbers = $districtResults->map(fn ($r) => $r->participant->lot_number)->filter()->unique()->values();

                    // Get session names and points
                    $sessionNames = [];
                    $sessionPoints = [];
                    $sessionScores = [];

                    foreach ($districtResults->groupBy('mfq_session_id') as $sessionId => $sessionResults) {
                        $session = $sessionResults->first()->session;
                        $sessionNames[$sessionId] = $session?->name ?? 'Sesi ' . $sessionId;

                        $sessionResults->sortBy('rank');
                        foreach ($sessionResults as $rank) {
                            $point = match ($rank->rank) {
                                1 => 3,
                                2 => 2,
                                3 => 1,
                                default => 0,
                            };
                            $sessionPoints[$sessionId] = ($sessionPoints[$sessionId] ?? 0) + $point;
                        }

                        // Best score per session
                        $sessionScores[$sessionId] = $sessionResults->sortByDesc('total_score')->first()->total_score ?? 0;
                    }

                    return [
                        'district_id' => $district->id ?? 0,
                        'district_name' => $districtName,
                        'lot_numbers' => $lotNumbers->toArray(),
                        'session_names' => $sessionNames,
                        'session_points' => $sessionPoints,
                        'total_points' => array_sum($sessionPoints),
                        'session_scores' => $sessionScores,
                        'total_score' => array_sum($sessionScores),
                        'participant_count' => $districtResults->pluck('participant_id')->unique()->count(),
                    ];
                })->filter()->values();

                // Sort by points (for Poin Ranking mode) and by score (for Total Skor mode)
                $byPoints = $rankingsByDistrict->sortByDesc('total_points')->values()->all();
                $byScore = $rankingsByDistrict->sortByDesc('total_score')->values()->all();

                $catData['rounds'][$round] = [
                    'by_rank' => $byPoints,
                    'by_score' => $byScore,
                    'session_count' => $roundSessions->count(),
                ];
            }

            // Build "Semua" combined ranking (Final above Penyisihan)
            $semuaResults = [];
            $seenDistricts = [];

            // Add Final results first
            $finalResults = $catData['rounds']['Final']['by_score'] ?? [];
            foreach ($finalResults as $r) {
                $key = $r['district_id'];
                if (!isset($seenDistricts[$key])) {
                    $seenDistricts[$key] = true;
                    $r['current_round'] = 'Final';
                    $semuaResults[$key] = $r;
                }
            }

            // Add Penyisihan results (only if not in Final)
            $penyisihanResults = $catData['rounds']['Penyisihan']['by_score'] ?? [];
            foreach ($penyisihanResults as $r) {
                $key = $r['district_id'];
                if (!isset($seenDistricts[$key])) {
                    $seenDistricts[$key] = true;
                    $r['current_round'] = 'Penyisihan';
                    $semuaResults[$key] = $r;
                }
            }

            $semuaByPoints = collect($semuaResults)->sortByDesc('total_points')->values()->all();
            $semuaByScore = collect($semuaResults)->sortByDesc('total_score')->values()->all();

            $catData['rounds']['Semua'] = [
                'by_rank' => $semuaByPoints,
                'by_score' => $semuaByScore,
                'session_count' => ($catData['rounds']['Penyisihan']['session_count'] ?? 0) + ($catData['rounds']['Final']['session_count'] ?? 0),
            ];

            $result[$catId] = $catData;
        }

        $result['participant_count'] = $allParticipants->unique()->count();
        $result['session_count'] = $sessions->count();

        return $result;
    }

    /**
     * Build champion/juara umum data
     */
    protected function buildChampionData(): array
    {
        // Get all districts
        $allDistricts = \App\Models\District::count();
        $districtPoints = $this->calculateDistrictPoints();
        $participatingCount = $districtPoints->count();
        $totalPoints = $districtPoints->sum('total_points');

        $sortedDistricts = $districtPoints
            ->sortByDesc('total_points')
            ->values()
            ->all();

        $topThree = array_slice($sortedDistricts, 0, 3);

        return [
            'total_districts' => $allDistricts,
            'participating_districts' => $participatingCount,
            'total_points' => $totalPoints,
            'top_three' => $topThree,
            'rankings' => $sortedDistricts,
        ];
    }

    /**
     * Calculate district points for champion
     */
    protected function calculateDistrictPoints(): \Illuminate\Support\Collection
    {
        $points = collect();

        // Points system: Rank 1=9, 2=7, 3=5, 4=3, 5=2, 6=1
        $pointsMap = [1 => 9, 2 => 7, 3 => 5, 4 => 3, 5 => 2, 6 => 1];

        // Get regular MTQ points
        $categories = CompetitionCategory::query()
            ->where('branch', '!=', 'MFQ')
            ->get();

        foreach ($categories as $category) {
            $participants = \App\Models\Participant::query()
                ->with(['district', 'scores'])
                ->where('competition_category_id', $category->id)
                ->where('verification_status', 'verified')
                ->get();

            if ($participants->isEmpty()) {
                continue;
            }

            $ranking = $this->buildSemuaRanking($participants);

            foreach ($ranking as $index => $participant) {
                $rank = $index + 1;
                if ($rank > 6) {
                    break;
                }

                $districtId = $participant['district_id'] ?? null;
                if (!$districtId) {
                    continue;
                }

                $poin = $pointsMap[$rank] ?? 0;

                if (!$points->has($districtId)) {
                    $points->put($districtId, [
                        'district_id' => $districtId,
                        'district_name' => $participant['district'] ?? '-',
                        'total_points' => 0,
                        'breakdown' => [],
                    ]);
                }

                $existing = $points->get($districtId);
                $existing['total_points'] += $poin;
                $existing['breakdown'][] = [
                    'category' => $participant['category'] ?? '-',
                    'branch' => $participant['branch'] ?? '-',
                    'rank' => $rank,
                    'points' => $poin,
                    'participant_name' => $participant['name'] ?? '-',
                ];
                $points->put($districtId, $existing);
            }
        }

        // Get MFQ points
        $mfqSessions = \App\Models\MfqSession::query()
            ->with(['category', 'results.participant.district'])
            ->where('status', 'completed')
            ->get();

        foreach ($mfqSessions as $session) {
            $category = $session->category;
            if (!$category) {
                continue;
            }

            $results = $session->results->sortBy('rank');

            foreach ($results as $index => $r) {
                $rank = $r->rank ?? ($index + 1);
                if ($rank > 6) {
                    continue;
                }

                $participant = $r->participant;
                if (!$participant || !$participant->district_id) {
                    continue;
                }

                $districtId = $participant->district_id;
                $poin = $pointsMap[$rank] ?? 0;

                if (!$points->has($districtId)) {
                    $points->put($districtId, [
                        'district_id' => $districtId,
                        'district_name' => $participant->district?->name ?? '-',
                        'total_points' => 0,
                        'breakdown' => [],
                    ]);
                }

                $existing = $points->get($districtId);
                $existing['total_points'] += $poin;
                $existing['breakdown'][] = [
                    'category' => $category->name,
                    'branch' => $category->branch,
                    'rank' => $rank,
                    'points' => $poin,
                    'participant_name' => $participant->name,
                    'is_mfq' => true,
                ];
                $points->put($districtId, $existing);
            }
        }

        return $points;
    }

    public function maqra(): View
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name')
            ->get();

        $maqraPackages = MaqraPackage::query()
            ->with('category')
            ->orderBy('competition_category_id')
            ->orderBy('round_label')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $maqraGroups = $categories->map(function (CompetitionCategory $category) use ($maqraPackages): array {
            $packages = $maqraPackages->where('competition_category_id', $category->id)->values();

            return [
                'category' => $category,
                'packages' => $packages,
                'rounds' => $packages->groupBy('round_label'),
            ];
        })->filter(fn (array $group): bool => $group['packages']->isNotEmpty())->values();

        return view('pages.maqra-v2', [
            'assets' => $this->viteAssets(),
            'rolePanel' => $this->rolePanel((string) auth()->user()?->role),
            'navigation' => $this->consoleNavigation((string) auth()->user()?->role, 'maqra'),
            'categories' => $categories,
            'maqraGroups' => $maqraGroups,
            'maqraStats' => [
                'categories' => $maqraGroups->count(),
                'packages' => $maqraPackages->count(),
                'active' => $maqraPackages->where('is_active', true)->count(),
                'inactive' => $maqraPackages->where('is_active', false)->count(),
            ],
            'roundOptions' => ['Penyisihan', 'Final'],
        ]);
    }

    public function storeMaqra(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $data = $request->validate([
            'competition_category_id' => ['required', 'integer', 'exists:competition_categories,id'],
            'round_label' => ['required', 'in:Penyisihan,Final'],
            'maqra_code' => ['required', 'string', 'max:30'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = strtoupper(trim((string) $data['maqra_code']));
        $sortOrder = (int) ($data['sort_order'] ?? 0);

        $exists = MaqraPackage::query()
            ->where('competition_category_id', $data['competition_category_id'])
            ->where('round_label', $data['round_label'])
            ->where('maqra_code', $code)
            ->exists();

        abort_if($exists, 422, 'Kode maqra sudah digunakan pada kategori dan babak yang sama.');

        $maqraPackage = MaqraPackage::query()->create([
            'competition_category_id' => $data['competition_category_id'],
            'round_label' => $data['round_label'],
            'maqra_code' => $code,
            'title' => trim((string) $data['title']),
            'content' => trim((string) $data['content']),
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
            'sort_order' => $sortOrder,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        ActivityLogger::log(
            'maqra.package.created',
            (auth()->user()?->name ?? 'Admin').' menambahkan paket maqra '.$maqraPackage->maqra_code.'.',
            $maqraPackage,
            [
                'competition_category_id' => $maqraPackage->competition_category_id,
                'round_label' => $maqraPackage->round_label,
                'maqra_code' => $maqraPackage->maqra_code,
                'title' => $maqraPackage->title,
                'is_active' => (bool) $maqraPackage->is_active,
            ]
        );

        return back()->with('status', 'Paket maqra baru berhasil ditambahkan.');
    }

    public function maqraCsvTemplate()
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $headers = [
            'branch',
            'category_name',
            'round_label',
            'maqra_code',
            'title',
            'content',
            'notes',
            'sort_order',
            'is_active',
        ];

        $sampleRows = [
            ['Tilawah Anak', 'Anak A', 'Penyisihan', 'TLW-A-01', 'Al-Fatihah 1-7', 'QS Al-Fatihah ayat 1 sampai 7', 'Contoh import', '1', '1'],
            ['Tilawah Anak', 'Anak A', 'Final', 'TLW-A-02', 'Al-Baqarah 1-5', 'QS Al-Baqarah ayat 1 sampai 5', 'Contoh import', '2', '1'],
        ];

        $filename = 'template-import-maqra-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $sampleRows): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);

            foreach ($sampleRows as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importMaqraCsv(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $data = $request->validate([
            'maqra_csv' => ['required', 'file', 'max:2048', 'mimes:csv,txt'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        $result = $this->processMaqraCsvImport(
            $data['maqra_csv'],
            (bool) ($data['update_existing'] ?? false),
            true,
        );

        if (! empty($result['fatal_error'])) {
            return back()->with('warning', (string) $result['fatal_error']);
        }

        $message = 'Import CSV selesai: '.$result['created'].' paket baru berhasil ditambahkan';

        if ($result['updated'] > 0) {
            $message .= ', '.$result['updated'].' paket diperbarui';
        }

        if ($result['duplicate_count'] > 0) {
            $message .= ', '.$result['duplicate_count'].' duplikat dilewati';
        }

        $invalidCount = $result['invalid_count'];
        if ($invalidCount > 0) {
            $message .= ', '.$invalidCount.' baris invalid dilewati';
        }

        if ($result['errors'] !== []) {
            $message .= '. Rincian: '.implode(' | ', array_slice($result['errors'], 0, 5));
        } else {
            $message .= '.';
        }

        if ($result['update_existing']) {
            $message .= ' Mode update aktif.';
        }

        ActivityLogger::log(
            'maqra.package.imported',
            (auth()->user()?->name ?? 'Admin').' mengimport paket maqra dari CSV.',
            null,
            [
                'created' => $result['created'] ?? 0,
                'updated' => $result['updated'] ?? 0,
                'duplicate_count' => $result['duplicate_count'] ?? 0,
                'invalid_count' => $result['invalid_count'] ?? 0,
                'update_existing' => (bool) ($result['update_existing'] ?? false),
            ]
        );

        return back()->with($result['errors'] !== [] ? 'warning' : 'status', $message);
    }

    public function previewMaqraCsv(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $data = $request->validate([
            'maqra_csv' => ['required', 'file', 'max:2048', 'mimes:csv,txt'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        $result = $this->processMaqraCsvImport(
            $data['maqra_csv'],
            (bool) ($data['update_existing'] ?? false),
            false,
        );

        if (! empty($result['fatal_error'])) {
            return response()->json([
                'ok' => false,
                'message' => (string) $result['fatal_error'],
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Preview CSV siap.',
            'summary' => [
                'total' => $result['total_rows'],
                'created' => $result['created'],
                'updated' => $result['updated'],
                'duplicate_count' => $result['duplicate_count'],
                'invalid_count' => $result['invalid_count'],
                'update_existing' => $result['update_existing'],
            ],
            'errors' => $result['errors'],
            'rows' => $result['rows'],
        ]);
    }

    public function downloadMaqraCsvReport(Request $request)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $data = $request->validate([
            'maqra_csv' => ['required', 'file', 'max:2048', 'mimes:csv,txt'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        $result = $this->processMaqraCsvImport(
            $data['maqra_csv'],
            (bool) ($data['update_existing'] ?? false),
            false,
        );

        if (! empty($result['fatal_error'])) {
            return back()->with('warning', (string) $result['fatal_error']);
        }

        $filename = 'laporan-maqra-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($result): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['row_number', 'branch', 'category_name', 'round_label', 'maqra_code', 'title', 'status', 'note']);

            foreach ($result['rows'] as $row) {
                fputcsv($output, [
                    $row['row_number'] ?? '',
                    $row['branch'] ?? '',
                    $row['category_name'] ?? '',
                    $row['round_label'] ?? '',
                    $row['maqra_code'] ?? '',
                    $row['title'] ?? '',
                    $row['status'] ?? '',
                    $row['note'] ?? '',
                ]);
            }

            if ($result['errors'] !== []) {
                fputcsv($output, []);
                fputcsv($output, ['errors']);
                foreach ($result['errors'] as $error) {
                    fputcsv($output, [$error]);
                }
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function processMaqraCsvImport(UploadedFile $file, bool $updateExisting, bool $persist = true): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [
                'fatal_error' => 'File CSV tidak dapat dibaca.',
                'update_existing' => $updateExisting,
                'total_rows' => 0,
                'created' => 0,
                'updated' => 0,
                'duplicate_count' => 0,
                'invalid_count' => 0,
                'errors' => [],
                'rows' => [],
            ];
        }

        $header = fgetcsv($handle);

        if (! is_array($header)) {
            fclose($handle);

            return [
                'fatal_error' => 'File CSV kosong atau format header tidak valid.',
                'update_existing' => $updateExisting,
                'total_rows' => 0,
                'created' => 0,
                'updated' => 0,
                'duplicate_count' => 0,
                'invalid_count' => 0,
                'errors' => [],
                'rows' => [],
            ];
        }

        $normalize = static function (?string $value): string {
            $value = strtolower(trim((string) $value));
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
            $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?: '';

            return trim($value, '_');
        };

        $columns = array_map($normalize, $header);
        $requiredColumns = ['branch', 'category_name', 'round_label', 'maqra_code', 'title', 'content'];
        $missingColumns = array_values(array_diff($requiredColumns, $columns));

        if ($missingColumns !== []) {
            fclose($handle);

            return [
                'fatal_error' => 'Kolom CSV kurang lengkap: '.implode(', ', $missingColumns).'.',
                'update_existing' => $updateExisting,
                'total_rows' => 0,
                'created' => 0,
                'updated' => 0,
                'duplicate_count' => 0,
                'invalid_count' => 0,
                'errors' => [],
                'rows' => [],
            ];
        }

        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (CompetitionCategory $category): array {
                $key = mb_strtolower(trim((string) $category->branch).'|'.trim((string) $category->name));

                return [$key => $category];
            });

        $created = 0;
        $updated = 0;
        $duplicateCount = 0;
        $invalidCount = 0;
        $errors = [];
        $rows = [];
        $totalRows = 0;
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($row === [null] || $row === [] || count(array_filter($row, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $totalRows++;

            $record = [];
            foreach ($columns as $index => $column) {
                $record[$column] = trim((string) ($row[$index] ?? ''));
            }

            $branch = $record['branch'] ?? '';
            $categoryName = $record['category_name'] ?? '';
            $roundLabel = $record['round_label'] ?? '';
            $maqraCode = strtoupper(trim((string) ($record['maqra_code'] ?? '')));
            $title = trim((string) ($record['title'] ?? ''));
            $content = trim((string) ($record['content'] ?? ''));
            $notes = trim((string) ($record['notes'] ?? ''));
            $sortOrder = (int) ($record['sort_order'] !== '' ? $record['sort_order'] : 0);
            $isActive = in_array(strtolower((string) ($record['is_active'] ?? '1')), ['1', 'true', 'yes', 'ya'], true);

            $previewRow = [
                'row_number' => $rowNumber,
                'branch' => $branch,
                'category_name' => $categoryName,
                'round_label' => $roundLabel,
                'maqra_code' => $maqraCode,
                'title' => $title,
                'status' => 'valid',
                'note' => '',
            ];

            if ($branch === '' || $categoryName === '' || $roundLabel === '' || $maqraCode === '' || $title === '' || $content === '') {
                $invalidCount++;
                $message = 'Ada kolom wajib yang kosong.';
                $errors[] = 'Baris '.$rowNumber.' dilewati: '.$message;
                $previewRow['status'] = 'invalid';
                $previewRow['note'] = $message;
                $rows[] = $previewRow;
                continue;
            }

            $category = $categories->get(mb_strtolower(trim($branch).'|'.trim($categoryName)));

            if (! $category) {
                $invalidCount++;
                $message = 'Golongan "'.$branch.' - '.$categoryName.'" tidak ditemukan.';
                $errors[] = 'Baris '.$rowNumber.' dilewati: '.$message;
                $previewRow['status'] = 'invalid';
                $previewRow['note'] = $message;
                $rows[] = $previewRow;
                continue;
            }

            $roundLabel = in_array($roundLabel, ['Penyisihan', 'Final'], true) ? $roundLabel : '';
            if ($roundLabel === '') {
                $invalidCount++;
                $message = 'Babak harus "Penyisihan" atau "Final".';
                $errors[] = 'Baris '.$rowNumber.' dilewati: '.$message;
                $previewRow['status'] = 'invalid';
                $previewRow['note'] = $message;
                $rows[] = $previewRow;
                continue;
            }

            $existingPackage = MaqraPackage::query()
                ->where('competition_category_id', $category->id)
                ->where('round_label', $roundLabel)
                ->where('maqra_code', $maqraCode)
                ->first();

            if ($existingPackage) {
                if ($updateExisting) {
                    if ($persist) {
                        $existingPackage->update([
                            'title' => $title,
                            'content' => $content,
                            'notes' => $notes !== '' ? $notes : null,
                            'sort_order' => $sortOrder,
                            'is_active' => $isActive,
                        ]);
                    }

                    $updated++;
                    $previewRow['status'] = 'updated';
                    $previewRow['note'] = 'Data akan diperbarui.';
                    $rows[] = $previewRow;
                    continue;
                }

                $duplicateCount++;
                $previewRow['status'] = 'duplicate';
                $previewRow['note'] = 'Data sudah ada dan akan dilewati.';
                $rows[] = $previewRow;
                continue;
            }

            if ($persist) {
                MaqraPackage::query()->create([
                    'competition_category_id' => $category->id,
                    'round_label' => $roundLabel,
                    'maqra_code' => $maqraCode,
                    'title' => $title,
                    'content' => $content,
                    'notes' => $notes !== '' ? $notes : null,
                    'sort_order' => $sortOrder,
                    'is_active' => $isActive,
                ]);
            }

            $created++;
            $previewRow['status'] = 'created';
            $previewRow['note'] = 'Data baru akan ditambahkan.';
            $rows[] = $previewRow;
        }

        fclose($handle);

        return [
            'fatal_error' => null,
            'update_existing' => $updateExisting,
            'total_rows' => $totalRows,
            'created' => $created,
            'updated' => $updated,
            'duplicate_count' => $duplicateCount,
            'invalid_count' => $invalidCount,
            'errors' => $errors,
            'rows' => array_slice($rows, 0, 12),
        ];
    }

    public function updateMaqra(Request $request, MaqraPackage $maqraPackage): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $data = $request->validate([
            'competition_category_id' => ['required', 'integer', 'exists:competition_categories,id'],
            'round_label' => ['required', 'in:Penyisihan,Final'],
            'maqra_code' => ['required', 'string', 'max:30'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = strtoupper(trim((string) $data['maqra_code']));
        $sortOrder = (int) ($data['sort_order'] ?? 0);

        $exists = MaqraPackage::query()
            ->where('id', '!=', $maqraPackage->id)
            ->where('competition_category_id', $data['competition_category_id'])
            ->where('round_label', $data['round_label'])
            ->where('maqra_code', $code)
            ->exists();

        abort_if($exists, 422, 'Kode maqra sudah digunakan pada kategori dan babak yang sama.');

        $maqraPackage->update([
            'competition_category_id' => $data['competition_category_id'],
            'round_label' => $data['round_label'],
            'maqra_code' => $code,
            'title' => trim((string) $data['title']),
            'content' => trim((string) $data['content']),
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
            'sort_order' => $sortOrder,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        ActivityLogger::log(
            'maqra.package.updated',
            (auth()->user()?->name ?? 'Admin').' memperbarui paket maqra '.$maqraPackage->maqra_code.'.',
            $maqraPackage,
            [
                'competition_category_id' => $maqraPackage->competition_category_id,
                'round_label' => $maqraPackage->round_label,
                'maqra_code' => $maqraPackage->maqra_code,
                'title' => $maqraPackage->title,
                'is_active' => (bool) $maqraPackage->is_active,
            ]
        );

        return back()->with('status', 'Paket maqra '.$maqraPackage->maqra_code.' berhasil diperbarui.');
    }

    public function destroyMaqra(MaqraPackage $maqraPackage): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        abort_if($maqraPackage->draws()->exists(), 422, 'Paket yang sudah pernah diambil tidak dapat dihapus. Nonaktifkan saja jika tidak dipakai.');

        $code = $maqraPackage->maqra_code;
        $maqraPackage->delete();

        ActivityLogger::log(
            'maqra.package.deleted',
            (auth()->user()?->name ?? 'Admin').' menghapus paket maqra '.$code.'.',
            $maqraPackage,
            ['maqra_code' => $code]
        );

        return back()->with('status', 'Paket maqra '.$code.' berhasil dihapus.');
    }

    public function juknis(): View
    {
        return view('pages/juknis-v2', [
            'assets' => $this->viteAssets(),
            'rolePanel' => $this->rolePanel((string) auth()->user()?->role),
            'juknis' => $this->juknisConfig(),
        ]);
    }

    public function participantRegistrationGuideSnapshot(Request $request): View
    {
        $mode = in_array($request->query('mode', 'form'), ['form', 'review'], true)
            ? (string) $request->query('mode', 'form')
            : 'form';

        return view('pages/participant-guide-snapshot', [
            'mode' => $mode,
            'documentConfig' => $this->documentConfig(),
            'previewUser' => User::query()->where('role', 'admin')->orderBy('id')->first(),
            'districts' => District::query()->orderBy('name')->limit(8)->get(),
            'participants' => Participant::query()
                ->with(['category', 'district'])
                ->where('verification_status', 'verified')
                ->orderBy('name')
                ->limit(8)
                ->get(),
            'stats' => [
                'participants' => Participant::count(),
                'districts' => District::count(),
                'verified' => Participant::query()->where('verification_status', 'verified')->count(),
                'categories' => CompetitionCategory::count(),
            ],
            'categories' => CompetitionCategory::query()
                ->orderBy('sort_order')
                ->orderBy('branch')
                ->orderBy('name')
                ->limit(6)
                ->get(),
        ]);
    }

    public function participantRegistrationGuidePdf(): \Illuminate\Http\Response
    {
        $payload = [
            'documentConfig' => $this->documentConfig(),
            'guideTitle' => 'Buku Petunjuk Pendaftaran Peserta MTQ',
            'guideSubtitle' => 'Panduan step by step untuk official kecamatan saat mendaftarkan peserta di e-MTQ',
            'stepScreenshots' => [
                [
                    'title' => 'Langkah 1 - Pilih golongan peserta',
                    'caption' => 'Pilih cabang dan golongan yang sesuai sebelum membuka form pendaftaran.',
                    'src' => $this->publicImageDataUri('images/guides/participant-registration/form.png'),
                ],
                [
                    'title' => 'Langkah 2 - Tinjau data sebelum simpan',
                    'caption' => 'Periksa identitas, kategori, dan berkas yang wajib agar pendaftaran tidak perlu diulang.',
                    'src' => $this->publicImageDataUri('images/guides/participant-registration/review.png'),
                ],
            ],
            'checklistItems' => [
                'Peserta sudah diverifikasi secara administratif oleh kecamatan.',
                'Golongan dan cabang sudah dipilih sesuai usia dan ketentuan.',
                'Nomor induk peserta, alamat, dan kontak aktif sudah lengkap.',
                'File berkas wajib seperti KK, KTP, dan dokumen pendukung sudah siap.',
            ],
            'tipsItems' => [
                'Pastikan satu peserta hanya dipilih pada golongan yang benar agar tidak salah kategori.',
                'Gunakan pratinjau sebelum menyimpan untuk mencegah kesalahan input data.',
                'Jika ada berkas yang kurang, lengkapi dulu sebelum menekan tombol simpan.',
            ],
            'footerNote' => 'Dokumen ini dibuat untuk membantu official memahami alur pendaftaran peserta MTQ secara ringkas dan jelas.',
        ];

        $html = view('pdf.participant-registration-guide', $payload)->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="buku-petunjuk-pendaftaran-peserta-mtq.pdf"',
        ]);
    }

    protected function publicImageDataUri(string $relativePath): ?string
    {
        $path = public_path($relativePath);

        if (! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $contents = file_get_contents($path);

        return $contents !== false
            ? 'data:'.$mime.';base64,'.base64_encode($contents)
            : null;
    }

    protected function featuredBranchHighlight($items, string $branch): string
    {
        $branchName = mb_strtolower($branch);

        if (str_contains($branchName, 'fahmil') || str_contains($branchName, 'syarhil')) {
            return 'Golongan putra 3 peserta per kecamatan | Golongan putri 3 peserta per kecamatan';
        }

        return $items->pluck('name')->filter()->take(2)->implode(' | ');
    }

    public function rolePanel(string $role): array
    {
        return match ($role) {
            'admin' => [
                'headline' => 'Kontrol penuh sistem e-MTQ',
                'description' => 'Pantau performa lomba, jaga kualitas data, dan arahkan tim dari satu panel yang ringkas.',
                'accent' => 'from-cyan-400/20 to-blue-500/10',
                'focus' => [
                    'Pastikan data peserta dan kategori selalu valid.',
                    'Amati ritme penilaian dan pengumuman.',
                    'Gunakan leaderboard sebagai sinyal keputusan cepat.',
                ],
                'actions' => [
                    ['label' => 'Buka Juknis', 'href' => route('juknis.index')],
                    ['label' => 'Daftarkan Peserta', 'href' => route('participants.index')],
                    ['label' => 'Official Kecamatan', 'href' => route('officials.index')],
                    ['label' => 'Panitia Golongan', 'href' => route('committees.index')],
                    ['label' => 'Modul Kategori', 'href' => route('categories.index')],
                    ['label' => 'Edit Juknis', 'href' => route('admin.juknis')],
                    ['label' => 'Kelola Konten', 'href' => route('admin.content')],
                    ['label' => 'Galeri MTQ', 'href' => route('gallery.index')],
                    ['label' => 'Dokumen Resmi', 'href' => route('admin.documents')],
                    ['label' => 'Hasil Nilai', 'href' => route('results.index')],
                    ['label' => 'Buka Penilaian', 'href' => route('scoring')],
                    ['label' => 'Muat Ulang Dashboard', 'href' => route('dashboard')],
                ],
            ],
            'panitia' => [
                'headline' => 'Pusat operasional arena',
                'description' => 'Fokus pada input nilai, alur sesi, dan koordinasi lapangan agar lomba berjalan rapi.',
                'accent' => 'from-emerald-400/20 to-cyan-500/10',
                'focus' => [
                    'Input nilai tepat peserta dan kategori.',
                    'Pantau sesi yang sedang berjalan.',
                    'Sampaikan perubahan jadwal secepat mungkin.',
                ],
                'actions' => [
                    ['label' => 'Buka Juknis', 'href' => route('juknis.index')],
                    ['label' => 'Daftarkan Peserta', 'href' => route('participants.index')],
                    ['label' => 'Modul Kategori', 'href' => route('categories.index')],
                    ['label' => 'Kelola Konten', 'href' => route('admin.content')],
                    ['label' => 'Galeri MTQ', 'href' => route('gallery.index')],
                    ['label' => 'Dokumen Resmi', 'href' => route('admin.documents')],
                    ['label' => 'Hasil Nilai', 'href' => route('results.index')],
                    ['label' => 'Input Nilai', 'href' => route('scoring')],
                    ['label' => 'Lihat Ringkasan', 'href' => route('dashboard')],
                ],
            ],
            'official', 'pendamping' => [
                'headline' => 'Ringkasan untuk official kafilah',
                'description' => 'Pantau status verifikasi peserta kecamatan, cek tindak lanjut berkas, dan ikuti jadwal lomba dari satu panel yang lebih fokus.',
                'accent' => 'from-violet-400/20 to-sky-500/10',
                'focus' => [
                    'Pastikan berkas peserta yang ditolak segera diperbaiki.',
                    'Pantau jumlah peserta yang masih menunggu verifikasi.',
                    'Cek sesi tampil dan pengumuman resmi untuk kecamatan Anda.',
                ],
                'actions' => [
                    ['label' => 'Buka Juknis', 'href' => route('juknis.index')],
                    ['label' => 'Pendaftaran Peserta', 'href' => route('participants.index')],
                    ['label' => 'Hasil Nilai', 'href' => route('results.index')],
                    ['label' => 'Lihat Kategori', 'href' => route('categories.index')],
                    ['label' => 'Lihat Peserta Kecamatan', 'href' => route('participants.list')],
                    ['label' => 'Galeri MTQ', 'href' => route('gallery.index')],
                    ['label' => 'Kembali Beranda', 'href' => route('home')],
                ],
            ],
            default => [
                'headline' => 'Panel peserta yang lebih personal',
                'description' => 'Pantau status berkas, jadwal tampil, dan ringkasan nilai Anda sendiri dari satu dashboard yang lebih tenang dan jelas.',
                'accent' => 'from-amber-400/20 to-orange-500/10',
                'focus' => [
                    'Pastikan status verifikasi berkas selalu aman.',
                    'Cek jadwal dan venue tampil berikutnya.',
                    'Pantau ringkasan nilai dan pengumuman resmi panitia.',
                ],
                'actions' => [
                    ['label' => 'Buka Juknis', 'href' => route('juknis.index')],
                    ['label' => 'Lihat Data Saya', 'href' => route('dashboard')],
                    ['label' => 'Hasil Nilai', 'href' => route('results.index')],
                    ['label' => 'Lihat Kategori', 'href' => route('categories.index')],
                    ['label' => 'Segarkan Dashboard', 'href' => route('dashboard')],
                    ['label' => 'Kembali Beranda', 'href' => route('home')],
                ],
            ],
        };
    }

    public function consoleNavigation(string $role, string $active): array
    {
        $navigation = array_values(array_filter([
            $this->consoleNavigationLink('dashboard', 'Overview', route('dashboard'), 'home'),
            $this->consoleNavigationGroup('referensi', 'Referensi', 'book-open', array_values(array_filter([
                $this->consoleNavigationLink('juknis', 'Juknis MTQ', route('juknis.index'), 'book-open'),
                $this->consoleNavigationLink('categories', 'Kategori MTQ', route('categories.index'), 'book-open'),
                $this->consoleNavigationLink('schedule', 'Jadwal', route('dashboard').'#jadwal', 'calendar'),
                $this->consoleNavigationLink('announcements', 'Pengumuman', route('dashboard').'#pengumuman', 'bell'),
            ]))),
            $this->consoleNavigationGroup('peserta', 'Peserta', 'users', array_values(array_filter([
                $this->consoleNavigationLink('participants.index', 'Pendaftaran', route('participants.index'), 'id-card'),
                $this->consoleNavigationLink('participants.list', 'Data Peserta', route('participants.list'), 'users'),
                in_array($role, ['admin', 'panitia'], true)
                    ? $this->consoleNavigationLink('participants.lot.menu', 'Pengambilan Lot', route('participants.lot.menu'), 'sparkles')
                    : null,
                in_array($role, ['admin', 'official', 'pendamping', 'panitia'], true)
                    ? $this->consoleNavigationLink('participants.maqra.menu', 'Pengambilan Maqra', route('participants.maqra.menu'), 'sparkles')
                    : null,
                $role === 'admin'
                    ? $this->consoleNavigationLink('participants.trash', 'Arsip Peserta', route('participants.trash'), 'trash')
                    : null,
            ]))),
            $this->consoleNavigationGroup('penilaian', 'Penilaian', 'chart', array_values(array_filter([
                $this->consoleNavigationLink('results', 'Hasil Nilai', route('results.index'), 'chart'),
                in_array($role, ['admin', 'panitia'], true)
                    ? $this->consoleNavigationLink('leaderboard', 'Leaderboard', route('leaderboard.index'), 'trophy')
                    : null,
                in_array($role, ['admin', 'panitia'], true)
                    ? $this->consoleNavigationLink('scoring', 'Penilaian', route('scoring'), 'chart')
                    : null,
            ]))),
            $this->consoleNavigationGroup('administrasi', 'Administrasi', 'shield', array_values(array_filter([
                in_array($role, ['admin', 'panitia'], true)
                    ? $this->consoleNavigationLink('admin.content', 'Kelola Konten', route('admin.content'), 'bell')
                    : null,
                in_array($role, ['admin', 'panitia'], true)
                    ? $this->consoleNavigationLink('admin.documents', 'Dokumen Resmi', route('admin.documents'), 'book-open')
                    : null,
                $role === 'admin'
                    ? $this->consoleNavigationLink('admin.juknis', 'Edit Juknis', route('admin.juknis'), 'pencil')
                    : null,
                $role === 'admin'
                    ? $this->consoleNavigationLink('locations.index', 'Lokasi MTQ', route('locations.index'), 'map-pin')
                    : null,
                in_array($role, ['admin', 'panitia'], true)
                    ? $this->consoleNavigationLink('application.logs', 'Log Aplikasi', route('application.logs'), 'clock')
                    : null,
                $role === 'admin'
                    ? $this->consoleNavigationLink('maqra', 'Kelola Maqra', route('maqra.index'), 'book-open')
                    : null,
                $role === 'admin'
                    ? $this->consoleNavigationLink('admin.lot-auto-calculate', 'Auto-Calculate Lot', route('admin.lot-auto-calculate'), 'calculator')
                    : null,
                in_array($role, ['admin', 'panitia'], true)
                    ? $this->consoleNavigationLink('admin.export', 'Export Data', route('admin.export'), 'download')
                    : null,
                $role === 'admin'
                    ? $this->consoleNavigationLink('appearance.schedules', 'Penampilan Peserta', route('appearance.schedules'), 'sparkles')
                    : null,
                $role === 'admin'
                    ? $this->consoleNavigationLink('officials.index', 'Official Kecamatan', route('officials.index'), 'users')
                    : null,
                $role === 'admin'
                    ? $this->consoleNavigationLink('committees.index', 'Panitia Golongan', route('committees.index'), 'shield')
                    : null,
            ]))),
            in_array($role, ['admin', 'panitia', 'official', 'pendamping'], true)
                ? $this->consoleNavigationLink('gallery.index', 'Galeri MTQ', route('gallery.index'), 'image')
                : null,
            $this->consoleNavigationLink('profile.settings', 'Profil Saya', route('profile.settings'), 'settings'),
        ]));

        return $this->consoleNavigationApplyActive($navigation, $active);
    }

    protected function consoleNavigationLink(string $key, string $label, string $href, string $icon): array
    {
        return [
            'type' => 'link',
            'key' => $key,
            'label' => $label,
            'href' => $href,
            'icon' => $icon,
        ];
    }

    protected function consoleNavigationGroup(string $key, string $label, string $icon, array $children): ?array
    {
        $children = array_values(array_filter($children));

        if ($children === []) {
            return null;
        }

        return [
            'type' => 'group',
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'children' => $children,
        ];
    }

    protected function consoleNavigationApplyActive(array $navigation, string $active): array
    {
        return array_values(array_map(function (array $item) use ($active): array {
            if (($item['type'] ?? 'link') === 'group') {
                $item['children'] = $this->consoleNavigationApplyActive($item['children'] ?? [], $active);
                $item['active'] = collect($item['children'])->contains(fn (array $child): bool => (bool) ($child['active'] ?? false));

                return $item;
            }

            $item['active'] = $item['key'] === $active;

            return $item;
        }, $navigation));
    }

    public function documentConfig(): array
    {
        $config = config('documents');
        $setting = DocumentSetting::current();

        if (! $setting) {
            return $config;
        }

        return array_replace_recursive($config, [
            'organization_name' => $setting->organization_name,
            'event_title' => $setting->event_title,
            'event_location' => $setting->event_location,
            'signature_city' => $setting->signature_city,
            'officials' => $setting->officials ?? [],
        ]);
    }

    public function juknisConfig(): array
    {
        $config = config('juknis', []);
        $setting = JuknisSetting::current();

        if (! $setting) {
            return $config;
        }

        return array_replace_recursive($config, $setting->content ?? []);
    }

    protected function resolveParticipantProfile(?string $nomorInduk): ?Participant
    {
        if (! filled($nomorInduk)) {
            return null;
        }

        return Participant::query()
            ->with(['category', 'district', 'scores'])
            ->where('nik', $nomorInduk)
            ->first();
    }

    protected function buildLeaders($participants): array
    {
        $rows = collect($participants)
            ->map(function (Participant $participant): array {
                $latestScore = $participant->scores->sortByDesc('submitted_at')->first();
                $priorityValues = $this->participantPriorityValues($participant);
                $averageScore = (float) ($participant->scores->avg('score') ?? 0);
                $latestScoreValue = (float) ($latestScore->score ?? 0);

                return [
                    'participant_id' => $participant->id,
                    'name' => $participant->name,
                    'institution' => $participant->institution,
                    'category' => $participant->category?->name ?? '-',
                    'latest_score' => number_format($latestScoreValue, 2),
                    'average_score' => number_format($averageScore, 2),
                    'latest_score_value' => $latestScoreValue,
                    'average_score_value' => $averageScore,
                    'priority_values' => $priorityValues,
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

            $latestComparison = $right['latest_score_value'] <=> $left['latest_score_value'];
            if ($latestComparison !== 0) {
                return $latestComparison;
            }

            return strcmp((string) $left['name'], (string) $right['name']);
        });

        return collect($rows)
            ->take(6)
            ->map(function (array $row): array {
                unset($row['latest_score_value'], $row['average_score_value'], $row['priority_values']);

                return $row;
            })
            ->values()
            ->all();
    }

    protected function buildRoundLeaders($participants, string $roundLabel): array
    {
        $rows = collect($participants)
            ->map(function (Participant $participant) use ($roundLabel): ?array {
                $roundScores = $participant->scores->filter(fn (ScoreEntry $entry): bool => (string) $entry->judging_round === $roundLabel);

                if ($roundScores->isEmpty()) {
                    return null;
                }

                $latestScore = $roundScores->sortByDesc('submitted_at')->first();
                $priorityValues = $this->participantPriorityValuesFromScores($participant, $roundScores);
                $averageScore = (float) ($roundScores->avg('score') ?? 0);
                $latestScoreValue = (float) ($latestScore->score ?? 0);

                return [
                    'participant_id' => $participant->id,
                    'name' => $participant->name,
                    'institution' => $participant->institution,
                    'district' => $participant->district?->name ?? '-',
                    'category' => $participant->category?->name ?? '-',
                    'branch' => $participant->category?->branch ?? '-',
                    'gender' => $participant->gender ?? null,
                    'round_label' => $roundLabel,
                    'latest_score' => number_format($latestScoreValue, 2),
                    'average_score' => number_format($averageScore, 2),
                    'latest_score_value' => $latestScoreValue,
                    'average_score_value' => $averageScore,
                    'entry_count' => $roundScores->count(),
                    'priority_values' => $priorityValues,
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

            $latestComparison = $right['latest_score_value'] <=> $left['latest_score_value'];
            if ($latestComparison !== 0) {
                return $latestComparison;
            }

            return strcmp((string) $left['name'], (string) $right['name']);
        });

        return collect($rows)
            ->take(10)
            ->map(function (array $row): array {
                unset($row['latest_score_value'], $row['average_score_value'], $row['priority_values']);

                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * Build ranking for "Semua" filter - Final participants always above Penyisihan-only
     */
    protected function buildSemuaRanking($participants): array
    {
        // Get Penyisihan and Final scores for each participant
        $participantsWithScores = collect($participants)->map(function (Participant $participant): ?array {
            $penyisihanScores = $participant->scores->filter(fn (ScoreEntry $entry): bool => (string) $entry->judging_round === 'Penyisihan');
            $finalScores = $participant->scores->filter(fn (ScoreEntry $entry): bool => (string) $entry->judging_round === 'Final');

            $hasFinal = $finalScores->isNotEmpty();
            $hasPenyisihan = $penyisihanScores->isNotEmpty();

            if (!$hasFinal && !$hasPenyisihan) {
                return null;
            }

            // Get latest score based on what's available
            $latestScoreEntry = null;
            $currentRound = null;
            $scoreValue = 0;

            if ($hasFinal) {
                $latestScoreEntry = $finalScores->sortByDesc('submitted_at')->first();
                $currentRound = 'Final';
                $scoreValue = (float) ($latestScoreEntry->score ?? 0);
            } elseif ($hasPenyisihan) {
                $latestScoreEntry = $penyisihanScores->sortByDesc('submitted_at')->first();
                $currentRound = 'Penyisihan';
                $scoreValue = (float) ($latestScoreEntry->score ?? 0);
            }

            $priorityValues = $this->participantPriorityValues($participant);

            return [
                'participant_id' => $participant->id,
                'name' => $participant->name,
                'institution' => $participant->institution,
                'district' => $participant->district?->name ?? '-',
                'category' => $participant->category?->name ?? '-',
                'branch' => $participant->category?->branch ?? '-',
                'gender' => $participant->gender ?? null,
                'current_round' => $currentRound,
                'latest_score' => number_format($scoreValue, 2),
                'average_score' => number_format($scoreValue, 2),
                'latest_score_value' => $scoreValue,
                'average_score_value' => $scoreValue,
                'entry_count' => ($hasFinal ? $finalScores->count() : 0) + ($hasPenyisihan ? $penyisihanScores->count() : 0),
                'priority_values' => $priorityValues,
                'has_final' => $hasFinal,
                'has_penyisihan' => $hasPenyisihan,
            ];
        })->filter()->values()->all();

        // Sort: Final participants first (by score), then Penyisihan-only (by score)
        usort($participantsWithScores, function (array $left, array $right): int {
            // Final participants always above Penyisihan-only
            if ($left['has_final'] && !$right['has_final']) {
                return -1;
            }
            if (!$left['has_final'] && $right['has_final']) {
                return 1;
            }

            // Both have same status, sort by score
            $scoreComparison = $right['latest_score_value'] <=> $left['latest_score_value'];
            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }

            // Tie-breaker: priority values
            $maxPriorityCount = max(count($left['priority_values'] ?? []), count($right['priority_values'] ?? []));
            for ($index = 0; $index < $maxPriorityCount; $index++) {
                $leftValue = (float) ($left['priority_values'][$index] ?? 0);
                $rightValue = (float) ($right['priority_values'][$index] ?? 0);
                $priorityComparison = $rightValue <=> $leftValue;
                if ($priorityComparison !== 0) {
                    return $priorityComparison;
                }
            }

            return strcmp((string) $left['name'], (string) $right['name']);
        });

        return collect($participantsWithScores)
            ->take(10)
            ->map(function (array $row): array {
                unset(
                    $row['latest_score_value'],
                    $row['average_score_value'],
                    $row['priority_values'],
                    $row['has_final'],
                    $row['has_penyisihan']
                );
                return $row;
            })
            ->values()
            ->all();
    }

    protected function buildOfficialMandateAlert(?District $district): ?array
    {
        if (! $district || ! filled($district->mandate_document_path)) {
            return [
                'level' => 'warning',
                'title' => 'Surat mandat kecamatan belum diupload',
                'message' => 'Upload surat mandat kecamatan agar seluruh official dapat membuka dan mengelola pendaftaran peserta.',
                'status' => 'missing',
                'href' => route('participants.index'),
            ];
        }

        if ($district->mandate_status === 'rejected') {
            return [
                'level' => 'danger',
                'title' => 'Surat mandat kecamatan ditolak',
                'message' => $district->mandate_verification_notes ?: 'Perlu upload ulang atau perbaikan surat mandat kecamatan.',
                'status' => 'rejected',
                'href' => route('participants.index'),
            ];
        }

        if ($district->mandate_status === 'submitted') {
            return [
                'level' => 'info',
                'title' => 'Surat mandat kecamatan menunggu verifikasi',
                'message' => 'Mandat sudah diupload dan sedang diperiksa panitia.',
                'status' => 'submitted',
                'href' => route('participants.index'),
            ];
        }

        return null;
    }

    protected function verificationSummaryPayload(): array
    {
        $registration = (array) config('juknis.registration', []);
        $now = Carbon::now('Asia/Bangkok');
        $openAt = $this->parseIndonesianDate((string) ($registration['official_edit_start'] ?? ''), false);
        $closeAt = $this->parseIndonesianDate((string) ($registration['official_edit_end'] ?? ''), true);
        $totalRegistered = Participant::query()
            ->whereIn('verification_status', ['submitted', 'verified', 'rejected'])
            ->count();
        $totalVerified = Participant::query()
            ->where('verification_status', 'verified')
            ->count();

        $state = [
            'is_open' => false,
            'tone' => 'warning',
            'title' => 'Perbaikan berkas peserta oleh official',
            'label' => 'Belum ada jadwal',
            'message' => 'Jadwal perbaikan berkas belum tersedia di juknis.',
            'open_at' => $openAt?->toIso8601String(),
            'close_at' => $closeAt?->toIso8601String(),
            'open_at_label' => $openAt ? $openAt->translatedFormat('d F Y H:i').' WIB' : null,
            'close_at_label' => $closeAt ? $closeAt->translatedFormat('d F Y H:i').' WIB' : null,
            'total_registered' => $totalRegistered,
            'total_verified' => $totalVerified,
        ];

        if ($openAt && $now->lt($openAt)) {
            $state['title'] = 'Perbaikan berkas segera dibuka';
            $state['label'] = 'Menunggu dibuka';
            $state['message'] = 'Edit Peserta untuk official akan dibuka pada '.$openAt->translatedFormat('d F Y').'.';
            return $state;
        }

        if ($openAt && $closeAt && $now->betweenIncluded($openAt, $closeAt)) {
            $state['is_open'] = true;
            $state['tone'] = 'success';
            $state['title'] = 'Perbaikan berkas sedang berlangsung';
            $state['label'] = 'Sedang berlangsung';
            $state['message'] = 'Edit Peserta untuk official dibuka sampai '.$closeAt->translatedFormat('d F Y').'.';
            return $state;
        }

        if ($closeAt && $now->gt($closeAt)) {
            $state['title'] = 'Perbaikan berkas selesai';
            $state['label'] = 'Sudah ditutup';
            $state['message'] = 'Edit Peserta untuk official ditutup pada '.$closeAt->translatedFormat('d F Y').'.';
            return $state;
        }

        return $state;
    }

    protected function verificationDistrictCounts(): array
    {
        $registeredCounts = Participant::query()
            ->whereNotNull('district_id')
            ->whereIn('verification_status', ['submitted', 'verified', 'rejected'])
            ->selectRaw('district_id, COUNT(*) as total, SUM(CASE WHEN verification_status = \'verified\' THEN 1 ELSE 0 END) as verified, SUM(CASE WHEN verification_status = \'rejected\' THEN 1 ELSE 0 END) as rejected')
            ->groupBy('district_id')
            ->get()
            ->keyBy('district_id');

        return District::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(function (District $district) use ($registeredCounts): array {
                $row = $registeredCounts->get((int) $district->id);
                $total = (int) ($row->total ?? 0);
                $verified = (int) ($row->verified ?? 0);
                $rejected = (int) ($row->rejected ?? 0);

                return [
                    'district_id' => $district->id,
                    'district_name' => (string) $district->name,
                    'total' => $total,
                    'verified' => $verified,
                    'rejected' => $rejected,
                ];
            })
            ->sort(function (array $left, array $right): int {
                $totalComparison = $right['total'] <=> $left['total'];

                if ($totalComparison !== 0) {
                    return $totalComparison;
                }

                return strcmp($left['district_name'], $right['district_name']);
            })
            ->values()
            ->all();
    }

    protected function parseIndonesianDate(string $value, bool $endOfDay = false): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\\d{1,2})\\s+([[:alpha:]]+)\\s+(\\d{4})$/u', $value, $matches) !== 1) {
            try {
                return Carbon::parse($value, 'Asia/Bangkok');
            } catch (\Throwable) {
                return null;
            }
        }

        $monthMap = [
            'januari' => 1,
            'februari' => 2,
            'maret' => 3,
            'april' => 4,
            'mei' => 5,
            'juni' => 6,
            'juli' => 7,
            'agustus' => 8,
            'september' => 9,
            'oktober' => 10,
            'november' => 11,
            'desember' => 12,
        ];

        $month = $monthMap[mb_strtolower($matches[2])] ?? null;

        if (! $month) {
            return null;
        }

        $date = Carbon::create((int) $matches[3], $month, (int) $matches[1], 0, 0, 0, 'Asia/Bangkok');

        if ($endOfDay) {
            $date->endOfDay();
        }

        return $date;
    }

    public function viteAssets(): array
    {
        $manifestPath = public_path('build/manifest.json');

        if (! file_exists($manifestPath)) {
            return [
                'css' => [],
                'js' => [],
            ];
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        return [
            'css' => collect($manifest['resources/css/app.css']['css'] ?? [])
                ->merge($manifest['resources/js/app.js']['css'] ?? [])
                ->prepend($manifest['resources/css/app.css']['file'] ?? null)
                ->filter()
                ->unique()
                ->map(fn (string $file): string => asset('build/'.$file))
                ->values()
                ->all(),
            'js' => collect([$manifest['resources/js/app.js']['file'] ?? null])
                ->filter()
                ->map(fn (string $file): string => asset('build/'.$file))
                ->values()
                ->all(),
        ];
    }

    public function setCurrentParticipant(Request $request): \Illuminate\Http\JsonResponse
    {
        $participantId = (int) $request->get('participant_id', 0);
        $categoryId = (int) $request->get('category_id', 0);

        if (!$participantId || !$categoryId) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        $participant = \App\Models\Participant::with(['district'])->find($participantId);
        if (!$participant) {
            return response()->json(['error' => 'Participant not found'], 404);
        }

        // Update cache
        Cache::put(
            $this->currentParticipantCacheKey($categoryId),
            $participantId,
            now()->addHours(12)
        );

        // Dispatch event
        $participantPhotoUrl = null;
        if ($participant->document_photo) {
            $participantPhotoUrl = asset('storage/'.ltrim(str_replace('\\', '/', $participant->document_photo), '/'));
        }

        try {
            \App\Events\ParticipantSelected::dispatch(
                $participantId,
                $categoryId,
                $participant->name,
                $participant->district?->name,
                $participant->lot_number,
                $participantPhotoUrl
            );
        } catch (\Throwable $e) {
            \Log::warning('PageController ParticipantSelected broadcast skipped: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'participant' => [
                'id' => $participant->id,
                'name' => $participant->name,
                'district_name' => $participant->district?->name,
                'lot_number' => $participant->lot_number,
                'photo_url' => $participantPhotoUrl,
            ]
        ]);
    }

    public function apiCurrentParticipant(Request $request): \Illuminate\Http\JsonResponse
    {
        $categoryId = (int) $request->get('category_id', 0);
        if (!$categoryId) {
            return response()->json(['error' => 'Category ID required'], 400);
        }

        $participantId = (int) Cache::get($this->currentParticipantCacheKey($categoryId), 0);

        if (!$participantId) {
            return response()->json(['participant' => null, 'latest_scored' => null]);
        }

        $participant = Participant::with(['district', 'category'])
            ->whereKey($participantId)
            ->first();

        if (!$participant) {
            return response()->json(['participant' => null, 'latest_scored' => null]);
        }

        $photoUrl = $this->publicParticipantPhotoUrl($participant);

        // Get latest scored entry for this category
        $latestScored = null;
        $categoryParticipantIds = Participant::query()
            ->where('competition_category_id', $categoryId)
            ->where('verification_status', 'verified')
            ->pluck('id');

        $latestEntry = ScoreEntry::query()
            ->whereIn('participant_id', $categoryParticipantIds)
            ->whereNotNull('scores') // New aggregated format first
            ->orderByDesc('submitted_at')
            ->first();

        if (!$latestEntry) {
            // Fallback to old format
            $latestEntry = ScoreEntry::query()
                ->whereIn('participant_id', $categoryParticipantIds)
                ->orderByDesc('submitted_at')
                ->first();
        }

        if ($latestEntry) {
            $latestEntry->load('participant.district');
            $latestParticipant = $latestEntry->participant;
            $latestPhotoUrl = $latestParticipant ? $this->publicParticipantPhotoUrl($latestParticipant) : null;

            // Get scores based on format
            $scores = $latestEntry->scores;
            $averageScore = $latestEntry->average_score;

            // Fallback for old format
            if ($scores === null) {
                $scores = [
                    $latestEntry->judge_name => [
                        'score' => (float) $latestEntry->score,
                        'breakdown' => $latestEntry->score_breakdown ?? [],
                        'remarks' => $latestEntry->remarks,
                    ]
                ];
                $averageScore = (float) $latestEntry->score;
            }

            $latestScored = [
                'participant_id' => $latestEntry->participant_id,
                'participant' => $latestParticipant?->name,
                'lot_number' => $latestParticipant?->lot_number,
                'district_name' => $latestParticipant?->district?->name,
                'institution' => $latestParticipant?->institution,
                'judging_round' => $latestEntry->judging_round,
                'average_score' => $averageScore,
                'scores' => $scores,
                'submitted_at' => $latestEntry->submitted_at?->toIso8601String(),
                'photo_url' => $latestPhotoUrl,
            ];
        }

        return response()->json([
            'participant' => [
                'id' => $participant->id,
                'name' => $participant->name,
                'district_name' => $participant->district?->name,
                'lot_number' => $participant->lot_number,
                'institution' => $participant->institution,
                'category_branch' => $participant->category?->branch,
                'category_name' => $participant->category?->name,
                'photo_url' => $photoUrl,
            ],
            'latest_scored' => $latestScored,
        ]);
    }

    protected function currentParticipantCacheKey(int $categoryId): string
    {
        return 'mtq:bigscreen:category:'.$categoryId.':current_participant_id';
    }

    protected function publicParticipantPhotoUrl(Participant $participant): ?string
    {
        $path = trim((string) ($participant->document_photo ?? $participant->avatar ?? ''));

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/'.ltrim(str_replace('\\', '/', $path), '/'));
    }

    public function galleryDocumentation(): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia', 'official', 'pendamping'], true), 403);

        $user = auth()->user();
        $districtScoped = in_array($user?->role, ['official', 'pendamping'], true);
        $districtId = $districtScoped ? (int) ($user?->district_id ?? 0) : null;
        $galleryHasDistrictColumn = Schema::hasTable('activity_documentations') && Schema::hasColumn('activity_documentations', 'district_id');
        $galleryQuery = Schema::hasTable('activity_documentations')
            ? ActivityDocumentation::query()->with('uploader')
            : null;

        if ($galleryQuery) {
            if ($districtScoped && $galleryHasDistrictColumn) {
                $districtId > 0
                    ? $galleryQuery->where('district_id', $districtId)
                    : $galleryQuery->whereRaw('1 = 0');
            }
        }

        $galleryItems = $galleryQuery
            ? $galleryQuery
                ->orderByDesc('is_cover_homepage')
                ->orderBy('sort_order')
                ->latest('created_at')
                ->latest('id')
                ->paginate(9)
                ->withQueryString()
            : null;

        $galleryStatsQuery = Schema::hasTable('activity_documentations')
            ? ActivityDocumentation::query()
            : null;

        if ($galleryStatsQuery) {
            if ($districtScoped && $galleryHasDistrictColumn) {
                $districtId > 0
                    ? $galleryStatsQuery->where('district_id', $districtId)
                    : $galleryStatsQuery->whereRaw('1 = 0');
            }
        }

        return view('pages.gallery-v2', [
            'assets' => $this->viteAssets(),
            'rolePanel' => $this->rolePanel((string) $user?->role),
            'navigation' => $this->consoleNavigation((string) $user?->role, 'gallery.index'),
            'galleryItems' => $galleryItems,
            'galleryStats' => [
                'total' => $galleryStatsQuery ? $galleryStatsQuery->count() : 0,
                'active' => $galleryStatsQuery ? (clone $galleryStatsQuery)->where('is_active', true)->count() : 0,
                'cover' => $galleryStatsQuery ? (clone $galleryStatsQuery)->where('is_cover_homepage', true)->count() : 0,
                'contributors' => $galleryStatsQuery ? (clone $galleryStatsQuery)->whereNotNull('uploaded_by')->distinct('uploaded_by')->count('uploaded_by') : 0,
                'this_week' => $galleryStatsQuery ? (clone $galleryStatsQuery)->where('created_at', '>=', now()->subDays(7))->count() : 0,
            ],
        ]);
    }

    public function storeGalleryDocumentation(Request $request): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia', 'official', 'pendamping'], true), 403);
        abort_unless(Schema::hasTable('activity_documentations'), 503);
        $galleryHasDistrictColumn = Schema::hasColumn('activity_documentations', 'district_id');

        $validated = $request->validate([
            'caption' => ['required', 'string', 'max:255'],
            'photos' => ['required', 'array', 'min:1', 'max:8'],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
            'is_cover_homepage' => ['nullable', 'boolean'],
        ], [
            'caption.required' => 'Caption galeri wajib diisi.',
            'caption.string' => 'Caption galeri harus berupa teks.',
            'caption.max' => 'Caption galeri maksimal 255 karakter.',
            'photos.required' => 'Pilih minimal satu foto untuk diupload.',
            'photos.array' => 'Format unggahan foto tidak valid.',
            'photos.min' => 'Pilih minimal satu foto untuk diupload.',
            'photos.max' => 'Maksimal 8 foto per unggahan galeri.',
            'photos.*.file' => 'Setiap item upload harus berupa file foto yang valid.',
            'photos.*.image' => 'File galeri harus berupa gambar.',
            'photos.*.mimes' => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'photos.*.max' => 'Ukuran maksimal setiap foto adalah 5 MB.',
            'is_active.boolean' => 'Status aktif galeri tidak valid.',
            'is_cover_homepage.boolean' => 'Opsi slideshow homepage tidak valid.',
        ], [
            'caption' => 'caption',
            'photos' => 'foto',
            'photos.*' => 'foto',
            'is_active' => 'status aktif',
            'is_cover_homepage' => 'slideshow homepage',
        ]);

        if (($validated['is_cover_homepage'] ?? false) && $this->galleryCoverCount() >= 5) {
            throw ValidationException::withMessages([
                'is_cover_homepage' => 'Maksimal 5 foto bisa dipilih untuk slideshow homepage.',
            ]);
        }

        $nextCoverSortOrder = ($validated['is_cover_homepage'] ?? false)
            ? $this->nextGalleryCoverSortOrder()
            : 0;

        $storedPaths = [];
        $uploadedCount = 0;

        foreach ($request->file('photos', []) as $photo) {
            if (! $photo instanceof UploadedFile) {
                continue;
            }

            $storedImage = $this->storeGalleryImageVariants($photo);
            $storedPaths[] = $storedImage['image_path'];

            $documentation = ActivityDocumentation::query()->create([
                'caption' => trim((string) $validated['caption']),
                'image_path' => $storedImage['image_path'],
                'thumbnail_path' => $storedImage['thumbnail_path'],
                'uploaded_by' => auth()->id(),
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'is_cover_homepage' => (bool) ($validated['is_cover_homepage'] ?? false),
                'sort_order' => $nextCoverSortOrder,
            ]);

            if ($galleryHasDistrictColumn) {
                $documentation->forceFill([
                    'district_id' => auth()->user()?->district_id,
                ])->save();
            }
            $uploadedCount++;

            ActivityLogger::log(
                'gallery.photo.created',
                (auth()->user()?->name ?? 'Pengguna').' mengupload foto dokumentasi "'.$documentation->caption.'".',
                $documentation,
                [
                    'caption' => $documentation->caption,
                    'is_active' => (bool) $documentation->is_active,
                    'is_cover_homepage' => (bool) $documentation->is_cover_homepage,
                ]
            );
        }

        return redirect()
            ->route('gallery.index', ['page' => $request->integer('return_page', 1)])
            ->with('status', sprintf('%d foto dokumentasi berhasil diupload ke galeri MTQ.', $uploadedCount));
    }

    public function setGalleryMainCover(ActivityDocumentation $activityDocumentation): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless(Schema::hasTable('activity_documentations'), 503);

        if (! $activityDocumentation->is_cover_homepage && $this->galleryCoverCount() >= 5) {
            return redirect()
                ->back()
                ->with('warning', 'Maksimal 5 foto bisa dipilih untuk slideshow homepage.');
        }

        $activityDocumentation->forceFill([
            'is_active' => true,
            'is_cover_homepage' => true,
            'sort_order' => $activityDocumentation->is_cover_homepage
                ? (int) ($activityDocumentation->sort_order ?? 0)
                : $this->nextGalleryCoverSortOrder(),
        ])->save();

        return redirect()
            ->route('gallery.index', [
                'focus_gallery_id' => $activityDocumentation->id,
                'page' => request()->integer('return_page', 1),
            ])
            ->with('status', 'Foto sudah dimasukkan ke slideshow homepage.');
    }

    public function releaseGalleryMainCover(ActivityDocumentation $activityDocumentation): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless(Schema::hasTable('activity_documentations'), 503);

        if ($activityDocumentation->is_cover_homepage) {
            $activityDocumentation->forceFill([
                'is_cover_homepage' => false,
            ])->save();
        }

        return redirect()
            ->route('gallery.index', [
                'focus_gallery_id' => $activityDocumentation->id,
                'page' => request()->integer('return_page', 1),
            ])
            ->with('status', 'Foto sudah dikeluarkan dari slideshow homepage.');
    }

    public function toggleGalleryCover(ActivityDocumentation $activityDocumentation): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        if (! $activityDocumentation->is_cover_homepage && $this->galleryCoverCount() >= 5) {
            return redirect()
                ->back()
                ->with('warning', 'Maksimal 5 foto bisa dipilih untuk slideshow homepage.');
        }

        $activityDocumentation->forceFill([
            'is_cover_homepage' => ! $activityDocumentation->is_cover_homepage,
            'sort_order' => $activityDocumentation->is_cover_homepage ? 0 : $this->nextGalleryCoverSortOrder(),
        ])->save();

        return redirect()
            ->route('gallery.index', ['page' => request()->integer('return_page', 1)])
            ->with('status', $activityDocumentation->is_cover_homepage
                ? 'Foto sudah dimasukkan ke slideshow homepage.'
                : 'Foto sudah dikeluarkan dari slideshow homepage.');
    }

    public function bulkSetGalleryCovers(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless(Schema::hasTable('activity_documentations'), 503);

        $selectedIds = collect($request->input('gallery_cover_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            throw ValidationException::withMessages([
                'gallery_cover_ids' => 'Pilih minimal satu foto untuk slideshow homepage.',
            ]);
        }

        if ($selectedIds->count() > 5) {
            throw ValidationException::withMessages([
                'gallery_cover_ids' => 'Maksimal 5 foto bisa dipilih untuk slideshow homepage.',
            ]);
        }

        $galleryItems = ActivityDocumentation::query()
            ->whereIn('id', $selectedIds->all())
            ->get()
            ->keyBy('id');

        if ($galleryItems->count() !== $selectedIds->count()) {
            throw ValidationException::withMessages([
                'gallery_cover_ids' => 'Beberapa foto slideshow tidak ditemukan.',
            ]);
        }

        DB::transaction(function () use ($galleryItems, $selectedIds): void {
            ActivityDocumentation::query()->where('is_cover_homepage', true)->update([
                'is_cover_homepage' => false,
            ]);

            $sortOrder = 1;

            foreach ($selectedIds as $id) {
                $item = $galleryItems->get($id);

                if (! $item instanceof ActivityDocumentation) {
                    continue;
                }

                $item->forceFill([
                    'is_active' => true,
                    'is_cover_homepage' => true,
                    'sort_order' => $sortOrder++,
                ])->save();
            }
        });

        return redirect()
            ->route('gallery.index', ['page' => $request->integer('return_page', 1)])
            ->with('status', sprintf('%d foto berhasil dipilih untuk slideshow homepage.', $selectedIds->count()));
    }

    public function moveGalleryCover(ActivityDocumentation $activityDocumentation, string $direction): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        if (! $activityDocumentation->is_cover_homepage) {
            return redirect()
                ->back()
                ->with('warning', 'Foto ini belum dipilih untuk slideshow homepage.');
        }

        $coverItems = ActivityDocumentation::query()
            ->where('is_cover_homepage', true)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->values();

        $currentIndex = $coverItems->search(fn (ActivityDocumentation $item): bool => $item->id === $activityDocumentation->id);

        if ($currentIndex === false) {
            return redirect()
                ->back()
                ->with('warning', 'Foto slideshow tidak ditemukan.');
        }

        $swapIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! $coverItems->has($swapIndex)) {
            return redirect()
                ->back()
                ->with('warning', $direction === 'up'
                    ? 'Foto ini sudah berada di urutan paling atas.'
                    : 'Foto ini sudah berada di urutan paling bawah.');
        }

        $targetItem = $coverItems->get($swapIndex);
        $currentSortOrder = (int) ($activityDocumentation->sort_order ?? 0);
        $targetSortOrder = (int) ($targetItem?->sort_order ?? 0);

        DB::transaction(function () use ($activityDocumentation, $targetItem, $currentSortOrder, $targetSortOrder): void {
            $activityDocumentation->forceFill(['sort_order' => $targetSortOrder])->save();

            if ($targetItem) {
                $targetItem->forceFill(['sort_order' => $currentSortOrder])->save();
            }
        });

        return redirect()
            ->route('gallery.index', ['page' => request()->integer('return_page', 1)])
            ->with('status', $direction === 'up'
                ? 'Foto slideshow dipindah ke urutan sebelumnya.'
                : 'Foto slideshow dipindah ke urutan berikutnya.');
    }

    protected function galleryCoverCount(): int
    {
        if (! Schema::hasTable('activity_documentations')) {
            return 0;
        }

        return ActivityDocumentation::query()->where('is_cover_homepage', true)->count();
    }

    protected function nextGalleryCoverSortOrder(): int
    {
        if (! Schema::hasTable('activity_documentations')) {
            return 1;
        }

        $maxSortOrder = (int) ActivityDocumentation::query()
            ->where('is_cover_homepage', true)
            ->max('sort_order');

        return $maxSortOrder + 1;
    }

    protected function storeGalleryImageVariants(UploadedFile $photo): array
    {
        $sourcePath = $photo->getRealPath();

        if ($sourcePath === false || ! is_file($sourcePath)) {
            throw new \RuntimeException('File upload foto tidak valid.');
        }

        $binary = file_get_contents($sourcePath);

        if ($binary === false) {
            throw new \RuntimeException('File upload foto tidak bisa dibaca.');
        }

        $image = imagecreatefromstring($binary);

        if ($image === false) {
            throw new \RuntimeException('File upload foto tidak bisa diproses.');
        }

        $main = $this->encodeGalleryImage($image, 1600, 1600, 82);
        $thumbnail = $this->encodeGalleryImage($image, 640, 640, 76);

        imagedestroy($image);

        $directory = 'gallery/documentations/'.now()->format('Y/m');
        $mainPath = $directory.'/'.Str::random(40).'.'.$main['extension'];
        $thumbnailPath = $directory.'/thumbs/'.Str::random(40).'.'.$thumbnail['extension'];

        Storage::disk('public')->put($mainPath, $main['contents']);
        Storage::disk('public')->put($thumbnailPath, $thumbnail['contents']);

        return [
            'image_path' => $mainPath,
            'thumbnail_path' => $thumbnailPath,
        ];
    }

    protected function encodeGalleryImage($sourceImage, int $maxWidth, int $maxHeight, int $quality): array
    {
        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        $ratio = min($maxWidth / max(1, $width), $maxHeight / max(1, $height), 1);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $supportsWebp = function_exists('imagewebp');
        $extension = $supportsWebp ? 'webp' : 'jpg';

        if ($supportsWebp) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
        } else {
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);
        }

        imagecopyresampled($canvas, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();

        if ($supportsWebp) {
            imagewebp($canvas, null, $quality);
        } else {
            imagejpeg($canvas, null, $quality);
        }

        $contents = ob_get_clean();

        imagedestroy($canvas);

        if ($contents === false || $contents === '') {
            throw new \RuntimeException('Foto gagal dikompres.');
        }

        return [
            'extension' => $extension,
            'contents' => $contents,
        ];
    }

    public function destroyGalleryDocumentation(ActivityDocumentation $activityDocumentation): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia', 'official', 'pendamping'], true), 403);
        abort_unless(
            auth()->user()?->role === 'admin' || (int) $activityDocumentation->uploaded_by === (int) auth()->id(),
            403
        );

        foreach ([$activityDocumentation->image_path, $activityDocumentation->thumbnail_path] as $path) {
            if (filled($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $caption = $activityDocumentation->caption;
        $activityDocumentation->delete();

        ActivityLogger::log(
            'gallery.photo.deleted',
            (auth()->user()?->name ?? 'Pengguna').' menghapus dokumentasi galeri "'.$caption.'".',
            $activityDocumentation,
            ['caption' => $caption]
        );

        return redirect()
            ->route('gallery.index')
            ->with('status', 'Dokumentasi berhasil dihapus dari galeri.');
    }

    public function backfillGalleryThumbnails(): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);
        abort_unless(Schema::hasTable('activity_documentations'), 503);

        $items = ActivityDocumentation::query()
            ->where(function ($query): void {
                $query->whereNull('thumbnail_path')
                    ->orWhere('thumbnail_path', '');
            })
            ->orderBy('id')
            ->get();

        $updated = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $mainPath = trim((string) $item->image_path);

            if ($mainPath === '' || ! Storage::disk('public')->exists($mainPath)) {
                $skipped++;
                continue;
            }

            $thumbnailPath = $this->generateThumbnailFromStoredImage($mainPath);

            if (! $thumbnailPath) {
                $skipped++;
                continue;
            }

            $item->forceFill([
                'thumbnail_path' => $thumbnailPath,
            ])->save();
            $updated++;
        }

        return redirect()
            ->route('gallery.index')
            ->with('status', sprintf('Backfill thumbnail selesai. %d foto diperbarui, %d foto dilewati.', $updated, $skipped));
    }

    protected function generateThumbnailFromStoredImage(string $storedPath): ?string
    {
        if (! Storage::disk('public')->exists($storedPath)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($storedPath);
        $binary = @file_get_contents($absolutePath);

        if ($binary === false) {
            return null;
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return null;
        }

        $thumbnail = $this->encodeGalleryImage($image, 640, 640, 76);
        imagedestroy($image);

        $directory = trim(dirname($storedPath), '.');
        $thumbnailDirectory = ($directory !== '' ? $directory : 'gallery/documentations').'/thumbs';
        $thumbnailPath = $thumbnailDirectory.'/'.Str::random(40).'.'.$thumbnail['extension'];

        Storage::disk('public')->put($thumbnailPath, $thumbnail['contents']);

        return $thumbnailPath;
    }

    protected function publicActivityDocumentationUrl(ActivityDocumentation $item): ?string
    {
        return $item->imageUrl();
    }

    public function categoryLotPrefix(CompetitionCategory $category): string
    {
        $configured = strtoupper(trim((string) ($category->lot_code ?? '')));

        if ($configured !== '') {
            return $configured;
        }

        $source = trim((string) ($category->branch ?? '').' '.(string) ($category->name ?? ''));
        $tokens = preg_split('/[^A-Z0-9]+/u', Str::upper($source), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $stopWords = ['AL', 'AN', 'BACA', 'CABANG', 'DAN', 'DARI', 'DENGAN', 'GOL', 'GOLONGAN', 'LOT', 'MTQ', 'NOMOR', 'PESERTA', 'QUR', 'QURAN', 'SENI'];
        $significant = array_values(array_filter($tokens, fn (string $token): bool => ! in_array($token, $stopWords, true)));

        if (count($significant) >= 2) {
            $prefix = mb_substr($significant[0], 0, 1).mb_substr($significant[1], 0, 1);
        } elseif (count($significant) === 1) {
            $prefix = mb_substr($significant[0], 0, min(4, mb_strlen($significant[0])));
        } else {
            $prefix = mb_substr(preg_replace('/[^A-Z0-9]/u', '', Str::upper((string) ($category->slug ?? 'MTQ'))) ?: 'MTQ', 0, 4);
        }

        return $prefix !== '' ? $prefix : 'MTQ';
    }

    public function categoryLotRange(CompetitionCategory $category): array
    {
        $min = (int) ($category->lot_number_min ?? 1);
        $max = (int) ($category->lot_number_max ?? 99);

        if ($min < 1) {
            $min = 1;
        }

        if ($max < $min) {
            [$min, $max] = [$max, $min];
        }

        if ($max < $min) {
            $max = $min;
        }

        return [$min, $max];
    }

    public function categoryLotRangeLabel(CompetitionCategory $category): string
    {
        [$min, $max] = $this->categoryLotRange($category);

        return sprintf('%02d - %02d', $min, $max);
    }

    public function categoryLotGroupSize(CompetitionCategory $category, ?string $gender = null): int
    {
        $slug = (string) ($category->slug ?? '');

        // Special case: old combined "Khatib dan Muadzin" category uses per-participant lot (soft-locked)
        if ($slug === 'khutbah-jumat-dan-adzan-khatib-dan-muadzin') {
            return 1;
        }

        // Special case: Khatib and Adzan categories use per-participant lot (1:1)
        if (in_array($slug, ['khutbah-jumat-dan-adzan-khatib', 'khutbah-jumat-dan-adzan-adzan'])) {
            return 1;
        }

        // Prioritas 1: cek kolom lot_group_type
        if (filled($category->lot_group_type)) {
            return match ($category->lot_group_type) {
                'triple' => 3,
                'pair' => 2,
                default => 1,
            };
        }

        // Fallback: string matching (untuk data lama)
        $branch = mb_strtolower((string) ($category->branch ?? ''));

        if (str_contains($branch, 'fahmil') || str_contains($branch, 'syarhil')) {
            return 3;
        }

        return 1;
    }

    public function categoryLotRuleLabel(CompetitionCategory $category, ?string $gender = null): string
    {
        $groupSize = $this->categoryLotGroupSize($category, $gender);
        $normalizedGender = mb_strtolower((string) $gender);

        if ($groupSize > 1) {
            return '1 kecamatan = 1 nomor lot';
        }

        return '1 peserta = 1 nomor lot';
    }

    public function categoryUsesMaqra(CompetitionCategory $category): bool
    {
        // Prioritas 1: cek kolom maqra_system_type
        if (filled($category->maqra_system_type)) {
            // Khutbah Jumat dan Adzan TIDAK menggunakan maqra
            if (in_array($category->maqra_system_type, ['khatib', 'muadzin'])) {
                return false;
            }

            return true;
        }

        // Fallback: string matching (untuk data lama)
        // Approach B: Check using model helper methods
        if ($category->isKhatibCategory() || $category->isAdzanCategory()) {
            return false;
        }

        $branch = mb_strtolower((string) ($category->branch ?? ''));
        $name = mb_strtolower((string) ($category->name ?? ''));
        $slug = mb_strtolower((string) ($category->slug ?? ''));
        $haystack = trim($branch.' '.$name.' '.$slug);

        // Old combined category (pair) also doesn't use maqra
        if (str_contains($name, 'khatib') && str_contains($name, 'muadzin')) {
            return false;
        }

        return str_contains($haystack, 'seni baca al qur')
            || str_contains($haystack, 'hafalan al qur')
            || str_contains($haystack, 'tafsir al qur')
            || str_contains($haystack, 'fahmil qur')
            || str_contains($haystack, 'syarhil qur');
    }

    public function categoryMaqraSystemLabel(CompetitionCategory $category): ?string
    {
        // Prioritas 1: cek kolom maqra_system_type
        if (filled($category->maqra_system_type)) {
            return ucfirst($category->maqra_system_type);
        }

        // Fallback: string matching (untuk data lama)
        $branch = mb_strtolower((string) ($category->branch ?? ''));

        return match (true) {
            str_contains($branch, 'seni baca al qur') => 'Tilawah',
            str_contains($branch, 'hafalan al qur') => 'Tahfizh',
            str_contains($branch, 'tafsir al qur') => 'Tafsir',
            str_contains($branch, 'fahmil qur') => 'Fahmil',
            str_contains($branch, 'syarhil qur') => 'Syarhil',
            str_contains($branch, 'khatib') => 'Khatib',
            str_contains($branch, 'muadzin') => 'Muadzin',
            default => null,
        };
    }

    public function categoryMaqraCodePrefix(CompetitionCategory $category): string
    {
        // Prioritas 1: cek kolom maqra_system_type
        if (filled($category->maqra_system_type)) {
            return match ($category->maqra_system_type) {
                'tilawah' => 'TLW',
                'tahfizh' => 'HFZ',
                'tafsir' => 'TFS',
                'fahmil' => 'FHL',
                'syarhil' => 'SYR',
                'khatib' => 'KTB',
                'muadzin' => 'MDZ',
                default => 'MQR',
            };
        }

        // Fallback: dari label
        return match ($this->categoryMaqraSystemLabel($category)) {
            'Tilawah' => 'TLW',
            'Tahfizh' => 'HFZ',
            'Tafsir' => 'TFS',
            'Fahmil' => 'FHL',
            'Syarhil' => 'SYR',
            'Khatib' => 'KTB',
            'Muadzin' => 'MDZ',
            default => 'MQR',
        };
    }

    public function categoryMaqraUsesDistrictSharing(CompetitionCategory $category): bool
    {
        // Prioritas 1: cek kolom lot_group_type
        if (filled($category->lot_group_type)) {
            return $category->lot_group_type !== 'single';
        }

        // Fallback: dari maqra_system_type
        $systemLabel = $this->categoryMaqraSystemLabel($category);

        return in_array($systemLabel, ['Fahmil', 'Syarhil', 'Khatib', 'Muadzin']);
    }

    public function categoryMaqraRuleLabel(CompetitionCategory $category): string
    {
        return $this->categoryMaqraUsesDistrictSharing($category)
            ? '1 kecamatan = 1 maqra'
            : '1 peserta = 1 maqra';
    }

    public function calculateRecommendedLotRange(CompetitionCategory $category): array
    {
        $groupSize = $this->categoryLotGroupSize($category);

        $putraCount = (int) Participant::query()
            ->where('competition_category_id', $category->id)
            ->where('gender', 'putra')
            ->where('verification_status', 'verified')
            ->count();

        $putriCount = (int) Participant::query()
            ->where('competition_category_id', $category->id)
            ->where('gender', 'putri')
            ->where('verification_status', 'verified')
            ->count();

        // For shared categories (group size > 1), divide by group size
        $putraSharedCount = $groupSize > 1 ? (int) ceil($putraCount / $groupSize) : $putraCount;
        $putriSharedCount = $groupSize > 1 ? (int) ceil($putriCount / $groupSize) : $putriCount;

        // Get the configured lot range for determining "unused" numbers
        [$configuredMin, $configuredMax] = $this->categoryLotRange($category);

        // Calculate pool max numbers
        $putraPoolMax = $putraSharedCount * 2;  // Last even number
        $putriPoolMax = $putriSharedCount * 2 - 1;  // Last odd number

        // Calculate unused numbers (beyond pool but within configured range)
        // Putra: even numbers from (pool_max + 2) to configuredMax
        $putraUnused = [];
        for ($n = $putraPoolMax + 2; $n <= $configuredMax; $n += 2) {
            $putraUnused[] = $n;
        }

        // Putri: odd numbers from (pool_max + 2) to configuredMax
        $putriUnused = [];
        for ($n = $putriPoolMax + 2; $n <= $configuredMax; $n += 2) {
            $putriUnused[] = $n;
        }

        return [
            'category_id' => $category->id,
            'category_label' => trim((string) ($category->branch ?? '').' - '.(string) ($category->name ?? '')),
            'group_size' => $groupSize,
            'is_shared' => $groupSize > 1,
            'configured_min' => $configuredMin,
            'configured_max' => $configuredMax,
            'putra' => [
                'participant_count' => $putraCount,
                'unique_lots_needed' => $putraSharedCount,
                'pool_min' => 2,
                'pool_max' => $putraPoolMax,
                'pool_numbers' => range(2, $putraPoolMax, 2),
                'unused_numbers' => $putraUnused,
            ],
            'putri' => [
                'participant_count' => $putriCount,
                'unique_lots_needed' => $putriSharedCount,
                'pool_min' => 1,
                'pool_max' => $putriPoolMax,
                'pool_numbers' => range(1, $putriPoolMax, 2),
                'unused_numbers' => $putriUnused,
            ],
        ];
    }

    public function lotAutoCalculate(): View
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name')
            ->get();

        return view('pages/admin-lot-auto-calculate-v2', [
            'assets' => $this->viteAssets(),
            'rolePanel' => $this->rolePanel((string) auth()->user()?->role),
            'navigation' => $this->consoleNavigation((string) auth()->user()?->role, 'admin.lot-auto-calculate'),
            'categories' => $categories,
        ]);
    }

    public function previewLotAutoCalculate(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $filters = $request->validate([
            'competition_category_id' => ['nullable', 'integer'],
        ]);

        $query = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name');

        if (filled($filters['competition_category_id'] ?? null)) {
            $query->whereKey((int) $filters['competition_category_id']);
        }

        $categories = $query->get();
        $calculations = $categories->map(fn (CompetitionCategory $category) => $this->calculateRecommendedLotRange($category))->values();

        return response()->json([
            'calculations' => $calculations,
        ]);
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

    public function rankingPriorityContext(?int $categoryId, ?string $branch = null, bool $preferSpecific = true): array
    {
        $labels = $this->priorityLabelsForCategory($categoryId, $branch);

        if ($labels !== [] && $preferSpecific) {
            return [
                'labels' => $labels,
                'specific' => true,
                'text' => 'Tie-break: '.implode(' > ', $labels),
            ];
        }

        return [
            'labels' => $labels,
            'specific' => false,
            'text' => 'Tie-break mengikuti prioritas poin pada setting penilaian tiap golongan.',
        ];
    }

    public function priorityLabelsForCategory(?int $categoryId, ?string $branch = null): array
    {
        $setting = ScoringSetting::forCategory($categoryId);
        $criteria = $setting?->scoring_points
            ?? config('scoring.criteria.'.($branch ?? ''))
            ?? config('scoring.criteria.default', []);
        $priorityKeys = $this->priorityKeysForCategory($categoryId, $branch);

        return collect($priorityKeys)
            ->map(fn (string $key): string => (string) ($criteria[$key] ?? ucwords(str_replace('_', ' ', $key))))
            ->filter(fn (string $label): bool => $label !== '')
            ->values()
            ->all();
    }

    protected function fetchSilatarEmployee(string $nip): ?array
    {
        $response = $this->safeGet(self::SILATAR_NIP_API.$nip);

        if (! $response || ! $response->successful()) {
            return null;
        }

        $user = $response->json('user');

        return is_array($user) ? $user : null;
    }

    protected function resolveDashboardUserEmail(User $user, array $employee): string
    {
        $email = trim((string) data_get($employee, 'email', ''));

        if ($email === '') {
            return (string) $user->email;
        }

        $owner = User::query()
            ->where('email', $email)
            ->whereKeyNot($user->id)
            ->first();

        return $owner ? (string) $user->email : $email;
    }

    protected function resolveDistrictFromEmployee(array $employee): ?District
    {
        $employeeDeptId = (int) data_get($employee, 'dept_id', 0);

        if ($employeeDeptId <= 0) {
            return null;
        }

        return District::query()
            ->where('silatar_id', $employeeDeptId)
            ->first();
    }

    protected function syncSilatarProfilePhoto(array $employee, ?string $existingPath): ?string
    {
        $avatarUrl = trim((string) data_get($employee, 'avatar', ''));

        if ($avatarUrl === '') {
            return $existingPath;
        }

        $response = $this->safeGet($avatarUrl);

        if (! $response || ! $response->successful()) {
            return $existingPath;
        }

        $extension = pathinfo(parse_url($avatarUrl, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg';
        $nomorInduk = (string) data_get($employee, 'nomor_induk', Str::random(12));
        $path = 'users/profile-photos/user-'.$nomorInduk.'.'.$extension;

        Storage::disk('public')->put($path, $response->body());

        return $path;
    }

    protected function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', trim($phone)) ?: '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }

        if (! str_starts_with($digits, '0')) {
            return '0'.$digits;
        }

        return $digits;
    }

    protected function safeGet(string $url): ?Response
    {
        try {
            return Http::acceptJson()->timeout(20)->retry(2, 500)->get($url);
        } catch (ConnectionException|RequestException) {
            try {
                return Http::acceptJson()->withoutVerifying()->timeout(20)->retry(2, 500)->get($url);
            } catch (ConnectionException|RequestException) {
                return null;
            }
        }
    }
}
