<?php

namespace App\Filament\Pages;

use App\Concerns\LogsSettingsChanges;
use App\Settings\MobileAppSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class ManageMobileAppSettings extends SettingsPage
{
    use HasPageShield, LogsSettingsChanges;

    /**
     * MAJOR.MINOR.PATCH+BUILD, matching `version:` in the app's pubspec.yaml.
     * The app compares the +BUILD number, because that is the only part
     * guaranteed to always go up.
     */
    private const VERSION_PATTERN = '/^\d+\.\d+\.\d+\+\d+$/';

    public static function getSettingsLabel(): string
    {
        return 'Pengaturan Aplikasi Mobile';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sistem');
    }

    public static function getNavigationLabel(): string
    {
        return __('Aplikasi Mobile');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Pengaturan Aplikasi Mobile');
    }

    protected static string $settings = MobileAppSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Versi Aplikasi'))
                    ->description(__('APK dibagikan langsung ke petugas, bukan lewat Play Store — jadi tidak ada pembaruan otomatis. Isian di sini yang memberi tahu aplikasi bahwa versinya sudah tertinggal.'))
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->columnSpanFull()
                    ->schema([
                        Text::make(__('Kosongkan keduanya kalau belum ingin memakai fitur ini. Selama Versi Terbaru kosong, aplikasi berjalan persis seperti sebelumnya: tanpa spanduk, tanpa blokir.'))
                            ->color('gray')
                            ->columnSpanFull(),
                        TextInput::make('latest_version')
                            ->label(__('Versi Terbaru'))
                            ->placeholder('1.0.3+4')
                            ->helperText(__('Versi terbaru yang sudah dirilis. Petugas dengan versi lebih lama akan melihat spanduk "Versi baru tersedia", tapi tetap bisa bekerja seperti biasa.'))
                            ->regex(self::VERSION_PATTERN)
                            ->validationMessages([
                                'regex' => __('Format versi harus seperti 1.0.3+4 — sama persis dengan yang tertulis di pubspec.yaml aplikasi.'),
                            ])
                            ->columnSpanFull(),
                        TextInput::make('minimum_version')
                            ->label(__('Versi Minimum'))
                            ->placeholder('1.0.0+1')
                            // The warning matters more than the field does.
                            // A blocked officer cannot file anything at all,
                            // and their queued outbox is held with them.
                            ->helperText(__('Versi paling lama yang masih boleh dipakai. Petugas di bawah versi ini akan DIBLOKIR sampai memperbarui. Naikkan hanya untuk perbaikan yang benar-benar wajib — petugas yang terblokir di tengah shift tidak bisa melapor sama sekali, dan laporan yang sudah mengantre di HP-nya ikut tertahan.'))
                            ->regex(self::VERSION_PATTERN)
                            ->validationMessages([
                                'regex' => __('Format versi harus seperti 1.0.0+1 — sama persis dengan yang tertulis di pubspec.yaml aplikasi.'),
                            ])
                            ->columnSpanFull(),
                        TextInput::make('download_url')
                            ->label(__('Tautan Unduh APK'))
                            ->placeholder('https://...')
                            ->helperText(__('Opsional. Kalau diisi, spanduk di aplikasi bisa langsung mengarahkan petugas ke sini. Kalau dikosongkan, petugas hanya diberi tahu bahwa ada versi baru.'))
                            ->url()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
