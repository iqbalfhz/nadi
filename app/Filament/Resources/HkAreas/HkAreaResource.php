<?php

namespace App\Filament\Resources\HkAreas;

use App\Filament\Resources\HkAreas\Pages\CreateHkArea;
use App\Filament\Resources\HkAreas\Pages\EditHkArea;
use App\Filament\Resources\HkAreas\Pages\ListHkAreas;
use App\Filament\Resources\HkAreas\Schemas\HkAreaForm;
use App\Filament\Resources\HkAreas\Tables\HkAreasTable;
use App\Models\HkArea;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HkAreaResource extends Resource
{
    protected static ?string $model = HkArea::class;

    protected static ?string $modelLabel = 'Titik HK';

    protected static ?string $pluralModelLabel = 'Titik HK';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'HK';

    protected static ?string $navigationLabel = 'Titik';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return HkAreaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HkAreasTable::configure($table);
    }

    /**
     * The category is shown on every row and the count backs the delete
     * guard, so both are loaded up front rather than per row.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('category')->withCount('inspections');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHkAreas::route('/'),
            'create' => CreateHkArea::route('/create'),
            'edit' => EditHkArea::route('/{record}/edit'),
        ];
    }
}
