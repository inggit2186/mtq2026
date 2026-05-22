<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
}
