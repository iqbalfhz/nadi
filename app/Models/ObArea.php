<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\ObAreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'is_active'])]
class ObArea extends Model
{
    /** @use HasFactory<ObAreaFactory> */
    use HasFactory, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Area OB';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ObChecklist, $this>
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(ObChecklist::class);
    }
}
