<?php

namespace App\Filament\Resources\VendorSales\Schemas;

use App\Enums\TicketPaymentMethod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class VendorSaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Koreksi Penjualan'))
                    ->description(__('Penjualan dicatat dari kasir di /app — halaman ini hanya untuk membetulkan data yang salah input.'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('quantity')
                            ->label(__('Jumlah'))
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Select::make('payment_method')
                            ->label(__('Metode Bayar'))
                            ->options(fn () => collect(TicketPaymentMethod::cases())
                                ->mapWithKeys(fn (TicketPaymentMethod $method) => [$method->value => $method->label()]))
                            ->required(),
                        TextInput::make('price')
                            ->label(__('Harga'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->helperText(__('Tidak dihitung ulang otomatis kalau Jumlah diubah — sesuaikan manual.'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
