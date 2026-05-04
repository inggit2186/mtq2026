<?php

namespace App\Http\Controllers;

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

class OfficialRegistrationController extends Controller
{
    private const SILATAR_NIP_API = 'https://ptsp.kemenagtanahdatar.cloud/api/v1/nip/';

    private const SILATAR_KUA_API = 'https://ptsp.kemenagtanahdatar.cloud/api/v1/getKUA';

    public function index(): View
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $officials = User::query()
            ->with('district')
            ->where('role', 'official')
            ->orderBy('district_id')
            ->orderBy('name')
            ->get();

        return view('pages/officials-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'officials' => $officials,
            'districts' => District::query()->orderBy('name')->get(),
            'officialStats' => [
                'total' => $officials->count(),
                'districts_covered' => $officials->pluck('district_id')->filter()->unique()->count(),
                'with_email' => $officials->filter(fn (User $user): bool => filled($user->email))->count(),
            ],
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
            return response()->json([
                'message' => 'NIP pegawai hanya boleh berisi angka.',
            ], 422);
        }

        $employee = $this->fetchSilatarEmployee($nip);

        if (! $employee) {
            return response()->json([
                'message' => 'Data pegawai SILATAR untuk NIP tersebut tidak ditemukan atau belum dapat diakses.',
            ], 404);
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
            'district_id' => ['required', 'exists:districts,id'],
        ], [
            'nip.required' => 'NIP pegawai wajib diisi.',
            'district_id.required' => 'Kecamatan official wajib dipilih sebelum akun didaftarkan.',
        ]);

        $nip = preg_replace('/\D+/', '', (string) $validated['nip']) ?: '';

        if ($nip === '') {
            return back()
                ->withInput()
                ->withErrors(['nip' => 'NIP pegawai hanya boleh berisi angka.']);
        }

        $employee = $this->fetchSilatarEmployee($nip);

        if (! $employee) {
            return back()
                ->withInput()
                ->withErrors(['nip' => 'Data pegawai SILATAR untuk NIP tersebut tidak ditemukan atau belum dapat diakses.']);
        }

        $district = District::query()->findOrFail((int) $validated['district_id']);

        $silatarUserId = (int) data_get($employee, 'id', 0);
        $nomorInduk = (string) data_get($employee, 'nomor_induk', $nip);
        $email = $this->resolveOfficialEmail($employee, $nomorInduk, $silatarUserId);
        $existing = User::query()
            ->where('nomor_induk', $nomorInduk)
            ->when($silatarUserId > 0, fn ($query) => $query->orWhere('silatar_user_id', $silatarUserId))
            ->first();

        if ($existing && $existing->role !== 'official') {
            return back()
                ->withInput()
                ->withErrors([
                    'nip' => 'NIP ini sudah terdaftar pada akun '.($existing->roleLabel()).' sehingga tidak bisa didaftarkan ulang sebagai official.',
                ]);
        }

        $generatedPassword = $this->generateSimplePassword();
        $profilePhotoPath = $this->syncOfficialProfilePhoto($employee, $existing?->profile_photo_path);
        $payload = [
            'name' => (string) data_get($employee, 'name', 'Official SILATAR'),
            'email' => $email,
            'phone' => $this->normalizePhoneNumber((string) data_get($employee, 'telp', '')),
            'nomor_induk' => $nomorInduk,
            'silatar_user_id' => $silatarUserId > 0 ? $silatarUserId : null,
            'role' => 'official',
            'district_id' => $district->id,
        ];

        if ($profilePhotoPath) {
            $payload['profile_photo_path'] = $profilePhotoPath;
        }

        if (! $existing) {
            $payload['password'] = $generatedPassword;
            if (User::supportsMustChangePasswordFlag()) {
                $payload['must_change_password'] = true;
            }
            $official = User::query()->create($payload);
            $whatsappSent = WhatsAppRegistrationSender::sendOfficialWelcome($official, $generatedPassword, $district->name);

            return redirect()
                ->route('officials.index')
                ->with('status', $whatsappSent
                    ? 'Official kecamatan berhasil didaftarkan dari SILATAR dan pesan WhatsApp sudah dikirim.'
                    : 'Official kecamatan berhasil didaftarkan dari SILATAR, tetapi pesan WhatsApp belum berhasil dikirim.')
                ->with('generated_credentials', [
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                    'phone' => $payload['phone'],
                    'nomor_induk' => $payload['nomor_induk'],
                    'district' => $district->name,
                    'password' => $generatedPassword,
                ]);
        }

        $existing->fill($payload)->save();

        return redirect()
            ->route('officials.index')
            ->with('status', 'Data official berhasil diperbarui dari SILATAR. Password akun lama tetap dipertahankan.');
    }

    public function destroy(User $official): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless($official->role === 'official', 404);

        if ($official->is(auth()->user())) {
            return back()->withErrors(['official' => 'Akun official yang sedang dipakai tidak bisa dihapus.']);
        }

        $profilePhotoPath = (string) ($official->profile_photo_path ?? '');
        $official->delete();

        if ($profilePhotoPath !== '') {
            Storage::disk('public')->delete($profilePhotoPath);
        }

        return redirect()
            ->route('officials.index')
            ->with('status', 'Akun official '.$official->name.' berhasil dihapus.');
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
        $district = $this->resolveDistrictFromEmployee($employee);
        $silatarUserId = (int) data_get($employee, 'id', 0);
        $nomorInduk = (string) data_get($employee, 'nomor_induk', $nip);

        return [
            'silatar_user_id' => $silatarUserId > 0 ? $silatarUserId : null,
            'name' => (string) data_get($employee, 'name', '-'),
            'email' => $this->resolveOfficialEmail($employee, $nomorInduk, $silatarUserId),
            'phone' => $this->normalizePhoneNumber((string) data_get($employee, 'telp', '')),
            'nomor_induk' => $nomorInduk,
            'avatar_url' => (string) data_get($employee, 'avatar', ''),
            'district_id' => $district?->id,
            'district_name' => $district?->name ?? 'Non-KUA',
            'unit_label' => trim(implode(' | ', array_filter([
                (string) data_get($employee, 'dept.nama', ''),
                (string) data_get($employee, 'unitkerja', ''),
                (string) data_get($employee, 'satker', ''),
            ]))),
            'mapping_note' => $district
                ? 'Kecamatan default sudah diisi dari hasil pembacaan data SILATAR dan masih bisa Anda ubah sebelum pendaftaran.'
                : 'Kecamatan belum berhasil dipetakan otomatis. Silakan pilih kecamatan manual sebelum melanjutkan.',
        ];
    }

    private function resolveDistrictFromEmployee(array $employee): ?District
    {
        $employeeDeptId = (int) data_get($employee, 'dept_id', 0);

        if ($employeeDeptId <= 0) {
            return null;
        }

        return District::query()
            ->where('silatar_id', $employeeDeptId)
            ->first();
    }

    private function resolveOfficialEmail(array $employee, string $nomorInduk, int $silatarUserId): string
    {
        $email = trim((string) data_get($employee, 'email', ''));

        if ($email !== '') {
            $owner = User::query()->where('email', $email)->first();

            if (! $owner || (string) $owner->nomor_induk === $nomorInduk || (int) ($owner->silatar_user_id ?? 0) === $silatarUserId) {
                return $email;
            }
        }

        return 'official.'.$nomorInduk.'@emtq.local';
    }

    private function syncOfficialProfilePhoto(array $employee, ?string $existingPath): ?string
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
        $path = 'users/profile-photos/official-'.$nomorInduk.'.'.$extension;

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
}
