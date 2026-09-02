<?php

namespace Tests\Feature;

use App\Filament\App\Pages\Security as AppSecurity;
use App\Filament\Pages\Security as AdminSecurity;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Two-factor settings used to live on a Livewire page from the starter kit,
 * which brought its own application shell — a sidebar reading "Platform /
 * Repository / Documentation". Opening your own security settings meant leaving
 * NADI and landing somewhere that looked like a different product.
 *
 * These pin the replacement: one page, the same in both panels, inside the
 * Filament shell.
 */
class SecurityPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, class-string}>
     */
    public static function panels(): array
    {
        return [
            'admin' => ['admin', AdminSecurity::class],
            'app' => ['app', AppSecurity::class],
        ];
    }

    private function signIn(string $panelId): User
    {
        Filament::setCurrentPanel(Filament::getPanel($panelId));

        $user = User::factory()->create();

        $this->actingAs($user);

        return $user;
    }

    #[DataProvider('panels')]
    public function test_the_page_renders_in_both_panels(string $panelId, string $page): void
    {
        $this->signIn($panelId);

        Livewire::test($page)
            ->assertOk()
            ->assertSee('Autentikasi dua langkah')
            ->assertSee('Belum aktif');
    }

    /**
     * The enable button generates the secret when the modal opens, not when it
     * is submitted — that is what lets a QR code be shown to scan before the
     * confirmation code is typed.
     */
    #[DataProvider('panels')]
    public function test_opening_the_enable_modal_prepares_a_qr_code(string $panelId, string $page): void
    {
        $user = $this->signIn($panelId);

        $this->assertNull($user->two_factor_secret);

        $component = Livewire::test($page)->mountAction('enableTwoFactor');

        $this->assertNotNull($user->refresh()->two_factor_secret);
        $this->assertNotSame('', $component->instance()->qrCodeSvg);
    }

    #[DataProvider('panels')]
    public function test_a_wrong_confirmation_code_does_not_enable_it(string $panelId, string $page): void
    {
        $user = $this->signIn($panelId);

        Livewire::test($page)
            ->mountAction('enableTwoFactor')
            ->callMountedAction(['code' => '000000']);

        $this->assertFalse($user->refresh()->hasEnabledTwoFactorAuthentication());
    }

    #[DataProvider('panels')]
    public function test_an_account_with_two_factor_on_can_turn_it_off(string $panelId, string $page): void
    {
        $user = $this->signIn($panelId);

        $this->enableTwoFactorFor($user);

        $this->assertTrue($user->refresh()->hasEnabledTwoFactorAuthentication());

        Livewire::test($page)->callAction('disableTwoFactor');

        $this->assertFalse($user->refresh()->hasEnabledTwoFactorAuthentication());
    }

    #[DataProvider('panels')]
    public function test_recovery_codes_can_be_read_and_replaced(string $panelId, string $page): void
    {
        $user = $this->signIn($panelId);

        $this->enableTwoFactorFor($user);

        $component = Livewire::test($page);

        $before = $component->instance()->recoveryCodes();

        $this->assertNotEmpty($before, 'Enabling two-factor must hand out recovery codes.');

        $component->callAction('regenerateRecoveryCodes');

        $this->assertNotSame(
            $before,
            Livewire::test($page)->instance()->recoveryCodes(),
            'Regenerating must actually replace the codes — a stale set would still open the account.',
        );
    }

    /**
     * Fortify hands out a secret the moment "enable" is pressed. Walking away
     * without confirming would otherwise leave the account looking protected
     * while no authenticator app knows the secret.
     */
    #[DataProvider('panels')]
    public function test_an_abandoned_setup_is_cleared_on_the_next_visit(string $panelId, string $page): void
    {
        $user = $this->signIn($panelId);

        app(EnableTwoFactorAuthentication::class)($user);

        $this->assertNotNull($user->refresh()->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        Livewire::test($page)->assertOk();

        $this->assertNull($user->refresh()->two_factor_secret);
    }

    /**
     * Account security is nobody's to grant — an employee holding no Shield
     * permissions at all still manages their own two-factor settings.
     */
    public function test_an_employee_without_any_permission_can_still_open_it(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs(User::factory()->create());

        Livewire::test(AppSecurity::class)->assertOk();
    }

    private function enableTwoFactorFor(User $user): void
    {
        app(EnableTwoFactorAuthentication::class)($user);

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
    }
}
