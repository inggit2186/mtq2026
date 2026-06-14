<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MsqTitle extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_id',
        'title_1',
        'title_2',
        'title_3',
        'created_by',
    ];

    protected $casts = [
        'district_id' => 'integer',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function forDistrict(int $districtId): ?self
    {
        return static::where('district_id', $districtId)->first();
    }

    public function getAllTitles(): array
    {
        return array_filter([
            $this->title_1,
            $this->title_2,
            $this->title_3,
        ]);
    }

    public function hasAllTitles(): bool
    {
        return filled($this->title_1) && filled($this->title_2) && filled($this->title_3);
    }
}
