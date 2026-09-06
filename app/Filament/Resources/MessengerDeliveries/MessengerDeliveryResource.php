<?php

namespace App\Filament\Resources\MessengerDeliveries;

use App\Filament\Resources\MessengerDeliveries\Pages\EditMessengerDelivery;
use App\Filament\Resources\MessengerDeliveries\Pages\ListMessengerDeliveries;
use App\Filament\Resources\MessengerDeliveries\Pages\ViewMessengerDelivery;
use App\Filament\Resources\MessengerDeliveries\Schemas\MessengerDeliveryForm;
use App\Filament\Resources\MessengerDeliveries\Schemas\MessengerDeliveryInfolist;
use App\Filament\Resources\MessengerDeliveries\Tables\MessengerDeliveriesTable;
use App\Models\MessengerDelivery;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * @extends resource<MessengerDelivery>
 */
class MessengerDeliveryResource extends Resource
{
    protected static ?string $model = MessengerDelivery::class;

    public static function getPluralModelLabel(): string
    {
        return __('Riwayat Pengiriman');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Messenger');
    }

    public static function getNavigationLabel(): string
    {
        return __('Riwayat Pengiriman');
    }

    public static function getModelLabel(): string
    {
        return __('Pengiriman');
    }

    /**
     * @return Builder<MessengerDelivery>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['sender', 'messenger']);
    }

    public static function table(Table $table): Table
    {
        return MessengerDeliveriesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MessengerDeliveryInfolist::configure($schema);
    }

    public static function form(Schema $schema): Schema
    {
        return MessengerDeliveryForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessengerDeliveries::route('/'),
            'view' => ViewMessengerDelivery::route('/{record}'),
            'edit' => EditMessengerDelivery::route('/{record}/edit'),
        ];
    }

    /**
     * Requests are raised by the person who needs the document moved, in
     * /app — that is who the courier calls when it is not where it should be.
     * A request with no requester behind it has nobody to ask.
     */
    public static function canCreate(): bool
    {
        return false;
    }
}
