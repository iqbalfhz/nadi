<?php

namespace App\Filament\App\Resources\ShortLinks;

use App\Filament\App\Resources\ShortLinks\Pages\CreateShortLink;
use App\Filament\App\Resources\ShortLinks\Pages\ListShortLinks;
use App\Filament\App\Resources\ShortLinks\Schemas\ShortLinkForm;
use App\Filament\Resources\ShortLinks\Tables\ShortLinksTable;
use App\Models\ShortLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * @extends resource<ShortLink>
 */
class ShortLinkResource extends Resource
{
    protected static ?string $model = ShortLink::class;

    public static function getPluralModelLabel(): string
    {
        return __('Short Link Saya');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Tools');
    }

    public static function getNavigationLabel(): string
    {
        return __('Short Link Saya');
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
            ->where('created_by', Auth::id());
    }

    public static function form(Schema $schema): Schema
    {
        return ShortLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShortLinksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShortLinks::route('/'),
            'create' => CreateShortLink::route('/create'),
        ];
    }
}
