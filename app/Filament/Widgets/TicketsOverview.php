<?php

namespace App\Filament\Widgets;

use App\Enums\TicketPaymentMethod;
use App\Models\Event;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TicketsOverview extends StatsOverviewWidget
{
    /**
     * Belongs on the Tiket Event report pages (both panels' ListTickets
     * header), not on /admin's Dashboard — which has its own sales cards
     * scoped to the dashboard's period filter. Without this, discoverWidgets()
     * would register it onto the Dashboard purely because the class lives in
     * a scanned directory.
     *
     * It also deliberately does NOT use HasWidgetShield. A permission of its
     * own would be unreachable: Shield's role UI lists widgets from the
     * panel's discovered set, which $isDiscovered = false removes it from, so
     * the permission could never be granted to a new role while still being
     * enforced. It is redundant anyway — this widget only renders inside a
     * page that is already gated by its own permission.
     */
    protected static bool $isDiscovered = false;

    /**
     * /app's cashier-facing page sets this to true so the cards match the
     * "Hari ini" default on the table below them — a cashier closing the
     * register wants today's total, not the whole event's running total.
     * Admin's page leaves this false: the whole event's total is what's
     * useful there.
     */
    public bool $scopeToToday = false;

    protected function getStats(): array
    {
        // latest('id'), not latest(): two rows created in the same second
        // would otherwise be ordered by whatever the database returns first.
        $event = Event::query()->where('is_open', true)->latest('id')->first()
            ?? Event::query()->latest('id')->first();

        $eventName = 'Belum ada event';
        $eventId = null;

        if ($event !== null) {
            $eventName = $event->name;
            $eventId = $event->id;
        }

        $scope = Ticket::query()
            ->where('event_id', $eventId)
            ->when($this->scopeToToday, fn ($query) => $query->whereDate('created_at', today()));

        $total = (clone $scope)->count();
        $revenue = (int) (clone $scope)->sum('price');
        $memberCount = (clone $scope)->where('is_member', true)->count();
        $regularCount = $total - $memberCount;

        $paymentCounts = collect(TicketPaymentMethod::cases())
            ->mapWithKeys(fn (TicketPaymentMethod $method): array => [
                $method->value => (clone $scope)->where('payment_method', $method)->count(),
            ]);

        return [
            Stat::make('Total Tiket Terjual', (string) $total)
                ->description($eventName)
                ->color('gray'),
            Stat::make('Total Pendapatan', 'Rp '.number_format($revenue, 0, ',', '.'))
                ->description('Untuk event di atas')
                ->color('success'),
            Stat::make('Member', (string) $memberCount)
                ->color('info'),
            Stat::make('Reguler', (string) $regularCount)
                ->color('gray'),
            Stat::make('Tunai', (string) $paymentCounts[TicketPaymentMethod::Cash->value])
                ->color(TicketPaymentMethod::Cash->color()),
            Stat::make('QRIS', (string) $paymentCounts[TicketPaymentMethod::Qris->value])
                ->color(TicketPaymentMethod::Qris->color()),
            Stat::make('EDC', (string) $paymentCounts[TicketPaymentMethod::Edc->value])
                ->color(TicketPaymentMethod::Edc->color()),
        ];
    }
}
