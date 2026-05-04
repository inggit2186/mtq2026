<?php

namespace App\Http\Controllers;

use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\User;
use App\Support\WhatsAppRegistrationSender;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommitteeRegistrationController extends Controller
{
    private const SILATAR_NIP_API = 'https://ptsp.kemenagtanahdatar.cloud/api/v1/nip/';

    public function index(): View
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $committees = User::query()
            ->with(['categoryAccesses.category', 'districtAccesses.district'])
            ->where('role', 'panitia')
            ->orderBy('name')
            ->get();

        return view('pages/committees-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'committees' => $committees,
            'categoryOptions' => CompetitionCategory::query()
                ->orderBy('sort_order')
                ->orderBy('branch')
                ->orderBy('name')
                ->get(['id', 'branch', 'name']),
            'districtOptions' => District::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'committeeStats' => [
                'total' => $committees->count(),
                'with_category_access' => $committees->filter(fn (User $user): bool => $user->categoryAccesses->isNotEmpty())->count(),
                'category_scope_total' => $committees->flatMap(fn (User $user) => $user->categoryAccesses->pluck('competition_category_id'))->unique()->count(),
                'with_district_access' => $committees->filter(fn (User $user): bool => $user->districtAccesses->isNotEmpty())->count(),
                'district_scope_total' => $committees->flatMap(fn (User $user) => $user->districtAccesses->pluck('district_id'))->unique()->count(),
            ],
            'generatedCredentials' => session('generated_credentials'),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'nip' => ['required', 'string', 'max:32'],
        ]);

        $nip = preg_replace('/\D+/', '', (string) $validated['nip']) ?: '';

        if ($nip === '') {
            return response()->json(['message' => 'NIP pegawai hanya boleh berisi angka.'], 422);
        }

        $employee = $this->fetchSilatarEmployee($nip);

        if (! $employee) {
            return response()->json(['message' => 'Data pegawai SILATAR untuk NIP tersebut tidak ditemukan atau belum dapat diakses.'], 404);
        }

        return response()->json([
            'preview' => $this->buildPreviewPayload($employee, $nip),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'nip' => ['required', 'string', 'max:32'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['required', 'integer', 'exists:competition_categories,id'],
            'district_ids' => ['nullable', 'array'],
            'district_ids.*' => ['required', 'integer', 'exists:districts,id'],
        ]);

        $nip = preg_replace('/\D+/', '', (string) $validated['nip']) ?: '';

        if ($nip === '') {
            return back()->withInput()->withErrors(['nip' => 'NIP pegawai hanya boleh berisi angka.']);
        }

        $employee = $this->fetchSilatarEmployee($nip);

        if (! $employee) {
            return back()->withInput()->withErrors(['nip' => 'Data pegawai SILATAR untuk NIP tersebut tidak ditemukan atau belum dapat diakses.']);
        }

        $categories = $this->validatedCategories((array) ($validated['category_ids'] ?? []));
        $districts = $this->validatedDistricts((array) ($validated['district_ids'] ?? []));

        $silatarUserId = (int) data_get($employee, 'id', 0);
        $nomorInduk = (string) data_get($employee, 'nomor_induk', $nip);
        $existing = User::query()
            ->with('categoryAccesses')
            ->where('nomor_induk', $nomorInduk)
            ->when($silatarUserId > 0, fn ($query) => $query->orWhere('silatar_user_id', $silatarUserId))
            ->first();

        if ($existing && $existing->role !== 'panitia') {
            return back()
                ->withInput()
                ->withErrors(['nip' => 'NIP ini sudah terdaftar pada akun '.$existing->roleLabel().' sehingga tidak bisa didaftarkan ulang sebagai panitia.']);
        }

        $generatedPassword = $this->generateSimplePassword();
        $profilePhotoPath = $this->syncProfilePhoto($employee, $existing?->profile_photo_path);
        $payload = [
            'name' => (string) data_get($employee, 'name', 'Panitia SILATAR'),
            'email' => $this->resolveEmail($employee, $nomorInduk, $silatarUserId, 'panitia'),
            'phone' => $this->normalizePhoneNumber((string) data_get($employee, 'telp', '')),
            'nomor_induk' => $nomorInduk,
            'silatar_user_id' => $silatarUserId > 0 ? $silatarUserId : null,
            'role' => 'panitia',
            'district_id' => null,
        ];

        if ($profilePhotoPath) {
            $payload['profile_photo_path'] = $profilePhotoPath;
        }

        if (! $existing) {
            $payload['password'] = $generatedPassword;
            if (User::supportsMustChangePasswordFlag()) {
                $payload['must_change_password'] = true;
            }
            $committee = User::query()->create($payload);
            $committee->categoryAccesses()->createMany(
                $categories->map(fn (CompetitionCategory $category): array => ['competition_category_id' => $category->id])->all()
            );
            $committee->districtAccesses()->createMany(
                $districts->map(fn (District $district): array => ['district_id' => $district->id])->all()
            );
            $whatsappSent = WhatsAppRegistrationSender::sendCommitteeWelcome(
                $committee,
                $generatedPassword,
                $categories->map(fn (CompetitionCategory $category): string => trim($category->branch.' - '.$category->name))->all(),
                $districts->pluck('name')->all(),
            );

            return redirect()
                ->route('committees.index')
                ->with('status', $whatsappSent
                    ? 'Panitia berhasil didaftarkan dari SILATAR dan pesan WhatsApp sudah dikirim.'
                    : 'Panitia berhasil didaftarkan dari SILATAR, tetapi pesan WhatsApp belum berhasil dikirim.')
                ->with('generated_credentials', [
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                    'phone' => $payload['phone'],
                    'nomor_induk' => $payload['nomor_induk'],
                    'categories' => $categories->map(fn (CompetitionCategory $category): string => trim($category->branch.' - '.$category->name))->all(),
                    'districts' => $districts->pluck('name')->all(),
                    'password' => $generatedPassword,
                ]);
        }

        $existing->fill($payload)->save();
        $existing->categoryAccesses()->delete();
        $existing->categoryAccesses()->createMany(
            $categories->map(fn (CompetitionCategory $category): array => ['competition_category_id' => $category->id])->all()
        );
        $existing->districtAccesses()->delete();
        $existing->districtAccesses()->createMany(
            $districts->map(fn (District $district): array => ['district_id' => $district->id])->all()
        );

        return redirect()
            ->route('committees.index')
            ->with('status', 'Data panitia beserta hak akses golongan dan kecamatan verifikator berhasil diperbarui dari SILATAR. Password akun lama tetap dipertahankan.');
    }

    public function updateBranches(Request $request, User $committee): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless($committee->role === 'panitia', 404);

        $validated = $request->validate([
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['required', 'integer', 'exists:competition_categories,id'],
            'district_ids' => ['nullable', 'array'],
            'district_ids.*' => ['required', 'integer', 'exists:districts,id'],
        ]);

        $categories = $this->validatedCategories((array) ($validated['category_ids'] ?? []));
        $districts = $this->validatedDistricts((array) ($validated['district_ids'] ?? []));

        $committee->categoryAccesses()->delete();
        $committee->categoryAccesses()->createMany(
            $categories->map(fn (CompetitionCategory $category): array => ['competition_category_id' => $category->id])->all()
        );
        $committee->districtAccesses()->delete();
        $committee->districtAccesses()->createMany(
            $districts->map(fn (District $district): array => ['district_id' => $district->id])->all()
        );

        return redirect()
            ->route('committees.index')
            ->with('status', 'Hak akses golongan dan kecamatan verifikator untuk panitia '.$committee->name.' berhasil diperbarui.');
    }

    public function destroy(User $committee): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless($committee->role === 'panitia', 404);

        if ($committee->is(auth()->user())) {
            return back()->withErrors(['committee' => 'Akun panitia yang sedang dipakai tidak bisa dihapus.']);
        }

        $profilePhotoPath = (string) ($committee->profile_photo_path ?? '');
        $committee->categoryAccesses()->delete();
        $committee->districtAccesses()->delete();
        $committee->delete();

        if ($profilePhotoPath !== '') {
            Storage::disk('public')->delete($profilePhotoPath);
        }

        return redirect()
            ->route('committees.index')
            ->with('status', 'Akun panitia '.$committee->name.' berhasil dihapus.');
    }

    private function fetchSilatarEmployee(string $nip): ?array
    {
        $response = $this->safeGet(self::SILATAR_NIP_API.$nip);

        if (! $response || ! $response->successful()) {
            return null;
        }

        $user = $response->json('user');

        return is_array($user) ? $user : null;
    }

    private function buildPreviewPayload(array $employee, string $nip): array
    {
        $silatarUserId = (int) data_get($employee, 'id', 0);
        $nomorInduk = (string) data_get($employee, 'nomor_induk', $nip);

        return [
            'silatar_user_id' => $silatarUserId > 0 ? $silatarUserId : null,
            'name' => (string) data_get($employee, 'name', '-'),
            'email' => $this->resolveEmail($employee, $nomorInduk, $silatarUserId, 'panitia'),
            'phone' => $this->normalizePhoneNumber((string) data_get($employee, 'telp', '')),
            'nomor_induk' => $nomorInduk,
            'avatar_url' => (string) data_get($employee, 'avatar', ''),
            'unit_label' => trim(implode(' | ', array_filter([
                (string) data_get($employee, 'dept.nama', ''),
                (string) data_get($employee, 'unitkerja', ''),
                (string) data_get($employee, 'satker', ''),
            ]))),
        ];
    }

    private function resolveEmail(array $employee, string $nomorInduk, int $silatarUserId, string $prefix): string
    {
        $email = trim((string) data_get($employee, 'email', ''));

        if ($email !== '') {
            $owner = User::query()->where('email', $email)->first();

            if (! $owner || (string) $owner->nomor_induk === $nomorInduk || (int) ($owner->silatar_user_id ?? 0) === $silatarUserId) {
                return $email;
            }
        }

        return $prefix.'.'.$nomorInduk.'@emtq.local';
    }

    private function syncProfilePhoto(array $employee, ?string $existingPath): ?string
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
        $path = 'users/profile-photos/panitia-'.$nomorInduk.'.'.$extension;

        Storage::disk('public')->put($path, $response->body());

        return $path;
    }

    private function generateSimplePassword(int $length = 8): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $password = '';

        for ($index = 0; $index < $length; $index++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password;
    }

    private function normalizePhoneNumber(string $phone): string
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

    private function safeGet(string $url): ?Response
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

    private function validatedCategories(array $categoryIds)
    {
        return CompetitionCategory::query()
            ->whereIn('id', array_values(array_unique(array_map('intval', $categoryIds))))
            ->orderBy('branch')
            ->orderBy('name')
            ->get(['id', 'branch', 'name'])
            ->values();
    }

    private function validatedDistricts(array $districtIds)
    {
        return District::query()
            ->whereIn('id', array_values(array_unique(array_map('intval', $districtIds))))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->values();
    }
}
