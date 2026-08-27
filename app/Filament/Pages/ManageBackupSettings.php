<?php

namespace App\Filament\Pages;

use App\Concerns\LogsSettingsChanges;
use App\Settings\BackupSettings;
use App\Support\BackupDrive;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Throwable;
use UnitEnum;

class ManageBackupSettings extends SettingsPage
{
    use HasPageShield, LogsSettingsChanges;

    public static function getSettingsLabel(): string
    {
        return 'Pengaturan Backup Penomoran Dokumen';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Backup Penomoran Dokumen';

    protected static ?string $title = 'Pengaturan Backup Penomoran Dokumen';

    protected static string $settings = BackupSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Backup Otomatis')
                    ->description('Berjalan tiap hari lewat scheduler. Hanya modul Penomoran Dokumen yang dicadangkan — bukan seluruh data NADI.')
                    ->icon(Heroicon::OutlinedCircleStack)
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Backup Otomatis Aktif')
                            ->helperText('Hanya mencadangkan data modul Penomoran Dokumen (jenis dokumen, PT, departemen, dokumen) — bukan seluruh fitur NADI. Kalau nonaktif, jadwal backup harian tetap ada tapi tidak akan benar-benar jalan.')
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('notify_email')
                            ->label('Email Google Drive & Notifikasi')
                            ->helperText('Akun Google Drive tujuan backup, sekaligus alamat yang menerima notifikasi kalau backup gagal.')
                            ->email()
                            ->required(fn (Get $get): bool => (bool) $get('enabled'))
                            ->columnSpanFull(),
                        TextInput::make('folder')
                            ->label('Folder di Google Drive')
                            ->helperText('Dibuat otomatis kalau belum ada. Kosongkan untuk simpan langsung di folder utama (root) Drive.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Kredensial Google Drive')
                    ->description('Diambil dari Google Cloud Console. Disimpan di database, bukan di .env, supaya bisa diganti tanpa deploy ulang.')
                    ->icon(Heroicon::OutlinedKey)
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextInput::make('client_id')
                            ->label('Google Client ID')
                            ->required(fn (Get $get): bool => (bool) $get('enabled')),
                        TextInput::make('client_secret')
                            ->label('Google Client Secret')
                            ->password()
                            ->revealable()
                            ->required(fn (Get $get): bool => (bool) $get('enabled')),
                        TextInput::make('refresh_token')
                            ->label('Google Refresh Token')
                            ->password()
                            ->revealable()
                            ->required(fn (Get $get): bool => (bool) $get('enabled'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * BackupSettings properties are plain (non-nullable) strings, but empty
     * text inputs dehydrate to null — coerce back to '' so save() doesn't
     * fail assigning null to a typed string property.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value === null) {
                $data[$key] = '';
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runNow')
                ->label('Jalankan Backup Sekarang')
                ->icon(Heroicon::OutlinedPlay)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Data modul Penomoran Dokumen (jenis dokumen, PT, departemen, dokumen) akan langsung dikirim ke Google Drive yang dikonfigurasi di atas.')
                ->action(function (): void {
                    try {
                        BackupDrive::ensureBackupFolderExists();

                        $exitCode = Artisan::call('backup:run', ['--only-to-disk' => 'google']);
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Backup gagal')
                            ->body($exception->getMessage())
                            ->send();

                        return;
                    }

                    if ($exitCode !== 0) {
                        Notification::make()
                            ->danger()
                            ->title('Backup gagal')
                            ->body(Artisan::output())
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Backup berhasil dikirim ke Google Drive')
                        ->send();
                }),
        ];
    }
}
