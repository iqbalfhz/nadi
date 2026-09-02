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

    public static function getPluralModelLabel(): string
    {
        return __('Short Link');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Tools');
    }

    public static function getNavigationLabel(): string
    {
        return __('Short Link');
    }

    public static function getModelLabel(): string
    {
        return __('Short Link');
    }

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
