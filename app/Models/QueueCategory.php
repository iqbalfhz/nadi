<?php

namespace App\Models;

use App\Enums\QueueTicketStatus;
use Database\Factories\QueueCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'code', 'is_active'])]
class QueueCategory extends Model
{
    /** @use HasFactory<QueueCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<QueueTicket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }

    /**
     * @return HasOne<QueueTicket, $this>
     */
    public function latestCalledTicket(): HasOne
    {
        return $this->hasOne(QueueTicket::class)
            ->where('status', QueueTicketStatus::Called)
            ->latestOfMany('called_at');
    }
}
