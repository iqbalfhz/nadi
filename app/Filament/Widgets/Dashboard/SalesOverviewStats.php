<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Resources\VendorSales\VendorSaleResource;
use App\Filament\Widgets\Concerns\InteractsWithDashboardFilters;
use App\Filament\Widgets\Support\DashboardMetric;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VendorSale;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * The money row: the two POS modules (Tiket Event, Bazar Kios) that were
 * built outside docs/NADI.MD, plus their combined revenue.
 *
 * Hidden entirely for admins who hold neither module's permission, so the
 * dashboard doesn't show an empty "Penjualan" section to, say, an HR admin.
 */
class SalesOverviewStats extends StatsOverviewWidget
{
    use InteractsWithDashboardFilters;

    protected static ?int $sort = -25;

    protected ?string $heading = 'Penjualan & Pendapatan';

    protected function getDescription(): ?string
    {
        return $this->rangeLabel();
    }

    public static function canView(): bool
    {
        return self::currentUserCanAny(['ViewAny:Ticket', 'ViewAny:VendorSale']);
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $canSeeTickets = $user->can('ViewAny:Ticket');
        $canSeeBazaar = $user->can('ViewAny:VendorSale');

        $ticketRevenue = $canSeeTickets
            ? $this->metric(Ticket::class, 'SUM(price)')
            : new DashboardMetric(0.0, 0.0);

        $bazaarRevenue = $canSeeBazaar
            ? $this->metric(VendorSale::class, 'SUM(price)')
            : new DashboardMetric(0.0, 0.0);

        $stats = [];

        // Only worth its own card when it actually rolls up two modules —
        // otherwise it would just repeat the single module card beside it.
        if ($canSeeTickets && $canSeeBazaar) {
            $stats[] = $this->buildStat(
                'Total Pendapatan',
                $ticketRevenue->plus($bazaarRevenue),
                Heroicon::OutlinedBanknotes,
                'success',
                isCurrency: true,
            );
        }

        if ($canSeeTickets) {
            $stats[] = $this->buildStat(
                'Pendapatan Tiket Event',
                $ticketRevenue,
                Heroicon::OutlinedCurrencyDollar,
                'success',
                TicketResource::getUrl(),
                isCurrency: true,
            );

            $stats[] = $this->buildStat(
                'Tiket Terjual',
                $this->metric(Ticket::class),
                Heroicon::OutlinedTicket,
                'primary',
                TicketResource::getUrl(),
            );
        }

        if ($canSeeBazaar) {
            $stats[] = $this->buildStat(
                'Pendapatan Bazar',
                $bazaarRevenue,
                Heroicon::OutlinedCurrencyDollar,
                'success',
                VendorSaleResource::getUrl(),
                isCurrency: true,
            );

            $stats[] = $this->buildStat(
                'Transaksi Bazar',
                // A cart checkout writes one row per line item sharing one
                // transaction_number, so "transaksi" means distinct numbers,
                // matching how VendorSalesOverview already counts them.
                $this->metric(VendorSale::class, 'COUNT(DISTINCT transaction_number)'),
                Heroicon::OutlinedBuildingStorefront,
                'primary',
                VendorSaleResource::getUrl(),
            );
        }

        return $stats;
    }

    private function buildStat(
        string $label,
        DashboardMetric $metric,
        Heroicon $icon,
        string $color,
        ?string $url = null,
        bool $isCurrency = false,
    ): Stat {
        $value = $isCurrency
            ? 'Rp '.number_format($metric->total, 0, ',', '.')
            : number_format($metric->total, 0, ',', '.');

        return Stat::make($label, $value)
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
