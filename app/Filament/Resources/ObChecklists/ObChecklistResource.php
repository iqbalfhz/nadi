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

    protected static ?string $pluralModelLabel = 'Riwayat Checklist OB';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'OB';

    protected static ?string $navigationLabel = 'Riwayat Checklist';

    protected static ?string $modelLabel = 'Checklist OB';

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
