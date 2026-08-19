<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Department;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_creating_a_user_defaults_to_active_and_redirects_to_the_list(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Budi Santoso',
                'email' => 'budi@tangcity.com',
                'password' => 'Pwnd@2022',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(UserResource::getUrl('index'));

        $user = User::where('email', 'budi@tangcity.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->is_active);
    }

    public function test_an_admin_can_deactivate_another_users_account(): void
    {
        $this->actingAsSuperAdmin();
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_an_admin_can_not_deactivate_their_own_account(): void
    {
        $admin = $this->actingAsSuperAdmin();

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->assertFormFieldIsDisabled('is_active');
    }

    public function test_a_department_can_be_assigned_when_creating_a_user(): void
    {
        $this->actingAsSuperAdmin();
        $department = Department::factory()->create(['is_active' => true]);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Citra Dewi',
                'email' => 'citra@tangcity.com',
                'password' => 'Pwnd@2022',
                'department_id' => $department->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'citra@tangcity.com')->firstOrFail();

        $this->assertTrue($user->department->is($department));
    }

    public function test_a_role_can_be_assigned_when_creating_a_user(): void
    {
        $this->actingAsSuperAdmin();
        $role = Role::where('name', 'super_admin')->firstOrFail();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Admin Baru',
                'email' => 'admin.baru@tangcity.com',
                'password' => 'Pwnd@2022',
                'roles' => [$role->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'admin.baru@tangcity.com')->firstOrFail();

        $this->assertTrue($user->hasRole('super_admin'));
    }

    public function test_leaving_the_password_blank_when_editing_keeps_the_existing_password(): void
    {
        $this->actingAsSuperAdmin();
        $user = User::factory()->create();
        $originalHash = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_an_admin_cannot_delete_their_own_account_from_the_list(): void
    {
        $admin = $this->actingAsSuperAdmin();

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden(DeleteAction::class, $admin);
    }
}
