<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;

class JuknisSetting extends Model
{
    protected $fillable = [
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public static function defaults(): array
    {
        return config('juknis', []);
    }

    public static function current(): ?self
    {
        if (! Schema::hasTable('juknis_settings')) {
            return null;
        }

        return static::query()->latest('id')->first();
    }

    public static function currentOrDefault(): self
    {
        return static::current() ?? new self([
            'content' => static::defaults(),
        ]);
    }

    public function content(): array
    {
        $content = $this->content ?? static::defaults();

        return is_array($content) ? $content : static::defaults();
    }

    public function appConfig(): array
    {
        return array_replace_recursive(static::defaults()['app'] ?? [], $this->content()['app'] ?? []);
    }

    public function footerConfig(): array
    {
        return array_replace_recursive(static::defaults()['footer'] ?? [], $this->content()['footer'] ?? []);
    }

    public function registrationWindows(): array
    {
        $windows = $this->content()['registration_windows'] ?? [];

        if (! is_array($windows)) {
            return [];
        }

        return collect($windows)
            ->filter(fn ($window): bool => is_array($window))
            ->map(function (array $window): array {
                $window['label'] = trim((string) ($window['label'] ?? ''));
                $window['start_at'] = trim((string) ($window['start_at'] ?? ''));
                $window['end_at'] = trim((string) ($window['end_at'] ?? ''));
                $window['official'] = is_array($window['official'] ?? null) ? $window['official'] : [];
                $window['panitia'] = is_array($window['panitia'] ?? null) ? $window['panitia'] : [];

                return $window;
            })
            ->values()
            ->all();
    }

    public function scheduledAccessEnabled(string $feature, ?string $role = null, ?Carbon $when = null): ?bool
    {
        $role = $role ?: (string) auth()->user()?->role;
        $when = $when ?: Carbon::now('Asia/Bangkok');

        if (! in_array($role, ['official', 'pendamping', 'panitia'], true)) {
            return null;
        }

        $windows = $this->registrationWindows();
        $relevantWindows = collect($windows)->filter(function (array $window) use ($role): bool {
            $featureMap = $window[$role === 'panitia' ? 'panitia' : 'official'] ?? [];

            return is_array($featureMap) && array_key_exists($feature, $featureMap);
        });

        if ($relevantWindows->isEmpty()) {
            return null;
        }

        $activeWindows = $relevantWindows->filter(function (array $window) use ($when): bool {
            $start = $this->parseDateTime((string) ($window['start_at'] ?? ''), false);
            $end = $this->parseDateTime((string) ($window['end_at'] ?? ''), true);

            if (! $start || ! $end) {
                return false;
            }

            return ! ($when->lt($start) || $when->gt($end));
        });

        return $activeWindows->contains(function (array $window) use ($role, $feature): bool {
            $featureMap = $window[$role === 'panitia' ? 'panitia' : 'official'] ?? [];

            return (bool) ($featureMap[$feature] ?? false);
        }) || false;
    }

    public function registrationWindowEnabledForRole(string $feature, ?string $role = null, ?Carbon $when = null): ?bool
    {
        return $this->scheduledAccessEnabled($feature, $role, $when);
    }

    protected function parseDateTime(string $value, bool $endOfDay = false): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::parse($value, 'Asia/Bangkok');

            return $endOfDay ? $date->endOfDay() : $date;
        } catch (\Throwable) {
            return null;
        }
    }
}
