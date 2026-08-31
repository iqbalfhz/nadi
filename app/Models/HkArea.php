<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\HkAreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['hk_category_id', 'name', 'is_active'])]
class HkArea extends Model
{
    /** @use HasFactory<HkAreaFactory> */
    use HasFactory, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Titik HK';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<HkCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(HkCategory::class, 'hk_category_id');
    }

    /**
     * @return HasMany<HkInspection, $this>
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(HkInspection::class);
    }
}
