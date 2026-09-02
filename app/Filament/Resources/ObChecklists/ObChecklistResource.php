<?php

namespace App\Filament\Resources\ObChecklists;

use App\Filament\Resources\ObChecklists\Pages\ListObChecklists;
use App\Filament\Resources\ObChecklists\Tables\ObChecklistsTable;
use App\Models\ObChecklist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * @extends resource<ObChecklist>
 */
class ObChecklistResource extends Resource
{
    protected static ?string $model = ObChecklist::class;

    public static function getPluralModelLabel(): string
    {
        return __('Riwayat Checklist OB');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('OB');
    }

    public static function getNavigationLabel(): string
    {
        return __('Riwayat Checklist');
    }

    public static function getModelLabel(): string
    {
        return __('Checklist OB');
    }

    /**
     * @return Builder<ObChecklist>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['area', 'user'])
            ->withCount('media');
    }

    public static function table(Table $table): Table
    {
        return ObChecklistsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObChecklists::route('/'),
        ];
    }
}
