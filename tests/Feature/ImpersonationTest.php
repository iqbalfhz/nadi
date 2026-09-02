<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use App\Support\Impersonation;
use Database\Seeders\ShieldSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Masuk sebagai" hands over the target's entire access, so the rules around
 * it matter more than the feature itself. Every restriction here exists to
 * stop the support tool becoming a way to gain privileges.
 */
class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->seed(ShieldSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    private function employee(array $permissions = ['ViewAny:ObChecklist']): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_a_super_admin_can_step_into_an_employees_account(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->employee();

        $this->actingAs($admin);

        Impersonation::start($admin, $employee);

        $this->assertTrue(Auth::user()?->is($employee));
        $this->assertTrue(Impersonation::isActive());
        $this->assertTrue(Impersonation::impersonator()?->is($admin));
    }

    /**
     * The bug this feature shipped with: impersonating threw the admin
     * straight out to the login page on the very next request.
     *
     * Both panels run AuthenticateSession, which logs out whenever the
     * password hash kept in the session stops matching the signed-in user's —
     * its way of spotting a stolen session. Auth::login() leaves that stored
     * hash alone, so an intentional swap looked exactly like a theft.
     *
     * Asserted on the session directly rather than through a request: the
     * suite runs on SESSION_DRIVER=array, which starts every request with an
     * empty session, so the middleware never finds a stale hash to disagree
     * with. An earlier version of this test drove two real requests and passed
     * against the broken code — proving nothing.
     */
    public function test_the_session_password_hash_follows_the_impersonated_user(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->employee();

        $this->actingAs($admin);

        Impersonation::start($admin, $employee);

        $this->assertSame(
            $employee->getAuthPassword(),
            Session::get('password_hash_'.Auth::getDefaultDriver()),
            'AuthenticateSession compares this against the signed-in user and logs out on a mismatch.',
        );
    }

    public function test_the_session_password_hash_is_restored_on_the_way_back(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->employee();

        $this->actingAs($admin);

        Impersonation::start($admin, $employee);
        Impersonation::stop();

        $this->assertSame(
            $admin->getAuthPassword(),
            Session::get('password_hash_'.Auth::getDefaultDriver()),
        );
    }

    /**
     * A smoke test, not the guard above: it confirms an impersonated session
     * can actually load a panel page end to end.
     */
    public function test_an_impersonated_session_can_open_the_targets_panel(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->employee(['ViewAny:ObChecklist', 'Create:ObChecklist']);

        $this->actingAs($admin);
        Impersonation::start($admin, $employee);

        $this->get(Filament::getPanel('app')->getUrl())->assertSuccessful();

        $this->assertAuthenticatedAs($employee);
    }

    public function test_stopping_restores_the_original_account(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->employee();

        $this->actingAs($admin);
        Impersonation::start($admin, $employee);

        $restored = Impersonation::stop();

        $this->assertTrue($restored?->is($admin));
        $this->assertTrue(Auth::user()?->is($admin));
        $this->assertFalse(Impersonation::isActive());
    }

    public function test_stopping_when_nothing_is_impersonated_does_nothing(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin);

        $this->assertNull(Impersonation::stop());
        $this->assertTrue(Auth::user()?->is($admin));
    }

    /**
     * The decisive one. Hiding the button is tidiness; this is the guard. A
     * user-admin who could impersonate a super_admin would inherit everything.
     */
    public function test_someone_who_is_not_a_super_admin_cannot_impersonate(): void
    {
        $userAdmin = $this->employee(['ViewAny:User', 'Update:User']);
        $employee = $this->employee();

        $this->actingAs($userAdmin);

        $this->assertFalse(Impersonation::canImpersonate($userAdmin));
        $this->assertFalse(Impersonation::isImpersonatable($userAdmin, $employee));

        Impersonation::start($userAdmin, $employee);

        $this->assertTrue(Auth::user()?->is($userAdmin), 'The session must not have changed hands.');
        $this->assertFalse(Impersonation::isActive());
    }

    public function test_a_super_admin_cannot_impersonate_another_super_admin(): void
    {
        $admin = $this->superAdmin();
        $other = $this->superAdmin();

        $this->actingAs($admin);

        $this->assertFalse(Impersonation::isImpersonatable($admin, $other));
    }

    public function test_an_admin_cannot_impersonate_themselves(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin);

        $this->assertFalse(Impersonation::isImpersonatable($admin, $admin));
    }

    /**
     * A deactivated account is locked out deliberately; impersonating it would
     * walk straight past that lock.
     */
    public function test_a_deactivated_account_cannot_be_impersonated(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->employee();
        $employee->forceFill(['is_active' => false])->save();

        $this->actingAs($admin);

        $this->assertFalse(Impersonation::isImpersonatable($admin, $employee->refresh()));
    }

    /**
     * Impersonating someone who can open no panel only lands the admin on a
     * 403 with no explanation.
     */
    public function test_an_account_with_no_panel_access_cannot_be_impersonated(): void
    {
        $admin = $this->superAdmin();
        $nobody = User::factory()->create();

        $this->actingAs($admin);

        $this->assertNull(Impersonation::landingPanelFor($nobody));
        $this->assertFalse(Impersonation::isImpersonatable($admin, $nobody));
    }

    public function test_impersonating_cannot_be_nested(): void
    {
        $admin = $this->superAdmin();
        $first = $this->employee();
        $second = $this->employee();

        $this->actingAs($admin);
        Impersonation::start($admin, $first);

        $this->assertFalse(Impersonation::canImpersonate($admin));

        Impersonation::start($admin, $second);

        $this->assertTrue(Auth::user()?->is($first), 'A second hop would lose the way back to the real account.');
    }

    public function test_the_button_appears_only_for_accounts_that_may_be_impersonated(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->employee();
        $otherAdmin = $this->superAdmin();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertActionVisible(TestAction::make('impersonate')->table($employee))
            ->assertActionHidden(TestAction::make('impersonate')->table($otherAdmin))
            ->assertActionHidden(TestAction::make('impersonate')->table($admin));
    }

    public function test_starting_and_stopping_are_both_written_to_the_activity_log(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->employee();

        $this->actingAs($admin);

        Impersonation::start($admin, $employee);
        Impersonation::stop();

        $descriptions = ActivityLog::query()->pluck('description');

        $this->assertTrue($descriptions->contains(fn (string $d): bool => str_contains($d, 'Mulai masuk sebagai')));
        $this->assertTrue($descriptions->contains(fn (string $d): bool => str_contains($d, 'Selesai masuk sebagai')));
    }

    /**
     * The point of the whole logging half: a change made while impersonating
     * belongs to the employee's account, but the log must still name the admin
     * who actually made it. An audit trail that quietly lies is worse than
     * none.
     */
    public function test_changes_made_while_impersonating_name_the_real_admin(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->employee();

        $this->actingAs($admin);
        Impersonation::start($admin, $employee);

        Department::factory()->create();

        $entry = ActivityLog::query()
            ->where('subject_type', Department::class)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame($admin->name, $entry->impersonatorName());
    }

    public function test_ordinary_entries_carry_no_impersonation_mark(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin);

        Department::factory()->create();

        $entry = ActivityLog::query()
            ->where('subject_type', Department::class)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertNull($entry->impersonatorName());
    }
}
