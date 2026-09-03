<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Signing a phone in.
 *
 * The web gets its guarantees from Fortify and the session. None of that
 * reaches a bearer token, so each one is rebuilt here — and each one is
 * worth a test precisely because it would fail silently and permissively.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ['ViewAny:ObChecklist', 'Create:ObChecklist'];

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('budi@tangcity.com|127.0.0.1');
    }

    public function test_a_worker_can_sign_in_and_receives_a_token(): void
    {
        $this->employee();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'budi@tangcity.com',
            'password' => 'rahasia-sekali',
            'device_name' => 'Redmi Note 12',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'modules']]);

        $this->assertSame(['ob'], $response->json('user.modules'));
        $this->assertSame(1, User::query()->sole()->tokens()->count());
    }

    public function test_the_wrong_password_is_refused(): void
    {
        $this->employee();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'budi@tangcity.com',
            'password' => 'salah',
            'device_name' => 'Redmi Note 12',
        ])->assertJsonValidationErrors('email');

        $this->assertSame(0, User::query()->sole()->tokens()->count());
    }

    /**
     * PreventInactiveUserLogin does this for the web. Without the same check
     * here, a deactivated account would be handed a token that outlives every
     * other way of shutting it out.
     */
    public function test_a_deactivated_account_is_refused(): void
    {
        $this->employee(['is_active' => false]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'budi@tangcity.com',
            'password' => 'rahasia-sekali',
            'device_name' => 'Redmi Note 12',
        ])->assertJsonValidationErrors('email');

        $this->assertSame(0, User::query()->sole()->tokens()->count());
    }

    public function test_repeated_failures_are_throttled(): void
    {
        $this->employee();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'budi@tangcity.com',
                'password' => 'salah',
                'device_name' => 'Redmi Note 12',
            ]);
        }

        // The sixth is refused even with the right password.
        $this->postJson('/api/v1/auth/login', [
            'email' => 'budi@tangcity.com',
            'password' => 'rahasia-sekali',
            'device_name' => 'Redmi Note 12',
        ])->assertStatus(429);
    }

    public function test_signing_out_revokes_only_that_token(): void
    {
        $user = $this->actingAsMobileUser(self::PERMISSIONS);
        $user->createToken('Tablet lain', ['mobile']);

        $this->postJson('/api/v1/auth/logout')->assertOk();

        // The other device stays signed in.
        $this->assertSame(1, $user->fresh()->tokens()->count());
    }

    /**
     * With two-factor on, the password step alone must not produce anything
     * that can reach a real endpoint.
     */
    public function test_two_factor_withholds_the_token_until_the_code_is_given(): void
    {
        $user = $this->employeeWithTwoFactor();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'budi@tangcity.com',
            'password' => 'rahasia-sekali',
            'device_name' => 'Redmi Note 12',
        ]);

        $response->assertOk()
            ->assertJson(['two_factor' => true])
            ->assertJsonMissingPath('token');

        $challenge = $response->json('challenge_token');

        // The half-finished login is useless anywhere else.
        $this->withToken($challenge)->getJson('/api/v1/me')->assertForbidden();

        // Fortify's provider only verifies; the code itself comes from the
        // Google2FA engine underneath it.
        $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));

        $completed = $this->withToken($challenge)->postJson('/api/v1/auth/two-factor-challenge', [
            'code' => $code,
            'device_name' => 'Redmi Note 12',
        ]);

        $completed->assertOk()->assertJsonStructure(['token']);

        // And the challenge token is spent, so the same half-login cannot be
        // completed a second time. The guard has to be forgotten first or the
        // successful request above would answer this one — see
        // TestCase::forgetAuthenticatedUser().
        $this->forgetAuthenticatedUser();

        $this->withToken($challenge)->postJson('/api/v1/auth/two-factor-challenge', [
            'code' => $code,
            'device_name' => 'Redmi Note 12',
        ])->assertUnauthorized();
    }

    public function test_a_wrong_two_factor_code_is_refused(): void
    {
        $this->employeeWithTwoFactor();

        $challenge = $this->postJson('/api/v1/auth/login', [
            'email' => 'budi@tangcity.com',
            'password' => 'rahasia-sekali',
            'device_name' => 'Redmi Note 12',
        ])->json('challenge_token');

        $this->withToken($challenge)->postJson('/api/v1/auth/two-factor-challenge', [
            'code' => '000000',
            'device_name' => 'Redmi Note 12',
        ])->assertJsonValidationErrors('code');
    }

    public function test_a_recovery_code_works_once(): void
    {
        $user = $this->employeeWithTwoFactor();
        $recovery = 'kode-pemulihan-satu';

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode([$recovery, 'kode-dua'])),
        ])->save();

        $challenge = fn (): string => $this->postJson('/api/v1/auth/login', [
            'email' => 'budi@tangcity.com',
            'password' => 'rahasia-sekali',
            'device_name' => 'Redmi Note 12',
        ])->json('challenge_token');

        $this->withToken($challenge())->postJson('/api/v1/auth/two-factor-challenge', [
            'recovery_code' => $recovery,
            'device_name' => 'Redmi Note 12',
        ])->assertOk();

        // Burned. A recovery code that survives its own use is a password
        // written on the back of the phone it protects.
        $this->forgetAuthenticatedUser();

        $this->withToken($challenge())->postJson('/api/v1/auth/two-factor-challenge', [
            'recovery_code' => $recovery,
            'device_name' => 'Redmi Note 12',
        ])->assertJsonValidationErrors('recovery_code');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function employee(array $attributes = []): User
    {
        $this->seed(ShieldSeeder::class);

        $user = User::factory()->create([
            'email' => 'budi@tangcity.com',
            'password' => Hash::make('rahasia-sekali'),
            ...$attributes,
        ]);

        $user->givePermissionTo(self::PERMISSIONS);

        return $user;
    }

    private function employeeWithTwoFactor(): User
    {
        $user = $this->employee();

        $user->forceFill([
            'two_factor_secret' => encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(['kode-satu'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }
}
