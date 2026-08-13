<?php

namespace App\Filament\Widgets;

use App\Enums\QueueTicketStatus;
use App\Models\QueueTicket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QueueTicketsOverview extends StatsOverviewWidget
{
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
                ->description('Nomor yang diambil hari ini')
                ->color('gray'),
            Stat::make('Selesai Dilayani', (string) $done)
                ->description('Tiket berstatus selesai')
                ->color('success'),
            Stat::make('Dilewati', (string) $skipped)
                ->description('Tiket berstatus dilewati')
                ->color('danger'),
            Stat::make('Rata-rata Waktu Layanan', $averageMinutes ? round($averageMinutes, 1).' menit' : '—')
                ->description('Dari dipanggil sampai selesai')
                ->color('warning'),
        ];
    }
}
