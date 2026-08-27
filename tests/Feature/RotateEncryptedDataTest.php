<?php

namespace Tests\Feature;

use App\Models\User;
use App\Settings\BackupSettings;
use App\Settings\QueueKioskSettings;
use Illuminate\Encryption\EncryptionServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Proves the whole APP_KEY rotation procedure, not just that the command
 * runs: encrypt under the old key, move to a new key with the old one kept
 * as a fallback, rotate, then drop the old key entirely and check every
 * value still opens. That last step is the one that matters — it is where a
 * half-done rotation would lock the office out of its own kiosk PIN and
 * Google Drive credentials with no way back through the UI.
 */
class RotateEncryptedDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_values_survive_a_full_rotation_and_the_old_key_being_dropped(): void
    {
        $oldKey = (string) config('app.key');

        $kiosk = app(QueueKioskSettings::class);
        $kiosk->pin = '246813';
        $kiosk->is_enabled = true;
        $kiosk->save();

        $backup = app(BackupSettings::class);
        $backup->client_secret = 'rahasia-google';
        $backup->refresh_token = 'token-refresh-google';
        $backup->save();

        $user = User::factory()->create([
            'two_factor_secret' => Crypt::encrypt('SECRETOTP'),
            'two_factor_recovery_codes' => Crypt::encrypt('kode-cadangan'),
        ]);

        // Step 1 of the procedure: new key in, old key kept as a fallback.
        $newKey = 'base64:'.base64_encode(random_bytes(32));
        $this->useKeys($newKey, previous: [$oldKey]);

        // Step 2: rewrite everything under the new key.
        $this->artisan('nadi:rotate-encrypted-data')->assertSuccessful();

        // Step 3: retire the old key. Anything the command missed becomes
        // unreadable right here.
        $this->useKeys($newKey, previous: []);

        $this->assertSame('246813', $this->settingValue('queue_kiosk', 'pin'));
        $this->assertSame('rahasia-google', $this->settingValue('backup', 'client_secret'));
        $this->assertSame('token-refresh-google', $this->settingValue('backup', 'refresh_token'));

        $user->refresh();
        $this->assertSame('SECRETOTP', Crypt::decrypt($user->two_factor_secret));
        $this->assertSame('kode-cadangan', Crypt::decrypt($user->two_factor_recovery_codes));
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $kiosk = app(QueueKioskSettings::class);
        $kiosk->pin = '246813';
        $kiosk->is_enabled = true;
        $kiosk->save();

        $before = DB::table('settings')->where('group', 'queue_kiosk')->where('name', 'pin')->value('payload');

        $this->artisan('nadi:rotate-encrypted-data', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(
            $before,
            DB::table('settings')->where('group', 'queue_kiosk')->where('name', 'pin')->value('payload'),
        );
    }

    public function test_it_refuses_and_writes_nothing_when_the_old_key_is_missing(): void
    {
        $kiosk = app(QueueKioskSettings::class);
        $kiosk->pin = '246813';
        $kiosk->is_enabled = true;
        $kiosk->save();

        $before = DB::table('settings')->where('group', 'queue_kiosk')->where('name', 'pin')->value('payload');

        // The mistake this guards against: swapping APP_KEY without listing
        // the old one in APP_PREVIOUS_KEYS.
        $this->useKeys('base64:'.base64_encode(random_bytes(32)), previous: []);

        $this->artisan('nadi:rotate-encrypted-data')->assertFailed();

        $this->assertSame(
            $before,
            DB::table('settings')->where('group', 'queue_kiosk')->where('name', 'pin')->value('payload'),
        );
    }

    /**
     * Reflection over app/Settings, so a future encrypted setting is picked
     * up without this command being touched.
     */
    public function test_it_finds_every_encrypted_setting_by_itself(): void
    {
        $kiosk = app(QueueKioskSettings::class);
        $kiosk->pin = '246813';
        $kiosk->is_enabled = true;
        $kiosk->save();

        $backup = app(BackupSettings::class);
        $backup->client_secret = 'rahasia';
        $backup->refresh_token = 'token';
        $backup->save();

        $this->artisan('nadi:rotate-encrypted-data', ['--dry-run' => true])
            ->expectsOutputToContain('queue_kiosk.pin')
            ->expectsOutputToContain('backup.client_secret')
            ->expectsOutputToContain('backup.refresh_token')
            ->assertSuccessful();
    }

    /**
     * The encrypter is a singleton built from config at boot, so changing the
     * key means rebuilding it — exactly what a deploy does.
     *
     * @param  array<int, string>  $previous
     */
    private function useKeys(string $key, array $previous): void
    {
        config(['app.key' => $key, 'app.previous_keys' => $previous]);

        $this->app->forgetInstance('encrypter');
        Crypt::clearResolvedInstances();

        (new EncryptionServiceProvider($this->app))->register();
    }

    private function settingValue(string $group, string $name): string
    {
        $payload = DB::table('settings')->where('group', $group)->where('name', $name)->value('payload');

        return (string) Crypt::decrypt((string) json_decode((string) $payload, true));
    }
}
