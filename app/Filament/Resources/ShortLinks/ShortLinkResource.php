<?php

namespace App\Filament\Resources\ShortLinks;

use App\Filament\Resources\ShortLinks\Pages\ListShortLinks;
use App\Filament\Resources\ShortLinks\Tables\ShortLinksTable;
use App\Models\ShortLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * @extends resource<ShortLink>
 */
class ShortLinkResource extends Resource
{
    protected static ?string $model = ShortLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = 'Tools';

    protected static ?string $navigationLabel = 'Short Link';

    protected static ?string $modelLabel = 'Short Link';

    /**
     * @return Builder<ShortLink>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('creator');
    }

    public static function table(Table $table): Table
    {
        return ShortLinksTable::configure($table, showCreator: true);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShortLinks::route('/'),
        ];
    }
}
