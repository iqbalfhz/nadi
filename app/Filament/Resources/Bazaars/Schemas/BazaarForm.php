<?php

namespace App\Filament\Resources\Bazaars\Schemas;

use App\Enums\PricingUnit;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BazaarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Bazar')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_open')
                    ->label('Bazar Dibuka')
                    ->helperText('Buka manual saat bazar mulai, tutup manual setelah selesai — tidak otomatis berdasarkan jadwal.')
                    ->default(false),

                Repeater::make('vendors')
                    ->relationship()
                    ->label('Kios')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kios')
                            ->required()
                            ->maxLength(255),

                        Repeater::make('products')
                            ->relationship()
                            ->label('Produk')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Produk')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),
                                Select::make('pricing_unit')
                                    ->label('Satuan Harga')
                                    ->options(fn () => collect(PricingUnit::cases())
                                        ->mapWithKeys(fn (PricingUnit $unit) => [$unit->value => $unit->label()]))
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),
                                TextInput::make('price')
                                    ->label(fn (Get $get): string => 'Harga ('.(PricingUnit::tryFrom($get('pricing_unit'))?->label() ?? 'pilih satuan dulu').')')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Produk')
                            ->collapsible(),
                    ])
                    ->columns(1)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah Kios')
                    ->collapsible(),
            ]);
    }
}
