<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
