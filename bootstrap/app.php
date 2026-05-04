<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => App\Http\Middleware\EnsureUserHasRole::class,
            'password.change' => App\Http\Middleware\EnsurePasswordChangeRequired::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();

$publicPath = env('APP_PUBLIC_PATH');

if (! $publicPath) {
    $siblingPublicPath = dirname(dirname(dirname(__DIR__))).'/public_html/emtq';

    $publicPath = is_dir($siblingPublicPath)
        ? $siblingPublicPath
        : dirname(__DIR__).'/public';
}

$app->usePublicPath($publicPath);

return $app;
