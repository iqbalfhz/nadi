<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\TicketPaymentMethod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('buyer_name')
                    ->label('Nama Pembeli')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_member')
                    ->label('Member')
                    ->helperText('Kalau diubah, sesuaikan juga Harga secara manual jika perlu — tidak otomatis dihitung ulang.'),
                TextInput::make('member_reference')
                    ->label('Barcode'),
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
