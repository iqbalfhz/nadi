<?php

namespace App\Filament\App\Widgets;

use App\Models\Room;
use App\Models\RoomBooking;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\Actions\CreateAction;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\DateSelectInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class BookingCalendarWidget extends CalendarWidget
{
    protected CalendarViewType $calendarView = CalendarViewType::ResourceTimeGridWeek;

    protected bool $dateSelectEnabled = true;

    /**
     * @return Collection<int, Room>|array<int, Room>|Builder<Room>
     */
    protected function getResources(): Collection|array|Builder
    {
        return Room::query();
    }

    /**
     * @return Collection<int, RoomBooking>|array<int, RoomBooking>|Builder<RoomBooking>
     */
    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        return RoomBooking::query()
            ->with('user')
            ->where('starts_at', '<', $info->end)
            ->where('ends_at', '>', $info->start);
    }

    protected function onDateSelect(DateSelectInfo $info): void
    {
        $this->mountAction('createRoomBooking');
    }

    public function createRoomBookingAction(): CreateAction
    {
        return $this->createAction(RoomBooking::class)
            ->mountUsing(function (Schema $schema, ?DateSelectInfo $dateSelect) {
                $schema->fill([
                    'room_id' => $dateSelect?->resource?->getId(),
                    'starts_at' => $dateSelect?->start,
                    'ends_at' => $dateSelect?->end,
                ]);
            })
            ->mutateDataUsing(function (array $data): array {
                $data['user_id'] = Auth::id();

                return $data;
            });
    }

    public function roomBookingSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('room_id')
                ->label('Ruangan')
                ->options(fn () => Room::query()->pluck('name', 'id'))
                ->required()
                ->live(),
            TextInput::make('title')
                ->label('Judul')
                ->required(),
            DateTimePicker::make('starts_at')
                ->label('Mulai')
                ->required()
                ->live(),
            DateTimePicker::make('ends_at')
                ->label('Selesai')
                ->required()
                ->after('starts_at')
                ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get) {
                    $roomId = $get('room_id');
                    $startsAt = $get('starts_at');

                    if (! $roomId || ! $startsAt || ! $value) {
                        return;
                    }

                    if (RoomBooking::overlaps((int) $roomId, Carbon::parse($startsAt), Carbon::parse($value))) {
                        $fail('Ruangan ini sudah dibooking pada sebagian rentang waktu tersebut.');
                    }
                }),
        ]);
    }
}
