<?php

namespace App\Filament\Pages;

use App\Concerns\LogsSettingsChanges;
use App\Settings\TelegramSettings;
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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnitEnum;

class ManageTelegramSettings extends SettingsPage
{
    use HasPageShield, LogsSettingsChanges;

    public static function getSettingsLabel(): string
    {
        return 'Pengaturan Telegram';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Telegram';

    protected static ?string $title = 'Pengaturan Telegram';

    protected static string $settings = TelegramSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Notifikasi Grup Telegram')
                    ->description('Setiap laporan Checklist HK yang masuk dikirim otomatis ke grup Telegram.')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Kirim ke Telegram')
                            ->helperText('Kalau dimatikan, laporan tetap tersimpan dan tampil di menu Laporan — hanya pengirimannya ke grup yang berhenti.')
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('bot_token')
                            ->label('Bot Token')
                            ->helperText('Didapat dari @BotFather di Telegram, bentuknya seperti 123456789:AAF... Simpan baik-baik, siapa pun yang punya token ini bisa mengirim atas nama bot Anda.')
                            ->password()
                            ->revealable()
                            ->required(fn (Get $get): bool => (bool) $get('enabled'))
                            ->columnSpanFull(),
                        TextInput::make('chat_id')
                            ->label('Chat ID Grup')
                            ->helperText('ID grup tujuan, biasanya diawali tanda minus (contoh: -1001234567890). Tambahkan bot ke grup dulu, baru ID-nya bisa dipakai.')
                            ->required(fn (Get $get): bool => (bool) $get('enabled'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label('Kirim Tes')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                // Reads the saved settings, not the form state: the point is
                // to prove what the job will actually use.
                ->action(fn () => $this->sendTestMessage()),
        ];
    }

    /**
     * Verifies the token and chat ID against Telegram without having to file a
     * fake inspection to find out they were wrong.
     */
    private function sendTestMessage(): void
    {
        $settings = app(TelegramSettings::class);

        if (! $settings->isReady()) {
            Notification::make()
                ->warning()
                ->title('Belum bisa dites')
                ->body('Nyalakan "Kirim ke Telegram", isi Bot Token dan Chat ID Grup, lalu simpan dulu sebelum mengirim tes.')
                ->send();

            return;
        }

        try {
            $response = Http::timeout(15)->asJson()->post($settings->endpoint('sendMessage'), [
                'chat_id' => $settings->chat_id,
                'text' => "✅ Tes koneksi NADI\n\nKalau pesan ini muncul di grup, pengaturan Telegram sudah benar dan laporan Checklist HK akan terkirim ke sini.",
            ]);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title('Tidak bisa menghubungi Telegram')
                ->body('Server tidak berhasil menjangkau Telegram. Cek koneksi internet server, lalu coba lagi. Rincian teknisnya tercatat di log.')
                ->persistent()
                ->send();

            return;
        }

        if ($response->successful()) {
            Notification::make()
                ->success()
                ->title('Pesan tes terkirim')
                ->body('Cek grup Telegram Anda — pesan tes seharusnya sudah muncul di sana.')
                ->send();

            return;
        }

        // Telegram's own wording ("chat not found", "Unauthorized") is the
        // machine text that must not reach the screen; it goes to the log and
        // the admin gets the two things that are actually ever wrong here.
        Log::error('Tes Telegram gagal.', [
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
        ]);

        Notification::make()
            ->danger()
            ->title('Telegram menolak pesan tes')
            ->body('Biasanya salah satu dari dua hal: Bot Token-nya keliru, atau bot belum ditambahkan sebagai anggota grup yang Chat ID-nya Anda isi. Rincian teknisnya tercatat di log.')
            ->persistent()
            ->send();
    }
}
