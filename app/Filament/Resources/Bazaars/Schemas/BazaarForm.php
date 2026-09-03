<?php

namespace App\Filament\Resources\Bazaars\Schemas;

use App\Enums\PricingUnit;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BazaarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Detail Bazar'))
                    ->description(__('Satu baris per kejadian bazar — dibuat baru tiap ada acara, bukan template berulang.'))
                    ->icon(Heroicon::OutlinedBuildingStorefront)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Nama Bazar'))
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_open')
                            ->label(__('Bazar Dibuka'))
                            ->helperText(__('Buka manual saat bazar mulai, tutup manual setelah selesai — tidak otomatis berdasarkan jadwal.'))
                            ->default(false),
                    ]),

                Section::make(__('Kios & Produk'))
                    ->description(__('Kios dan produknya diketik ulang tiap bazar — harga ditentukan per kombinasi kios + produk, bukan master data yang dipakai lagi.'))
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->columnSpanFull()
                    ->schema([
                        // Layout components default to a single grid column, so
                        // without columnSpanFull() this repeater renders in the
                        // left half of the 2-column form and leaves the whole
                        // right side blank.
                        Repeater::make('vendors')
                            ->relationship()
                            ->hiddenLabel()
                            ->columnSpanFull()
                            // Collapsed rows show a bare bar without this — with
                            // three or four kios that's unnavigable.
                            ->itemLabel(fn (array $state): string => blank($state['name'] ?? null)
                                ? 'Kios baru'
                                : $state['name'])
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('Nama Kios'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('tax_rate')
                                    ->label(__('PB1 (%)'))
                                    ->helperText(__('Ditambahkan di atas harga produk. Isi 0 untuk kios yang tidak dikenakan pajak.'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(10)
                                    ->suffix('%'),

                                Repeater::make('products')
                                    ->relationship()
                                    ->label(__('Produk'))
                                    ->columnSpanFull()
                                    ->itemLabel(fn (array $state): string => blank($state['name'] ?? null)
                                        ? 'Produk baru'
                                        : $state['name'])
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('Nama Produk'))
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('pricing_unit')
                                            ->label(__('Satuan Harga'))
                                            ->options(fn () => collect(PricingUnit::cases())
                                                ->mapWithKeys(fn (PricingUnit $unit) => [$unit->value => $unit->label()]))
                                            ->required()
                                            ->live(),
                                        TextInput::make('price')
                                            ->label(fn (Get $get): string => 'Harga ('.(PricingUnit::tryFrom($get('pricing_unit'))?->label() ?? 'pilih satuan dulu').')')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->prefix('Rp'),
                                        TextInput::make('initial_stock')
                                            ->label(fn (Get $get): string => 'Stok Awal ('.(PricingUnit::tryFrom($get('pricing_unit'))?->unitSuffix() ?? 'opsional').')')
                                            ->helperText(__('Kosongkan jika tidak ingin membatasi stok.'))
                                            ->numeric()
                                            ->minValue(0),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(1)
                                    ->addActionLabel('Tambah Produk')
                                    ->collapsible(),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Kios')
                            ->collapsible(),
                    ]),
            ]);
    }
}
