<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\BazaarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'is_open'])]
class Bazaar extends Model
{
    /** @use HasFactory<BazaarFactory> */
    use HasFactory, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Bazar';
    }

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Vendor, $this>
     */
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    /**
     * @return HasMany<VendorSale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(VendorSale::class);
    }
}
