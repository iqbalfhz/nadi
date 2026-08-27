<?php

namespace App\Filament\Pages;

use App\Concerns\LogsSettingsChanges;
use App\Settings\QueueKioskSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageQueueKioskSettings extends SettingsPage
{
    use HasPageShield, LogsSettingsChanges;

    public static function getSettingsLabel(): string
    {
        return 'Pengaturan Kiosk Antrian';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Antrian';

    protected static ?string $navigationLabel = 'Kiosk Antrian';

    protected static ?string $title = 'Pengaturan Kiosk Antrian';

    protected static string $settings = QueueKioskSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kiosk Ambil Nomor')
                    ->description('Tablet yang dipasang di lobi supaya pengunjung bisa ambil nomor antrian sendiri.')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->columnSpanFull()
                    // Both fields full width: their helper text is long enough
                    // that a half-column layout leaves one side towering over
                    // an almost empty other half.
                    ->schema([
                        TextInput::make('pin')
                            ->label('PIN Kiosk')
                            ->helperText('Diminta sekali saat tablet kiosk pertama kali disetup. Mengganti PIN di sini otomatis membuat semua kiosk yang sudah aktif diminta memasukkan PIN baru lagi — pakai ini kalau ada akses yang mencurigakan.')
                            ->required()
                            ->minLength(6)
                            ->maxLength(32)
                            ->columnSpanFull(),
                        Toggle::make('is_enabled')
                            ->label('Kiosk Aktif')
                            ->helperText('Matikan untuk langsung menutup akses ambil-nomor dari semua kiosk, tanpa perlu mengganti PIN.')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
