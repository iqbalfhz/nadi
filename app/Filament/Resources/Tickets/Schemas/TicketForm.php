<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\TicketPaymentMethod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Koreksi Tiket')
                    ->description('Tiket dijual dari /app — halaman ini hanya untuk membetulkan data yang salah input.')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('buyer_name')
                            ->label('Nama Pembeli')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('member_reference')
                            ->label('Barcode')
                            ->maxLength(255),
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
                        // Last and full width: a toggle sitting in a half column
                        // next to a text input reads as an orphan, and its
                        // helper text needs the room.
                        Toggle::make('is_member')
                            ->label('Member')
                            ->helperText('Kalau diubah, sesuaikan juga Harga secara manual jika perlu — tidak otomatis dihitung ulang.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
