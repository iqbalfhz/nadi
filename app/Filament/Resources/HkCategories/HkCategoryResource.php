<?php

namespace App\Filament\Resources\HkCategories;

use App\Filament\Resources\HkCategories\Pages\CreateHkCategory;
use App\Filament\Resources\HkCategories\Pages\EditHkCategory;
use App\Filament\Resources\HkCategories\Pages\ListHkCategories;
use App\Filament\Resources\HkCategories\Schemas\HkCategoryForm;
use App\Filament\Resources\HkCategories\Tables\HkCategoriesTable;
use App\Models\HkCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HkCategoryResource extends Resource
{
    protected static ?string $model = HkCategory::class;

    protected static ?string $modelLabel = 'Kategori HK';

    protected static ?string $pluralModelLabel = 'Kategori HK';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'HK';

    protected static ?string $navigationLabel = 'Kategori';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return HkCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HkCategoriesTable::configure($table);
    }

    /**
     * Both counts drive the index columns, and `inspections_count` is what the
     * delete guard reads — without eager counting it would fire a query per
     * row just to decide whether to grey out a button.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount(['areas', 'inspections']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHkCategories::route('/'),
            'create' => CreateHkCategory::route('/create'),
            'edit' => EditHkCategory::route('/{record}/edit'),
        ];
    }
}
