<?php

namespace App\Filament\Pages;

use App\Settings\QueueKioskSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageQueueKioskSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Antrian';

    protected static ?string $navigationLabel = 'Kiosk Antrian';

    protected static ?string $title = 'Pengaturan Kiosk Antrian';

    protected static string $settings = QueueKioskSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('pin')
                    ->label('PIN Kiosk')
                    ->helperText('Diminta sekali saat tablet kiosk pertama kali disetup. Mengganti PIN di sini otomatis membuat semua kiosk yang sudah aktif diminta memasukkan PIN baru lagi — pakai ini kalau ada akses yang mencurigakan.')
                    ->required()
                    ->minLength(6)
                    ->maxLength(32),
                Toggle::make('is_enabled')
                    ->label('Kiosk Aktif')
                    ->helperText('Matikan untuk langsung menutup akses ambil-nomor dari semua kiosk, tanpa perlu mengganti PIN.')
                    ->required(),
            ]);
    }
}
