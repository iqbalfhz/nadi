<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;

/**
 * Two-factor management, rendered inside a Filament panel.
 *
 * This used to live on a Livewire page from the starter kit, which came with
 * its own application shell — a different sidebar reading "Platform /
 * Repository / Documentation", none of which belongs to NADI. Anyone opening
 * their security settings left the product and landed somewhere that looked
 * like a different application.
 *
 * Shared by both panels through a trait rather than one page class, because
 * Filament discovers pages per panel directory; the two page classes are thin
 * and this holds everything they do.
 *
 * @property-read string $qrCodeSvg
 */
trait ManagesTwoFactor
{
    /**
     * The setup secret, held only while the enable modal is open. Never
     * persisted to the component state beyond that: it is the shared secret
     * that makes the codes work.
     */
    public string $manualSetupKey = '';

    public string $qrCodeSvg = '';

    public function mount(): void
    {
        // Fortify hands out a secret the moment "enable" is pressed, but the
        // account is only really protected once a code has been confirmed. An
        // abandoned setup leaves a secret behind that would otherwise show as
        // "enabled" while no authenticator app knows about it.
        if (Fortify::confirmsTwoFactorAuthentication() && $this->user()->two_factor_secret && ! $this->user()->two_factor_confirmed_at) {
            app(DisableTwoFactorAuthentication::class)($this->user());
        }
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->user()->hasEnabledTwoFactorAuthentication();
    }

    /**
     * @return array<int, string>
     */
    public function recoveryCodes(): array
    {
        $codes = $this->user()->two_factor_recovery_codes;

        if (blank($codes)) {
            return [];
        }

        try {
            $decoded = json_decode(decrypt($codes), true);
        } catch (Exception) {
            return [];
        }

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    /**
     * Called from the Blade view. Kept as a method rather than a variable set
     * in an `@php` block: a multi-line `@php` block inside a Filament page view
     * makes the whole component render empty, which surfaces as Livewire's
     * unhelpful `Attempt to read property "childNodes" on null`.
     */
    public function recoveryCodeCount(): int
    {
        return count($this->recoveryCodes());
    }

    protected function enableAction(): Action
    {
        return Action::make('enableTwoFactor')
            ->label('Aktifkan 2FA')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->visible(fn (): bool => ! $this->isTwoFactorEnabled())
            // Generating the secret on mount, not on submit, is what lets the
            // modal show a QR code to scan before the confirmation code is
            // typed.
            ->mountUsing(function (): void {
                app(EnableTwoFactorAuthentication::class)($this->user());

                $this->loadSetupData();
            })
            ->modalHeading('Aktifkan autentikasi dua langkah')
            ->modalDescription('Pindai kode QR ini dengan aplikasi authenticator di HP Anda — Google Authenticator, Authy, atau sejenisnya — lalu masukkan 6 digit kode yang muncul.')
            ->modalSubmitActionLabel('Aktifkan')
            ->schema([
                Text::make(fn (): Htmlable => $this->setupInstructions())
                    ->columnSpanFull(),
                TextInput::make('code')
                    ->label('Kode dari aplikasi')
                    ->helperText('Enam digit yang sedang ditampilkan aplikasi authenticator Anda.')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                try {
                    app(ConfirmTwoFactorAuthentication::class)($this->user(), (string) $data['code']);
                } catch (Exception) {
                    // Fortify throws a validation exception whose message names
                    // the field, not the problem. Say the two things that are
                    // actually ever wrong.
                    Notification::make()
                        ->danger()
                        ->title('Kode tidak cocok')
                        ->body('Pastikan Anda memasukkan kode yang sedang tampil, bukan yang sudah berganti. Cek juga jam di HP Anda — kode ini bergantung pada waktu yang tepat.')
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Autentikasi dua langkah aktif')
                    ->body('Sekarang simpan kode pemulihannya — itu satu-satunya jalan masuk kalau HP Anda hilang.')
                    ->send();
            });
    }

    protected function disableAction(): Action
    {
        return Action::make('disableTwoFactor')
            ->label('Nonaktifkan 2FA')
            ->icon(Heroicon::OutlinedShieldExclamation)
            ->color('danger')
            ->visible(fn (): bool => $this->isTwoFactorEnabled())
            ->requiresConfirmation()
            ->modalHeading('Nonaktifkan autentikasi dua langkah?')
            ->modalDescription('Setelah ini, akun Anda hanya dijaga password. Kode pemulihan yang lama juga ikut hangus.')
            ->modalSubmitActionLabel('Ya, nonaktifkan')
            ->action(function (): void {
                app(DisableTwoFactorAuthentication::class)($this->user());

                Notification::make()
                    ->warning()
                    ->title('Autentikasi dua langkah dimatikan')
                    ->send();
            });
    }

    protected function recoveryCodesAction(): Action
    {
        return Action::make('showRecoveryCodes')
            ->label('Lihat kode pemulihan')
            ->icon(Heroicon::OutlinedKey)
            ->color('gray')
            ->visible(fn (): bool => $this->isTwoFactorEnabled())
            ->modalHeading('Kode pemulihan')
            ->modalDescription('Simpan di tempat aman dan terpisah dari HP Anda. Setiap kode hanya bisa dipakai sekali.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->schema([
                Text::make(fn (): Htmlable => $this->recoveryCodeList())
                    ->columnSpanFull(),
            ]);
    }

    protected function regenerateRecoveryCodesAction(): Action
    {
        return Action::make('regenerateRecoveryCodes')
            ->label('Buat ulang kode pemulihan')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->visible(fn (): bool => $this->isTwoFactorEnabled())
            ->requiresConfirmation()
            ->modalHeading('Buat ulang kode pemulihan?')
            ->modalDescription('Kode lama langsung hangus. Kalau Anda sudah menyimpannya di suatu tempat, catatan itu jadi tidak berlaku.')
            ->modalSubmitActionLabel('Buat ulang')
            ->action(function (): void {
                app(GenerateNewRecoveryCodes::class)($this->user());

                Notification::make()
                    ->success()
                    ->title('Kode pemulihan baru dibuat')
                    ->body('Buka "Lihat kode pemulihan" lalu simpan yang baru.')
                    ->send();
            });
    }

    private function loadSetupData(): void
    {
        $user = $this->user()->fresh();

        try {
            if ($user === null || blank($user->two_factor_secret)) {
                throw new Exception('Two-factor secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception $exception) {
            report($exception);

            $this->qrCodeSvg = '';
            $this->manualSetupKey = '';
        }
    }

    private function setupInstructions(): Htmlable
    {
        if ($this->qrCodeSvg === '') {
            return new HtmlString('<p class="fi-color-danger">Kode QR gagal dibuat. Tutup jendela ini dan coba lagi.</p>');
        }

        return new HtmlString(
            '<div class="flex flex-col items-center gap-3">'
            .'<div class="rounded-lg bg-white p-3">'.$this->qrCodeSvg.'</div>'
            .'<p class="text-sm text-gray-500 dark:text-gray-400">Tidak bisa memindai? Masukkan kunci ini secara manual:</p>'
            .'<code class="select-all rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-gray-800">'
            .e($this->manualSetupKey)
            .'</code>'
            .'</div>',
        );
    }

    private function recoveryCodeList(): Htmlable
    {
        $codes = $this->recoveryCodes();

        if ($codes === []) {
            return new HtmlString('<p>Belum ada kode pemulihan. Coba buat ulang lewat tombol di halaman ini.</p>');
        }

        $items = implode('', array_map(
            fn (string $code): string => '<li class="select-all font-mono text-sm">'.e($code).'</li>',
            $codes,
        ));

        return new HtmlString('<ul class="grid gap-1 sm:grid-cols-2">'.$items.'</ul>');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
