<?php

namespace App\Console\Commands;

use App\Settings\QueueKioskSettings;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;
use Throwable;

/**
 * Re-encrypts everything APP_KEY protects, using the key currently configured.
 *
 * Rotating APP_KEY on its own does NOT work in this app: it would leave the
 * kiosk PIN, the Google Drive credentials and every 2FA secret undecryptable
 * — and the very Pengaturan pages that would let an admin re-enter the first
 * two load those values in order to render, so they break as well and there
 * is no way back through the UI.
 *
 * The supported route is Laravel's own two-key window:
 *
 *   1. Put the NEW key in APP_KEY and the OLD one in APP_PREVIOUS_KEYS, then
 *      deploy. Nothing breaks: the encrypter decrypts with the current key
 *      and falls back to the previous ones.
 *   2. Run this command. Every value below is decrypted through that fallback
 *      and written back encrypted with the new key.
 *   3. Remove APP_PREVIOUS_KEYS and deploy again. The old key is now dead.
 *
 * Run with --dry-run first: it proves every value still decrypts before
 * anything is written.
 */
class RotateEncryptedData extends Command
{
    protected $signature = 'nadi:rotate-encrypted-data {--dry-run : Check that every value can be decrypted, without writing anything}';

    protected $description = 'Re-encrypt APP_KEY-protected data (kiosk PIN, Google Drive credentials, 2FA secrets) with the current key';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->comment('Dry run — nothing will be written.');
        }

        try {
            // One transaction: a half-rotated database would leave some
            // values readable only with the old key and some only with the
            // new one, which no single configuration can then open.
            $rotated = DB::transaction(fn (): int => $this->rotateSettings($isDryRun) + $this->rotateTwoFactorSecrets($isDryRun));
        } catch (DecryptException $exception) {
            $this->error('A value could not be decrypted: '.$exception->getMessage());
            $this->newLine();
            $this->line('The old key is most likely missing from APP_PREVIOUS_KEYS. Nothing was written.');

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('Rotation aborted, nothing was written: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info($isDryRun
            ? "{$rotated} value(s) decrypted cleanly — safe to run for real."
            : "{$rotated} value(s) re-encrypted with the current APP_KEY.");

        if (! $isDryRun && $rotated > 0) {
            $this->line('Remove APP_PREVIOUS_KEYS and deploy again to retire the old key.');
        }

        return self::SUCCESS;
    }

    /**
     * Settings marked #[ShouldBeEncrypted] — currently the kiosk PIN plus the
     * Google Drive client secret and refresh token.
     */
    private function rotateSettings(bool $isDryRun): int
    {
        $rotated = 0;

        foreach ($this->encryptedSettingKeys() as [$group, $name]) {
            $row = DB::table('settings')->where('group', $group)->where('name', $name)->first();

            if ($row === null) {
                continue;
            }

            // Spatie stores every payload as JSON, so the ciphertext arrives
            // wrapped as a JSON string rather than raw.
            $ciphertext = json_decode((string) $row->payload, true);

            if (! is_string($ciphertext) || $ciphertext === '') {
                $this->line("  skipped {$group}.{$name} (empty)");

                continue;
            }

            $plaintext = Crypt::decrypt($ciphertext);

            if (! $isDryRun) {
                DB::table('settings')
                    ->where('id', $row->id)
                    ->update(['payload' => json_encode(Crypt::encrypt($plaintext))]);
            }

            $this->line("  {$group}.{$name}");
            $rotated++;
        }

        return $rotated;
    }

    /**
     * Fortify encrypts both of these on the users table, and they are what
     * stands between a user and their own account — a rotation that forgets
     * them locks every 2FA user out of their own login.
     */
    private function rotateTwoFactorSecrets(bool $isDryRun): int
    {
        $rotated = 0;

        DB::table('users')
            ->where(fn (Builder $query) => $query
                ->whereNotNull('two_factor_secret')
                ->orWhereNotNull('two_factor_recovery_codes'))
            ->orderBy('id')
            ->each(function (object $user) use ($isDryRun, &$rotated): void {
                $updates = [];

                foreach (['two_factor_secret', 'two_factor_recovery_codes'] as $column) {
                    $ciphertext = $user->{$column};

                    if (! is_string($ciphertext) || $ciphertext === '') {
                        continue;
                    }

                    // encrypt()/decrypt(), not the *String variants: both
                    // Fortify and Spatie's settings write these with the
                    // serializing pair, and mixing the two would change what
                    // the ciphertext unwraps to.
                    $updates[$column] = Crypt::encrypt(Crypt::decrypt($ciphertext));
                }

                if ($updates === []) {
                    return;
                }

                if (! $isDryRun) {
                    DB::table('users')->where('id', $user->id)->update($updates);
                }

                $this->line('  users#'.$user->id.' 2FA ('.implode(', ', array_keys($updates)).')');
                $rotated += count($updates);
            });

        return $rotated;
    }

    /**
     * Discovered by reflection rather than listed by hand, so a new encrypted
     * setting — or a whole new Settings class — is covered without anyone
     * remembering this command exists.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function encryptedSettingKeys(): array
    {
        $namespace = (new ReflectionClass(QueueKioskSettings::class))->getNamespaceName();

        $keys = [];

        foreach (glob(app_path('Settings/*.php')) ?: [] as $file) {
            /** @var class-string $class */
            $class = $namespace.'\\'.basename($file, '.php');

            if (! class_exists($class) || ! is_subclass_of($class, Settings::class)) {
                continue;
            }

            foreach ((new ReflectionClass($class))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                if ($property->getAttributes(ShouldBeEncrypted::class) === []) {
                    continue;
                }

                $keys[] = [$class::group(), $property->getName()];
            }
        }

        return $keys;
    }
}
