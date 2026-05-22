<?php

namespace App\Providers;

use App\Models\JuknisSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! Schema::hasTable('juknis_settings')) {
            return;
        }

        $setting = JuknisSetting::currentOrDefault();
        $appConfig = $setting->appConfig();
        $footerConfig = $setting->footerConfig();

        config([
            'juknis' => array_replace_recursive(config('juknis', []), $setting->content()),
            'app.name' => (string) ($appConfig['name'] ?? config('app.name', 'e-MTQ')),
            'mtq.branding' => $appConfig,
            'mtq.footer' => $footerConfig,
        ]);
    }
}
