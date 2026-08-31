<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\HkCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'requires_floor', 'is_active'])]
class HkCategory extends Model
{
    /** @use HasFactory<HkCategoryFactory> */
    use HasFactory, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Kategori HK';
    }

    protected function casts(): array
    {
        return [
            'requires_floor' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<HkArea, $this>
     */
    public function areas(): HasMany
    {
        return $this->hasMany(HkArea::class);
    }

    /**
     * @return HasMany<HkInspection, $this>
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(HkInspection::class);
    }
}
