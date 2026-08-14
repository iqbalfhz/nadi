<?php

namespace App\Filament\Widgets;

use App\Enums\TicketPaymentMethod;
use App\Models\Event;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TicketsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $event = Event::query()->where('is_open', true)->latest()->first()
            ?? Event::query()->latest()->first();

        $eventName = 'Belum ada event';
        $eventId = null;

        if ($event !== null) {
            $eventName = $event->name;
            $eventId = $event->id;
        }

        $scope = Ticket::query()->where('event_id', $eventId);

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
