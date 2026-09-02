<?php

namespace App\Filament\Resources\VendorSales;

use App\Filament\Resources\VendorSales\Pages\EditVendorSale;
use App\Filament\Resources\VendorSales\Pages\ListVendorSales;
use App\Filament\Resources\VendorSales\Schemas\VendorSaleForm;
use App\Filament\Resources\VendorSales\Tables\VendorSalesTable;
use App\Models\VendorSale;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * @extends resource<VendorSale>
 */
class VendorSaleResource extends Resource
{
    protected static ?string $model = VendorSale::class;

    public static function getPluralModelLabel(): string
    {
        return __('Riwayat Penjualan Bazar');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Bazar');
    }

    public static function getNavigationLabel(): string
    {
        return __('Riwayat Penjualan Bazar');
    }

    public static function getModelLabel(): string
    {
        return __('Penjualan Bazar');
    }

    /**
     * @return Builder<VendorSale>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['bazaar', 'vendor', 'vendorProduct', 'soldByUser']);
    }

    public static function form(Schema $schema): Schema
    {
        return VendorSaleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorSalesTable::configure($table, canEdit: true);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorSales::route('/'),
            'edit' => EditVendorSale::route('/{record}/edit'),
        ];
    }
}
