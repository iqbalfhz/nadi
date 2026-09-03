<?php

namespace App\Filament\Resources\QueueTickets\Pages;

use App\Concerns\LogsDataAccess;
use App\Enums\QueueTicketStatus;
use App\Filament\Resources\QueueTickets\QueueTicketResource;
use App\Filament\Widgets\QueueTicketsOverview;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column as ExportColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListQueueTickets extends ListRecords
{
    use LogsDataAccess;

    protected static string $resource = QueueTicketResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            QueueTicketsOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Respects whatever the "Tanggal"/"Status" table filters are
            // currently set to — exports the filtered list, not everything.
            ExportAction::make()
                ->label(__('Export Excel'))
                ->after(fn () => $this->logDataAccess('Export Excel', 'Riwayat Tiket Antrian'))
                ->exports([
                    ExcelExport::make('tiket')
                        ->withFilename(fn (): string => 'riwayat-tiket-antrian-'.now()->format('Y-m-d'))
                        ->withColumns([
                            ExportColumn::make('formatted_number')->heading(__('Nomor')),
                            ExportColumn::make('category.name')->heading(__('Loket')),
                            ExportColumn::make('status')
                                ->heading(__('Status'))
                                ->formatStateUsing(fn (QueueTicketStatus $state): string => $state->label()),
                            ExportColumn::make('counter_label')->heading(__('Loket/Counter')),
                            ExportColumn::make('calledByUser.name')->heading(__('Dipanggil Oleh')),
                            ExportColumn::make('called_at')->heading(__('Waktu Dipanggil')),
                            ExportColumn::make('done_at')->heading(__('Waktu Selesai/Dilewati')),
                            ExportColumn::make('created_at')->heading(__('Waktu Ambil Nomor')),
                        ]),
                ]),
        ];
    }
}
