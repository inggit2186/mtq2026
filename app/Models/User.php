<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'nomor_induk',
        'silatar_user_id',
        'password',
        'role',
        'district_id',
        'profile_photo_path',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'silatar_user_id' => 'integer',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public static function supportsMustChangePasswordFlag(): bool
    {
        return Schema::hasColumn((new self())->getTable(), 'must_change_password');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'published_by');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function categoryAccesses(): HasMany
    {
        return $this->hasMany(UserCategoryAccess::class);
    }

    public function districtAccesses(): HasMany
    {
        return $this->hasMany(UserDistrictAccess::class);
    }

    public function accessibleCategoryIds(): array
    {
        return $this->categoryAccesses()
            ->orderBy('competition_category_id')
            ->pluck('competition_category_id')
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    public function accessibleDistrictIds(): array
    {
        return $this->districtAccesses()
            ->orderBy('district_id')
            ->pluck('district_id')
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Admin',
            'panitia' => 'Panitia',
            'official', 'pendamping' => 'Official',
            'peserta' => 'Peserta',
            default => ucfirst((string) $this->role),
        };
    }

    public function profilePhotoUrl(): ?string
    {
        $path = (string) ($this->profile_photo_path ?? '');

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/'.ltrim(str_replace('\\', '/', $path), '/'));
    }

    public function profileInitials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'U';
    }
}
