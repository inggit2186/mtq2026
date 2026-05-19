<?php

namespace App\Http\Controllers;

use App\Events\ParticipantVerificationUpdated;
use App\Models\ArchivedParticipant;
use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\MaqraPackage;
use App\Models\OfficialAccessSetting;
use App\Models\Participant;
use App\Models\ParticipantMaqraDraw;
use App\Models\ParticipantVerificationLog;
use App\Models\User;
use App\Support\WhatsAppRegistrationSender;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Support\ActivityLogger;
use App\Support\RealtimeBroadcaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ParticipantRegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $districtId = in_array($user?->role, ['official', 'pendamping'], true) ? $user?->district_id : null;
        $verificationDistrictIds = $this->verificationDistrictIdsForUser($user);
        $restrictPanitiaDistricts = $user?->role === 'panitia';

        $participants = Participant::query()
            ->with(['category', 'district', 'latestMaqraDraw.maqraPackage'])
            ->when($restrictPanitiaDistricts, fn ($query) => $query->whereIn('district_id', $verificationDistrictIds))
            ->when($districtId, fn ($query) => $query->where('district_id', $districtId))
            ->orderByDesc('created_at')
            ->get();
        $participantPerPage = 12;
        $participantCurrentPage = max(1, $request->integer('page', 1));
        $participantPageItems = $participants->forPage($participantCurrentPage, $participantPerPage)->values();
        $participantPaginator = new LengthAwarePaginator(
            $participantPageItems,
            $participants->count(),
            $participantPerPage,
            $participantCurrentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->get();

        $districts = District::query()
            ->when($restrictPanitiaDistricts, fn ($query) => $query->whereIn('id', $verificationDistrictIds))
            ->orderBy('name')
            ->get();

        $categoryUsageParticipants = Participant::query()
            ->select(['competition_category_id', 'district_id', 'verification_status', 'gender', 'participant_role'])
            ->when($restrictPanitiaDistricts, fn ($query) => $query->whereIn('district_id', $verificationDistrictIds))
            ->when($districtId, fn ($query) => $query->where('district_id', $districtId))
            ->get()
            ->groupBy('competition_category_id');

        $currentDistrict = $districtId ? $districts->firstWhere('id', $districtId) : null;

        $categoryUsage = $categories->mapWithKeys(function (CompetitionCategory $category) use ($categoryUsageParticipants, $districtId, $districts, $currentDistrict): array {
            $isDistrictBased = $this->isDistrictQuotaCategory($category);
            $districtScope = $districtId ? 'kecamatan' : 'kabupaten';
            $availableSlots = $districtId
                ? $this->categoryAvailableSlotsForDistrict($category, $districtId)
                : $this->categoryAvailableSlotsAcrossDistricts($category, $districts);
            $usedEntries = collect($categoryUsageParticipants->get($category->id, collect()));
            $mainEntries = $usedEntries->filter(fn ($entry) => ($entry->participant_role ?? 'main') !== 'reserve');
            $reserveEntries = $usedEntries->filter(fn ($entry) => ($entry->participant_role ?? 'main') === 'reserve');
            $registered = $mainEntries->count();
            $verified = $mainEntries->where('verification_status', 'verified')->count();
            $pending = $mainEntries->where('verification_status', 'submitted')->count();
            $draft = $mainEntries->where('verification_status', 'draft')->count();
            $remaining = max($availableSlots - $registered, 0);
            $genderRule = $this->genderQuotaRule($category);

            return [
                $category->id => [
                    'scope_label' => $districtScope,
                    'available_slots' => $availableSlots,
                    'registered' => $registered,
                    'verified' => $verified,
                    'pending' => $pending,
                    'draft' => $draft,
                    'remaining_slots' => $remaining,
                    'reserve_registered' => $reserveEntries->count(),
                    'reserve_remaining_slots' => max($availableSlots - $reserveEntries->count(), 0),
                    'district_based' => $isDistrictBased,
                    'quota_multiplier' => 1,
                    'host_district' => $this->districtMatchesHost($currentDistrict),
                    'gender_rule' => $genderRule,
                    'putra_registered' => $mainEntries->where('gender', 'putra')->count(),
                    'putri_registered' => $mainEntries->where('gender', 'putri')->count(),
                ],
            ];
        });

        return view('pages/participants-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) $user?->role),
            'participants' => $participants,
            'categories' => $categories,
            'categoryUsage' => $categoryUsage,
            'districts' => $districts,
            'districtLocked' => (bool) $districtId,
            'officialAccessSetting' => OfficialAccessSetting::currentOrDefault(),
            'registrationWindowOpen' => $this->registrationDeadlineOpen(),
            'canVerify' => $this->canUserVerifyParticipants(),
            'filters' => [
                'district_id' => $districtId ?: ($restrictPanitiaDistricts && count($verificationDistrictIds) === 1 ? (string) $verificationDistrictIds[0] : ''),
                'competition_category_id' => '',
                'verification_status' => '',
                'keyword' => '',
            ],
            'officialMandate' => $this->officialMandateContext($user),
            'registrationStats' => [
                'total' => $participants->count(),
                'verified' => $participants->where('verification_status', 'verified')->count(),
                'pending' => $participants->where('verification_status', 'submitted')->count(),
                'draft' => $participants->where('verification_status', 'draft')->count(),
            ],
        ]);
    }

    public function list(Request $request): View
    {
        $user = auth()->user();
        $districtId = in_array($user?->role, ['official', 'pendamping'], true) ? $user?->district_id : null;
        $verificationDistrictIds = $this->verificationDistrictIdsForUser($user);
        $restrictPanitiaDistricts = $user?->role === 'panitia';
        $filters = $this->participantListFilters($request);
        $participants = $this->participantListQuery($filters, $districtId, $restrictPanitiaDistricts ? $verificationDistrictIds : [])
            ->get();
        $participants = $this->participantListSortParticipants(
            $participants,
            $this->participantListSortColumn($filters['sort'] ?? 'created_at'),
            $this->participantListSortDirection($filters['direction'] ?? 'desc')
        );
        $participantPerPage = 12;
        $participantCurrentPage = max(1, $request->integer('page', 1));
        $participantPageItems = $participants->forPage($participantCurrentPage, $participantPerPage)->values();
        $participantPaginator = new LengthAwarePaginator(
            $participantPageItems,
            $participants->count(),
            $participantPerPage,
            $participantCurrentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $maqraSwapCandidatesMap = $participants->mapWithKeys(function (Participant $source) use ($participants): array {
            $sourceRound = (string) ($source->latestMaqraDraw?->round_label ?? '');

            if ($sourceRound === '' || ! $source->latestMaqraDraw?->maqraPackage) {
                return [$source->id => collect()];
            }

            $candidates = $participants
                ->filter(function (Participant $candidate) use ($source, $sourceRound): bool {
                    return $candidate->id !== $source->id
                        && (int) $candidate->competition_category_id === (int) $source->competition_category_id
                        && (string) $candidate->gender === (string) $source->gender
                        && $candidate->verification_status === 'verified'
                        && filled($candidate->latestMaqraDraw?->maqraPackage)
                        && (string) ($candidate->latestMaqraDraw?->round_label ?? '') === $sourceRound;
                })
                ->sortBy('name')
                ->values();

            return [$source->id => $candidates];
        });

        return view('pages/participants-list-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) $user?->role),
            'participants' => $participants,
            'participantsPage' => $participantPageItems,
            'participantsPaginator' => $participantPaginator,
            'participantsPerPage' => $participantPerPage,
            'categories' => CompetitionCategory::query()->orderBy('sort_order')->orderBy('branch')->get(),
            'districts' => District::query()
                ->when($restrictPanitiaDistricts, fn ($query) => $query->whereIn('id', $verificationDistrictIds))
                ->orderBy('name')
                ->get(),
            'districtLocked' => (bool) $districtId,
            'restrictPanitiaDistricts' => $restrictPanitiaDistricts,
            'verificationDistrictIds' => $verificationDistrictIds,
            'registrationWindowOpen' => $this->registrationDeadlineOpen(),
            'canVerify' => $this->canUserVerifyParticipants(),
            'canDrawParticipant' => $this->canUserDrawLot(),
            'canDrawMaqra' => $this->canUserDrawMaqra(),
            'canManageMaqra' => (string) $user?->role === 'admin',
            'officialAccessSetting' => OfficialAccessSetting::currentOrDefault(),
            'maqraSwapCandidatesMap' => $maqraSwapCandidatesMap,
            'filters' => [
                'district_id' => $districtId ?: ($filters['district_id'] ?? ($restrictPanitiaDistricts && count($verificationDistrictIds) === 1 ? (string) $verificationDistrictIds[0] : '')),
                'competition_category_id' => $filters['competition_category_id'] ?? '',
                'verification_status' => $filters['verification_status'] ?? '',
                'keyword' => $filters['keyword'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
            ],
            'registrationStats' => [
                'total' => $participants->count(),
                'verified' => $participants->where('verification_status', 'verified')->count(),
                'pending' => $participants->where('verification_status', 'submitted')->count(),
                'draft' => $participants->where('verification_status', 'draft')->count(),
            ],
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia', 'official', 'pendamping'], true), 403);

        [$filters, $districtId, $participants, $selectedDistrict] = $this->participantExportContext($request);
        $documentConfig = app(PageController::class)->documentConfig();
        $generatedAt = now();
        $filenameDistrict = $selectedDistrict?->name
            ?? ($districtId ? 'kecamatan' : 'semua-kecamatan');
        $filename = 'rekap-peserta-'.Str::slug((string) $filenameDistrict).'-'.$generatedAt->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($participants, $selectedDistrict, $filters, $generatedAt, $documentConfig): void {
            echo view('pages/participants-export-excel', [
                'participants' => $participants,
                'selectedDistrict' => $selectedDistrict,
                'filters' => $filters,
                'generatedAt' => $generatedAt,
                'documentConfig' => $documentConfig,
                'rows' => $this->participantExportRows($participants),
                'summary' => $this->participantExportSummary($participants),
            ])->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia', 'official', 'pendamping'], true), 403);

        [$filters, , $participants, $selectedDistrict] = $this->participantExportContext($request);

        return view('pages/participants-export-print', [
            'participants' => $participants,
            'selectedDistrict' => $selectedDistrict,
            'filters' => $filters,
            'generatedAt' => now(),
            'documentConfig' => app(PageController::class)->documentConfig(),
            'rows' => $this->participantExportRows($participants),
            'summary' => $this->participantExportSummary($participants),
        ]);
    }

    public function trash(Request $request): View
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $filters = $request->validate([
            'district_id' => ['nullable', 'integer'],
            'competition_category_id' => ['nullable', 'integer'],
            'verification_status' => ['nullable', 'in:draft,submitted,verified,rejected'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ]);

        $participants = ArchivedParticipant::query()
            ->with(['category', 'district'])
            ->when(filled($filters['district_id'] ?? null), fn ($query) => $query->where('district_id', $filters['district_id']))
            ->when(filled($filters['competition_category_id'] ?? null), fn ($query) => $query->where('competition_category_id', $filters['competition_category_id']))
            ->when(filled($filters['verification_status'] ?? null), fn ($query) => $query->where('verification_status', $filters['verification_status']))
            ->when(filled($filters['keyword'] ?? null), function ($query) use ($filters) {
                $keyword = trim((string) $filters['keyword']);

                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('registration_number', 'like', '%'.$keyword.'%')
                        ->orWhere('nik', 'like', '%'.$keyword.'%')
                        ->orWhere('institution', 'like', '%'.$keyword.'%');
                });
            })
            ->orderByDesc('archived_at')
            ->get();
        $legacyTrashCount = Participant::onlyTrashed()->count();

        return view('pages/participants-trash-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'participants' => $participants,
            'categories' => CompetitionCategory::query()->orderBy('sort_order')->orderBy('branch')->get(),
            'districts' => District::query()->orderBy('name')->get(),
            'filters' => [
                'district_id' => $filters['district_id'] ?? '',
                'competition_category_id' => $filters['competition_category_id'] ?? '',
                'verification_status' => $filters['verification_status'] ?? '',
                'keyword' => $filters['keyword'] ?? '',
            ],
            'trashStats' => [
                'total' => $participants->count(),
                'verified' => $participants->where('verification_status', 'verified')->count(),
                'pending' => $participants->where('verification_status', 'submitted')->count(),
                'rejected' => $participants->where('verification_status', 'rejected')->count(),
            ],
            'legacyTrashCount' => $legacyTrashCount,
        ]);
    }

    public function importLegacyTrash(): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $legacyParticipants = Participant::onlyTrashed()
            ->with(['category', 'district'])
            ->orderByDesc('deleted_at')
            ->get();

        if ($legacyParticipants->isEmpty()) {
            return redirect()
                ->route('participants.trash')
                ->with('status', 'Tidak ada data soft delete lama yang perlu dipindahkan ke arsip baru.');
        }

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($legacyParticipants, &$imported, &$skipped): void {
            foreach ($legacyParticipants as $legacyParticipant) {
                $alreadyArchived = ArchivedParticipant::query()
                    ->where('source_participant_id', $legacyParticipant->id)
                    ->orWhere('registration_number', $legacyParticipant->registration_number)
                    ->exists();

                if ($alreadyArchived) {
                    $legacyParticipant->forceDelete();
                    $skipped++;
                    continue;
                }

                ArchivedParticipant::query()->create(array_merge(
                    $this->archivedParticipantPayload($legacyParticipant),
                    ['source_participant_id' => $legacyParticipant->id]
                ));

                $legacyParticipant->forceDelete();
                $imported++;
            }
        });

        ActivityLogger::log(
            'participant.legacy_archived_imported',
            'Admin memindahkan arsip lama peserta ke tabel arsip baru.',
            null,
            [
                'imported_count' => $imported,
                'skipped_count' => $skipped,
                'legacy_total' => $legacyParticipants->count(),
            ]
        );

        return redirect()
            ->route('participants.trash')
            ->with('status', 'Pemindahan arsip lama selesai. '.$imported.' data dipindahkan'.($skipped > 0 ? ', '.$skipped.' data dilewati karena sudah ada di arsip baru.' : '.'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $districtLocked = in_array($user?->role, ['official', 'pendamping'], true);
        $this->assertOfficialFeatureEnabled('participant_registration_open', 'Pendaftaran peserta untuk official sedang ditutup.');

        if ($districtLocked && ! $this->officialMandateContext($user)['ready']) {
            return redirect()
                ->route('participants.index')
                ->withErrors(['mandate_document' => 'Upload surat mandat official PDF terlebih dahulu sebelum mendaftarkan peserta.']);
        }

        $validated = $this->validateParticipantForm($request, true);

        if ($districtLocked) {
            $validated['district_id'] = $user?->district_id;
        }

        $isDraft = ($validated['submit_action'] ?? '') === 'draft';

        if (! $isDraft) {
            $this->ensureParticipantAgeMatchesCategory(
                (int) $validated['competition_category_id'],
                (string) $validated['date_of_birth'],
            );

            $this->ensureCategoryCapacity(
                (int) $validated['competition_category_id'],
                $validated['district_id'] ? (int) $validated['district_id'] : null,
                $validated['gender'],
                null,
                $validated['participant_role'],
            );
        }

        $registrationNumber = 'REG-'.now()->format('ymd').'-'.Str::upper(Str::random(5));

        $participant = Participant::query()->create([
            'district_id' => $validated['district_id'] ?: null,
            'competition_category_id' => $validated['competition_category_id'],
            'registration_number' => $registrationNumber,
            'participant_role' => $validated['participant_role'],
            'name' => $validated['name'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'nik' => $validated['nik'] ?? null,
            'ktp_date' => $validated['ktp_date'] ?? null,
            'place_of_birth' => $validated['place_of_birth'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'kk_number' => $validated['kk_number'] ?? null,
            'kk_date' => $validated['kk_date'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'institution' => $validated['institution'] ?? null,
            'last_education' => $validated['last_education'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'bank_account_name' => $validated['bank_account_name'] ?? null,
            'current_address' => $validated['current_address'] ?? null,
            'ktp_address' => $validated['ktp_address'] ?? null,
            'ktp_district' => $validated['ktp_district'] ?? null,
            'ktp_regency' => $validated['ktp_regency'] ?? null,
            'document_kk' => $this->storeUploadedFile($request, 'kk_document', 'participants/documents/kk'),
            'document_ktp' => $this->storeUploadedFile($request, 'ktp_document', 'participants/documents/ktp'),
            'document_birth_certificate' => $this->storeUploadedFile($request, 'birth_certificate_document', 'participants/documents/akta'),
            'document_photo' => $this->storeUploadedFile($request, 'photo_document', 'participants/documents/photo'),
            'document_last_diploma' => $this->storeUploadedFile($request, 'last_diploma_document', 'participants/documents/ijazah'),
            'document_bank_book' => $this->storeUploadedFile($request, 'bank_book_document', 'participants/documents/tabungan'),
            'document_certificates' => $this->storeUploadedFiles($request, 'certificate_documents', 'participants/documents/piagam'),
            'document_other_files' => $this->storeUploadedFiles($request, 'other_documents', 'participants/documents/lainnya'),
            'status' => 'active',
            'verification_status' => $validated['submit_action'],
            'verification_notes' => $validated['submit_action'] === 'submitted'
                ? 'Berkas telah dikirim untuk verifikasi tahap I.'
                : 'Data masih disimpan sebagai draft.',
        ]);

        $this->logParticipantActivity(
            'participant.created',
            $participant,
            ($user?->name ?? 'Pengguna').' mendaftarkan peserta '.$participant->name.' dengan nomor '.$participant->registration_number.'.',
            ['submit_action' => $validated['submit_action']]
        );

        return redirect()
            ->route('participants.index')
            ->with('status', 'Peserta '.$participant->name.' berhasil didaftarkan dengan nomor '.$participant->registration_number.'.');
    }

    public function uploadMandate(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        abort_unless(in_array($user?->role, ['official', 'pendamping'], true), 403);
        $this->assertOfficialFeatureEnabled('mandate_upload_open', 'Upload surat mandat official sedang ditutup oleh admin.');
        $district = District::query()->find((int) $user?->district_id);

        if (! $district) {
            throw ValidationException::withMessages([
                'mandate_document' => 'Akun official belum terhubung ke kecamatan. Hubungi admin untuk mengatur kecamatan akun ini.',
            ]);
        }

        $request->validate([
            'mandate_document' => ['required', 'file', 'mimes:pdf', 'max:4096'],
        ], [
            'mandate_document.required' => 'Surat mandat wajib diupload sebelum melakukan pendaftaran.',
            'mandate_document.mimes' => 'Surat mandat harus berupa file PDF.',
            'mandate_document.max' => 'Surat mandat maksimal berukuran 4 MB.',
        ]);

        $file = $request->file('mandate_document');

        try {
            $newPath = $file?->store('districts/mandates', 'public');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'mandate_document' => 'Surat mandat gagal disimpan ke storage. Periksa permission folder storage/app/public.',
            ]);
        }

        if (! $newPath || ! Storage::disk('public')->exists($newPath)) {
            throw ValidationException::withMessages([
                'mandate_document' => 'Surat mandat belum berhasil disimpan ke storage. Periksa permission folder storage/app/public.',
            ]);
        }

        $mandateState = $this->officialMandateState($user, $district);
        $oldPath = (string) ($mandateState['path'] ?? '');
        $scope = (string) ($mandateState['scope'] ?? 'none');

        try {
            DB::transaction(function () use ($user, $district, $newPath, $scope): void {
                $payload = [
                    'mandate_document_path' => $newPath,
                    'mandate_uploaded_at' => now(),
                    'mandate_status' => 'submitted',
                    'mandate_verification_notes' => null,
                    'mandate_verified_by' => null,
                    'mandate_verified_at' => null,
                ];

                if ($scope === 'district') {
                    District::query()
                        ->whereKey($district->id)
                        ->update($payload);

                    return;
                }

                if ($scope === 'user') {
                    User::query()
                        ->whereKey($user->id)
                        ->update($payload);

                    return;
                }

                throw new \RuntimeException('Skema penyimpanan surat mandat tidak tersedia.');
            });
        } catch (\Throwable) {
            Storage::disk('public')->delete($newPath);

            throw ValidationException::withMessages([
                'mandate_document' => 'Surat mandat sudah terunggah, tetapi database gagal diperbarui. Silakan coba lagi.',
            ]);
        }

        if ($oldPath !== '' && $oldPath !== $newPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $district->refresh();
        $user = $user->fresh();
        $savedMandateState = $this->officialMandateState($user, $district);
        ActivityLogger::log(
            'mandate.uploaded',
            ($user?->name ?? 'Official').' mengupload surat mandat Kecamatan '.$district->name.'.',
            $district,
            [
                'district_id' => $district->id,
                'district_name' => $district->name,
                'mandate_status' => $savedMandateState['status'] ?? 'submitted',
                'mandate_document_path' => $newPath,
                'mandate_scope' => $savedMandateState['scope'] ?? 'district',
            ]
        );

        $status = 'Surat mandat kecamatan berhasil diupload. Semua official pada kecamatan ini dapat melanjutkan pendaftaran peserta.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $status,
                'redirect_url' => route('participants.index'),
            ]);
        }

        return redirect()
            ->route('participants.index')
            ->with('status', $status);
    }

    public function previewMandate(): StreamedResponse|BinaryFileResponse
    {
        $user = auth()->user();
        abort_unless(in_array($user?->role, ['official', 'pendamping'], true), 403);
        $district = District::query()->findOrFail((int) $user?->district_id);
        $mandateState = $this->officialMandateState($user, $district);
        $path = (string) ($mandateState['path'] ?? '');
        abort_unless(filled($path), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    public function previewDistrictMandate(District $district): StreamedResponse|BinaryFileResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);
        $this->authorizeDistrictVerificationAccess($district);
        abort_unless(filled($district->mandate_document_path), 404);
        abort_unless(Storage::disk('public')->exists($district->mandate_document_path), 404);

        return Storage::disk('public')->response($district->mandate_document_path);
    }

    public function verifyMandate(Request $request, District $district): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);
        $this->authorizeDistrictVerificationAccess($district);
        abort_unless(filled($district->mandate_document_path), 404);

        $validated = $request->validate([
            'mandate_status' => ['required', 'in:verified,rejected'],
            'mandate_verification_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $district->update([
            'mandate_status' => $validated['mandate_status'],
            'mandate_verification_notes' => $validated['mandate_verification_notes'] ?: match ($validated['mandate_status']) {
                'verified' => 'Surat mandat kecamatan telah diverifikasi panitia.',
                default => 'Surat mandat perlu diperbaiki atau diupload ulang.',
            },
            'mandate_verified_by' => auth()->id(),
            'mandate_verified_at' => now(),
        ]);

        ActivityLogger::log(
            'mandate.'.$validated['mandate_status'],
            (auth()->user()?->name ?? 'Panitia').' memperbarui verifikasi surat mandat Kecamatan '.$district->name.' menjadi '.$validated['mandate_status'].'.',
            $district,
            [
                'district_id' => $district->id,
                'district_name' => $district->name,
                'mandate_status' => $validated['mandate_status'],
                'notes' => $district->mandate_verification_notes,
            ]
        );

        return redirect()
            ->route('participants.index')
            ->with('status', 'Verifikasi surat mandat Kecamatan '.$district->name.' berhasil diperbarui.');
    }

    public function edit(Participant $participant): View
    {
        $participant->load(['category', 'district']);
        $this->authorizeParticipantAccess($participant);
        $this->assertOfficialFeatureEnabled('participant_edit_open', 'Edit peserta untuk official sedang ditutup.');
        $this->authorizeParticipantEdit($participant);

        $user = auth()->user();

        return view('pages/participant-edit-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) $user?->role),
            'participant' => $participant,
            'categories' => CompetitionCategory::query()->orderBy('sort_order')->orderBy('branch')->get(),
            'districts' => District::query()->orderBy('name')->get(),
            'districtLocked' => in_array($user?->role, ['official', 'pendamping'], true),
            'registrationWindowOpen' => $this->registrationDeadlineOpen(),
            'documentMap' => $this->documentMap($participant),
        ]);
    }

    public function update(Request $request, Participant $participant): RedirectResponse
    {
        $this->authorizeParticipantAccess($participant);
        $this->assertOfficialFeatureEnabled('participant_edit_open', 'Edit peserta untuk official sedang ditutup.');
        $this->authorizeParticipantEdit($participant);

        $user = auth()->user();
        $districtLocked = in_array($user?->role, ['official', 'pendamping'], true);
        $validated = $this->validateParticipantForm($request, false, $participant);
        $isDraft = ($validated['submit_action'] ?? '') === 'draft';

        if ($districtLocked) {
            $validated['district_id'] = $user?->district_id;
        }

        if (! $isDraft) {
            $this->ensureParticipantAgeMatchesCategory(
                (int) $validated['competition_category_id'],
                (string) $validated['date_of_birth'],
            );

            $this->ensureCategoryCapacity(
                (int) $validated['competition_category_id'],
                $validated['district_id'] ? (int) $validated['district_id'] : null,
                $validated['gender'],
                $participant->id,
                $validated['participant_role'],
            );
        }

        $attributes = [
            'district_id' => $validated['district_id'] ?: null,
            'competition_category_id' => $validated['competition_category_id'],
            'participant_role' => $validated['participant_role'],
            'name' => $validated['name'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'nik' => $validated['nik'] ?? null,
            'ktp_date' => $validated['ktp_date'] ?? null,
            'place_of_birth' => $validated['place_of_birth'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'kk_number' => $validated['kk_number'] ?? null,
            'kk_date' => $validated['kk_date'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'institution' => $validated['institution'] ?? null,
            'last_education' => $validated['last_education'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'bank_account_name' => $validated['bank_account_name'] ?? null,
            'current_address' => $validated['current_address'] ?? null,
            'ktp_address' => $validated['ktp_address'] ?? null,
            'ktp_district' => $validated['ktp_district'] ?? null,
            'ktp_regency' => $validated['ktp_regency'] ?? null,
            'verification_status' => $validated['submit_action'],
            'verification_notes' => $validated['submit_action'] === 'submitted'
                ? 'Peserta mengirim ulang data untuk verifikasi.'
                : 'Perbaikan data masih disimpan sebagai draft.',
        ];

        $fileFields = [
            'kk_document' => 'document_kk',
            'ktp_document' => 'document_ktp',
            'birth_certificate_document' => 'document_birth_certificate',
            'photo_document' => 'document_photo',
            'last_diploma_document' => 'document_last_diploma',
            'bank_book_document' => 'document_bank_book',
        ];

        foreach ($fileFields as $input => $column) {
            if ($request->hasFile($input)) {
                $this->deleteStoredFile($participant->{$column});
                $attributes[$column] = $this->storeUploadedFile($request, $input, match ($column) {
                    'document_kk' => 'participants/documents/kk',
                    'document_ktp' => 'participants/documents/ktp',
                    'document_birth_certificate' => 'participants/documents/akta',
                    'document_last_diploma' => 'participants/documents/ijazah',
                    'document_bank_book' => 'participants/documents/tabungan',
                    default => 'participants/documents/photo',
                });
            }
        }

        foreach ([
            'certificate_documents' => ['column' => 'document_certificates', 'directory' => 'participants/documents/piagam'],
            'other_documents' => ['column' => 'document_other_files', 'directory' => 'participants/documents/lainnya'],
        ] as $input => $config) {
            if ($request->hasFile($input)) {
                $this->deleteStoredFiles($participant->{$config['column']});
                $attributes[$config['column']] = $this->storeUploadedFiles($request, $input, $config['directory']);
            }
        }

        $revisionNotes = $participant->document_revision_notes ?? [];

        foreach ([
            'kk_document' => 'kk',
            'ktp_document' => 'ktp',
            'birth_certificate_document' => 'birth_certificate',
            'photo_document' => 'photo',
            'last_diploma_document' => 'last_diploma',
            'bank_book_document' => 'bank_book',
            'certificate_documents' => 'certificates',
            'other_documents' => 'other_files',
        ] as $input => $key) {
            if ($request->hasFile($input)) {
                $revisionNotes[$key] = null;
            }
        }

        $attributes['document_revision_notes'] = $revisionNotes;

        $participant->update($attributes);

        ParticipantVerificationLog::query()->create([
            'participant_id' => $participant->id,
            'verified_by' => auth()->id(),
            'status' => 'updated',
            'notes' => $isDraft
                ? 'Data peserta diperbarui dan disimpan sebagai draft oleh '.($user?->name ?? 'pengguna').'.'
                : 'Data peserta diperbarui dan dikirim ulang oleh '.($user?->name ?? 'pengguna').'.',
        ]);

        $this->logParticipantActivity(
            'participant.updated',
            $participant,
            ($user?->name ?? 'Pengguna').' memperbarui data peserta '.$participant->name.'.',
            [
                'submit_action' => $validated['submit_action'],
                'uploaded_documents' => collect(array_keys($fileFields))
                    ->merge(['certificate_documents', 'other_documents'])
                    ->filter(fn (string $input): bool => $request->hasFile($input))
                    ->values()
                    ->all(),
            ]
        );

        return redirect()
            ->route('participants.show', $participant)
            ->with('status', $isDraft
                ? 'Draft peserta '.$participant->name.' berhasil disimpan.'
                : 'Data peserta '.$participant->name.' berhasil diperbarui.');
    }

    public function archive(Participant $participant): RedirectResponse
    {
        $this->authorizeParticipantAccess($participant);
        $this->authorizeParticipantDelete($participant);

        $participantName = $participant->name;

        DB::transaction(function () use ($participant, $participantName): void {
            ArchivedParticipant::query()->create($this->archivedParticipantPayload($participant));
            $this->logParticipantActivity(
                'participant.archived',
                $participant,
                (auth()->user()?->name ?? 'Pengguna').' mengarsipkan peserta '.$participantName.'.',
            );
            $participant->forceDelete();
        });

        return redirect()
            ->route('participants.list')
            ->with('status', 'Data peserta '.$participantName.' dipindahkan ke arsip admin.');
    }

    public function restore(ArchivedParticipant $participant): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        if (! CompetitionCategory::query()->whereKey($participant->competition_category_id)->exists()) {
            return redirect()
                ->route('participants.trash')
                ->withErrors([
                    'participant' => 'Pemulihan gagal. Golongan asal arsip sudah tidak tersedia.',
                ]);
        }

        if (filled($participant->district_id) && ! District::query()->whereKey($participant->district_id)->exists()) {
            return redirect()
                ->route('participants.trash')
                ->withErrors([
                    'participant' => 'Pemulihan gagal. Kecamatan asal arsip sudah tidak tersedia.',
                ]);
        }

        $participantName = $participant->name;
        $activeNikExists = filled($participant->nik)
            && Participant::query()->where('nik', $participant->nik)->exists();

        if ($activeNikExists) {
            return redirect()
                ->route('participants.trash')
                ->withErrors([
                    'participant' => 'Pemulihan gagal. NIK '.$participant->nik.' sudah digunakan oleh peserta aktif.',
                ]);
        }

        $activeRegistrationExists = Participant::query()
            ->where('registration_number', $participant->registration_number)
            ->exists();

        if ($activeRegistrationExists) {
            return redirect()
                ->route('participants.trash')
                ->withErrors([
                    'participant' => 'Pemulihan gagal. Nomor registrasi '.$participant->registration_number.' sudah digunakan oleh peserta aktif.',
                ]);
        }

        DB::transaction(function () use ($participant, $participantName): void {
            $restored = Participant::query()->create($this->archivedParticipantPayloadToActive($participant));
            $participant->delete();

            $this->logParticipantActivity(
                'participant.restored',
                $restored,
                (auth()->user()?->name ?? 'Admin').' memulihkan peserta '.$participantName.' dari arsip.',
            );
        });

        return redirect()
            ->route('participants.trash')
            ->with('status', 'Data peserta '.$participantName.' berhasil dipulihkan ke daftar peserta.');
    }

    public function destroyArchived(ArchivedParticipant $participant): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $participantName = $participant->name;

        DB::transaction(function () use ($participant, $participantName): void {
            $this->deleteArchivedParticipantFiles($participant);
            $this->logParticipantActivity(
                'participant.permanently_deleted',
                $participant,
                (auth()->user()?->name ?? 'Admin').' menghapus permanen arsip peserta '.$participantName.'.',
            );
            $participant->delete();
        });

        return redirect()
            ->route('participants.trash')
            ->with('status', 'Data peserta '.$participantName.' dihapus permanen dari arsip.');
    }

    public function show(Participant $participant): View
    {
        $participant->load(['category', 'district', 'verificationLogs.verifier', 'latestMaqraDraw.maqraPackage', 'maqraDraws.maqraPackage']);
        $this->authorizeParticipantDetailAccess($participant);
        $user = auth()->user();
        $canDrawParticipant = $this->canUserDrawLot();
        $canDrawMaqra = $this->canUserDrawMaqra($participant);
        $canManageLot = $user?->role === 'admin';
        $canManageMaqra = $user?->role === 'admin' && $this->participantUsesMaqra($participant);
        $maqraSwapCandidates = $canManageMaqra && $participant->latestMaqraDraw?->maqraPackage
            ? Participant::query()
                ->with(['district', 'maqraDraws.maqraPackage'])
                ->where('id', '!=', $participant->id)
                ->where('competition_category_id', $participant->competition_category_id)
                ->where('gender', $participant->gender)
                ->where('verification_status', 'verified')
                ->whereHas('maqraDraws', fn ($query) => $query->where('round_label', (string) $participant->latestMaqraDraw?->round_label))
                ->orderBy('name')
                ->get(['id', 'name', 'registration_number'])
            : collect();

        return view('pages/participant-detail-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'participant' => $participant,
            'documentMap' => $this->documentMap($participant),
            'cvDownloadUrl' => $this->participantCvDownloadUrl($participant),
            'canVerify' => $this->canUserVerifyParticipant($participant),
            'officialAccessSetting' => OfficialAccessSetting::currentOrDefault(),
            'registrationWindowOpen' => $this->registrationDeadlineOpen(),
            'canDrawParticipant' => $canDrawParticipant,
            'canDrawMaqra' => $canDrawMaqra,
            'districtMandate' => $this->districtMandateForParticipant($participant),
            'canManageLot' => $canManageLot,
            'canManageMaqra' => $canManageMaqra,
            'maqraSwapCandidates' => $maqraSwapCandidates,
            'lotSwapCandidates' => $canManageLot
                ? Participant::query()
                    ->with('district')
                    ->where('id', '!=', $participant->id)
                    ->where('competition_category_id', $participant->competition_category_id)
                    ->where('gender', $participant->gender)
                    ->where('verification_status', 'verified')
                    ->whereNotNull('lot_number')
                    ->orderBy('name')
                    ->get(['id', 'name', 'registration_number', 'lot_number'])
                : collect(),
        ]);
    }

    public function previewDocument(Request $request, Participant $participant, string $document): StreamedResponse|BinaryFileResponse
    {
        $participant->load(['category', 'district']);
        $this->authorizeParticipantDetailAccess($participant);
        $this->assertOfficialFeatureEnabled('participant_documents_open', 'Akses dokumen peserta untuk official sedang ditutup oleh admin.');

        $file = $this->resolveDocumentEntry($this->documentMap($participant), $document, (int) $request->query('index', 0));
        abort_unless($file['path'], 404);
        abort_unless(Storage::disk('public')->exists($file['path']), 404);

        return Storage::disk('public')->response($file['path']);
    }

    public function downloadDocument(Request $request, Participant $participant, string $document): StreamedResponse
    {
        $participant->load(['category', 'district']);
        $this->authorizeParticipantDetailAccess($participant);
        $this->assertOfficialFeatureEnabled('participant_documents_open', 'Akses dokumen peserta untuk official sedang ditutup oleh admin.');

        $file = $this->resolveDocumentEntry($this->documentMap($participant), $document, (int) $request->query('index', 0));
        abort_unless($file['path'], 404);
        abort_unless(Storage::disk('public')->exists($file['path']), 404);

        return Storage::disk('public')->download($file['path'], basename((string) $file['path']));
    }

    public function downloadCv(Participant $participant)
    {
        $participant->load(['category', 'district', 'verificationLogs.verifier']);
        $this->authorizeParticipantCvAccess($participant);
        $this->assertOfficialFeatureEnabled('participant_documents_open', 'Akses CV peserta untuk official sedang ditutup oleh admin.');

        $payload = $this->participantCvPayload($participant);
        $html = view('pdf.participant-cv', $payload)->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'cv-peserta-'.Str::slug((string) $participant->name).'-'.Str::slug((string) ($participant->registration_number ?? 'registrasi')).'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function accessibleCategoryIdsForUser($user): array
    {
        if (! $user || $user->role !== 'panitia') {
            return [];
        }

        return $user->accessibleCategoryIds();
    }

    protected function accessibleCategoryIdsForLotUser($user): ?array
    {
        if (! $user) {
            return [];
        }

        if ($user->role === 'admin') {
            return null;
        }

        if ($user->role !== 'panitia') {
            return [];
        }

        return $user->accessibleCategoryIds();
    }

    public function lotMenu(Request $request): View
    {
        $user = auth()->user();
        abort_unless(in_array($user?->role, ['admin', 'panitia'], true), 403);
        abort_unless($this->officialAccessSetting()->isEnabled('participant_lot_open'), 403, 'Masa ambil nomor lot untuk panitia sedang ditutup oleh admin.');

        $filters = $request->validate([
            'competition_category_id' => ['nullable', 'integer'],
            'participant_id' => ['nullable', 'integer'],
        ]);

        $accessibleCategoryIds = $this->accessibleCategoryIdsForLotUser($user);

        $categoriesQuery = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name');

        if (is_array($accessibleCategoryIds)) {
            $categoriesQuery->whereIn('id', $accessibleCategoryIds);
        }

        $categories = $categoriesQuery->get();
        $selectedCategoryId = filled($filters['competition_category_id'] ?? null)
            ? (int) $filters['competition_category_id']
            : (int) ($categories->first()?->id ?? 0);
        $selectedCategory = $selectedCategoryId > 0
            ? $categories->firstWhere('id', $selectedCategoryId)
            : $categories->first();

        $participantsQuery = Participant::query()
            ->with(['category', 'district'])
            ->where('verification_status', 'verified')
            ->when(is_array($accessibleCategoryIds), fn ($query) => $query->whereIn('competition_category_id', $accessibleCategoryIds))
            ->orderBy('name');

        $participants = $participantsQuery->get();
        $participantsByCategory = $participants->groupBy(fn (Participant $participant) => (int) $participant->competition_category_id);

        $selectedParticipantId = filled($filters['participant_id'] ?? null)
            ? (int) $filters['participant_id']
            : (int) ($participants->first()?->id ?? 0);
        $selectedParticipant = $selectedParticipantId > 0
            ? $participants->firstWhere('id', $selectedParticipantId)
            : null;

        if (! $selectedParticipant && $selectedCategoryId > 0) {
            $selectedParticipant = $participantsByCategory->get($selectedCategoryId, collect())->first();
        }

        $selectedParticipant ??= $participants->first();

        return view('pages.pengambilan-lot-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'navigation' => app(PageController::class)->consoleNavigation((string) auth()->user()?->role, 'participants.lot.menu'),
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'participants' => $participants,
            'participantsByCategory' => $participantsByCategory,
            'selectedParticipant' => $selectedParticipant,
            'summaryStats' => [
                'category_total' => $categories->count(),
                'participant_total' => $participants->count(),
                'verified_total' => Participant::query()
                    ->where('verification_status', 'verified')
                    ->when(is_array($accessibleCategoryIds), fn ($query) => $query->whereIn('competition_category_id', $accessibleCategoryIds))
                    ->count(),
            ],
            'filters' => [
                'competition_category_id' => $selectedCategoryId > 0 ? $selectedCategoryId : '',
                'participant_id' => $selectedParticipant?->id ?? '',
            ],
            'judgeNameDefault' => (string) auth()->user()?->name,
        ]);
    }

    public function maqraMenu(Request $request): View
    {
        $user = auth()->user();
        abort_unless(in_array($user?->role, ['admin', 'official', 'pendamping', 'panitia'], true), 403);

        $filters = $request->validate([
            'round' => ['nullable', 'string'],
            'participant_id' => ['nullable', 'integer'],
        ]);

        $roundLabel = in_array((string) ($filters['round'] ?? null), ['Penyisihan', 'Final'], true)
            ? (string) $filters['round']
            : 'Penyisihan';

        if ($user?->role !== 'admin') {
            abort_unless(
                $this->officialMaqraRoundEnabled($roundLabel),
                403,
                sprintf('Masa ambil maqra babak %s untuk official sedang ditutup oleh admin.', $roundLabel)
            );
        }

        $district = in_array($user?->role, ['official', 'pendamping'], true)
            ? District::query()->find($user?->district_id)
            : null;
        $allowedCategoryIds = $this->allowedMaqraCategoryIdsForUser($user);
        $maqraOpenCategoryIds = $this->officialAccessSetting()->maqraOpenCategoryIds();
        $applyMaqraCategoryFilter = $maqraOpenCategoryIds !== [];

        $participantsQuery = Participant::query()
            ->with(['category', 'district', 'latestMaqraDraw.maqraPackage'])
            ->where('verification_status', 'verified')
            ->when($district, fn ($query) => $query->where('district_id', $district->id))
            ->when(is_array($allowedCategoryIds), fn ($query) => $query->whereIn('competition_category_id', $allowedCategoryIds))
            ->when($applyMaqraCategoryFilter, fn ($query) => $query->whereIn('competition_category_id', $maqraOpenCategoryIds))
            ->orderBy('competition_category_id')
            ->orderBy('name');
        $participants = $participantsQuery
            ->get()
            ->filter(fn (Participant $participant): bool => $this->participantUsesMaqra($participant))
            ->values();

        if (in_array($user?->role, ['official', 'pendamping'], true)) {
            $participants = $participants
                ->filter(function (Participant $participant): bool {
                    $maqraLotRange = $this->officialAccessSetting()->maqraOpenLotRangeForCategory((int) $participant->competition_category_id);

                    if (! is_array($maqraLotRange)) {
                        return true;
                    }

                    $lotSequence = $this->participantLotSequenceNumber($participant);
                    $lotMin = (int) ($maqraLotRange['min'] ?? 0);
                    $lotMax = (int) ($maqraLotRange['max'] ?? 0);

                    return filled($lotSequence)
                        && $lotSequence >= $lotMin
                        && $lotSequence <= $lotMax;
                })
                ->values();
        }

        $categoryIds = $participants->pluck('competition_category_id')->filter()->unique()->values();
        $categories = CompetitionCategory::query()
            ->when($categoryIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $categoryIds))
            ->when($applyMaqraCategoryFilter, fn ($query) => $query->whereIn('id', $maqraOpenCategoryIds))
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name')
            ->get();

        $participantsByCategory = $participants->groupBy(fn (Participant $participant) => (int) $participant->competition_category_id);

        $selectedParticipantId = filled($filters['participant_id'] ?? null)
            ? (int) $filters['participant_id']
            : (int) ($participants->first()?->id ?? 0);
        $selectedParticipant = $participants->firstWhere('id', $selectedParticipantId) ?? $participants->first();
        $selectedCategory = $selectedParticipant?->category
            ? $categories->firstWhere('id', $selectedParticipant->competition_category_id)
            : $categories->first();

        $maqraCategoryIds = $participants->pluck('competition_category_id')->filter()->unique()->values();
        $maqraPackageTotalsByCategory = MaqraPackage::query()
            ->where('round_label', $roundLabel)
            ->where('is_active', true)
            ->when($maqraCategoryIds->isNotEmpty(), fn ($query) => $query->whereIn('competition_category_id', $maqraCategoryIds))
            ->selectRaw('competition_category_id, COUNT(*) as total_packages')
            ->groupBy('competition_category_id')
            ->pluck('total_packages', 'competition_category_id')
            ->map(fn ($count): int => (int) $count);
        $maqraUsedPackageTotalsByCategory = ParticipantMaqraDraw::query()
            ->join('maqra_packages', 'maqra_packages.id', '=', 'participant_maqra_draws.maqra_package_id')
            ->where('participant_maqra_draws.round_label', $roundLabel)
            ->when($maqraCategoryIds->isNotEmpty(), fn ($query) => $query->whereIn('maqra_packages.competition_category_id', $maqraCategoryIds))
            ->selectRaw('maqra_packages.competition_category_id as competition_category_id, COUNT(DISTINCT participant_maqra_draws.maqra_package_id) as used_packages')
            ->groupBy('maqra_packages.competition_category_id')
            ->pluck('used_packages', 'competition_category_id')
            ->map(fn ($count): int => (int) $count);
        $maqraRemainingPackagesByCategory = $maqraPackageTotalsByCategory
            ->keys()
            ->merge($maqraUsedPackageTotalsByCategory->keys())
            ->unique()
            ->mapWithKeys(function ($categoryId) use ($maqraPackageTotalsByCategory, $maqraUsedPackageTotalsByCategory): array {
                $total = (int) ($maqraPackageTotalsByCategory[$categoryId] ?? 0);
                $used = (int) ($maqraUsedPackageTotalsByCategory[$categoryId] ?? 0);

                return [$categoryId => max($total - $used, 0)];
            });

        $scopeLabel = match ((string) ($user?->role ?? '')) {
            'admin' => 'Semua Golongan',
            'panitia' => 'Golongan sesuai hak akses',
            default => $district?->name ?? 'Semua Kecamatan',
        };
        $maqraOpenCategoryIds = $this->officialAccessSetting()->maqraOpenCategoryIds();
        $maqraOpenCategoriesSummary = $categories
            ->filter(fn (CompetitionCategory $category): bool => in_array((int) $category->id, $maqraOpenCategoryIds, true))
            ->map(function (CompetitionCategory $category): array {
                $range = $this->officialAccessSetting()->maqraOpenLotRangeForCategory((int) $category->id);

                return [
                    'id' => (int) $category->id,
                    'label' => trim((string) $category->branch.' - '.(string) $category->name),
                    'range_label' => is_array($range)
                        ? sprintf('%03d - %03d', (int) ($range['min'] ?? 0), (int) ($range['max'] ?? 0))
                        : 'Semua lot',
                ];
            })
            ->values();

        return view('pages.pengambilan-maqra-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'navigation' => app(PageController::class)->consoleNavigation((string) auth()->user()?->role, 'participants.maqra.menu'),
            'district' => $district,
            'officialAccessSetting' => $this->officialAccessSetting(),
            'scopeLabel' => $scopeLabel,
            'categories' => $categories,
            'participantsByCategory' => $participantsByCategory,
            'participants' => $participants,
            'selectedCategory' => $selectedCategory,
            'selectedParticipant' => $selectedParticipant,
            'roundLabel' => $roundLabel,
            'maqraOpenCategoriesSummary' => $maqraOpenCategoriesSummary,
            'summaryStats' => [
                'category_total' => $categories->count(),
                'participant_total' => $participants->count(),
                'maqra_total' => $participants->sum(fn (Participant $participant): int => $participant->maqraDraws->count()),
            ],
            'maqraRemainingPackagesByCategory' => $maqraRemainingPackagesByCategory,
            'filters' => [
                'round' => $roundLabel,
                'participant_id' => $selectedParticipant?->id ?? '',
            ],
            'judgeNameDefault' => (string) auth()->user()?->name,
        ]);
    }

    public function lotDraw(Participant $participant): View
    {
        $this->authorizePanitiaLotAccess();
        $participant->loadMissing(['category', 'district']);
        $this->authorizeParticipantLotAccess($participant);
        abort_unless($participant->verification_status === 'verified', 403, 'Layar undian hanya tersedia untuk peserta yang sudah terverifikasi.');

        return view('pages.participant-lot-draw', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'participant' => $participant,
            'lotPrefix' => $this->participantLotPrefix($participant),
            'lotRangeLabel' => app(PageController::class)->categoryLotRangeLabel($participant->category),
            'lotRuleLabel' => app(PageController::class)->categoryLotRuleLabel($participant->category, (string) $participant->gender),
            'lotGroupSize' => app(PageController::class)->categoryLotGroupSize($participant->category, (string) $participant->gender),
            'lotParity' => $participant->gender === 'putra' ? 'even' : 'odd',
            'photoDataUri' => $this->participantPhotoDataUri((string) ($participant->document_photo ?? '')),
            'initials' => $this->participantInitials($participant),
        ]);
    }

    public function assignLotNumber(Request $request, Participant $participant): RedirectResponse|JsonResponse
    {
        $this->authorizePanitiaLotAccess();

        $participant->loadMissing(['category', 'district']);
        $this->authorizeParticipantLotAccess($participant);
        abort_unless($participant->category, 422, 'Kategori peserta belum tersedia untuk mengambil nomor lot.');
        abort_unless(in_array($participant->gender, ['putra', 'putri'], true), 422, 'Jenis kelamin peserta harus putra atau putri untuk mengambil nomor lot.');
        abort_unless($participant->verification_status === 'verified', 403, 'Nomor lot hanya dapat diambil untuk peserta yang sudah terverifikasi.');

        $lotResult = DB::transaction(function () use ($participant): array {
            $lockedParticipant = Participant::query()
                ->with('category')
                ->lockForUpdate()
                ->findOrFail($participant->id);

            if (filled($lockedParticipant->lot_number)) {
                return [
                    'lot_number' => (string) $lockedParticipant->lot_number,
                    'created' => false,
                ];
            }

            CompetitionCategory::query()
                ->whereKey($lockedParticipant->competition_category_id)
                ->lockForUpdate()
                ->firstOrFail();

            $prefix = $this->participantLotPrefix($lockedParticipant);
            $groupSize = $this->participantLotGroupSize($lockedParticipant);
            $sharedLotNumber = $groupSize > 1
                ? $this->existingSharedParticipantLotNumber($lockedParticipant, $groupSize)
                : null;

            if (filled($sharedLotNumber)) {
                $lockedParticipant->update([
                    'lot_number' => $sharedLotNumber,
                    'lot_assigned_at' => now(),
                ]);

                return [
                    'lot_number' => (string) $sharedLotNumber,
                    'created' => false,
                    'shared' => true,
                ];
            }

            [$minSequence, $maxSequence] = app(PageController::class)->categoryLotRange($lockedParticipant->category);
            $nextSequence = $this->nextParticipantLotSequence($lockedParticipant, $prefix, $minSequence, $maxSequence);
            $candidate = sprintf('%s-%03d', $prefix, $nextSequence);

            $lockedParticipant->update([
                'lot_number' => $candidate,
                'lot_assigned_at' => now(),
            ]);

            return [
                'lot_number' => $candidate,
                'created' => true,
            ];
        });
        $lotNumber = (string) $lotResult['lot_number'];

        $this->logParticipantActivity(
            $lotResult['created'] ? 'participant.lot.assigned' : 'participant.lot.reused',
            $participant,
            (auth()->user()?->name ?? 'Panitia').' '.(($lotResult['created'] ?? false) ? 'mengambil' : 'membuka kembali').' nomor lot peserta '.$participant->name.': '.$lotNumber.'.',
            [
                'lot_number' => $lotNumber,
                'created' => (bool) $lotResult['created'],
                'shared' => (bool) ($lotResult['shared'] ?? false),
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Nomor lot berhasil diambil.',
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'lot_number' => $lotNumber,
                'lot_assigned_at' => now()->format('d M Y H:i'),
                'lot_prefix' => $this->participantLotPrefix($participant),
                'lot_range' => app(PageController::class)->categoryLotRangeLabel($participant->category),
                'lot_rule' => app(PageController::class)->categoryLotRuleLabel($participant->category, (string) $participant->gender),
                'district_shared' => $this->participantLotGroupSize($participant) > 1,
                'category_id' => (int) $participant->competition_category_id,
                'district_id' => (int) ($participant->district_id ?? 0),
                'gender' => $participant->gender,
            ]);
        }

        return redirect()
            ->route('participants.lot.draw', $participant)
            ->with('status', 'Nomor lot peserta '.$participant->name.' berhasil diambil: '.$lotNumber.'.');
    }

    public function maqraDraw(Participant $participant): View
    {
        $participant->loadMissing(['category', 'district', 'latestMaqraDraw.maqraPackage', 'maqraDraws.maqraPackage']);
        $roundLabel = $this->maqraRoundFromRequest(request());
        $this->authorizeOfficialMaqraAccess($participant, $roundLabel);
        $this->authorizeParticipantAccess($participant);
        abort_unless($participant->verification_status === 'verified', 403, 'Layar maqra hanya tersedia untuk peserta yang sudah terverifikasi.');
        abort_unless($this->participantUsesMaqra($participant), 403, 'Kategori peserta ini tidak menggunakan pengambilan maqra.');

        $category = $participant->category;

        $candidatePackages = MaqraPackage::query()
            ->where('competition_category_id', $participant->competition_category_id)
            ->where('round_label', $roundLabel)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $currentDraw = $participant->maqraDraws
            ->firstWhere('round_label', $roundLabel);

        if ($currentDraw) {
            $currentDraw->loadMissing('maqraPackage');
        }

        return view('pages.participant-maqra-draw', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'participant' => $participant,
            'maqraRound' => $roundLabel,
            'maqraRoundLabel' => $this->maqraRoundLabel($roundLabel),
            'maqraSystemLabel' => app(PageController::class)->categoryMaqraSystemLabel($category) ?? 'Maqra',
            'maqraCodePrefix' => app(PageController::class)->categoryMaqraCodePrefix($category),
            'maqraCandidates' => $candidatePackages->map(fn (MaqraPackage $package): array => [
                'id' => $package->id,
                'code' => $package->maqra_code,
                'title' => $package->title,
                'content' => $package->content,
            ])->values()->all(),
            'maqraPackage' => $currentDraw?->maqraPackage,
            'maqraDrawnAt' => $currentDraw?->drawn_at,
            'maqraPackageCount' => $candidatePackages->count(),
            'maqraPhotoDataUri' => $this->participantPhotoDataUri((string) ($participant->document_photo ?? '')),
            'initials' => $this->participantInitials($participant),
        ]);
    }

    public function assignMaqra(Request $request, Participant $participant): RedirectResponse|JsonResponse
    {
        $participant->loadMissing(['category', 'district', 'maqraDraws.maqraPackage']);
        $this->authorizeParticipantAccess($participant);
        abort_unless($participant->verification_status === 'verified', 403, 'Maqra hanya dapat diambil untuk peserta yang sudah terverifikasi.');
        abort_unless($this->participantUsesMaqra($participant), 403, 'Kategori peserta ini tidak menggunakan pengambilan maqra.');

        $validated = $request->validate([
            'maqra_round' => ['required', 'in:Penyisihan,Final'],
        ]);

        $roundLabel = (string) $validated['maqra_round'];
        $this->authorizeOfficialMaqraAccess($participant, $roundLabel);
        $drawResult = DB::transaction(function () use ($participant, $roundLabel): array {
            $lockedParticipant = Participant::query()
                ->with(['category', 'maqraDraws.maqraPackage'])
                ->lockForUpdate()
                ->findOrFail($participant->id);

            $sharedParticipants = $this->participantMaqraSharedParticipants($lockedParticipant);
            $sharedParticipantIds = $sharedParticipants->pluck('id')->values();

            $existingDraw = ParticipantMaqraDraw::query()
                ->with('maqraPackage')
                ->where('round_label', $roundLabel)
                ->whereIn('participant_id', $sharedParticipantIds->all())
                ->first();

            if ($existingDraw) {
                $sharedParticipants->each(function (Participant $sharedParticipant) use ($existingDraw, $roundLabel): void {
                    ParticipantMaqraDraw::query()->firstOrCreate(
                        [
                            'participant_id' => $sharedParticipant->id,
                            'round_label' => $roundLabel,
                        ],
                        [
                            'maqra_package_id' => $existingDraw->maqra_package_id,
                            'drawn_at' => $existingDraw->drawn_at ?? now(),
                        ]
                    );
                });

                return [
                    'draw' => $existingDraw,
                    'created' => false,
                    'shared' => $sharedParticipants->count() > 1,
                ];
            }

            $packagePool = MaqraPackage::query()
                ->where('competition_category_id', $lockedParticipant->competition_category_id)
                ->where('round_label', $roundLabel)
                ->where('is_active', true);
            $packagePool = (clone $packagePool)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $unusedPackageIds = ParticipantMaqraDraw::query()
                ->where('round_label', $roundLabel)
                ->whereHas('maqraPackage', fn ($query) => $query->where('competition_category_id', $lockedParticipant->competition_category_id))
                ->pluck('maqra_package_id');

            $availablePackages = $packagePool
                ->reject(fn (MaqraPackage $package): bool => $unusedPackageIds->contains($package->id))
                ->values();

            abort_unless($availablePackages->isNotEmpty(), 422, 'Belum ada data maqra yang belum dipakai untuk golongan dan babak ini.');

            $package = $availablePackages->random();

            $drawnAt = now();
            $sharedParticipants->each(function (Participant $sharedParticipant) use ($package, $roundLabel, $drawnAt): void {
                ParticipantMaqraDraw::query()->create([
                    'participant_id' => $sharedParticipant->id,
                    'maqra_package_id' => $package->id,
                    'round_label' => $roundLabel,
                    'drawn_at' => $drawnAt,
                ]);
            });

            $draw = ParticipantMaqraDraw::query()
                ->with('maqraPackage')
                ->where('participant_id', $lockedParticipant->id)
                ->where('round_label', $roundLabel)
                ->firstOrFail();

            return [
                'draw' => $draw,
                'created' => true,
                'shared' => $sharedParticipants->count() > 1,
            ];
        });
        /** @var ParticipantMaqraDraw $draw */
        $draw = $drawResult['draw'];

        $this->logParticipantActivity(
            $drawResult['created'] ? 'participant.maqra.assigned' : 'participant.maqra.reused',
            $participant,
            (auth()->user()?->name ?? 'Panitia').' '.($drawResult['created'] ? 'mengambil' : 'membuka kembali').' maqra peserta '.$participant->name.' pada babak '.$roundLabel.'.',
            [
                'maqra_round' => $roundLabel,
                'maqra_package_id' => $draw->maqra_package_id,
                'maqra_code' => $draw->maqraPackage?->maqra_code,
                'maqra_title' => $draw->maqraPackage?->title,
                'created' => (bool) $drawResult['created'],
                'shared' => (bool) ($drawResult['shared'] ?? false),
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Maqra berhasil diambil.',
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'maqra_round' => $roundLabel,
                'maqra_round_label' => $this->maqraRoundLabel($roundLabel),
                'maqra_code' => $draw->maqraPackage?->maqra_code,
                'maqra_title' => $draw->maqraPackage?->title,
                'maqra_content' => $draw->maqraPackage?->content,
                'drawn_at' => optional($draw->drawn_at)->format('d M Y H:i'),
                'maqra_prefix' => app(PageController::class)->categoryMaqraCodePrefix($participant->category),
                'system_label' => app(PageController::class)->categoryMaqraSystemLabel($participant->category),
                'district_id' => $participant->district_id,
                'district_shared' => $this->participantMaqraUsesDistrictSharing($participant),
                'category_id' => $participant->competition_category_id,
            ]);
        }

        return redirect()
            ->route('participants.maqra.draw', ['participant' => $participant, 'round' => $roundLabel])
            ->with('status', 'Maqra peserta '.$participant->name.' berhasil diambil.');
    }

    public function resetMaqra(Request $request, Participant $participant): RedirectResponse
    {
        $this->authorizeAdminMaqraManagement();
        $participant->loadMissing(['category', 'district', 'maqraDraws.maqraPackage']);
        abort_unless($participant->verification_status === 'verified', 422, 'Hanya peserta terverifikasi yang dapat direset maqra-nya.');
        abort_unless($this->participantUsesMaqra($participant), 422, 'Kategori peserta ini tidak menggunakan pengambilan maqra.');

        $validated = $request->validate([
            'maqra_round' => ['required', 'in:Penyisihan,Final'],
        ]);

        $roundLabel = (string) $validated['maqra_round'];
        $drawsToDelete = ParticipantMaqraDraw::query()
            ->with('maqraPackage')
            ->where('participant_id', $participant->id)
            ->where('round_label', $roundLabel)
            ->get();
        $deletedCount = ParticipantMaqraDraw::query()
            ->where('participant_id', $participant->id)
            ->where('round_label', $roundLabel)
            ->delete();

        abort_unless($deletedCount > 0, 422, 'Pengambilan maqra untuk babak ini belum ada.');

        $this->logParticipantActivity(
            'participant.maqra.reset',
            $participant,
            (auth()->user()?->name ?? 'Admin').' menghapus maqra peserta '.$participant->name.' pada babak '.$roundLabel.'.',
            [
                'maqra_round' => $roundLabel,
                'deleted_count' => $deletedCount,
                'deleted_packages' => $drawsToDelete
                    ->map(fn (ParticipantMaqraDraw $draw): array => [
                        'id' => $draw->maqra_package_id,
                        'code' => $draw->maqraPackage?->maqra_code,
                        'title' => $draw->maqraPackage?->title,
                    ])
                    ->values()
                    ->all(),
            ]
        );

        return back()->with('status', 'Pengambilan maqra peserta '.$participant->name.' pada babak '.$roundLabel.' berhasil direset.');
    }

    public function swapMaqra(Request $request, Participant $participant): RedirectResponse
    {
        $this->authorizeAdminMaqraManagement();
        $participant->loadMissing(['category', 'district', 'maqraDraws.maqraPackage']);
        abort_unless($participant->verification_status === 'verified', 422, 'Peserta harus terverifikasi sebelum maqra bisa ditukar.');
        abort_unless($this->participantUsesMaqra($participant), 422, 'Kategori peserta ini tidak menggunakan pengambilan maqra.');

        $validated = $request->validate([
            'maqra_round' => ['required', 'in:Penyisihan,Final'],
            'swap_participant_id' => ['required', 'integer', 'exists:participants,id'],
        ]);

        $roundLabel = (string) $validated['maqra_round'];
        $swapParticipant = Participant::query()
            ->with(['category', 'district', 'maqraDraws.maqraPackage'])
            ->findOrFail((int) $validated['swap_participant_id']);

        abort_unless($swapParticipant->id !== $participant->id, 422, 'Peserta tujuan tukar tidak boleh sama.');
        abort_unless($swapParticipant->verification_status === 'verified', 422, 'Peserta tujuan harus terverifikasi.');
        abort_unless($this->participantUsesMaqra($swapParticipant), 422, 'Peserta tujuan tidak menggunakan pengambilan maqra.');
        abort_unless((int) $swapParticipant->competition_category_id === (int) $participant->competition_category_id, 422, 'Tukar maqra hanya bisa dalam golongan yang sama.');
        abort_unless((string) $swapParticipant->gender === (string) $participant->gender, 422, 'Tukar maqra hanya bisa untuk peserta dengan jenis kelamin yang sama.');

        $result = DB::transaction(function () use ($participant, $swapParticipant, $roundLabel): array {
            $participantLocked = Participant::query()
                ->with(['maqraDraws.maqraPackage'])
                ->where('id', $participant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $swapLocked = Participant::query()
                ->with(['maqraDraws.maqraPackage'])
                ->where('id', $swapParticipant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $participantDraw = $participantLocked->maqraDraws->firstWhere('round_label', $roundLabel);
            $swapDraw = $swapLocked->maqraDraws->firstWhere('round_label', $roundLabel);

            abort_unless($participantDraw, 422, 'Peserta pertama belum memiliki maqra pada babak ini.');
            abort_unless($swapDraw, 422, 'Peserta tujuan belum memiliki maqra pada babak ini.');

            $participantPackageId = $participantDraw->maqra_package_id;
            $swapPackageId = $swapDraw->maqra_package_id;
            $participantOldPackage = $participantDraw->maqraPackage;
            $swapOldPackage = $swapDraw->maqraPackage;

            $participantDraw->update(['maqra_package_id' => $swapPackageId, 'drawn_at' => now()]);
            $swapDraw->update(['maqra_package_id' => $participantPackageId, 'drawn_at' => now()]);

            return [
                'first_old' => [
                    'id' => $participantPackageId,
                    'code' => $participantOldPackage?->maqra_code,
                    'title' => $participantOldPackage?->title,
                ],
                'second_old' => [
                    'id' => $swapPackageId,
                    'code' => $swapOldPackage?->maqra_code,
                    'title' => $swapOldPackage?->title,
                ],
                'first' => $participantDraw->refresh()->load('maqraPackage'),
                'second' => $swapDraw->refresh()->load('maqraPackage'),
            ];
        });

        $this->logParticipantActivity(
            'participant.maqra.swapped',
            $participant,
            (auth()->user()?->name ?? 'Admin').' menukar maqra babak '.$roundLabel.' peserta '.$participant->name.' dengan '.$swapParticipant->name.'.',
            [
                'maqra_round' => $roundLabel,
                'first_participant_id' => $participant->id,
                'first_participant_name' => $participant->name,
                'first_old_package' => $result['first_old'],
                'first_new_package' => [
                    'id' => $result['first']->maqra_package_id,
                    'code' => $result['first']->maqraPackage?->maqra_code,
                    'title' => $result['first']->maqraPackage?->title,
                ],
                'second_participant_id' => $swapParticipant->id,
                'second_participant_name' => $swapParticipant->name,
                'second_old_package' => $result['second_old'],
                'second_new_package' => [
                    'id' => $result['second']->maqra_package_id,
                    'code' => $result['second']->maqraPackage?->maqra_code,
                    'title' => $result['second']->maqraPackage?->title,
                ],
            ]
        );

        return back()->with(
            'status',
            'Maqra babak '.$roundLabel.' peserta '.$participant->name.' dan '.$swapParticipant->name.' berhasil ditukar.'
        );
    }

    public function resetLotNumber(Participant $participant): RedirectResponse
    {
        $this->authorizeAdminLotManagement();
        $participant->loadMissing(['category', 'district']);
        abort_unless($participant->verification_status === 'verified', 422, 'Hanya peserta terverifikasi yang dapat di-reset nomor lot-nya.');

        $oldLotNumber = (string) $participant->lot_number;

        $participant->update([
            'lot_number' => null,
            'lot_assigned_at' => null,
        ]);

        $this->logParticipantActivity(
            'participant.lot.reset',
            $participant,
            (auth()->user()?->name ?? 'Admin').' menghapus nomor lot peserta '.$participant->name.' dari '.$oldLotNumber.'.',
            ['old_lot_number' => $oldLotNumber]
        );

        return back()->with('status', 'Nomor lot peserta '.$participant->name.' berhasil direset.');
    }

    public function updateLotNumber(Request $request, Participant $participant): RedirectResponse
    {
        $this->authorizeAdminLotManagement();
        $participant->loadMissing(['category', 'district']);
        abort_unless($participant->verification_status === 'verified', 422, 'Hanya peserta terverifikasi yang dapat diubah nomor lot-nya.');
        abort_unless($participant->category, 422, 'Kategori peserta belum tersedia untuk mengubah nomor lot.');

        $validated = $request->validate([
            'lot_sequence' => ['required', 'integer', 'min:1', 'max:999999'],
        ]);

        [$minSequence, $maxSequence] = app(PageController::class)->categoryLotRange($participant->category);
        $sequence = (int) $validated['lot_sequence'];
        $requiredParity = $participant->gender === 'putra' ? 0 : 1;

        abort_unless($sequence >= $minSequence && $sequence <= $maxSequence, 422, 'Nomor harus berada dalam range lot golongan ini.');
        abort_unless(($sequence % 2) === $requiredParity, 422, 'Nomor harus sesuai aturan genap/ganjil jenis kelamin peserta.');

        $lotNumber = sprintf('%s-%03d', $this->participantLotPrefix($participant), $sequence);
        abort_if(
            Participant::query()->where('id', '!=', $participant->id)->where('lot_number', $lotNumber)->exists(),
            422,
            'Nomor lot tersebut sudah dipakai peserta lain.'
        );

        $oldLotNumber = (string) ($participant->lot_number ?? '');

        $participant->update([
            'lot_number' => $lotNumber,
            'lot_assigned_at' => now(),
        ]);

        $this->logParticipantActivity(
            'participant.lot.updated',
            $participant,
            (auth()->user()?->name ?? 'Admin').' mengubah nomor lot peserta '.$participant->name.' dari '.($oldLotNumber ?: '-').' menjadi '.$lotNumber.'.',
            [
                'old_lot_number' => $oldLotNumber ?: null,
                'new_lot_number' => $lotNumber,
                'lot_sequence' => $sequence,
            ]
        );

        return back()->with('status', 'Nomor lot peserta '.$participant->name.' berhasil diubah menjadi '.$lotNumber.'.');
    }

    public function swapLotNumber(Request $request, Participant $participant): RedirectResponse
    {
        $this->authorizeAdminLotManagement();
        $participant->loadMissing(['category', 'district']);
        abort_unless($participant->verification_status === 'verified', 422, 'Peserta harus terverifikasi sebelum nomor lot bisa ditukar.');
        abort_unless(filled($participant->lot_number), 422, 'Peserta ini belum memiliki nomor lot untuk ditukar.');

        $validated = $request->validate([
            'swap_participant_id' => ['required', 'integer', 'exists:participants,id'],
        ]);

        $swapParticipant = Participant::query()
            ->with(['category', 'district'])
            ->findOrFail((int) $validated['swap_participant_id']);

        abort_unless($swapParticipant->id !== $participant->id, 422, 'Peserta tujuan tukar tidak boleh sama.');
        abort_unless($swapParticipant->verification_status === 'verified', 422, 'Peserta tujuan harus terverifikasi.');
        abort_unless(filled($swapParticipant->lot_number), 422, 'Peserta tujuan belum memiliki nomor lot.');
        abort_unless((int) $swapParticipant->competition_category_id === (int) $participant->competition_category_id, 422, 'Tukar nomor lot hanya bisa dalam golongan yang sama.');
        abort_unless((string) $swapParticipant->gender === (string) $participant->gender, 422, 'Tukar nomor lot hanya bisa untuk peserta dengan jenis kelamin yang sama.');

        $participantOldLot = (string) $participant->lot_number;
        $swapOldLot = (string) $swapParticipant->lot_number;

        DB::transaction(function () use ($participant, $swapParticipant): void {
            $firstId = min($participant->id, $swapParticipant->id);
            $secondId = max($participant->id, $swapParticipant->id);

            $participants = Participant::query()
                ->whereIn('id', [$firstId, $secondId])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            /** @var Participant $first */
            $first = $participants->get($participant->id);
            /** @var Participant $second */
            $second = $participants->get($swapParticipant->id);

            $firstLot = (string) $first->lot_number;
            $secondLot = (string) $second->lot_number;
            $firstAssignedAt = $first->lot_assigned_at;
            $secondAssignedAt = $second->lot_assigned_at;
            $tempLot = 'SWP-'.Str::upper(Str::random(8));

            $first->update([
                'lot_number' => $tempLot,
                'lot_assigned_at' => now(),
            ]);

            $second->update([
                'lot_number' => $firstLot,
                'lot_assigned_at' => $firstAssignedAt,
            ]);

            $first->update([
                'lot_number' => $secondLot,
                'lot_assigned_at' => $secondAssignedAt,
            ]);
        });

        $this->logParticipantActivity(
            'participant.lot.swapped',
            $participant,
            (auth()->user()?->name ?? 'Admin').' menukar nomor lot peserta '.$participant->name.' dengan '.$swapParticipant->name.'.',
            [
                'first_participant_id' => $participant->id,
                'first_participant_name' => $participant->name,
                'first_old_lot_number' => $participantOldLot,
                'first_new_lot_number' => $swapOldLot,
                'second_participant_id' => $swapParticipant->id,
                'second_participant_name' => $swapParticipant->name,
                'second_old_lot_number' => $swapOldLot,
                'second_new_lot_number' => $participantOldLot,
            ]
        );

        return back()->with('status', 'Nomor lot peserta '.$participant->name.' berhasil ditukar dengan '.$swapParticipant->name.'.');
    }

    public function previewTrashedDocument(Request $request, ArchivedParticipant $participant, string $document): StreamedResponse|BinaryFileResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $participant->loadMissing(['category', 'district']);
        $file = $this->resolveDocumentEntry($this->documentMap($participant), $document, (int) $request->query('index', 0));
        abort_unless($file['path'], 404);
        abort_unless(Storage::disk('public')->exists($file['path']), 404);

        return Storage::disk('public')->response($file['path']);
    }

    public function downloadTrashedDocument(Request $request, ArchivedParticipant $participant, string $document): StreamedResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $participant->loadMissing(['category', 'district']);
        $file = $this->resolveDocumentEntry($this->documentMap($participant), $document, (int) $request->query('index', 0));
        abort_unless($file['path'], 404);
        abort_unless(Storage::disk('public')->exists($file['path']), 404);

        return Storage::disk('public')->download($file['path'], basename((string) $file['path']));
    }

    public function verify(Request $request, Participant $participant): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);
        if (auth()->user()?->role === 'panitia' && ! $this->participantVerificationOpen()) {
            abort(403, 'Masa verifikasi peserta untuk panitia sedang ditutup oleh admin.');
        }
        $this->authorizeDistrictVerificationAccess($participant->district);

        $validated = $request->validate([
            'verification_status' => ['required', 'in:verified,rejected,submitted'],
            'verification_notes' => ['nullable', 'string', 'max:1000'],
            'mandate_status' => ['nullable', 'in:verified,rejected,submitted'],
            'mandate_verification_notes' => ['nullable', 'string', 'max:1000'],
            'document_revision_notes' => ['nullable', 'array'],
            'document_revision_notes.kk' => ['nullable', 'string', 'max:500'],
            'document_revision_notes.ktp' => ['nullable', 'string', 'max:500'],
            'document_revision_notes.birth_certificate' => ['nullable', 'string', 'max:500'],
            'document_revision_notes.photo' => ['nullable', 'string', 'max:500'],
            'document_revision_notes.last_diploma' => ['nullable', 'string', 'max:500'],
            'document_revision_notes.bank_book' => ['nullable', 'string', 'max:500'],
            'document_revision_notes.certificates' => ['nullable', 'string', 'max:500'],
            'document_revision_notes.other_files' => ['nullable', 'string', 'max:500'],
        ]);

        $documentRevisionNotes = collect($validated['document_revision_notes'] ?? [])
            ->map(fn ($value) => filled($value) ? trim((string) $value) : null)
            ->all();

        if ($validated['verification_status'] === 'verified') {
            $documentRevisionNotes = [];
        }

        $districtMandate = District::query()
            ->whereKey($participant->district_id)
            ->whereNotNull('mandate_document_path')
            ->first();

        $participant->update([
            'verification_status' => $validated['verification_status'],
            'verification_notes' => $validated['verification_notes'] ?: match ($validated['verification_status']) {
                'verified' => 'Berkas peserta telah diverifikasi dan dinyatakan lengkap.',
                'rejected' => 'Berkas peserta perlu diperbaiki sesuai catatan verifikasi.',
                default => 'Peserta dikembalikan ke status menunggu verifikasi.',
            },
            'document_revision_notes' => $documentRevisionNotes,
        ]);

        ParticipantVerificationLog::query()->create([
            'participant_id' => $participant->id,
            'verified_by' => auth()->id(),
            'status' => $participant->verification_status,
            'notes' => $participant->verification_notes,
        ]);

        RealtimeBroadcaster::dispatch(new ParticipantVerificationUpdated($participant));

        if ($participant->verification_status !== 'verified') {
            $this->notifyDistrictOfficialsAboutParticipantVerification($participant);
        }

        $this->logParticipantActivity(
            'participant.'.$participant->verification_status,
            $participant,
            (auth()->user()?->name ?? 'Panitia').' memperbarui status verifikasi peserta '.$participant->name.' menjadi '.$participant->verification_status.'.',
            [
                'verification_status' => $participant->verification_status,
                'verification_notes' => $participant->verification_notes,
                'document_revision_notes' => $documentRevisionNotes,
            ]
        );

        if ($districtMandate && filled($validated['mandate_status'] ?? null)) {
            $districtMandate->update([
                'mandate_status' => $validated['mandate_status'],
                'mandate_verification_notes' => $validated['mandate_verification_notes'] ?: match ($validated['mandate_status']) {
                    'verified' => 'Surat mandat kecamatan telah diverifikasi bersama data peserta.',
                    'rejected' => 'Surat mandat perlu diperbaiki atau diupload ulang.',
                    default => 'Surat mandat dikembalikan ke status menunggu pemeriksaan.',
                },
                'mandate_verified_by' => auth()->id(),
                'mandate_verified_at' => now(),
            ]);

            ActivityLogger::log(
                'mandate.'.$validated['mandate_status'],
                (auth()->user()?->name ?? 'Panitia').' memperbarui surat mandat Kecamatan '.$districtMandate->name.' bersamaan dengan verifikasi peserta '.$participant->name.'.',
                $districtMandate,
                [
                    'district_id' => $districtMandate->id,
                    'district_name' => $districtMandate->name,
                    'mandate_status' => $validated['mandate_status'],
                    'notes' => $districtMandate->mandate_verification_notes,
                    'participant_id' => $participant->id,
                    'participant_name' => $participant->name,
                    'registration_number' => $participant->registration_number,
                ]
            );
        }

        return redirect()
            ->route('participants.list', $request->query())
            ->with('status', 'Status verifikasi peserta '.$participant->name.' diperbarui menjadi '.ucfirst($validated['verification_status']).'.');
    }

    protected function logParticipantActivity(string $action, Participant|ArchivedParticipant $participant, string $description, array $properties = []): void
    {
        $participant->loadMissing(['district', 'category']);

        ActivityLogger::log(
            $action,
            $description,
            $participant,
            array_merge($this->participantLogContext($participant), $properties)
        );
    }

    protected function participantLogContext(Participant|ArchivedParticipant $participant): array
    {
        return [
            'participant_id' => $participant->id,
            'participant_name' => $participant->name,
            'registration_number' => $participant->registration_number,
            'district_id' => $participant->district_id,
            'district_name' => $participant->district?->name,
            'category_id' => $participant->competition_category_id,
            'category_label' => trim((string) ($participant->category?->branch ?? '').' - '.(string) ($participant->category?->name ?? '')),
            'verification_status' => $participant->verification_status,
        ];
    }

    protected function authorizeParticipantAccess(Participant $participant): void
    {
        $user = auth()->user();

        if (in_array($user?->role, ['official', 'pendamping'], true)) {
            abort_unless((int) $user?->district_id === (int) $participant->district_id, 403);
        }

        if ($user?->role === 'panitia') {
            $this->authorizeDistrictVerificationAccess($participant->district);
        }
    }

    protected function authorizeAdminLotManagement(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    protected function authorizeAdminMaqraManagement(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    protected function authorizePanitiaLotAccess(): void
    {
        $user = auth()->user();

        abort_unless(in_array($user?->role, ['admin', 'panitia'], true), 403);

        if ($user?->role === 'panitia' && ! $this->officialFeatureEnabled('participant_lot_open')) {
            abort(403, 'Masa ambil nomor lot untuk panitia sedang ditutup oleh admin.');
        }
    }

    protected function authorizeParticipantLotAccess(Participant $participant): void
    {
        $user = auth()->user();

        if ($user?->role !== 'panitia') {
            return;
        }

        $accessibleCategoryIds = $this->accessibleCategoryIdsForLotUser($user) ?? [];

        abort_unless(
            in_array((int) $participant->competition_category_id, $accessibleCategoryIds, true),
            403,
            'Akun panitia ini tidak memiliki hak akses golongan untuk pengambilan lot pada peserta tersebut.'
        );
    }

    protected function authorizeParticipantDetailAccess(Participant $participant): void
    {
        $user = auth()->user();

        if (in_array($user?->role, ['admin'], true)) {
            return;
        }

        if (in_array($user?->role, ['official', 'pendamping'], true)) {
            abort_unless((int) $user?->district_id === (int) $participant->district_id, 403);

            return;
        }

        if ($user?->role === 'panitia') {
            return;
        }

        abort(403);
    }

    protected function authorizeOfficialMaqraAccess(Participant $participant, ?string $roundLabel = null): void
    {
        $user = auth()->user();

        abort_unless(in_array($user?->role, ['admin', 'official', 'pendamping'], true), 403);

        if ($user?->role !== 'admin') {
            abort_unless((int) $user->district_id === (int) $participant->district_id, 403);
        }

        $round = in_array($roundLabel, ['Penyisihan', 'Final'], true) ? $roundLabel : $this->maqraRoundFromRequest(request());

        if ($user?->role !== 'admin' && ! $this->officialMaqraRoundEnabled($round)) {
            abort(403, sprintf('Masa ambil maqra babak %s untuk official sedang ditutup oleh admin.', $round));
        }

        $allowedCategoryIds = $this->allowedMaqraCategoryIdsForUser($user);

        if ($user?->role !== 'admin' && (! is_array($allowedCategoryIds) || ! in_array((int) $participant->competition_category_id, $allowedCategoryIds, true))) {
            abort(403, 'Golongan peserta ini belum dibuka untuk pengambilan maqra oleh admin.');
        }

        if ($user?->role !== 'admin') {
            $maqraLotRange = $this->officialAccessSetting()->maqraOpenLotRangeForCategory((int) $participant->competition_category_id);
            if (is_array($maqraLotRange)) {
                $participantLotSequence = $this->participantLotSequenceNumber($participant);
                $lotMin = (int) ($maqraLotRange['min'] ?? 0);
                $lotMax = (int) ($maqraLotRange['max'] ?? 0);

                abort_unless(
                    filled($participantLotSequence) && $participantLotSequence >= $lotMin && $participantLotSequence <= $lotMax,
                    403,
                    sprintf(
                        'Pengambilan maqra untuk nomor lot %s belum dibuka oleh admin.',
                        $participant->lot_number ?: '-'
                    )
                );
            }
        }
    }

    protected function authorizeParticipantCvAccess(Participant $participant): void
    {
        $user = auth()->user();

        abort_unless($user, 403);
        abort_unless($participant->verification_status === 'verified', 403, 'CV hanya tersedia untuk peserta yang sudah terverifikasi.');

        if (in_array($user->role, ['admin', 'panitia'], true)) {
            return;
        }

        if (in_array($user->role, ['official', 'pendamping'], true)) {
            abort_unless((int) $user->district_id === (int) $participant->district_id, 403);

            return;
        }

        if ($user->role === 'peserta') {
            abort_unless((string) $user->nomor_induk === (string) $participant->nik, 403);

            return;
        }

        abort(403);
    }

    protected function participantCvDownloadUrl(?Participant $participant): ?string
    {
        if (! $participant || $participant->verification_status !== 'verified') {
            return null;
        }

        return route('participants.cv', $participant);
    }

    protected function participantUsesMaqra(?Participant $participant): bool
    {
        return $participant?->category ? app(PageController::class)->categoryUsesMaqra($participant->category) : false;
    }

    protected function participantMaqraUsesDistrictSharing(?Participant $participant): bool
    {
        return $participant?->category ? app(PageController::class)->categoryMaqraUsesDistrictSharing($participant->category) : false;
    }

    protected function participantMaqraSharedParticipants(Participant $participant)
    {
        if (! $this->participantMaqraUsesDistrictSharing($participant) || ! filled($participant->district_id)) {
            return collect([$participant]);
        }

        return Participant::query()
            ->with(['category', 'district', 'maqraDraws.maqraPackage'])
            ->where('competition_category_id', $participant->competition_category_id)
            ->where('district_id', $participant->district_id)
            ->where('gender', $participant->gender)
            ->where('verification_status', 'verified')
            ->where('participant_role', 'main')
            ->orderBy('name')
            ->get()
            ->filter(fn (Participant $candidate): bool => $this->participantUsesMaqra($candidate))
            ->values();
    }

    protected function allowedMaqraCategoryIdsForUser($user): ?array
    {
        if (! $user) {
            return [];
        }

        if ($user->role === 'admin') {
            return null;
        }

        $openCategoryIds = $this->officialAccessSetting()->maqraOpenCategoryIds();

        if (in_array($user->role, ['official', 'pendamping'], true)) {
            return $openCategoryIds;
        }

        if ($user->role === 'panitia') {
            $accessibleCategoryIds = $this->accessibleCategoryIdsForUser($user);

            if ($accessibleCategoryIds === [] || $openCategoryIds === []) {
                return [];
            }

            return array_values(array_intersect($accessibleCategoryIds, $openCategoryIds));
        }

        return [];
    }

    protected function canUserDrawLot(): bool
    {
        $role = (string) auth()->user()?->role;

        return in_array($role, ['admin', 'panitia'], true)
            && ($role === 'admin' || $this->officialAccessSetting()->isEnabled('participant_lot_open'));
    }

    protected function canUserDrawMaqra(?Participant $participant = null): bool
    {
        $role = (string) auth()->user()?->role;

        if (! in_array($role, ['admin', 'official', 'pendamping'], true)) {
            return false;
        }

        if ($role !== 'admin' && ! $this->officialAccessSetting()->maqraAnyRoundEnabled()) {
            return false;
        }

        $allowedCategoryIds = $this->allowedMaqraCategoryIdsForUser(auth()->user());

        if ($role === 'admin') {
            return true;
        }

        if ($participant) {
            return (int) (auth()->user()?->district_id ?? 0) === (int) $participant->district_id
                && $this->participantUsesMaqra($participant)
                && in_array((int) $participant->competition_category_id, is_array($allowedCategoryIds) ? $allowedCategoryIds : [], true);
        }

        return is_array($allowedCategoryIds) && $allowedCategoryIds !== [] && (bool) auth()->user()?->district_id;
    }

    protected function officialMaqraRoundEnabled(string $roundLabel): bool
    {
        $round = in_array($roundLabel, ['Penyisihan', 'Final'], true) ? $roundLabel : 'Penyisihan';

        return $this->officialAccessSetting()->maqraRoundEnabled($round);
    }

    protected function maqraRoundFromRequest(Request $request): string
    {
        $round = (string) $request->query('round', 'Penyisihan');

        return in_array($round, ['Penyisihan', 'Final'], true) ? $round : 'Penyisihan';
    }

    protected function maqraRoundLabel(string $round): string
    {
        return match ($round) {
            'Final' => 'Final',
            default => 'Penyisihan',
        };
    }

    protected function participantLotPrefix(Participant $participant): string
    {
        return app(PageController::class)->categoryLotPrefix($participant->category);
    }

    protected function participantLotSequenceNumber(?Participant $participant): ?int
    {
        if (! $participant || ! filled($participant->lot_number)) {
            return null;
        }

        if (! preg_match('/-(\d+)$/', (string) $participant->lot_number, $matches)) {
            return null;
        }

        $sequence = (int) $matches[1];

        return $sequence > 0 ? $sequence : null;
    }

    protected function participantLotGroupSize(Participant $participant): int
    {
        if (! $participant->category) {
            return 1;
        }

        return app(PageController::class)->categoryLotGroupSize($participant->category, (string) $participant->gender);
    }

    protected function existingSharedParticipantLotNumber(Participant $participant, int $groupSize): ?string
    {
        if ($groupSize <= 1 || ! $participant->category || ! filled($participant->district_id) || ! filled($participant->gender)) {
            return null;
        }

        $sharedParticipants = Participant::query()
            ->where('competition_category_id', $participant->competition_category_id)
            ->where('district_id', $participant->district_id)
            ->where('gender', $participant->gender)
            ->whereNotNull('lot_number')
            ->where('id', '!=', $participant->id)
            ->orderBy('lot_assigned_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'lot_number', 'lot_assigned_at']);

        if ($sharedParticipants->isEmpty()) {
            return null;
        }

        $currentChunk = $sharedParticipants->chunk($groupSize)->last();

        if (! $currentChunk || $currentChunk->count() >= $groupSize) {
            return null;
        }

        return (string) ($currentChunk->first()->lot_number ?? '');
    }

    protected function nextParticipantLotSequence(Participant $participant, string $prefix, int $minSequence, int $maxSequence): int
    {
        $requiredParity = $participant->gender === 'putra' ? 0 : 1;

        $existingNumbers = Participant::query()
            ->where('competition_category_id', $participant->competition_category_id)
            ->whereNotNull('lot_number')
            ->where('id', '!=', $participant->id)
            ->where('lot_number', 'like', $prefix.'-%')
            ->lockForUpdate()
            ->pluck('lot_number')
            ->map(function (?string $lotNumber) use ($prefix): ?int {
                if (! $lotNumber || ! str_starts_with($lotNumber, $prefix.'-')) {
                    return null;
                }

                $suffix = (int) substr($lotNumber, strlen($prefix) + 1);

                return $suffix > 0 ? $suffix : null;
            })
            ->filter(fn ($value): bool => is_int($value) && $value > 0)
            ->values();

        $availableNumbers = [];

        for ($candidate = $minSequence; $candidate <= $maxSequence; $candidate++) {
            if (($candidate % 2) !== $requiredParity) {
                continue;
            }

            if ($existingNumbers->contains($candidate)) {
                continue;
            }

            if (Participant::query()
                ->where('id', '!=', $participant->id)
                ->where('lot_number', sprintf('%s-%03d', $prefix, $candidate))
                ->exists()
            ) {
                continue;
            }

            $availableNumbers[] = $candidate;
        }

        if ($availableNumbers === []) {
            abort(422, 'Tidak ada nomor lot tersisa pada range yang diatur untuk golongan ini.');
        }

        shuffle($availableNumbers);

        return (int) $availableNumbers[0];
    }

    protected function participantCvPayload(Participant $participant): array
    {
        $documentMap = $this->documentMap($participant);
        $verificationLog = $participant->verificationLogs->first();
        $verifiedAt = $verificationLog?->created_at ?? $participant->updated_at;
        $verifiedBy = $verificationLog?->verifier?->name ?? 'Panitia e-MTQ';

        $documentRows = [
            ['label' => 'Kartu Keluarga', 'available' => filled($documentMap['kk']['path'] ?? null), 'note' => $documentMap['kk']['revision_note'] ?? null],
            ['label' => 'KTP', 'available' => filled($documentMap['ktp']['path'] ?? null), 'note' => $documentMap['ktp']['revision_note'] ?? null],
            ['label' => 'Akta Kelahiran', 'available' => filled($documentMap['birth_certificate']['path'] ?? null), 'note' => $documentMap['birth_certificate']['revision_note'] ?? null],
            ['label' => 'Pas Foto', 'available' => filled($documentMap['photo']['path'] ?? null), 'note' => $documentMap['photo']['revision_note'] ?? null],
            ['label' => 'Ijazah Terakhir', 'available' => filled($documentMap['last_diploma']['path'] ?? null), 'note' => $documentMap['last_diploma']['revision_note'] ?? null],
            ['label' => 'Buku Tabungan', 'available' => filled($documentMap['bank_book']['path'] ?? null), 'note' => $documentMap['bank_book']['revision_note'] ?? null],
            ['label' => 'Piagam', 'available' => collect($documentMap['certificates']['files'] ?? [])->filter()->isNotEmpty(), 'note' => $documentMap['certificates']['revision_note'] ?? null, 'count' => collect($documentMap['certificates']['files'] ?? [])->filter()->count()],
            ['label' => 'Dokumen Lainnya', 'available' => collect($documentMap['other_files']['files'] ?? [])->filter()->isNotEmpty(), 'note' => $documentMap['other_files']['revision_note'] ?? null, 'count' => collect($documentMap['other_files']['files'] ?? [])->filter()->count()],
        ];

        return [
            'documentConfig' => app(PageController::class)->documentConfig(),
            'participant' => $participant,
            'documentRows' => $documentRows,
            'photoDataUri' => $this->participantPhotoDataUri((string) ($participant->document_photo ?? '')),
            'initials' => $this->participantInitials($participant),
            'verifiedAt' => $verifiedAt,
            'verifiedBy' => $verifiedBy,
            'documentComplete' => collect($documentRows)->where('available', true)->count(),
            'documentTotal' => count($documentRows),
            'ageLabel' => $participant->date_of_birth
                ? $participant->date_of_birth->diff(now())->y.' tahun '.$participant->date_of_birth->diff(now())->m.' bulan'
                : '-',
            'verifiedStatusLabel' => 'Terverifikasi',
            'roleLabel' => ($participant->participant_role ?? 'main') === 'reserve' ? 'Peserta Cadangan' : 'Peserta Inti',
            'branchLabel' => trim((string) ($participant->category?->branch ?? '-').' | '.(string) ($participant->category?->name ?? '-')),
            'districtLabel' => $participant->district?->name ?? '-',
            'verificationNotes' => $participant->verification_notes ?: 'Tidak ada catatan verifikasi.',
            'summaryRows' => [
                ['label' => 'Nomor Registrasi', 'value' => (string) ($participant->registration_number ?? '-')],
                ['label' => 'NIK', 'value' => (string) ($participant->nik ?? '-')],
                ['label' => 'Tempat, Tgl Lahir', 'value' => trim((string) ($participant->place_of_birth ?? '-').', '.optional($participant->date_of_birth)->format('d M Y'))],
                ['label' => 'No. HP', 'value' => (string) ($participant->phone ?? '-')],
                ['label' => 'Pendidikan', 'value' => (string) ($participant->last_education ?? '-')],
                ['label' => 'Institusi', 'value' => (string) ($participant->institution ?? '-')],
                ['label' => 'Alamat Saat Ini', 'value' => (string) ($participant->current_address ?? '-')],
                ['label' => 'Alamat KTP', 'value' => trim((string) ($participant->ktp_address ?? '-').' | '.(string) ($participant->ktp_district ?? '-').' | '.(string) ($participant->ktp_regency ?? '-'))],
            ],
        ];
    }

    protected function participantPhotoDataUri(string $path): ?string
    {
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($path);

        if (! is_file($absolutePath)) {
            return null;
        }

        $mimeType = mime_content_type($absolutePath) ?: 'image/jpeg';
        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            return null;
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    protected function participantInitials(Participant $participant): string
    {
        $parts = preg_split('/\s+/', trim((string) $participant->name)) ?: [];
        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'P';
    }

    protected function authorizeParticipantEdit(Participant $participant): void
    {
        $user = auth()->user();

        if (in_array($user?->role, ['official', 'pendamping'], true)) {
            $district = District::query()->find($user?->district_id);

            if ($district?->mandate_status === 'rejected') {
                abort(403, 'Data peserta tidak dapat diedit karena surat mandat kecamatan sedang ditolak. Upload ulang surat mandat terlebih dahulu.');
            }
        }

        if (
            in_array($user?->role, ['official', 'pendamping'], true)
            && in_array($participant->verification_status, ['submitted', 'verified'], true)
        ) {
            abort(403, 'Data peserta yang sudah dikirim ke verifikasi tidak dapat diedit lagi oleh official.');
        }
    }

    protected function authorizeParticipantDelete(Participant $participant): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($user->role === 'admin') {
            return;
        }

        if (! $this->participantDeletionOpen()) {
            abort(403, 'Akses hapus peserta untuk official dan panitia sedang ditutup.');
        }

        if ($user->role === 'panitia') {
            return;
        }

        if (! in_array($user->role, ['official', 'pendamping'], true)) {
            abort(403);
        }

        if (! in_array($participant->verification_status, ['submitted', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'participant' => 'Peserta hanya dapat diarsipkan jika statusnya masih menunggu atau ditolak.',
            ]);
        }
    }

    protected function officialMandateContext($user): array
    {
        $required = in_array($user?->role, ['official', 'pendamping'], true);
        $district = $required ? District::query()->find($user?->district_id) : null;
        $mandateState = $this->officialMandateState($user, $district);
        $path = (string) ($mandateState['path'] ?? '');
        $status = (string) ($mandateState['status'] ?? (filled($path) ? 'submitted' : 'missing'));

        return [
            'required' => $required,
            'ready' => ! $required || (filled($path) && $status !== 'rejected'),
            'can_manage_participants' => $required,
            'path' => $path,
            'status' => $status,
            'district_name' => $district?->name,
            'notes' => $mandateState['notes'] ?? null,
            'uploaded_at' => $mandateState['uploaded_at'] ?? null,
            'verified_at' => $mandateState['verified_at'] ?? null,
            'preview_url' => $required && filled($path) ? route('participants.mandate.preview') : null,
        ];
    }

    protected function officialMandateState(?User $user, ?District $district = null): array
    {
        $districtHasMandateColumns = Schema::hasTable('districts')
            && Schema::hasColumn('districts', 'mandate_document_path');
        $userHasMandateColumns = Schema::hasTable('users')
            && Schema::hasColumn('users', 'mandate_document_path');

        $scope = 'none';
        $source = null;

        if ($district && $districtHasMandateColumns) {
            $scope = 'district';
            $source = $district;
        } elseif ($userHasMandateColumns && $user) {
            $scope = 'user';
            $source = $user;
        }

        $path = (string) ($source?->mandate_document_path ?? '');
        $status = (string) ($source?->mandate_status ?? (filled($path) ? 'submitted' : 'missing'));

        return [
            'scope' => $scope,
            'path' => $path,
            'status' => $status,
            'notes' => $source?->mandate_verification_notes,
            'uploaded_at' => $source?->mandate_uploaded_at,
            'verified_at' => $source?->mandate_verified_at,
        ];
    }

    protected function districtMandateForParticipant(Participant $participant): ?District
    {
        if (! in_array(auth()->user()?->role, ['admin', 'panitia'], true)) {
            return null;
        }

        return District::query()
            ->with(['mandateVerifier'])
            ->whereKey($participant->district_id)
            ->whereNotNull('mandate_document_path')
            ->first();
    }

    protected function validateParticipantForm(Request $request, bool $isCreate, ?Participant $participant = null): array
    {
        $isDraft = (string) $request->input('submit_action') === 'draft';
        $nikRules = [$isDraft ? 'nullable' : 'required', 'string', 'max:32'];
        if ($participant) {
            $nikRules[] = Rule::unique('participants', 'nik')->ignore($participant->id)->withoutTrashed();
        } else {
            $nikRules[] = Rule::unique('participants', 'nik')->withoutTrashed();
        }

        $underSeventeen = $this->participantIsUnderSeventeen((string) $request->input('date_of_birth'));
        $requiredOrNullable = $isDraft ? 'nullable' : 'required';
        $requiredOrNullableWhenAdult = $isDraft ? 'nullable' : ($underSeventeen ? 'nullable' : 'required');
        $existingDocumentExists = static function (?Participant $participant, string $column): bool {
            return filled($participant?->{$column});
        };
        $requiredOrNullableForFile = static function (string $column) use ($isDraft, $participant, $existingDocumentExists): string {
            if ($isDraft || $existingDocumentExists($participant, $column)) {
                return 'nullable';
            }

            return 'required';
        };
        $requiredOrNullableForAdultFile = static function (string $column) use ($isDraft, $underSeventeen, $participant, $existingDocumentExists): string {
            if ($isDraft || $underSeventeen || $existingDocumentExists($participant, $column)) {
                return 'nullable';
            }

            return 'required';
        };

        return $request->validate([
            'district_id' => ['nullable', 'exists:districts,id'],
            'competition_category_id' => ['required', 'exists:competition_categories,id'],
            'participant_role' => ['required', 'in:main,reserve'],
            'name' => [$requiredOrNullable, 'string', 'max:255'],
            'gender' => [$requiredOrNullable, 'in:putra,putri'],
            'nik' => $nikRules,
            'ktp_date' => [$requiredOrNullableWhenAdult, 'date'],
            'place_of_birth' => [$requiredOrNullable, 'string', 'max:255'],
            'date_of_birth' => [$requiredOrNullable, 'date'],
            'kk_number' => [$requiredOrNullable, 'string', 'max:32'],
            'kk_date' => [$requiredOrNullable, 'date'],
            'phone' => [$requiredOrNullable, 'string', 'max:30'],
            'institution' => [$requiredOrNullable, 'string', 'max:255'],
            'last_education' => [$requiredOrNullable, 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:60'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'current_address' => [$requiredOrNullable, 'string', 'max:1000'],
            'ktp_address' => [$requiredOrNullable, 'string', 'max:1000'],
            'ktp_district' => [$requiredOrNullable, 'string', 'max:255'],
            'ktp_regency' => [$requiredOrNullable, 'string', 'max:255'],
            'kk_document' => [$requiredOrNullableForFile('document_kk'), 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'ktp_document' => [$requiredOrNullableForAdultFile('document_ktp'), 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'birth_certificate_document' => [$requiredOrNullableForFile('document_birth_certificate'), 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'photo_document' => [$requiredOrNullableForFile('document_photo'), 'image', 'mimes:jpg,jpeg,png', 'max:2048', 'dimensions:min_width=300,min_height=400,ratio=3/4'],
            'last_diploma_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'bank_book_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'certificate_documents' => ['nullable', 'array'],
            'certificate_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'other_documents' => ['nullable', 'array'],
            'other_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'submit_action' => ['required', 'in:draft,submitted'],
        ], [
            'district_id.exists' => 'Kecamatan yang dipilih tidak ditemukan.',
            'competition_category_id.required' => 'Pilih golongan peserta terlebih dahulu.',
            'competition_category_id.exists' => 'Golongan peserta yang dipilih tidak ditemukan.',
            'participant_role.required' => 'Status peserta wajib dipilih.',
            'name.required' => 'Nama lengkap peserta wajib diisi.',
            'gender.required' => 'Jenis kelamin peserta wajib dipilih.',
            'nik.required' => 'NIK peserta wajib diisi.',
            'nik.unique' => 'NIK tersebut sudah terdaftar pada peserta lain.',
            'ktp_date.date' => 'Tanggal KTP tidak valid.',
            'place_of_birth.required' => 'Tempat lahir peserta wajib diisi.',
            'date_of_birth.required' => 'Tanggal lahir peserta wajib diisi.',
            'date_of_birth.date' => 'Tanggal lahir tidak valid.',
            'kk_number.required' => 'Nomor KK peserta wajib diisi.',
            'kk_date.required' => 'Tanggal KK peserta wajib diisi.',
            'kk_date.date' => 'Tanggal KK tidak valid.',
            'phone.required' => 'Nomor HP peserta wajib diisi.',
            'institution.required' => 'Asal lembaga peserta wajib diisi.',
            'last_education.required' => 'Pendidikan terakhir peserta wajib diisi.',
            'current_address.required' => 'Alamat saat ini peserta wajib diisi.',
            'ktp_address.required' => 'Alamat sesuai KTP wajib diisi.',
            'ktp_district.required' => 'Kecamatan KTP wajib diisi.',
            'ktp_regency.required' => 'Kabupaten KTP wajib diisi.',
            'kk_document.required' => 'Dokumen KK wajib diunggah.',
            'kk_document.file' => 'Dokumen KK harus berupa file yang sah.',
            'kk_document.mimes' => 'Dokumen KK harus berformat PDF, JPG, JPEG, atau PNG.',
            'ktp_document.required' => 'Dokumen KTP wajib diunggah.',
            'ktp_document.file' => 'Dokumen KTP harus berupa file yang sah.',
            'ktp_document.mimes' => 'Dokumen KTP harus berformat PDF, JPG, JPEG, atau PNG.',
            'birth_certificate_document.required' => 'Akta kelahiran wajib diunggah.',
            'birth_certificate_document.file' => 'Akta kelahiran harus berupa file yang sah.',
            'birth_certificate_document.mimes' => 'Akta kelahiran harus berformat PDF, JPG, JPEG, atau PNG.',
            'photo_document.required' => 'Pas foto wajib diunggah.',
            'photo_document.image' => 'Pas foto harus berupa gambar.',
            'photo_document.mimes' => 'Pas foto harus berformat JPG, JPEG, atau PNG.',
            'photo_document.max' => 'Pas foto maksimal berukuran 2 MB.',
            'photo_document.dimensions' => 'Pas foto harus memiliki rasio 3:4 dengan ukuran minimal 300 x 400 piksel.',
            'last_diploma_document.file' => 'Ijazah terakhir harus berupa file yang sah.',
            'last_diploma_document.mimes' => 'Ijazah terakhir harus berformat PDF, JPG, JPEG, atau PNG.',
            'bank_book_document.file' => 'Buku tabungan harus berupa file yang sah.',
            'bank_book_document.mimes' => 'Buku tabungan harus berformat PDF, JPG, JPEG, atau PNG.',
            'certificate_documents.array' => 'Format dokumen piagam tidak valid.',
            'certificate_documents.*.file' => 'Setiap piagam harus berupa file yang sah.',
            'certificate_documents.*.mimes' => 'Piagam harus berformat PDF, JPG, JPEG, atau PNG.',
            'other_documents.array' => 'Format dokumen lainnya tidak valid.',
            'other_documents.*.file' => 'Setiap dokumen lainnya harus berupa file yang sah.',
            'other_documents.*.mimes' => 'Dokumen lainnya harus berformat PDF, JPG, JPEG, atau PNG.',
            'submit_action.required' => 'Pilih apakah data akan disimpan sebagai draf atau dikirim untuk verifikasi.',
        ]);
    }

    protected function participantIsUnderSeventeen(string $dateOfBirth): bool
    {
        try {
            $birthDate = Carbon::parse($dateOfBirth)->startOfDay();
        } catch (Throwable) {
            return false;
        }

        $referenceDate = Carbon::create(2026, 7, 1)->startOfDay();

        if ($birthDate->greaterThan($referenceDate)) {
            return false;
        }

        return $birthDate->diffInYears($referenceDate) < 17;
    }

    protected function ensureParticipantAgeMatchesCategory(int $categoryId, string $dateOfBirth): void
    {
        $category = CompetitionCategory::query()->findOrFail($categoryId);
        $rules = $this->ageRulesFromRequirement((string) $category->age_requirement);

        if (! $rules['min'] && ! $rules['max']) {
            return;
        }

        $referenceDate = Carbon::create(2026, 7, 1)->startOfDay();
        $birthDate = Carbon::parse($dateOfBirth)->startOfDay();

        if ($birthDate->greaterThan($referenceDate)) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'Tanggal lahir tidak boleh melebihi tanggal acuan 1 Juli 2026.',
            ]);
        }

        $age = $birthDate->diff($referenceDate);

        if ($rules['min'] && $this->compareAgeParts($age, $rules['min']) < 0) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'Usia peserta per 1 Juli 2026 belum memenuhi batas minimal '.$this->formatAgeRule($rules['min']).' untuk golongan '.$category->name.'.',
            ]);
        }

        if ($rules['max'] && $this->compareAgeParts($age, $rules['max']) > 0) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'Usia peserta per 1 Juli 2026 melebihi batas maksimal '.$this->formatAgeRule($rules['max']).' untuk golongan '.$category->name.'.',
            ]);
        }
    }

    protected function ageRulesFromRequirement(string $requirement): array
    {
        $requirement = mb_strtolower($requirement);
        $min = $this->extractAgeRule($requirement, 'minimal');
        $max = str_contains($requirement, 'maksimal')
            ? $this->extractAgeRule($requirement, 'maksimal')
            : $this->extractAgeRule($requirement);

        return [
            'min' => $min ?: ($max ? ['years' => 1, 'months' => 0, 'days' => 0] : null),
            'max' => $max,
        ];
    }

    protected function extractAgeRule(string $requirement, ?string $marker = null): ?array
    {
        if ($marker) {
            if (! preg_match('/'.preg_quote($marker, '/').'\s+(\d+)\s*tahun(?:\s+(\d+)\s*bulan)?(?:\s+(\d+)\s*hari)?/u', $requirement, $matches)) {
                return null;
            }

            return [
                'years' => (int) ($matches[1] ?? 0),
                'months' => (int) ($matches[2] ?? 0),
                'days' => (int) ($matches[3] ?? 0),
            ];
        }

        if (! preg_match('/(\d+)\s*tahun(?:\s+(\d+)\s*bulan)?(?:\s+(\d+)\s*hari)?/u', $requirement, $matches)) {
            return null;
        }

        return [
            'years' => (int) ($matches[1] ?? 0),
            'months' => (int) ($matches[2] ?? 0),
            'days' => (int) ($matches[3] ?? 0),
        ];
    }

    protected function compareAgeParts(\DateInterval $age, array $rule): int
    {
        foreach ([['y', 'years'], ['m', 'months'], ['d', 'days']] as [$property, $key]) {
            $actual = (int) $age->{$property};
            $expected = (int) ($rule[$key] ?? 0);

            if ($actual > $expected) {
                return 1;
            }

            if ($actual < $expected) {
                return -1;
            }
        }

        return 0;
    }

    protected function formatAgeRule(array $rule): string
    {
        return collect([
            $rule['years'] ? $rule['years'].' tahun' : null,
            $rule['months'] ? $rule['months'].' bulan' : null,
            $rule['days'] ? $rule['days'].' hari' : null,
        ])->filter()->implode(' ');
    }

    protected function normalizeDistrictLabel(?string $value): string
    {
        $normalized = mb_strtolower(trim((string) $value));
        $normalized = preg_replace('/^kecamatan\s+/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    protected function hostDistrictName(): string
    {
        return $this->normalizeDistrictLabel((string) config('juknis.host', ''));
    }

    protected function officialAccessSetting(): OfficialAccessSetting
    {
        return OfficialAccessSetting::currentOrDefault();
    }

    protected function registrationDeadlineOpen(): bool
    {
        $closeAt = $this->registrationDeadlineCloseAt();

        if (! $closeAt) {
            return true;
        }

        return Carbon::now('Asia/Bangkok')->lte($closeAt);
    }

    protected function participantVerificationOpen(): bool
    {
        return $this->officialAccessSetting()->isEnabled('participant_verification_open');
    }

    protected function participantDeletionOpen(): bool
    {
        if (! $this->officialAccessSetting()->isEnabled('participant_delete_open')) {
            return false;
        }

        if (in_array(auth()->user()?->role, ['official', 'pendamping'], true)) {
            return $this->registrationDeadlineOpen();
        }

        return true;
    }

    protected function officialFeatureEnabled(string $feature): bool
    {
        if (in_array(auth()->user()?->role, ['admin', 'panitia'], true)) {
            return true;
        }

        if (! $this->officialAccessSetting()->isEnabled($feature)) {
            return false;
        }

        if (! in_array(auth()->user()?->role, ['official', 'pendamping'], true)) {
            return false;
        }

        if (in_array($feature, ['participant_registration_open', 'participant_edit_open'], true)) {
            return $this->registrationDeadlineOpen();
        }

        return true;
    }

    protected function assertOfficialFeatureEnabled(string $feature, string $message): void
    {
        if (in_array(auth()->user()?->role, ['official', 'pendamping'], true) && ! $this->officialFeatureEnabled($feature)) {
            abort(403, $message);
        }
    }

    protected function canUserVerifyParticipants(): bool
    {
        $role = auth()->user()?->role;

        return $role === 'admin' || ($role === 'panitia' && $this->participantVerificationOpen());
    }

    protected function canUserVerifyParticipant(Participant $participant): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role !== 'panitia' || ! $this->participantVerificationOpen()) {
            return false;
        }

        return $this->districtVerificationAccessAllowed($participant->district, $user);
    }

    protected function isDistrictQuotaCategory(CompetitionCategory $category): bool
    {
        return str_contains(mb_strtolower((string) $category->notes), 'kk');
    }

    protected function districtMatchesHost(?District $district): bool
    {
        if (! $district) {
            return false;
        }

        return $this->normalizeDistrictLabel($district->name) === $this->hostDistrictName();
    }

    protected function registrationDeadlineCloseAt(): ?Carbon
    {
        $registration = (array) config('juknis.registration', []);
        $closeAt = $this->parseIndonesianDate((string) ($registration['close'] ?? ''), true);

        return $closeAt?->timezone('Asia/Bangkok');
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

    protected function categoryDistrictQuotaMultiplier(CompetitionCategory $category, ?District $district = null): int
    {
        if (! $this->isDistrictQuotaCategory($category)) {
            return 1;
        }

        return 1;
    }

    protected function categoryAvailableSlotsForDistrict(CompetitionCategory $category, ?int $districtId): int
    {
        if (! $districtId) {
            return (int) $category->quota;
        }

        $district = District::query()->find($districtId);

        return (int) $category->quota * $this->categoryDistrictQuotaMultiplier($category, $district);
    }

    protected function categoryAvailableSlotsAcrossDistricts(CompetitionCategory $category, iterable $districts): int
    {
        if (! $this->isDistrictQuotaCategory($category)) {
            return (int) $category->quota;
        }

        $total = 0;

        foreach ($districts as $district) {
            $total += (int) $category->quota * $this->categoryDistrictQuotaMultiplier($category, $district);
        }

        return $total;
    }

    protected function ensureCategoryCapacity(int $categoryId, ?int $districtId = null, string $gender = 'putra', ?int $ignoreParticipantId = null, string $participantRole = 'main'): void
    {
        $category = CompetitionCategory::query()->findOrFail($categoryId);
        $isDistrictBased = $this->isDistrictQuotaCategory($category);
        $genderRule = $this->genderQuotaRule($category);
        $district = $districtId ? District::query()->find($districtId) : null;
        $quotaMultiplier = $this->categoryDistrictQuotaMultiplier($category, $district);

        if ($isDistrictBased && ! $districtId) {
            throw ValidationException::withMessages([
                'district_id' => 'Kategori ini wajib dikaitkan ke kecamatan terlebih dahulu.',
            ]);
        }

        $registeredQuery = Participant::query()
            ->where('competition_category_id', $categoryId)
            ->when($ignoreParticipantId, fn ($query) => $query->where('id', '!=', $ignoreParticipantId));

        if ($isDistrictBased) {
            $registeredQuery->where('district_id', $districtId);
        }

        $capacity = (int) $category->quota * $quotaMultiplier;

        if ($participantRole === 'reserve') {
            $reserveRegistered = (clone $registeredQuery)
                ->where('participant_role', 'reserve')
                ->count();

            if ($reserveRegistered >= $capacity) {
                $scopeLabel = $isDistrictBased ? 'pada kecamatan yang dipilih' : 'pada tingkat kabupaten';

                throw ValidationException::withMessages([
                    'participant_role' => 'Kuota peserta cadangan untuk kategori '.$category->name.' sudah penuh '.$scopeLabel.'. Maksimal cadangan adalah '.$capacity.' peserta.',
                ]);
            }

            return;
        }

        $registered = (clone $registeredQuery)
            ->where(function ($query): void {
                $query->whereNull('participant_role')
                    ->orWhere('participant_role', 'main');
            })
            ->count();

        if ($registered >= $capacity) {
            $scopeLabel = $isDistrictBased ? 'pada kecamatan yang dipilih' : 'pada tingkat kabupaten';

            throw ValidationException::withMessages([
                'competition_category_id' => 'Slot kategori '.$category->name.' sudah penuh '.$scopeLabel.'.',
            ]);
        }

        if ($genderRule === 'paired_two') {
            $limitPerGender = 1 * $quotaMultiplier;
            $genderRegistered = (clone $registeredQuery)
                ->where(function ($query): void {
                    $query->whereNull('participant_role')
                        ->orWhere('participant_role', 'main');
                })
                ->where('gender', $gender)
                ->count();

            if ($genderRegistered >= $limitPerGender) {
                $genderLabel = $gender === 'putra' ? 'putra' : 'putri';
                $scopeLabel = $isDistrictBased ? 'untuk kecamatan yang dipilih' : 'pada tingkat kabupaten';
                $limitText = $limitPerGender === 1
                    ? '1 putra + 1 putri'
                    : $limitPerGender.' putra + '.$limitPerGender.' putri';

                throw ValidationException::withMessages([
                    'gender' => 'Kuota '.$category->name.' untuk peserta '.$genderLabel.' sudah terisi '.$scopeLabel.'. Aturan kategori ini adalah '.$limitText.'.',
                ]);
            }
        }

        if ($genderRule === 'putra_two') {
            if ($gender !== 'putra') {
                throw ValidationException::withMessages([
                    'gender' => 'Golongan '.$category->name.' hanya menerima peserta putra.',
                ]);
            }

            $limit = 2 * $quotaMultiplier;
            $putraRegistered = (clone $registeredQuery)
                ->where(function ($query): void {
                    $query->whereNull('participant_role')
                        ->orWhere('participant_role', 'main');
                })
                ->where('gender', 'putra')
                ->count();

            if ($putraRegistered >= $limit) {
                $scopeLabel = $isDistrictBased ? 'untuk kecamatan yang dipilih' : 'pada tingkat kabupaten';

                throw ValidationException::withMessages([
                    'gender' => 'Kuota '.$category->name.' untuk peserta putra sudah terisi '.$limit.' orang '.$scopeLabel.'.',
                ]);
            }
        }

        if ($genderRule === 'putra_three') {
            if ($gender !== 'putra') {
                throw ValidationException::withMessages([
                    'gender' => 'Golongan '.$category->name.' hanya menerima peserta putra.',
                ]);
            }

            $limit = 3 * $quotaMultiplier;
            $putraRegistered = (clone $registeredQuery)
                ->where(function ($query): void {
                    $query->whereNull('participant_role')
                        ->orWhere('participant_role', 'main');
                })
                ->where('gender', 'putra')
                ->count();

            if ($putraRegistered >= $limit) {
                $scopeLabel = $isDistrictBased ? 'untuk kecamatan yang dipilih' : 'pada tingkat kabupaten';

                throw ValidationException::withMessages([
                    'gender' => 'Kuota '.$category->name.' untuk peserta putra sudah terisi '.$limit.' orang '.$scopeLabel.'.',
                ]);
            }
        }

        if ($genderRule === 'putri_three') {
            if ($gender !== 'putri') {
                throw ValidationException::withMessages([
                    'gender' => 'Golongan '.$category->name.' hanya menerima peserta putri.',
                ]);
            }

            $limit = 3 * $quotaMultiplier;
            $putriRegistered = (clone $registeredQuery)
                ->where(function ($query): void {
                    $query->whereNull('participant_role')
                        ->orWhere('participant_role', 'main');
                })
                ->where('gender', 'putri')
                ->count();

            if ($putriRegistered >= $limit) {
                $scopeLabel = $isDistrictBased ? 'untuk kecamatan yang dipilih' : 'pada tingkat kabupaten';

                throw ValidationException::withMessages([
                    'gender' => 'Kuota '.$category->name.' untuk peserta putri sudah terisi '.$limit.' orang '.$scopeLabel.'.',
                ]);
            }
        }
    }

    protected function genderQuotaRule(CompetitionCategory $category): ?string
    {
        $branch = mb_strtolower((string) $category->branch);
        $name = mb_strtolower((string) $category->name);

        if (str_contains($branch, 'khutbah') && str_contains($branch, 'adzan')) {
            return 'putra_two';
        }

        if (
            (str_contains($branch, 'fahmil') || str_contains($branch, 'syarhil'))
            && str_contains($name, 'putra')
            && (int) $category->quota === 3
        ) {
            return 'putra_three';
        }

        if (
            (str_contains($branch, 'fahmil') || str_contains($branch, 'syarhil'))
            && str_contains($name, 'putri')
            && (int) $category->quota === 3
        ) {
            return 'putri_three';
        }

        if (str_contains($name, 'putra') || str_contains($name, 'putri')) {
            return null;
        }

        if ((int) $category->quota === 2) {
            return 'paired_two';
        }

        return null;
    }

    protected function participantListFilters(Request $request): array
    {
        return $request->validate([
            'district_id' => ['nullable', 'integer'],
            'competition_category_id' => ['nullable', 'integer'],
            'verification_status' => ['nullable', 'in:draft,submitted,verified,rejected'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'in:name,registration_number,district,category,verification_status,nik,lot_number,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);
    }

    protected function participantListQuery(array $filters, ?int $districtId = null, array $allowedDistrictIds = [])
    {
        return Participant::query()
            ->with(['category', 'district'])
            ->when($allowedDistrictIds !== [], fn ($query) => $query->whereIn('district_id', $allowedDistrictIds))
            ->when($districtId, fn ($query) => $query->where('district_id', $districtId))
            ->when(! $districtId && filled($filters['district_id'] ?? null), fn ($query) => $query->where('district_id', $filters['district_id']))
            ->when(filled($filters['competition_category_id'] ?? null), fn ($query) => $query->where('competition_category_id', $filters['competition_category_id']))
            ->when(filled($filters['verification_status'] ?? null), fn ($query) => $query->where('verification_status', $filters['verification_status']))
            ->when(filled($filters['keyword'] ?? null), function ($query) use ($filters) {
                $keyword = trim((string) $filters['keyword']);

                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('registration_number', 'like', '%'.$keyword.'%')
                        ->orWhere('nik', 'like', '%'.$keyword.'%')
                        ->orWhere('institution', 'like', '%'.$keyword.'%');
                });
            });
    }

    protected function participantListSortColumn(string $sort): string
    {
        return match ($sort) {
            'name' => 'name',
            'registration_number' => 'registration_number',
            'district' => 'district',
            'category' => 'category',
            'verification_status' => 'verification_status',
            'nik' => 'nik',
            'lot_number' => 'lot_number',
            'created_at' => 'created_at',
            default => 'created_at',
        };
    }

    protected function participantListSortDirection(string $direction): string
    {
        return $direction === 'asc' ? 'asc' : 'desc';
    }

    protected function participantListSortParticipants(\Illuminate\Support\Collection $participants, string $sortColumn, string $direction): \Illuminate\Support\Collection
    {
        $descending = $direction === 'desc';

        return $participants->sortBy(function (Participant $participant) use ($sortColumn) {
            return match ($sortColumn) {
                'registration_number' => Str::lower((string) ($participant->registration_number ?? '')),
                'district' => Str::lower((string) ($participant->district?->name ?? '')),
                'category' => (function () use ($participant): string {
                    $categoryLabel = trim((string) ($participant->category?->branch ?? ''));
                    $categoryLabel = $categoryLabel !== ''
                        ? trim($categoryLabel.' '.(string) ($participant->category?->name ?? ''))
                        : (string) ($participant->category?->name ?? '');

                    return Str::lower($categoryLabel);
                })(),
                'verification_status' => match ((string) ($participant->verification_status ?? 'draft')) {
                    'draft' => 1,
                    'submitted' => 2,
                    'verified' => 3,
                    'rejected' => 4,
                    default => 5,
                },
                'nik' => Str::lower((string) ($participant->nik ?? '')),
                'lot_number' => (int) ($participant->lot_number ?? PHP_INT_MAX),
                'created_at' => $participant->created_at?->timestamp ?? 0,
                default => Str::lower((string) ($participant->name ?? '')),
            };
        }, SORT_NATURAL | SORT_FLAG_CASE, $descending)->values();
    }

    protected function participantExportContext(Request $request): array
    {
        $user = auth()->user();
        $districtId = in_array($user?->role, ['official', 'pendamping'], true) ? $user?->district_id : null;
        $verificationDistrictIds = $this->verificationDistrictIdsForUser($user);
        $restrictPanitiaDistricts = $user?->role === 'panitia';
        $filters = $this->participantListFilters($request);
        $participants = $this->participantListQuery($filters, $districtId, $restrictPanitiaDistricts ? $verificationDistrictIds : [])
            ->orderBy('district_id')
            ->orderBy('competition_category_id')
            ->orderBy('name')
            ->get();

        $selectedDistrictId = $districtId ?: (filled($filters['district_id'] ?? null) ? (int) $filters['district_id'] : ($restrictPanitiaDistricts && count($verificationDistrictIds) === 1 ? $verificationDistrictIds[0] : null));
        $selectedDistrict = $selectedDistrictId
            ? District::query()->find($selectedDistrictId)
            : null;

        return [$filters, $districtId, $participants, $selectedDistrict];
    }

    protected function verificationDistrictIdsForUser($user): array
    {
        if (! $user || $user->role !== 'panitia') {
            return [];
        }

        return $user->accessibleDistrictIds();
    }

    protected function authorizeDistrictVerificationAccess(?District $district): void
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'panitia') {
            return;
        }

        $allowedDistrictIds = $this->verificationDistrictIdsForUser($user);

        if (! $district || ! in_array((int) $district->id, $allowedDistrictIds, true)) {
            abort(403, 'Akun panitia ini tidak memiliki hak akses verifikator untuk kecamatan tersebut.');
        }
    }

    protected function districtVerificationAccessAllowed(?District $district, $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (! $user || $user->role !== 'panitia' || ! $district) {
            return false;
        }

        $allowedDistrictIds = $this->verificationDistrictIdsForUser($user);

        return in_array((int) $district->id, $allowedDistrictIds, true);
    }

    protected function participantExportRows($participants): array
    {
        $referenceDate = $this->parseIndonesianDate((string) config('juknis.age_reference_date', '1 Juli 2026')) ?? Carbon::create(2026, 7, 1)->startOfDay();

        return $participants->map(function (Participant $participant) use ($referenceDate): array {
            $documentMap = $this->documentMap($participant);

            return [
                'registration_number' => (string) ($participant->registration_number ?? '-'),
                'role_label' => ($participant->participant_role ?? 'main') === 'reserve' ? 'Cadangan' : 'Inti',
                'verification_status' => $this->participantVerificationStatusLabel((string) $participant->verification_status),
                'name' => (string) ($participant->name ?? '-'),
                'gender' => (string) ($participant->gender ?? '-'),
                'nik' => (string) ($participant->nik ?? '-'),
                'place_of_birth' => (string) ($participant->place_of_birth ?? '-'),
                'date_of_birth' => optional($participant->date_of_birth)->format('d/m/Y') ?: '-',
                'age_per_reference' => $this->participantAgeAtReference($participant->date_of_birth, $referenceDate),
                'phone' => (string) ($participant->phone ?? '-'),
                'district' => (string) ($participant->district?->name ?? '-'),
                'branch' => (string) ($participant->category?->branch ?? '-'),
                'category' => (string) ($participant->category?->name ?? '-'),
                'institution' => (string) ($participant->institution ?? '-'),
                'last_education' => (string) ($participant->last_education ?? '-'),
                'ktp_date' => optional($participant->ktp_date)->format('d/m/Y') ?: '-',
                'kk_number' => (string) ($participant->kk_number ?? '-'),
                'kk_date' => optional($participant->kk_date)->format('d/m/Y') ?: '-',
                'bank_name' => (string) ($participant->bank_name ?? '-'),
                'bank_account_number' => (string) ($participant->bank_account_number ?? '-'),
                'bank_account_name' => (string) ($participant->bank_account_name ?? '-'),
                'current_address' => (string) ($participant->current_address ?? '-'),
                'ktp_address' => (string) ($participant->ktp_address ?? '-'),
                'ktp_district' => (string) ($participant->ktp_district ?? '-'),
                'ktp_regency' => (string) ($participant->ktp_regency ?? '-'),
                'verification_notes' => (string) ($participant->verification_notes ?? '-'),
                'documents' => [
                    'kk' => $this->documentChecklistMark($documentMap['kk']['path'] ?? null),
                    'ktp' => $this->documentChecklistMark($documentMap['ktp']['path'] ?? null),
                    'birth_certificate' => $this->documentChecklistMark($documentMap['birth_certificate']['path'] ?? null),
                    'photo' => $this->documentChecklistMark($documentMap['photo']['path'] ?? null),
                    'last_diploma' => $this->documentChecklistMark($documentMap['last_diploma']['path'] ?? null),
                    'bank_book' => $this->documentChecklistMark($documentMap['bank_book']['path'] ?? null),
                    'certificates' => $this->documentChecklistMark($documentMap['certificates']['files'] ?? []),
                    'other_files' => $this->documentChecklistMark($documentMap['other_files']['files'] ?? []),
                ],
            ];
        })->all();
    }

    protected function participantAgeAtReference(mixed $dateOfBirth, Carbon $referenceDate): string
    {
        try {
            $birthDate = $dateOfBirth instanceof Carbon
                ? $dateOfBirth->copy()->startOfDay()
                : Carbon::parse((string) $dateOfBirth, 'Asia/Bangkok')->startOfDay();
        } catch (\Throwable) {
            return '-';
        }

        if ($birthDate->greaterThan($referenceDate)) {
            return '-';
        }

        $age = $birthDate->diff($referenceDate);
        $parts = collect([
            $age->y > 0 ? $age->y.' th' : null,
            $age->m > 0 ? $age->m.' bl' : null,
            $age->d > 0 ? $age->d.' hr' : null,
        ])->filter()->values();

        return $parts->isNotEmpty() ? $parts->implode(' ') : '0 hr';
    }

    protected function participantExportSummary($participants): array
    {
        return [
            'total' => $participants->count(),
            'main_total' => $participants->filter(fn ($participant) => ($participant->participant_role ?? 'main') !== 'reserve')->count(),
            'reserve_total' => $participants->filter(fn ($participant) => ($participant->participant_role ?? 'main') === 'reserve')->count(),
            'verified' => $participants->where('verification_status', 'verified')->count(),
            'pending' => $participants->where('verification_status', 'submitted')->count(),
            'draft' => $participants->where('verification_status', 'draft')->count(),
            'rejected' => $participants->where('verification_status', 'rejected')->count(),
        ];
    }

    protected function participantVerificationStatusLabel(string $status): string
    {
        return match ($status) {
            'verified' => 'Terverifikasi',
            'submitted' => 'Menunggu',
            'rejected' => 'Ditolak',
            default => 'Draft',
        };
    }

    protected function notifyDistrictOfficialsAboutParticipantVerification(Participant $participant): void
    {
        $participant->loadMissing(['district', 'category']);

        $district = District::query()
            ->with(['users' => fn ($query) => $query->whereIn('role', ['official', 'pendamping'])->orderBy('name')])
            ->find($participant->district_id);

        if (! $district) {
            return;
        }

        $recipients = $district->users
            ->filter(fn (User $user): bool => filled($user->phone))
            ->unique(fn (User $user): string => preg_replace('/\D+/', '', (string) $user->phone))
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $statusLabel = $this->participantVerificationStatusLabel((string) $participant->verification_status);
        $categoryLabel = trim((string) ($participant->category?->branch ?? '').' - '.(string) ($participant->category?->name ?? ''));
        $verificationNotes = filled($participant->verification_notes)
            ? (string) $participant->verification_notes
            : 'Tidak ada catatan tambahan.';

        $message = match ((string) $participant->verification_status) {
            'submitted' => $this->buildParticipantVerificationPendingMessage($participant, $district, $statusLabel, $categoryLabel, $verificationNotes),
            'rejected' => $this->buildParticipantVerificationRejectedMessage($participant, $district, $statusLabel, $categoryLabel, $verificationNotes),
            default => $this->buildParticipantVerificationGenericMessage($participant, $district, $statusLabel, $categoryLabel, $verificationNotes),
        };

        $recipients->each(function (User $recipient) use ($message): void {
            WhatsAppRegistrationSender::sendCustomMessage((string) $recipient->phone, $message);
        });
    }

    protected function buildParticipantVerificationPendingMessage(Participant $participant, District $district, string $statusLabel, string $categoryLabel, string $verificationNotes): string
    {
        return implode("\n", [
            "\u{1F7E1} *Info Verifikasi Peserta e-MTQ*",
            '',
            'Peserta berikut masih memerlukan tindak lanjut berkas.',
            '',
            '━━━━━━━━━━━━',
            "\u{1F464} *Data Peserta*",
            '• Nama: '.($participant->name ?: '-'),
            '• No. Registrasi: '.($participant->registration_number ?: '-'),
            '• Kecamatan: '.($participant->district?->name ?: $district->name ?: '-'),
            '• Kategori: '.($categoryLabel !== '' ? $categoryLabel : '-'),
            '',
            '━━━━━━━━━━━━',
            "\u{23F3} *Status Saat Ini*",
            '• Status: *'.$statusLabel.'*',
            '• Catatan: '.$verificationNotes,
            '',
            '━━━━━━━━━━━━',
            "\u{1F9ED} *Tindak Lanjut*",
            'Mohon cek berkas peserta dan lengkapi/perbaiki data yang masih perlu diproses.',
            '',
            'Terima kasih.',
        ]);
    }

    protected function buildParticipantVerificationRejectedMessage(Participant $participant, District $district, string $statusLabel, string $categoryLabel, string $verificationNotes): string
    {
        return implode("\n", [
            "\u{1F534} *Pemberitahuan Verifikasi Peserta e-MTQ*",
            '',
            'Berkas peserta berikut ditandai perlu perbaikan.',
            '',
            '━━━━━━━━━━━━',
            "\u{1F464} *Data Peserta*",
            '• Nama: '.($participant->name ?: '-'),
            '• No. Registrasi: '.($participant->registration_number ?: '-'),
            '• Kecamatan: '.($participant->district?->name ?: $district->name ?: '-'),
            '• Kategori: '.($categoryLabel !== '' ? $categoryLabel : '-'),
            '',
            '━━━━━━━━━━━━',
            "\u{1F4C4} *Status Verifikasi*",
            '• Status: *'.$statusLabel.'*',
            '• Catatan: '.$verificationNotes,
            '',
            '━━━━━━━━━━━━',
            "\u{1F6E0}\u{FE0F} *Tindak Lanjut*",
            'Mohon koordinasikan perbaikan berkas kepada pihak terkait sebelum peserta diajukan kembali.',
            '',
            'Terima kasih.',
        ]);
    }

    protected function buildParticipantVerificationGenericMessage(Participant $participant, District $district, string $statusLabel, string $categoryLabel, string $verificationNotes): string
    {
        return implode("\n", [
            "\u{1F4E3} *Notifikasi Verifikasi Peserta e-MTQ*",
            '',
            'Ada pembaruan status verifikasi yang perlu ditindaklanjuti.',
            '',
            '━━━━━━━━━━━━',
            "\u{1F464} *Data Peserta*",
            '• Nama: '.($participant->name ?: '-'),
            '• No. Registrasi: '.($participant->registration_number ?: '-'),
            '• Kecamatan: '.($participant->district?->name ?: $district->name ?: '-'),
            '• Kategori: '.($categoryLabel !== '' ? $categoryLabel : '-'),
            '',
            '━━━━━━━━━━━━',
            "\u{1F4C4} *Status Verifikasi*",
            '• Status: *'.$statusLabel.'*',
            '• Catatan: '.$verificationNotes,
            '',
            'Silakan cek detail peserta di aplikasi e-MTQ.',
        ]);
    }

    protected function documentChecklistMark(array|string|null $value): string
    {
        if (is_array($value)) {
            return collect($value)->filter()->isNotEmpty() ? 'âœ“' : '-';
        }

        return filled($value) ? 'âœ“' : '-';
    }

    protected function archivedParticipantPayload(Participant $participant): array
    {
        return array_merge($this->participantBasePayload($participant), [
            'source_participant_id' => $participant->id,
            'archived_by' => auth()->id(),
            'archived_at' => now(),
        ]);
    }

    protected function archivedParticipantPayloadToActive(ArchivedParticipant $participant): array
    {
        return array_merge($this->participantBasePayload($participant), [
            'status' => 'active',
        ]);
    }

    protected function participantBasePayload(Participant|ArchivedParticipant $participant): array
    {
        return [
            'district_id' => $participant->district_id ?: null,
            'competition_category_id' => $participant->competition_category_id,
            'registration_number' => $participant->registration_number,
            'participant_role' => $participant->participant_role,
            'name' => $participant->name,
            'gender' => $participant->gender,
            'nik' => $participant->nik,
            'ktp_date' => $participant->ktp_date,
            'place_of_birth' => $participant->place_of_birth,
            'date_of_birth' => $participant->date_of_birth,
            'kk_number' => $participant->kk_number,
            'kk_date' => $participant->kk_date,
            'phone' => $participant->phone,
            'institution' => $participant->institution,
            'last_education' => $participant->last_education,
            'bank_name' => $participant->bank_name,
            'bank_account_number' => $participant->bank_account_number,
            'bank_account_name' => $participant->bank_account_name,
            'current_address' => $participant->current_address,
            'ktp_address' => $participant->ktp_address,
            'ktp_district' => $participant->ktp_district,
            'ktp_regency' => $participant->ktp_regency,
            'region' => $participant->region,
            'avatar' => $participant->avatar,
            'document_kk' => $participant->document_kk,
            'document_ktp' => $participant->document_ktp,
            'document_birth_certificate' => $participant->document_birth_certificate,
            'document_photo' => $participant->document_photo,
            'document_last_diploma' => $participant->document_last_diploma,
            'document_bank_book' => $participant->document_bank_book,
            'document_certificates' => $participant->document_certificates ?? [],
            'document_other_files' => $participant->document_other_files ?? [],
            'status' => $participant->status ?? 'active',
            'verification_status' => $participant->verification_status ?? 'draft',
            'lot_number' => $participant->lot_number,
            'lot_assigned_at' => $participant->lot_assigned_at,
            'verification_notes' => $participant->verification_notes,
            'document_revision_notes' => $participant->document_revision_notes ?? [],
        ];
    }

    protected function deleteArchivedParticipantFiles(ArchivedParticipant $participant): void
    {
        $this->deleteStoredFile($participant->avatar);

        $documentMap = $this->documentMap($participant);

        foreach ($documentMap as $entry) {
            if ($entry['multiple'] ?? false) {
                $this->deleteStoredFiles($entry['files'] ?? []);
                continue;
            }

            $this->deleteStoredFile($entry['path'] ?? null);
        }
    }

    protected function documentMap(Participant|ArchivedParticipant $participant): array
    {
        $revisionNotes = $participant->document_revision_notes ?? [];

        return [
            'kk' => [
                'label' => 'Kartu Keluarga',
                'path' => $participant->document_kk,
                'files' => [],
                'multiple' => false,
                'revision_note' => $revisionNotes['kk'] ?? null,
            ],
            'ktp' => [
                'label' => 'KTP',
                'path' => $participant->document_ktp,
                'files' => [],
                'multiple' => false,
                'revision_note' => $revisionNotes['ktp'] ?? null,
            ],
            'birth_certificate' => [
                'label' => 'Akta Kelahiran',
                'path' => $participant->document_birth_certificate,
                'files' => [],
                'multiple' => false,
                'revision_note' => $revisionNotes['birth_certificate'] ?? null,
            ],
            'photo' => [
                'label' => 'Pas Foto',
                'path' => $participant->document_photo,
                'files' => [],
                'multiple' => false,
                'revision_note' => $revisionNotes['photo'] ?? null,
            ],
            'last_diploma' => [
                'label' => 'Ijazah Terakhir',
                'path' => $participant->document_last_diploma,
                'files' => [],
                'multiple' => false,
                'revision_note' => $revisionNotes['last_diploma'] ?? null,
            ],
            'bank_book' => [
                'label' => 'Buku Tabungan',
                'path' => $participant->document_bank_book,
                'files' => [],
                'multiple' => false,
                'revision_note' => $revisionNotes['bank_book'] ?? null,
            ],
            'certificates' => [
                'label' => 'Piagam',
                'path' => null,
                'files' => collect($participant->document_certificates ?? [])->filter()->values()->all(),
                'multiple' => true,
                'revision_note' => $revisionNotes['certificates'] ?? null,
            ],
            'other_files' => [
                'label' => 'Dokumen Lainnya',
                'path' => null,
                'files' => collect($participant->document_other_files ?? [])->filter()->values()->all(),
                'multiple' => true,
                'revision_note' => $revisionNotes['other_files'] ?? null,
            ],
        ];
    }

    protected function storeUploadedFile(Request $request, string $input, string $directory): ?string
    {
        return $request->file($input)?->store($directory, 'public');
    }

    protected function storeUploadedFiles(Request $request, string $input, string $directory): array
    {
        return collect($request->file($input, []))
            ->filter()
            ->map(fn ($file) => $file->store($directory, 'public'))
            ->filter()
            ->values()
            ->all();
    }

    protected function deleteStoredFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function deleteStoredFiles(array|string|null $paths): void
    {
        collect(is_array($paths) ? $paths : [$paths])
            ->filter()
            ->each(fn ($path) => $this->deleteStoredFile((string) $path));
    }

    protected function resolveDocumentEntry(array $documentMap, string $document, int $index = 0): array
    {
        abort_unless(isset($documentMap[$document]), 404);

        $entry = $documentMap[$document];

        if (! ($entry['multiple'] ?? false)) {
            return $entry;
        }

        $files = collect($entry['files'] ?? [])->values();
        abort_unless($files->has($index), 404);

        return [
            'label' => $entry['label'].' #'.($index + 1),
            'path' => $files->get($index),
            'files' => [],
            'multiple' => false,
            'revision_note' => $entry['revision_note'] ?? null,
        ];
    }
}
