<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChangeRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user
            || ! User::supportsMustChangePasswordFlag()
            || ! in_array($user->role, ['official', 'panitia'], true)
            || ! (bool) $user->must_change_password
        ) {
            return $next($request);
        }

        $routeName = (string) $request->route()?->getName();
        $allowedRoutes = [
            'dashboard',
            'dashboard.password.update',
            'dashboard.realtime-summary',
            'dashboard.user-sync',
            'admin.impersonate.stop',
            'logout',
        ];

        if (in_array($routeName, $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()
            ->route('dashboard')
            ->with('warning', 'Akun Anda wajib mengganti password terlebih dahulu sebelum melanjutkan.');
    }
}
