<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ActivityDocumentation extends Model
{
    use HasFactory;

    protected $fillable = [
        'caption',
        'image_path',
        'thumbnail_path',
        'uploaded_by',
        'is_active',
        'is_cover_homepage',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_by' => 'integer',
            'is_active' => 'boolean',
            'is_cover_homepage' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function imageUrl(): ?string
    {
        $path = trim((string) ($this->image_path ?? ''));

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/'.ltrim(str_replace('\\', '/', $path), '/'));
    }

    public function thumbnailUrl(): ?string
    {
        $path = trim((string) ($this->thumbnail_path ?? ''));

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return $this->imageUrl();
        }

        return asset('storage/'.ltrim(str_replace('\\', '/', $path), '/'));
    }
}
