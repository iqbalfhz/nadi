<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Database\Factories\RoomFactory;
use Guava\Calendar\Contracts\Resourceable;
use Guava\Calendar\ValueObjects\CalendarResource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['area_id', 'name', 'capacity'])]
class Room extends Model implements Resourceable
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory, LogsNadiActivity;

    public static function activitySubjectLabel(): string
    {
        return 'Ruangan';
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

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
            ->title("{$this->area->name} — {$this->name}");
    }
}
