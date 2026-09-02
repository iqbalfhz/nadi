<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * AdminUserSeeder runs on every deploy. It used to call updateOrCreate() with a
 * hardcoded password, so every push silently reset the super admin's password
 * to a value committed in plain text — a password change never survived the
 * next deploy, and anyone with repository access knew it.
 *
 * These pin the two halves of the replacement: the role is still restored every
 * time (that is what rescued the account when super_admin was deleted), while
 * the password is left alone once the account exists.
 */
class AdminAccountRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ShieldSeeder::class);
    }

    public function test_the_seeder_creates_the_admin_account_with_the_super_admin_role(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', AdminUserSeeder::EMAIL)->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('super_admin'));
    }

    public function test_running_the_seeder_again_does_not_touch_an_existing_password(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', AdminUserSeeder::EMAIL)->firstOrFail();
        $admin->forceFill(['password' => 'password-yang-dipilih-sendiri'])->save();

        // Stands in for the next deploy.
        $this->seed(AdminUserSeeder::class);

        $this->assertTrue(
            Hash::check('password-yang-dipilih-sendiri', $admin->refresh()->password),
            'A deploy must never reset a password the admin chose themselves.',
        );
    }

    /**
     * The half of the old behaviour that was worth keeping: an accidentally
     * removed super_admin role comes back on the next deploy.
     */
    public function test_running_the_seeder_again_restores_a_lost_super_admin_role(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', AdminUserSeeder::EMAIL)->firstOrFail();
        $admin->syncRoles([]);

        $this->assertFalse($admin->refresh()->hasRole('super_admin'));

        $this->seed(AdminUserSeeder::class);

        $this->assertTrue($admin->refresh()->hasRole('super_admin'));
    }

    public function test_the_command_sets_a_generated_password_and_shows_it_once(): void
    {
        $this->seed(AdminUserSeeder::class);

        $before = User::query()->where('email', AdminUserSeeder::EMAIL)->firstOrFail()->password;

        $this->artisan('nadi:admin-password', ['--generate' => true])
            ->assertSuccessful();

        $after = User::query()->where('email', AdminUserSeeder::EMAIL)->firstOrFail()->password;

        $this->assertNotSame($before, $after);
    }

    public function test_the_command_refuses_an_email_that_does_not_exist(): void
    {
        $this->artisan('nadi:admin-password', ['email' => 'bukan@siapa-siapa.test', '--generate' => true])
            ->assertFailed();
    }

    /**
     * Nothing in the repository should reveal a working production password.
     *
     * Matches the code shape — a quoted literal assigned to 'password' — rather
     * than a word, so the docblock explaining the old bug does not trip it.
     */
    public function test_the_seeder_carries_no_literal_password(): void
    {
        $source = file_get_contents(database_path('seeders/AdminUserSeeder.php'));

        $this->assertIsString($source);

        $this->assertDoesNotMatchRegularExpression(
            "/'password'\s*=>\s*'/",
            $source,
            'A literal password here would be written onto the account on every single deploy, and would sit in the repository in plain text.',
        );
    }
}
