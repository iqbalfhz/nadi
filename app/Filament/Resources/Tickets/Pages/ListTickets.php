<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Enums\TicketPaymentMethod;
use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Widgets\TicketsOverview;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column as ExportColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListTickets extends ListRecords
{
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
                ->label('Export Excel')
                ->exports([
                    ExcelExport::make('tiket')
                        ->withFilename(fn (): string => 'penjualan-tiket-event-'.now()->format('Y-m-d'))
                        ->withColumns([
                            ExportColumn::make('transaction_number')->heading('No. Transaksi'),
                            ExportColumn::make('event.name')->heading('Event'),
                            ExportColumn::make('buyer_name')->heading('Nama Pembeli'),
                            ExportColumn::make('is_member')->heading('Member'),
                            ExportColumn::make('member_reference')->heading('Barcode'),
                            ExportColumn::make('payment_method')
                                ->heading('Metode Bayar')
                                ->formatStateUsing(fn (TicketPaymentMethod $state): string => $state->label()),
                            ExportColumn::make('price')->heading('Harga'),
                            ExportColumn::make('soldByUser.name')->heading('Kasir'),
                            ExportColumn::make('created_at')->heading('Waktu'),
                        ]),
                ]),
        ];
    }
}
