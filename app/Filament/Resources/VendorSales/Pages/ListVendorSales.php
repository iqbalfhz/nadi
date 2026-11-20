<?php

namespace App\Filament\Resources\VendorSales\Pages;

use App\Concerns\LogsDataAccess;
use App\Filament\Resources\VendorSales\Tables\VendorSalesTable;
use App\Filament\Resources\VendorSales\VendorSaleResource;
use App\Filament\Widgets\VendorSalesOverview;
use App\Filament\Widgets\VendorSettlementOverview;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class ListVendorSales extends ListRecords
{
    use LogsDataAccess;

    protected static string $resource = VendorSaleResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            VendorSalesOverview::class,
            VendorSettlementOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label(__('Export Excel'))
                ->after(fn () => $this->logDataAccess('Export Excel', 'Laporan Penjualan Bazar'))
                ->exports([VendorSalesTable::excelExport()]),
        ];
    }
}
