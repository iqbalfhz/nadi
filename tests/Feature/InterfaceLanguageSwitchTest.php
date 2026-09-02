<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\InterfaceLanguage;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

/**
 * The language choice is stored on the account, not in the session, so it
 * follows the person to every device they sign in from — the same way
 * Filament remembers a light/dark preference.
 */
class InterfaceLanguageSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_with_no_preference_follows_the_application(): void
    {
        $user = User::factory()->create(['locale' => null]);

        $this->assertSame(config('app.locale'), InterfaceLanguage::for($user));
    }

    /**
     * Null rather than a stored 'id' on purpose: changing APP_LOCALE later
     * should still move everyone who never chose for themselves.
     */
    public function test_a_stored_preference_wins_over_the_application_default(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->assertSame('en', InterfaceLanguage::for($user));
    }

    public function test_an_unsupported_stored_value_falls_back_rather_than_breaking(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->assertSame(InterfaceLanguage::fallback(), InterfaceLanguage::for($user));
    }

    public function test_choosing_a_language_is_remembered_on_the_account(): void
    {
        $user = User::factory()->create(['locale' => null]);

        $this->actingAs($user)
            ->from('/admin')
            ->post(route('language.switch'), ['locale' => 'en'])
            ->assertRedirect('/admin');

        $this->assertSame('en', $user->refresh()->locale);
    }

    public function test_an_unsupported_language_is_ignored(): void
    {
        $user = User::factory()->create(['locale' => 'id']);

        $this->actingAs($user)
            ->from('/admin')
            ->post(route('language.switch'), ['locale' => 'fr']);

        $this->assertSame('id', $user->refresh()->locale, 'An unknown locale must not be written to the account.');
    }

    public function test_a_signed_out_visitor_cannot_change_anything(): void
    {
        $this->post(route('language.switch'), ['locale' => 'en'])
            ->assertRedirect(route('login'));
    }

    public function test_english_translations_resolve(): void
    {
        App::setLocale('en');

        $this->assertSame('Account Security', __('Keamanan Akun'));
        $this->assertSame('Two-factor authentication', __('Autentikasi dua langkah'));
    }

    /**
     * Indonesian is the source language, so its strings pass straight through
     * — a lang/id.json entry for them would be redundant, and a missing one
     * must never show a blank.
     */
    public function test_indonesian_needs_no_dictionary_entry_of_its_own(): void
    {
        App::setLocale('id');

        $this->assertSame('Keamanan Akun', __('Keamanan Akun'));
    }

    /**
     * Asserted against rendered output rather than the panel's hook list,
     * which Filament keeps private — and rendering is what actually matters.
     */
    public function test_the_switcher_is_rendered_in_both_panels(): void
    {
        $this->seed(ShieldSeeder::class);

        $admin = User::factory()->create(['locale' => 'id']);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->get(Filament::getPanel('admin')->getUrl())
            ->assertSuccessful()
            ->assertSee(route('language.switch'))
            ->assertSee('English');

        $employee = User::factory()->create(['locale' => 'id']);
        $employee->givePermissionTo(['ViewAny:ObChecklist', 'Create:ObChecklist']);

        $this->actingAs($employee)
            ->get(Filament::getPanel('app')->getUrl())
            ->assertSuccessful()
            ->assertSee(route('language.switch'))
            ->assertSee('English');
    }
}
