<?php

namespace App\Filament\Resources\Barcodes;

use App\Filament\Resources\Barcodes\Pages\ListBarcodes;
use App\Filament\Resources\Barcodes\Tables\BarcodesTable;
use App\Models\Barcode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * @extends resource<Barcode>
 */
class BarcodeResource extends Resource
{
    protected static ?string $model = Barcode::class;

    public static function getPluralModelLabel(): string
    {
        return __('Riwayat Barcode');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Tools');
    }

    public static function getNavigationLabel(): string
    {
        return __('Riwayat Barcode');
    }

    public static function getModelLabel(): string
    {
        return __('Barcode');
    }

    /**
     * @return Builder<Barcode>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('creator');
    }

    public static function table(Table $table): Table
    {
        return BarcodesTable::configure($table, showCreator: true);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBarcodes::route('/'),
        ];
    }
}
