<?php

namespace App\Filament\App\Widgets;

use App\Models\Area;
use App\Models\Room;
use App\Models\RoomBooking;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\Actions\CreateAction;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\DateSelectInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class BookingCalendarWidget extends CalendarWidget
{
    use HasWidgetShield;

    protected string $view = 'filament.app.widgets.booking-calendar-widget';

    protected CalendarViewType $calendarView = CalendarViewType::ResourceTimeGridDay;

    protected bool $dateSelectEnabled = true;

    protected bool $dateClickEnabled = true;

    // Without this, the calendar renders/interprets times in whatever timezone the
    // browser happens to be in, while everything else (admin forms, the database)
    // uses FilamentTimezone/app.timezone (Asia/Jakarta). This keeps both in sync.
    protected bool $useFilamentTimezone = true;

    public ?int $areaId = null;

    public string $miniCalendarMonth;

    public ?string $selectedDate = null;

    public function mount(): void
    {
        $this->miniCalendarMonth = now()->format('Y-m-01');
        $this->selectedDate = now()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [
            'headerToolbar' => [
                'start' => 'prev,next today',
                'center' => 'title',
                'end' => 'resourceTimeGridDay,resourceTimeGridWeek,resourceTimelineMonth',
            ],
            'slotMinTime' => '07:00:00',
        ];
    }

    /**
     * @return Collection<int, Room>|array<int, Room>|Builder<Room>
     */
    protected function getResources(): Collection|array|Builder
    {
        return Room::query()
            ->with('area')
            ->join('areas', 'areas.id', '=', 'rooms.area_id')
            ->when($this->areaId, fn (Builder $query) => $query->where('rooms.area_id', $this->areaId))
            ->orderBy('areas.name')
            ->orderBy('rooms.name')
            ->select('rooms.*');
    }

    /**
     * @return Collection<int, RoomBooking>|array<int, RoomBooking>|Builder<RoomBooking>
     */
    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        return RoomBooking::query()
            ->with('user')
            ->when($this->areaId, fn (Builder $query) => $query->whereHas('room', fn (Builder $q) => $q->where('area_id', $this->areaId)))
            ->where('starts_at', '<', $info->end)
            ->where('ends_at', '>', $info->start);
    }

    public function updatedAreaId(): void
    {
        $this->refreshResources();
        $this->refreshRecords();
    }

    /**
     * @return Collection<int, string>
     */
    public function getAreas(): Collection
    {
        return Area::query()->orderBy('name')->pluck('name', 'id');
    }

    protected function onDateSelect(DateSelectInfo $info): void
    {
        $this->mountAction('createRoomBooking');
    }

    protected function onDateClick(DateClickInfo $info): void
    {
        $this->mountAction('createRoomBooking');
    }

    public function createRoomBookingAction(): CreateAction
    {
        return $this->createAction(RoomBooking::class)
            ->mountUsing(function (Schema $schema, ?DateSelectInfo $dateSelect, ?DateClickInfo $dateClick) {
                if ($dateSelect) {
                    $roomId = $dateSelect->resource?->getId();
                    $startsAt = $dateSelect->start;
                    $endsAt = $dateSelect->end;
                } else {
                    $roomId = $dateClick?->resource?->getId();
                    $startsAt = $dateClick?->date;
                    $endsAt = $dateClick?->date?->addHour();
                }

                $schema->fill([
                    'room_id' => $roomId,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
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
                ->options(fn () => Room::query()
                    ->with('area')
                    ->orderBy('name')
                    ->get()
                    ->groupBy(fn (Room $room) => $room->area->name)
                    ->map(fn (Collection $rooms) => $rooms->pluck('name', 'id')))
                ->required()
                ->searchable()
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

    /**
     * Weeks (Monday-first) of Carbon dates for the mini calendar grid, covering
     * the visible month plus the leading/trailing days needed to fill full weeks.
     *
     * @return array<int, array<int, CarbonImmutable>>
     */
    public function getMiniCalendarWeeks(): array
    {
        $start = CarbonImmutable::parse($this->miniCalendarMonth)->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = CarbonImmutable::parse($this->miniCalendarMonth)->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $week = [];

        for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $week[] = $date;

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        return $weeks;
    }

    public function miniCalendarPreviousMonth(): void
    {
        $this->miniCalendarMonth = CarbonImmutable::parse($this->miniCalendarMonth)->subMonthNoOverflow()->format('Y-m-01');
    }

    public function miniCalendarNextMonth(): void
    {
        $this->miniCalendarMonth = CarbonImmutable::parse($this->miniCalendarMonth)->addMonthNoOverflow()->format('Y-m-01');
    }

    public function selectMiniCalendarDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->miniCalendarMonth = CarbonImmutable::parse($date)->format('Y-m-01');
        $this->setOption('date', $date);
    }
}
