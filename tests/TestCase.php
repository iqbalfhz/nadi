<?php

namespace Tests;

use App\Http\Controllers\Api\V1\AuthController;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * Create a user with the super_admin role and every currently-defined permission
     * attached directly to that role, mirroring production seeding.
     *
     * Shield's `super_admin.define_via_gate` is false in config/filament-shield.php,
     * so there is no Gate::before bypass — super_admin only works because the role
     * holds every permission directly. RefreshDatabase only runs migrations, and the
     * permission records themselves are seeded data (created by `shield:generate` +
     * `shield:seeder`), not migrated schema, so a fresh test database has zero
     * Permission rows until ShieldSeeder actually runs.
     */
    protected function actingAsSuperAdmin(): User
    {
        $this->seed(ShieldSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user);

        return $user;
    }

    /**
     * Create a plain employee holding exactly the given permission(s) — direct
     * user permissions via Spatie, not a role, so each test can grant precisely
     * what that scenario needs without depending on how any real role is
     * configured. Mirrors actingAsSuperAdmin()'s need to seed ShieldSeeder
     * first so the permission rows exist to attach in a fresh test database.
     *
     * @param  string|array<int, string>  $permissions
     */
    protected function actingAsEmployeeWithPermissions(string|array $permissions): User
    {
        $this->seed(ShieldSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        $this->actingAs($user);

        return $user;
    }

    /**
     * The same employee, reached over the mobile API instead of a session.
     *
     * Sanctum::actingAs rather than actingAs: the API's own middleware checks
     * the token's abilities, so a session-authenticated user would sail past
     * a gate that a real request has to satisfy. The 'mobile' ability is what
     * every issued token carries — see AuthController.
     *
     * @param  string|array<int, string>  $permissions
     */
    protected function actingAsMobileUser(string|array $permissions): User
    {
        $this->seed(ShieldSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        Sanctum::actingAs($user, [AuthController::MOBILE_ABILITY]);

        return $user;
    }

    /**
     * A header set every write endpoint requires. Random per call, because
     * two unrelated submissions sharing a key is exactly the bug the header
     * exists to prevent.
     *
     * @return array<string, string>
     */
    protected function idempotencyHeader(?string $key = null): array
    {
        return ['Idempotency-Key' => $key ?? (string) Str::uuid()];
    }

    /**
     * Forget who the last request authenticated as.
     *
     * RequestGuard caches the user it resolved, and the container survives
     * from one request to the next *within a single test* — so once any
     * request has authenticated, every later one is treated as that same
     * user whatever token it carries. Production never sees this; each real
     * request builds its own container.
     *
     * It matters here because the tests that need it most are the ones
     * asserting a refusal: without this, "a revoked token is rejected" walks
     * straight past the auth layer on a cached user and then passes or fails
     * for reasons that have nothing to do with the code under test.
     */
    protected function forgetAuthenticatedUser(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
