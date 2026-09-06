<?php

namespace App\Filament\Resources\ObChecklists;

use App\Filament\Resources\ObChecklists\Pages\EditObChecklist;
use App\Filament\Resources\ObChecklists\Pages\ListObChecklists;
use App\Filament\Resources\ObChecklists\Pages\ViewObChecklist;
use App\Filament\Resources\ObChecklists\Schemas\ObChecklistForm;
use App\Filament\Resources\ObChecklists\Schemas\ObChecklistInfolist;
use App\Filament\Resources\ObChecklists\Tables\ObChecklistsTable;
use App\Models\ObChecklist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
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

    public static function infolist(Schema $schema): Schema
    {
        return ObChecklistInfolist::configure($schema);
    }

    public static function form(Schema $schema): Schema
    {
        return ObChecklistForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObChecklists::route('/'),
            'view' => ViewObChecklist::route('/{record}'),
            'edit' => EditObChecklist::route('/{record}/edit'),
        ];
    }

    /**
     * A checklist records that somebody went to a place and cleaned it. One
     * created at a desk records a visit that never happened, so there is no
     * create page here — the report comes from the field or not at all.
     */
    public static function canCreate(): bool
    {
        return false;
    }
}
