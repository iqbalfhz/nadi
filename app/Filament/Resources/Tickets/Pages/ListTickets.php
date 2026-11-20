<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Concerns\LogsDataAccess;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Widgets\TicketsOverview;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class ListTickets extends ListRecords
{
    use LogsDataAccess;

    protected static string $resource = TicketResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TicketsOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label(__('Export Excel'))
                ->after(fn () => $this->logDataAccess('Export Excel', 'Laporan Tiket Event'))
                ->exports([TicketsTable::excelExport()]),
        ];
    }
}
