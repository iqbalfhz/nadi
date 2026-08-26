<?php

namespace App\Filament\Widgets\Dashboard;

use App\Enums\QueueTicketStatus;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\MessengerDeliveries\MessengerDeliveryResource;
use App\Filament\Resources\ObChecklists\ObChecklistResource;
use App\Filament\Resources\QueueTickets\QueueTicketResource;
use App\Filament\Resources\RoomBookings\RoomBookingResource;
use App\Filament\Resources\SecurityPatrols\SecurityPatrolResource;
use App\Filament\Widgets\Concerns\InteractsWithDashboardFilters;
use App\Filament\Widgets\Support\DashboardMetric;
use App\Models\Document;
use App\Models\MessengerDelivery;
use App\Models\ObChecklist;
use App\Models\QueueTicket;
use App\Models\RoomBooking;
use App\Models\SecurityPatrol;
use App\Models\User;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Headline numbers for the six operational modules from docs/NADI.MD, each
 * card carrying its own trend against the previous period and a sparkline.
 *
 * Every card is permission-filtered the same way the /app dashboard's cards
 * are: an admin who can't open a module doesn't get its numbers either.
 */
class OperationalOverviewStats extends StatsOverviewWidget
{
    use InteractsWithDashboardFilters;

    /**
     * Any one of these is enough to be shown the widget at all; each
     * individual card is then gated on its own module's permission below.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'ViewAny:Document',
        'ViewAny:RoomBooking',
        'ViewAny:QueueTicket',
        'ViewAny:ObChecklist',
        'ViewAny:SecurityPatrol',
        'ViewAny:MessengerDelivery',
    ];

    protected static ?int $sort = -30;

    protected ?string $heading = 'Ringkasan Operasional';

    protected function getDescription(): ?string
    {
        return $this->rangeLabel();
    }

    public static function canView(): bool
    {
        return self::currentUserCanAny(self::PERMISSIONS);
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $stats = [];

        if ($user->can('ViewAny:Document')) {
            $stats[] = $this->buildStat(
                'Dokumen Terbit',
                $this->metric(Document::class),
                Heroicon::OutlinedDocumentDuplicate,
                'primary',
                DocumentResource::getUrl(),
            );
        }

        if ($user->can('ViewAny:RoomBooking')) {
            $stats[] = $this->buildStat(
                'Booking Ruangan',
                // Dated by when the room is actually used, not when the
                // booking was typed in — that's the number a facility report
                // is about.
                $this->metric(RoomBooking::class, dateColumn: 'starts_at'),
                Heroicon::OutlinedCalendarDays,
                'info',
                RoomBookingResource::getUrl(),
            );
        }

        if ($user->can('ViewAny:QueueTicket')) {
            $stats[] = $this->buildStat(
                'Antrian Dilayani',
                $this->metric(
                    QueueTicket::class,
                    scope: fn (Builder $query) => $query->where('status', QueueTicketStatus::Done),
                ),
                Heroicon::OutlinedQueueList,
                'warning',
                QueueTicketResource::getUrl(),
            );
        }

        if ($user->can('ViewAny:ObChecklist')) {
            $stats[] = $this->buildStat(
                'Checklist OB',
                $this->metric(ObChecklist::class),
                Heroicon::OutlinedClipboardDocumentCheck,
                'success',
                ObChecklistResource::getUrl(),
            );
        }

        if ($user->can('ViewAny:SecurityPatrol')) {
            $stats[] = $this->buildStat(
                'Scan Patroli',
                $this->metric(SecurityPatrol::class),
                Heroicon::OutlinedShieldCheck,
                'danger',
                SecurityPatrolResource::getUrl(),
            );
        }

        if ($user->can('ViewAny:MessengerDelivery')) {
            $stats[] = $this->buildStat(
                'Pengiriman Kurir',
                $this->metric(MessengerDelivery::class),
                Heroicon::OutlinedTruck,
                'gray',
                MessengerDeliveryResource::getUrl(),
            );
        }

        return $stats;
    }

    private function buildStat(string $label, DashboardMetric $metric, Heroicon $icon, string $color, string $url): Stat
    {
        return Stat::make($label, number_format($metric->total, 0, ',', '.'))
            ->description($metric->trendDescription())
            ->descriptionIcon($metric->trendIcon(), IconPosition::Before)
            ->descriptionColor($metric->trendColor())
            ->chart($metric->series)
            ->chartColor($color)
            ->icon($icon)
            ->color($color)
            ->url($url);
    }
}
