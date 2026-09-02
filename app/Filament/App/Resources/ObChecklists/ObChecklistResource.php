<?php

namespace App\Filament\App\Resources\ObChecklists;

use App\Filament\App\Resources\ObChecklists\Pages\CreateObChecklist;
use App\Filament\App\Resources\ObChecklists\Pages\ListObChecklists;
use App\Filament\App\Resources\ObChecklists\Schemas\ObChecklistForm;
use App\Filament\App\Resources\ObChecklists\Tables\ObChecklistsTable;
use App\Models\ObChecklist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * @extends resource<ObChecklist>
 */
class ObChecklistResource extends Resource
{
    protected static ?string $model = ObChecklist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('OB');
    }

    public static function getNavigationLabel(): string
    {
        return __('Checklist Saya');
    }

    public static function getModelLabel(): string
    {
        return __('Checklist OB');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Checklist Saya');
    }

    public static function form(Schema $schema): Schema
    {
        return ObChecklistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObChecklistsTable::configure($table);
    }

    /**
     * @return Builder<ObChecklist>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('area')
            ->where('user_id', Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObChecklists::route('/'),
            'create' => CreateObChecklist::route('/create'),
        ];
    }
}
