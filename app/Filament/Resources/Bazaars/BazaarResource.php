<?php

namespace App\Filament\Resources\Bazaars;

use App\Filament\Resources\Bazaars\Pages\CreateBazaar;
use App\Filament\Resources\Bazaars\Pages\EditBazaar;
use App\Filament\Resources\Bazaars\Pages\ListBazaars;
use App\Filament\Resources\Bazaars\Schemas\BazaarForm;
use App\Filament\Resources\Bazaars\Tables\BazaarsTable;
use App\Models\Bazaar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * @extends resource<Bazaar>
 */
class BazaarResource extends Resource
{
    protected static ?string $model = Bazaar::class;

    public static function getPluralModelLabel(): string
    {
        return __('Bazar');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Bazar');
    }

    public static function getNavigationLabel(): string
    {
        return __('Bazar');
    }

    public static function getModelLabel(): string
    {
        return __('Bazar');
    }

    /**
     * @return Builder<Bazaar>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['vendors', 'sales']);
    }

    public static function form(Schema $schema): Schema
    {
        return BazaarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BazaarsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBazaars::route('/'),
            'create' => CreateBazaar::route('/create'),
            'edit' => EditBazaar::route('/{record}/edit'),
        ];
    }
}
