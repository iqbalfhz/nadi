<?php

namespace App\Filament\App\Resources\MessengerDeliveries;

use App\Filament\App\Resources\MessengerDeliveries\Pages\CreateMessengerDelivery;
use App\Filament\App\Resources\MessengerDeliveries\Pages\ListMessengerDeliveries;
use App\Filament\App\Resources\MessengerDeliveries\Schemas\MessengerDeliveryForm;
use App\Filament\App\Resources\MessengerDeliveries\Tables\MessengerDeliveriesTable;
use App\Models\MessengerDelivery;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * @extends resource<MessengerDelivery>
 */
class MessengerDeliveryResource extends Resource
{
    protected static ?string $model = MessengerDelivery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Messenger';

    protected static ?string $navigationLabel = 'Pengiriman Saya';

    protected static ?string $modelLabel = 'Pengiriman Saya';

    protected static ?string $pluralModelLabel = 'Pengiriman Saya';

    public static function form(Schema $schema): Schema
    {
        return MessengerDeliveryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MessengerDeliveriesTable::configure($table);
    }

    /**
     * @return Builder<MessengerDelivery>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('messenger')
            ->where('sender_id', Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessengerDeliveries::route('/'),
            'create' => CreateMessengerDelivery::route('/create'),
        ];
    }
}
