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
     * Belongs on the Bazar report pages (both panels' ListVendorSales
     * header), not on /admin's Dashboard — which has its own sales cards
     * scoped to the dashboard's period filter. Without this, discoverWidgets()
     * would register it onto the Dashboard purely because the class lives in
     * a scanned directory.
     */
    protected static bool $isDiscovered = false;

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

        // A cart checkout writes one VendorSale row per line item sharing one
        // transaction_number, so "how many transactions" means distinct
        // transaction_number, not raw row count.
        $total = (clone $scope)->distinct('transaction_number')->count('transaction_number');
        $revenue = (int) (clone $scope)->sum('price');

        $paymentCounts = collect(TicketPaymentMethod::cases())
            ->mapWithKeys(fn (TicketPaymentMethod $method): array => [
                $method->value => (clone $scope)->where('payment_method', $method)->distinct('transaction_number')->count('transaction_number'),
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
