<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Guava\Calendar\Contracts\Resourceable;
use Guava\Calendar\ValueObjects\CalendarResource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'capacity', 'location'])]
class Room extends Model implements Resourceable
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    /**
     * @return HasMany<RoomBooking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(RoomBooking::class);
    }

    public function toCalendarResource(): CalendarResource
    {
        return CalendarResource::make((string) $this->id)
            ->title($this->name);
    }
}
