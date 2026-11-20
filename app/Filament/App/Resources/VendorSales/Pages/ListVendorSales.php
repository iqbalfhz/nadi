<?php

namespace App\Filament\App\Resources\VendorSales\Pages;

use App\Concerns\LogsDataAccess;
use App\Filament\App\Resources\VendorSales\VendorSaleResource;
use App\Filament\Resources\VendorSales\Tables\VendorSalesTable;
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
            VendorSalesOverview::make(['scopeToToday' => true]),
            VendorSettlementOverview::make(['scopeToToday' => true]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label(__('Export Excel'))
                ->after(fn () => $this->logDataAccess('Export Excel', 'Laporan Penjualan Bazar (/app)'))
                ->exports([VendorSalesTable::excelExport()]),
        ];
    }
}
