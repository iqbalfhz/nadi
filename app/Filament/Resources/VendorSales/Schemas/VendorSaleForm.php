<?php

namespace App\Filament\Resources\VendorSales\Schemas;

use App\Enums\TicketPaymentMethod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VendorSaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->helperText('Kalau diubah, sesuaikan juga Harga secara manual jika perlu — tidak otomatis dihitung ulang.'),
                Select::make('payment_method')
                    ->label('Metode Bayar')
                    ->options(fn () => collect(TicketPaymentMethod::cases())
                        ->mapWithKeys(fn (TicketPaymentMethod $method) => [$method->value => $method->label()]))
                    ->required(),
                TextInput::make('price')
                    ->label('Harga')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix('Rp'),
            ]);
    }
}
