<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['bazaar_id', 'name'])]
class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Kios';
    }

    /**
     * @return BelongsTo<Bazaar, $this>
     */
    public function bazaar(): BelongsTo
    {
        return $this->belongsTo(Bazaar::class);
    }

    /**
     * Relation name must stay exactly "products" — BazaarForm's nested
     * Repeater::make('products')->relationship() targets it by that name.
     *
     * @return HasMany<VendorProduct, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(VendorProduct::class);
    }

    /**
     * @return HasMany<VendorSale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(VendorSale::class);
    }
}
