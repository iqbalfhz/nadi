<?php

namespace App\Filament\Resources\ObAreas;

use App\Filament\Resources\ObAreas\Pages\CreateObArea;
use App\Filament\Resources\ObAreas\Pages\EditObArea;
use App\Filament\Resources\ObAreas\Pages\ListObAreas;
use App\Filament\Resources\ObAreas\Schemas\ObAreaForm;
use App\Filament\Resources\ObAreas\Tables\ObAreasTable;
use App\Models\ObArea;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ObAreaResource extends Resource
{
    protected static ?string $model = ObArea::class;

    public static function getPluralModelLabel(): string
    {
        return __('Area/Titik OB');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('OB');
    }

    public static function getNavigationLabel(): string
    {
        return __('Area/Titik');
    }

    public static function getModelLabel(): string
    {
        return __('Area/Titik OB');
    }

    public static function form(Schema $schema): Schema
    {
        return ObAreaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObAreasTable::configure($table);
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
            'index' => ListObAreas::route('/'),
            'create' => CreateObArea::route('/create'),
            'edit' => EditObArea::route('/{record}/edit'),
        ];
    }
}
