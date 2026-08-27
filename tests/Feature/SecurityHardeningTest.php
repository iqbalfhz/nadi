<?php

namespace Tests\Feature;

use App\Models\ShortLink;
use App\Models\User;
use App\Settings\QueueKioskSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    // ---- Short links: an internal-looking URL that goes anywhere ----

    public function test_a_link_to_an_untrusted_destination_shows_the_destination_first(): void
    {
        $link = ShortLink::factory()->create([
            'target_url' => 'https://situs-palsu.test/login',
            'created_by' => User::factory()->create()->id,
        ]);

        $this->get("/s/{$link->code}")
            ->assertOk()
            ->assertSee('Anda akan meninggalkan')
            ->assertSee('https://situs-palsu.test/login');
    }

    public function test_confirming_the_warning_follows_the_link(): void
    {
        $link = ShortLink::factory()->create([
            'target_url' => 'https://situs-palsu.test/login',
            'created_by' => User::factory()->create()->id,
        ]);

        $this->get("/s/{$link->code}?lanjut=1")
            ->assertRedirect('https://situs-palsu.test/login');
    }

    public function test_a_trusted_destination_still_redirects_in_one_click(): void
    {
        // The whole point of the feature is shortening Drive/Docs links —
        // making those cost an extra click would just get it abandoned.
        $link = ShortLink::factory()->create([
            'target_url' => 'https://drive.google.com/file/d/abc/view',
            'created_by' => User::factory()->create()->id,
        ]);

        $this->get("/s/{$link->code}")
            ->assertRedirect('https://drive.google.com/file/d/abc/view');
    }

    public function test_a_lookalike_domain_is_not_treated_as_trusted(): void
    {
        $link = ShortLink::factory()->create([
            'target_url' => 'https://drive.google.com.penipu.test/login',
            'created_by' => User::factory()->create()->id,
        ]);

        $this->get("/s/{$link->code}")->assertOk()->assertSee('Anda akan meninggalkan');
    }

    public function test_a_non_web_scheme_is_refused_outright(): void
    {
        $link = ShortLink::factory()->create([
            'target_url' => 'javascript:alert(1)',
            'created_by' => User::factory()->create()->id,
        ]);

        $this->get("/s/{$link->code}")->assertNotFound();
    }

    public function test_the_app_own_domain_is_trusted(): void
    {
        $link = ShortLink::factory()->create([
            'target_url' => config('app.url').'/admin/documents',
            'created_by' => User::factory()->create()->id,
        ]);

        $this->get("/s/{$link->code}")->assertRedirect(config('app.url').'/admin/documents');
    }

    // ---- Kiosk unlock token ----

    public function test_the_kiosk_cookie_does_not_reveal_the_pin(): void
    {
        $settings = app(QueueKioskSettings::class);
        $settings->pin = '135791';
        $settings->is_enabled = true;

        $token = $settings->unlockToken();

        // A bare sha256 of a six-digit PIN is a million guesses away from
        // being reversed — the stored token must not be that.
        $this->assertNotSame(hash('sha256', '135791'), $token);
        $this->assertSame(hash_hmac('sha256', '135791', (string) config('app.key')), $token);
    }

    public function test_the_kiosk_stays_locked_for_a_forged_cookie(): void
    {
        $settings = app(QueueKioskSettings::class);
        $settings->pin = '135791';
        $settings->is_enabled = true;
        $settings->save();

        // What an attacker could compute without knowing APP_KEY.
        $this->withCookie('queue_kiosk_unlocked', hash('sha256', '135791'))
            ->get(route('queue.kiosk.take'))
            ->assertRedirect(route('queue.kiosk.pin'));
    }

    // ---- Response headers ----

    public function test_every_response_carries_the_baseline_security_headers(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    // ---- Session cookie ----

    public function test_the_session_cookie_is_https_only_in_production(): void
    {
        // Booted in a separate process on purpose: config/session.php was
        // already require()d during this test run's own bootstrap, and PHP
        // will not evaluate the same file twice — re-requiring it here just
        // returns true, so the assertion would silently prove nothing.
        $this->assertSame('true', $this->sessionSecureUnder('production'));

        // Local dev is served over plain http, so it has to stay off there
        // or nobody could log in.
        $this->assertSame('false', $this->sessionSecureUnder('local'));
    }

    private function sessionSecureUnder(string $environment): string
    {
        $script = <<<'PHP_SCRIPT'
            require "vendor/autoload.php";
            $app = require "bootstrap/app.php";
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            echo var_export((bool) config("session.secure"), true);
            PHP_SCRIPT;

        return trim(Process::env(['APP_ENV' => $environment])
            ->path(base_path())
            ->run([PHP_BINARY, '-r', $script])
            ->output());
    }
}
