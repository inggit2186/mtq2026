<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DocumentSetting extends Model
{
    protected $fillable = [
        'organization_name',
        'event_title',
        'event_location',
        'signature_city',
        'officials',
    ];

    protected $casts = [
        'officials' => 'array',
    ];

    public static function current(): ?self
    {
        if (! Schema::hasTable('document_settings')) {
            return null;
        }

        return static::query()->latest('id')->first();
    }
}
