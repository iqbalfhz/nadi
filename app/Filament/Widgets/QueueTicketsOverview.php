<?php

namespace App\Filament\Widgets;

use App\Enums\QueueTicketStatus;
use App\Models\QueueTicket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QueueTicketsOverview extends StatsOverviewWidget
{
    /**
     * Belongs on the Antrian report page (ListQueueTickets' header widget),
     * not on the dashboard. Without this, discoverWidgets() would register it
     * onto /admin's Dashboard too, purely because the class lives inside a
     * scanned directory — see App\Filament\App\Widgets\BookingCalendarWidget
     * for the same opt-out.
     *
     * It also deliberately does NOT use HasWidgetShield. A permission of its
     * own would be unreachable: Shield's role UI lists widgets from the
     * panel's discovered set, which $isDiscovered = false removes it from, so
     * the permission could never be granted to a new role while still being
     * enforced. It is redundant anyway — this widget only renders inside a
     * page that is already gated by its own permission.
     */
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $today = QueueTicket::query()->whereDate('created_at', today());

        $total = (clone $today)->count();
        $done = (clone $today)->where('status', QueueTicketStatus::Done)->count();
        $skipped = (clone $today)->where('status', QueueTicketStatus::Skipped)->count();

        $averageMinutes = (clone $today)
            ->where('status', QueueTicketStatus::Done)
            ->whereNotNull('called_at')
            ->whereNotNull('done_at')
            ->get()
            ->avg(fn (QueueTicket $ticket) => $ticket->called_at?->diffInSeconds($ticket->done_at) / 60);

        return [
            Stat::make('Total Hari Ini', (string) $total)
                ->description(__('Nomor yang diambil hari ini'))
                ->color('gray'),
            Stat::make('Selesai Dilayani', (string) $done)
                ->description(__('Tiket berstatus selesai'))
                ->color('success'),
            Stat::make('Dilewati', (string) $skipped)
                ->description(__('Tiket berstatus dilewati'))
                ->color('danger'),
            Stat::make('Rata-rata Waktu Layanan', $averageMinutes ? round($averageMinutes, 1).' menit' : '—')
                ->description(__('Dari dipanggil sampai selesai'))
                ->color('warning'),
        ];
    }
}
