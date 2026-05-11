<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\WhatsAppRegistrationSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.portal-v2', [
            'assets' => app(PageController::class)->viteAssets(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($credentials['login']);
        $user = User::query()
            ->where('email', $login)
            ->orWhere('nomor_induk', $login)
            ->first();

        if (! $user || ! Auth::attempt([
            'email' => $user->email,
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            return back()
                ->withInput($request->except('password'))
                ->withErrors([
                    'login' => 'Email / NIP / NIK atau password tidak cocok.',
                ]);
        }

        $request->session()->regenerate();

        ActivityLogger::log(
            'auth.login',
            ($user->name ?? 'User').' login ke aplikasi.',
            $user,
            ['remember' => $request->boolean('remember')]
        );

        return redirect()->intended(route('dashboard'));
    }

    public function requestPasswordReset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nip' => ['required', 'string', 'max:32'],
        ]);

        $nip = preg_replace('/\D+/', '', (string) $validated['nip']) ?: '';

        if ($nip === '') {
            return redirect()
                ->route('login')
                ->withInput(['nip' => (string) $validated['nip']])
                ->withErrors(['nip' => 'NIP pegawai hanya boleh berisi angka.']);
        }

        $user = User::query()
            ->where('nomor_induk', $nip)
            ->first();

        if (! $user) {
            return redirect()
                ->route('login')
                ->withInput(['nip' => $nip])
                ->withErrors(['nip' => 'Akun dengan NIP tersebut tidak ditemukan.']);
        }

        if (! filled($user->phone)) {
            return redirect()
                ->route('login')
                ->withInput(['nip' => $nip])
                ->withErrors(['nip' => 'Nomor WhatsApp akun ini belum terdaftar. Hubungi admin untuk memperbarui data akun.']);
        }

        $generatedPassword = $this->generateSimplePassword();
        $previousPassword = (string) $user->getRawOriginal('password');
        $previousMustChange = User::supportsMustChangePasswordFlag() ? (bool) $user->must_change_password : null;

        $payload = [
            'password' => $generatedPassword,
        ];

        if (User::supportsMustChangePasswordFlag()) {
            $payload['must_change_password'] = true;
        }

        $user->forceFill($payload)->save();
        $user->refresh();

        $whatsappSent = WhatsAppRegistrationSender::sendPasswordReset($user, $generatedPassword);

        if (! $whatsappSent) {
            $restorePayload = [
                'password' => $previousPassword,
            ];

            if (User::supportsMustChangePasswordFlag() && $previousMustChange !== null) {
                $restorePayload['must_change_password'] = $previousMustChange;
            }

            $user->forceFill($restorePayload)->save();

            ActivityLogger::log(
                'auth.password.reset.failed',
                ($user->name ?? 'User').' meminta reset password, tetapi pengiriman WhatsApp gagal.',
                $user,
                ['nip' => $nip]
            );

            return redirect()
                ->route('login')
                ->withInput(['nip' => $nip])
                ->withErrors(['nip' => 'Password baru berhasil dibuat, tetapi gagal dikirim ke WhatsApp. Silakan coba lagi atau hubungi admin.']);
        }

        ActivityLogger::log(
            'auth.password.reset',
            ($user->name ?? 'User').' meminta reset password melalui halaman login.',
            $user,
            ['nip' => $nip]
        );

        return redirect()
            ->route('login')
            ->with('status', 'Password baru telah dikirim ke WhatsApp yang terdaftar untuk NIP tersebut.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        ActivityLogger::log(
            'auth.logout',
            ($user?->name ?? 'User').' logout dari aplikasi.',
            $user,
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function impersonate(Request $request): RedirectResponse
    {
        abort_unless((string) auth()->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:64'],
        ]);

        $identifier = trim((string) $validated['identifier']);
        $lookup = preg_replace('/\s+/u', '', $identifier) ?: '';

        $targetUser = User::query()
            ->whereKey(is_numeric($lookup) ? (int) $lookup : -1)
            ->orWhere('nomor_induk', $lookup)
            ->first();

        if (! $targetUser) {
            throw ValidationException::withMessages([
                'identifier' => 'Pengguna dengan ID atau NIP tersebut tidak ditemukan.',
            ]);
        }

        $currentUser = auth()->user();

        if ($currentUser && (int) $currentUser->id === (int) $targetUser->id) {
            return back()->with('warning', 'Anda sudah memakai akun tersebut.');
        }

        $impersonation = $request->session()->get('impersonation', []);

        if (! isset($impersonation['original_user_id']) && $currentUser) {
            $impersonation['original_user_id'] = (int) $currentUser->id;
            $impersonation['original_user_name'] = (string) $currentUser->name;
            $impersonation['original_user_role'] = (string) $currentUser->role;
        }

        $impersonation['target_user_id'] = (int) $targetUser->id;
        $impersonation['target_user_name'] = (string) $targetUser->name;
        $impersonation['target_user_role'] = (string) $targetUser->role;
        $impersonation['started_at'] = now()->toDateTimeString();

        ActivityLogger::log(
            'auth.impersonation.start',
            ($currentUser?->name ?? 'Admin').' membuka mode masuk sebagai '.$targetUser->name.'.',
            $targetUser,
            [
                'original_user_id' => $currentUser?->id,
                'original_user_name' => $currentUser?->name,
                'original_user_role' => $currentUser?->role,
                'target_user_id' => $targetUser->id,
                'target_user_name' => $targetUser->name,
                'target_user_role' => $targetUser->role,
            ]
        );

        Auth::login($targetUser);
        $request->session()->regenerate();
        $request->session()->put('impersonation', $impersonation);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Anda sekarang masuk sebagai '.$targetUser->name.'. Gunakan banner di atas untuk kembali ke akun admin.');
    }

    public function stopImpersonation(Request $request): RedirectResponse
    {
        $impersonation = $request->session()->pull('impersonation');

        abort_unless(is_array($impersonation) && filled($impersonation['original_user_id'] ?? null), 403);

        $originalUser = User::query()->find((int) $impersonation['original_user_id']);

        if (! $originalUser) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['identifier' => 'Akun admin asal tidak ditemukan. Silakan login ulang.']);
        }

        Auth::login($originalUser);
        $request->session()->regenerate();

        ActivityLogger::log(
            'auth.impersonation.stop',
            ($originalUser->name ?? 'Admin').' kembali dari mode login sebagai user lain.',
            $originalUser,
            [
                'original_user_id' => $originalUser->id,
                'target_user_id' => $impersonation['target_user_id'] ?? null,
            ]
        );

        return redirect()
            ->route('dashboard')
            ->with('status', 'Anda kembali ke akun admin '.$originalUser->name.'.');
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
}
