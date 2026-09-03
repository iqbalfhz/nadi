<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Concerns\LogsDataAccess;
use App\Enums\TicketPaymentMethod;
use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Widgets\TicketsOverview;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column as ExportColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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
                ->exports([
                    ExcelExport::make('tiket')
                        ->withFilename(fn (): string => 'penjualan-tiket-event-'.now()->format('Y-m-d'))
                        ->withColumns([
                            ExportColumn::make('transaction_number')->heading(__('No. Transaksi')),
                            ExportColumn::make('event.name')->heading(__('Event')),
                            ExportColumn::make('buyer_name')->heading(__('Nama Pembeli')),
                            ExportColumn::make('is_member')->heading(__('Member')),
                            ExportColumn::make('member_reference')->heading(__('Barcode')),
                            ExportColumn::make('payment_method')
                                ->heading(__('Metode Bayar'))
                                ->formatStateUsing(fn (TicketPaymentMethod $state): string => $state->label()),
                            ExportColumn::make('price')->heading(__('Harga')),
                            ExportColumn::make('soldByUser.name')->heading(__('Kasir')),
                            ExportColumn::make('created_at')->heading(__('Waktu')),
                        ]),
                ]),
        ];
    }
}
