<?php

namespace App\Filament\App\Widgets;

use App\Enums\MessengerDeliveryStatus;
use App\Enums\QueueTicketStatus;
use App\Models\MessengerDelivery;
use App\Models\ObChecklist;
use App\Models\QueueTicket;
use App\Models\RoomBooking;
use App\Models\SecurityPatrol;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VendorSale;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Permission-filtered, same philosophy as QuickLinksWidget: a card only
 * appears if the current user actually holds the permission for the module
 * it reports on. "Saya"-labeled cards are personal counts (this user's own
 * bookings/checklists/patrols); the rest are module-wide "hari ini" totals,
 * matching how those modules already report elsewhere (Tiket Event and
 * Bazar sales are deliberately never scoped to one cashier).
 */
class DashboardStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -5;

    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $stats = [];

        if ($user->can('ViewAny:RoomBooking')) {
            $stats[] = Stat::make(
                'Booking Saya Mendatang',
                (string) RoomBooking::query()
                    ->where('user_id', $user->id)
                    ->where('ends_at', '>=', now())
                    ->count(),
            )->icon(Heroicon::OutlinedCalendarDays)->color('info');
        }

        if ($user->can('ViewAny:ObChecklist')) {
            $stats[] = Stat::make(
                'Checklist Saya Hari Ini',
                (string) ObChecklist::query()
                    ->where('user_id', $user->id)
                    ->whereDate('created_at', today())
                    ->count(),
            )->icon(Heroicon::OutlinedClipboardDocumentCheck)->color('success');
        }

        if ($user->can('ViewAny:MessengerDelivery')) {
            $stats[] = Stat::make(
                'Tugas Kurir Aktif',
                (string) MessengerDelivery::query()
                    ->where('messenger_id', $user->id)
                    ->where('status', '!=', MessengerDeliveryStatus::Delivered)
                    ->count(),
            )->icon(Heroicon::OutlinedTruck)->color('warning');
        }

        if ($user->can('View:QueueOperator')) {
            $stats[] = Stat::make(
                'Antrian Saya Layani Hari Ini',
                (string) QueueTicket::query()
                    ->where('called_by', $user->id)
                    ->where('status', QueueTicketStatus::Done)
                    ->whereDate('called_at', today())
                    ->count(),
            )->icon(Heroicon::OutlinedQueueList)->color('gray');
        }

        if ($user->can('View:SecurityScan')) {
            $stats[] = Stat::make(
                'Patroli Saya Hari Ini',
                (string) SecurityPatrol::query()
                    ->where('user_id', $user->id)
                    ->whereDate('created_at', today())
                    ->count(),
            )->icon(Heroicon::OutlinedShieldCheck)->color('danger');
        }

        if ($user->can('ViewAny:Ticket')) {
            $stats[] = Stat::make(
                'Tiket Terjual Hari Ini',
                (string) Ticket::query()->whereDate('created_at', today())->count(),
            )->icon(Heroicon::OutlinedTicket)->color('primary');
        }

        if ($user->can('ViewAny:VendorSale')) {
            $stats[] = Stat::make(
                'Transaksi Bazar Hari Ini',
                (string) VendorSale::query()
                    ->whereDate('created_at', today())
                    ->distinct('transaction_number')
                    ->count('transaction_number'),
            )->icon(Heroicon::OutlinedBuildingStorefront)->color('primary');
        }

        return $stats;
    }
}
