<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class OfficialAccessSetting extends Model
{
    protected $fillable = [
        'participant_registration_open',
        'participant_edit_open',
        'mandate_upload_open',
        'participant_documents_open',
    ];

    protected function casts(): array
    {
        return [
            'participant_registration_open' => 'boolean',
            'participant_edit_open' => 'boolean',
            'mandate_upload_open' => 'boolean',
            'participant_documents_open' => 'boolean',
        ];
    }

    public static function defaults(): array
    {
        return [
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
        ];
    }

    public static function current(): ?self
    {
        if (! Schema::hasTable('official_access_settings')) {
            return null;
        }

        return static::query()->latest('id')->first();
    }

    public static function currentOrDefault(): self
    {
        return static::current() ?? new self(static::defaults());
    }

    public function isEnabled(string $feature): bool
    {
        $defaults = static::defaults();

        return (bool) ($this->{$feature} ?? ($defaults[$feature] ?? true));
    }
}
