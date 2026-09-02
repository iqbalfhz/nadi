<?php

namespace App\Filament\Pages;

use App\Concerns\LogsSettingsChanges;
use App\Settings\TelegramSettings;
use App\Support\QueueHealth;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
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

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sistem');
    }

    public static function getNavigationLabel(): string
    {
        return __('Telegram');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Pengaturan Telegram');
    }

    protected static string $settings = TelegramSettings::class;

    public function form(Schema $schema): Schema
    {
        $queue = QueueHealth::read();

        return $schema
            ->components([
                // Placed above the credentials because when someone opens this
                // page it is usually to find out why a report never arrived —
                // and a stalled worker, not a wrong token, is the likelier
                // cause once the settings have been working.
                Section::make('Status Antrean')
                    ->description('Laporan Checklist HK dikirim lewat antrean, bukan langsung — jadi pengawas tidak perlu menunggu Telegram saat menyimpan.')
                    ->icon(Heroicon::OutlinedQueueList)
                    ->columnSpanFull()
                    ->schema([
                        Text::make($queue->summary())
                            ->color($queue->color())
                            ->columnSpanFull(),
                    ]),
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
            // An escape hatch for exactly the situation that prompted this
            // page's queue panel: the worker is down, reports are piling up,
            // and the only fix used to be a terminal on the server. Shown only
            // when something is actually waiting.
            Action::make('drain')
                ->label('Proses Sekarang')
                ->icon(Heroicon::OutlinedPlay)
                ->color('warning')
                ->visible(fn (): bool => QueueHealth::read()->pending > 0)
                ->action(fn () => $this->drainQueue()),
            Action::make('retry')
                ->label('Coba Lagi yang Gagal')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('danger')
                ->visible(fn (): bool => QueueHealth::read()->failed > 0)
                ->action(fn () => $this->retryFailedJobs()),
        ];
    }

    /**
     * Works the queue inside this request, capped hard so a web worker can
     * never hang on it. Anything left over stays queued for the real worker.
     */
    private function drainQueue(): void
    {
        try {
            Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--max-time' => 15,
                '--tries' => 3,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        $queue = QueueHealth::read();

        if ($queue->pending === 0 && $queue->failed === 0) {
            Notification::make()
                ->success()
                ->title('Antrean selesai diproses')
                ->body('Semua yang tertunda sudah terkirim. Cek grup Telegram Anda.')
                ->send();

            return;
        }

        Notification::make()
            ->warning()
            ->title('Belum semuanya selesai')
            ->body($queue->summary().' Kalau ini terus terjadi, pekerja antrean di server perlu diperiksa.')
            ->persistent()
            ->send();
    }

    private function retryFailedJobs(): void
    {
        // Pushes them back onto the queue; they still need a worker — or the
        // "Proses Sekarang" button — to actually run.
        Artisan::call('queue:retry', ['id' => ['all']]);

        Notification::make()
            ->success()
            ->title('Dimasukkan kembali ke antrean')
            ->body('Pekerjaan yang gagal sudah diantrekan ulang. Klik "Proses Sekarang" kalau ingin langsung dijalankan.')
            ->send();
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
