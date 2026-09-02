<?php

namespace App\Filament\App\Resources\VendorSales;

use App\Filament\App\Resources\VendorSales\Pages\ListVendorSales;
use App\Filament\Resources\VendorSales\Tables\VendorSalesTable;
use App\Models\VendorSale;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * "Laporan Penjualan Bazar" — deliberately NOT scoped to Auth::id(), same
 * rationale as the Tiket Event report: any cashier needs to see the whole
 * bazaar's combined sales (every cashier, every kios) to close out, not just
 * what they personally sold.
 *
 * @extends resource<VendorSale>
 */
class VendorSaleResource extends Resource
{
    protected static ?string $model = VendorSale::class;

    public static function getModelLabel(): string
    {
        return __('Penjualan Bazar');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Laporan Penjualan Bazar');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Bazar');
    }

    public static function getNavigationLabel(): string
    {
        return __('Laporan Penjualan Bazar');
    }

    /**
     * @return Builder<VendorSale>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['bazaar', 'vendor', 'vendorProduct', 'soldByUser']);
    }

    public static function table(Table $table): Table
    {
        return VendorSalesTable::configure($table, defaultToLatestBazaar: true, defaultToToday: true);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorSales::route('/'),
        ];
    }
}
