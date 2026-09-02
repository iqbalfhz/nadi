<?php

namespace App\Filament\Resources\QueueCategories;

use App\Filament\Resources\QueueCategories\Pages\CreateQueueCategory;
use App\Filament\Resources\QueueCategories\Pages\EditQueueCategory;
use App\Filament\Resources\QueueCategories\Pages\ListQueueCategories;
use App\Filament\Resources\QueueCategories\Schemas\QueueCategoryForm;
use App\Filament\Resources\QueueCategories\Tables\QueueCategoriesTable;
use App\Models\QueueCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class QueueCategoryResource extends Resource
{
    protected static ?string $model = QueueCategory::class;

    public static function getPluralModelLabel(): string
    {
        return __('Loket');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Antrian');
    }

    public static function getNavigationLabel(): string
    {
        return __('Loket');
    }

    public static function getModelLabel(): string
    {
        return __('Loket');
    }

    public static function form(Schema $schema): Schema
    {
        return QueueCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QueueCategoriesTable::configure($table);
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
            'index' => ListQueueCategories::route('/'),
            'create' => CreateQueueCategory::route('/create'),
            'edit' => EditQueueCategory::route('/{record}/edit'),
        ];
    }
}
