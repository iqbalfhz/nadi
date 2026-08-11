<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

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
}
