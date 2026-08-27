<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\NadiRoleSeeder;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The role structure is only worth writing down if it actually separates the
 * two populations — an employee must not be able to reach /admin, and a
 * module admin must not pick up every other module along the way.
 */
class NadiRoleSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ShieldSeeder first: NadiRoleSeeder attaches permissions that only
        // exist once Shield has generated them.
        $this->seed(ShieldSeeder::class);
        $this->seed(NadiRoleSeeder::class);
    }

    /**
     * A role with no permissions is worse than no role at all: it looks
     * configured in the UI while granting nothing, and a typo in the seeder
     * produces exactly that silently.
     */
    public function test_every_role_it_creates_actually_holds_permissions(): void
    {
        $roles = Role::query()->where('name', '!=', 'super_admin')->withCount('permissions')->get();

        $this->assertGreaterThanOrEqual(16, $roles->count());

        foreach ($roles as $role) {
            $this->assertGreaterThan(0, $role->permissions_count, "Role [{$role->name}] holds no permissions.");
        }
    }

    public function test_a_plain_employee_gets_app_but_is_refused_at_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');

        $this->actingAs($user);

        $this->get('/app')->assertOk();
        $this->get('/admin')->assertForbidden();
    }

    public function test_job_roles_stack_on_top_of_karyawan(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['karyawan', 'kurir']);

        $this->actingAs($user);

        // From karyawan...
        $this->assertTrue($user->can('Create:RoomBooking'));
        // ...and from kurir, without either role having to repeat the other.
        $this->assertTrue($user->can('View:MessengerTasks'));

        // Still nothing that would open /admin.
        $this->get('/admin')->assertForbidden();
    }

    public function test_a_module_admin_can_enter_admin_but_only_reaches_its_own_module(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin-dokumen');

        $this->actingAs($user);

        $this->get('/admin')->assertOk();
        $this->get('/admin/documents')->assertOk();
        $this->get('/admin/document-types')->assertOk();

        // Nothing from any other module leaks in.
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/queue-tickets')->assertForbidden();
        $this->get('/admin/bazaars')->assertForbidden();
    }

    public function test_a_cashier_reaches_the_till_and_its_report_but_not_the_admin_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['karyawan', 'kasir-event']);

        $this->actingAs($user);

        $this->get('/app/sell-ticket')->assertOk();
        $this->get('/app/tickets')->assertOk();

        $this->get('/admin')->assertForbidden();
        $this->get('/admin/events')->assertForbidden();
    }

    /**
     * The seeder only ever adds, so an admin's own adjustments in the Roles
     * UI survive it being run again after a new module ships.
     */
    public function test_running_it_again_keeps_permissions_added_by_hand(): void
    {
        $role = Role::query()->where('name', 'kurir')->firstOrFail();
        $role->givePermissionTo('View:SecurityScan');

        $this->seed(NadiRoleSeeder::class);

        $this->assertTrue($role->fresh()->hasPermissionTo('View:SecurityScan'));
        $this->assertTrue($role->fresh()->hasPermissionTo('View:MessengerTasks'));
    }

    /**
     * Departments are data, not roles — the point of this structure. If
     * department-named roles ever come back, that decision has been lost.
     */
    public function test_it_creates_no_department_named_roles(): void
    {
        $departmentNames = Department::query()->pluck('name')->map(fn (string $n): string => strtolower($n));

        foreach (Role::query()->pluck('name') as $roleName) {
            $this->assertFalse(
                $departmentNames->contains(strtolower($roleName)),
                "Role [{$roleName}] is named after a department — roles describe a job, departments are stored on users.department_id.",
            );
        }
    }
}
