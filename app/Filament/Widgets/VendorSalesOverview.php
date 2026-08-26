<?php

namespace App\Filament\Widgets;

use App\Enums\TicketPaymentMethod;
use App\Models\Bazaar;
use App\Models\VendorSale;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VendorSalesOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    /**
     * /app's cashier-facing page sets this to true so the cards match the
     * "Hari ini" default on the table below them. Admin's page leaves this
     * false: the whole bazaar's running total is what's useful there.
     */
    public bool $scopeToToday = false;

    protected function getStats(): array
    {
        $bazaar = Bazaar::query()->where('is_open', true)->latest()->first()
            ?? Bazaar::query()->latest()->first();

        $bazaarName = 'Belum ada bazar';
        $bazaarId = null;

        if ($bazaar !== null) {
            $bazaarName = $bazaar->name;
            $bazaarId = $bazaar->id;
        }

        $scope = VendorSale::query()
            ->where('bazaar_id', $bazaarId)
            ->when($this->scopeToToday, fn ($query) => $query->whereDate('created_at', today()));

        $total = (clone $scope)->count();
        $revenue = (int) (clone $scope)->sum('price');

        $paymentCounts = collect(TicketPaymentMethod::cases())
            ->mapWithKeys(fn (TicketPaymentMethod $method): array => [
                $method->value => (clone $scope)->where('payment_method', $method)->count(),
            ]);

        return [
            Stat::make('Total Transaksi', (string) $total)
                ->description($bazaarName)
                ->color('gray'),
            Stat::make('Total Pendapatan', 'Rp '.number_format($revenue, 0, ',', '.'))
                ->description('Untuk bazar di atas')
                ->color('success'),
            Stat::make('Tunai', (string) $paymentCounts[TicketPaymentMethod::Cash->value])
                ->color(TicketPaymentMethod::Cash->color()),
            Stat::make('QRIS', (string) $paymentCounts[TicketPaymentMethod::Qris->value])
                ->color(TicketPaymentMethod::Qris->color()),
            Stat::make('EDC', (string) $paymentCounts[TicketPaymentMethod::Edc->value])
                ->color(TicketPaymentMethod::Edc->color()),
        ];
    }
}
