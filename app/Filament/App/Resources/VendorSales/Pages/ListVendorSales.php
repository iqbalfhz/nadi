<?php

namespace App\Filament\App\Resources\VendorSales\Pages;

use App\Concerns\LogsDataAccess;
use App\Enums\PricingUnit;
use App\Enums\TicketPaymentMethod;
use App\Filament\App\Resources\VendorSales\VendorSaleResource;
use App\Filament\Widgets\VendorSalesOverview;
use App\Filament\Widgets\VendorSettlementOverview;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column as ExportColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListVendorSales extends ListRecords
{
    use LogsDataAccess;

    protected static string $resource = VendorSaleResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            VendorSalesOverview::make(['scopeToToday' => true]),
            VendorSettlementOverview::make(['scopeToToday' => true]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Excel')
                ->after(fn () => $this->logDataAccess('Export Excel', 'Laporan Penjualan Bazar (/app)'))
                ->exports([
                    ExcelExport::make('penjualan-bazar')
                        ->withFilename(fn (): string => 'penjualan-bazar-'.now()->format('Y-m-d'))
                        ->withColumns([
                            ExportColumn::make('transaction_number')->heading('No. Transaksi'),
                            ExportColumn::make('bazaar.name')->heading('Bazar'),
                            ExportColumn::make('vendor.name')->heading('Kios'),
                            ExportColumn::make('vendorProduct.name')->heading('Produk'),
                            ExportColumn::make('quantity')->heading('Jumlah'),
                            ExportColumn::make('pricing_unit')
                                ->heading('Satuan')
                                ->formatStateUsing(fn (PricingUnit $state): string => $state->label()),
                            ExportColumn::make('payment_method')
                                ->heading('Metode Bayar')
                                ->formatStateUsing(fn (TicketPaymentMethod $state): string => $state->label()),
                            ExportColumn::make('price')->heading('Subtotal'),
                            ExportColumn::make('tax_rate')->heading('Tarif PB1 (%)'),
                            ExportColumn::make('tax_amount')->heading('PB1'),
                            ExportColumn::make('soldByUser.name')->heading('Kasir'),
                            ExportColumn::make('created_at')->heading('Waktu'),
                        ]),
                ]),
        ];
    }
}
