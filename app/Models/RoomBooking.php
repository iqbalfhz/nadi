<?php

namespace App\Models;

use App\Concerns\LogsNadiActivity;
use Carbon\CarbonInterface;
use Database\Factories\RoomBookingFactory;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['room_id', 'user_id', 'title', 'starts_at', 'ends_at'])]
class RoomBooking extends Model implements Eventable
{
    /** @use HasFactory<RoomBookingFactory> */
    use HasFactory, LogsNadiActivity, SoftDeletes;

    public static function activitySubjectLabel(): string
    {
        return 'Booking Ruangan';
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this booking overlaps the given time range in the given room.
     */
    public static function overlaps(int $roomId, CarbonInterface $startsAt, CarbonInterface $endsAt, ?int $excludingId = null): bool
    {
        return static::query()
            ->where('room_id', $roomId)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($excludingId, fn ($query) => $query->whereKeyNot($excludingId))
            ->exists();
    }

    public function toCalendarEvent(): CalendarEvent
    {
        return CalendarEvent::make($this)
            ->title("{$this->title} — {$this->user->name}")
            ->start($this->starts_at)
            ->end($this->ends_at)
            ->resourceId((string) $this->room_id);
    }
}
