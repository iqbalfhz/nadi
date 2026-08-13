<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $tenants = '[]';
        $users = '[]';
        $userTenantPivot = '[]';
        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["Create:Area","Create:Role","Create:Room","Create:RoomBooking","Delete:Area","Delete:Role","Delete:Room","Delete:RoomBooking","DeleteAny:Area","DeleteAny:Role","DeleteAny:Room","DeleteAny:RoomBooking","ForceDelete:Area","ForceDelete:Role","ForceDelete:Room","ForceDelete:RoomBooking","ForceDeleteAny:Area","ForceDeleteAny:Role","ForceDeleteAny:Room","ForceDeleteAny:RoomBooking","Reorder:Area","Reorder:Role","Reorder:Room","Reorder:RoomBooking","Replicate:Area","Replicate:Role","Replicate:Room","Replicate:RoomBooking","Restore:Area","Restore:Role","Restore:Room","Restore:RoomBooking","RestoreAny:Area","RestoreAny:Role","RestoreAny:Room","RestoreAny:RoomBooking","Update:Area","Update:Role","Update:Room","Update:RoomBooking","View:Area","View:Role","View:Room","View:RoomBooking","ViewAny:Area","ViewAny:Role","ViewAny:Room","ViewAny:RoomBooking","ViewAny:User","View:User","Create:User","Update:User","Delete:User","DeleteAny:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","ViewAny:QueueCategory","View:QueueCategory","Create:QueueCategory","Update:QueueCategory","Delete:QueueCategory","DeleteAny:QueueCategory","Restore:QueueCategory","ForceDelete:QueueCategory","ForceDeleteAny:QueueCategory","RestoreAny:QueueCategory","Replicate:QueueCategory","Reorder:QueueCategory","ViewAny:QueueTicket","View:QueueTicket","Create:QueueTicket","Update:QueueTicket","Delete:QueueTicket","DeleteAny:QueueTicket","Restore:QueueTicket","ForceDelete:QueueTicket","ForceDeleteAny:QueueTicket","RestoreAny:QueueTicket","Replicate:QueueTicket","Reorder:QueueTicket","ViewAny:Advertisement","View:Advertisement","Create:Advertisement","Update:Advertisement","Delete:Advertisement","DeleteAny:Advertisement","Restore:Advertisement","ForceDelete:Advertisement","ForceDeleteAny:Advertisement","RestoreAny:Advertisement","Replicate:Advertisement","Reorder:Advertisement","View:ManageQueueKioskSettings","View:QueueTicketsOverview","ViewAny:Company","View:Company","Create:Company","Update:Company","Delete:Company","DeleteAny:Company","Restore:Company","ForceDelete:Company","ForceDeleteAny:Company","RestoreAny:Company","Replicate:Company","Reorder:Company","ViewAny:Department","View:Department","Create:Department","Update:Department","Delete:Department","DeleteAny:Department","Restore:Department","ForceDelete:Department","ForceDeleteAny:Department","RestoreAny:Department","Replicate:Department","Reorder:Department","ViewAny:DocumentType","View:DocumentType","Create:DocumentType","Update:DocumentType","Delete:DocumentType","DeleteAny:DocumentType","Restore:DocumentType","ForceDelete:DocumentType","ForceDeleteAny:DocumentType","RestoreAny:DocumentType","Replicate:DocumentType","Reorder:DocumentType","ViewAny:Document","View:Document","Create:Document","Update:Document","Delete:Document","DeleteAny:Document","Restore:Document","ForceDelete:Document","ForceDeleteAny:Document","RestoreAny:Document","Replicate:Document","Reorder:Document","View:ManageBackupSettings"]}]';
        $directPermissions = '[]';

        // 1. Seed tenants first (if present)
        if (! blank($tenants) && $tenants !== '[]') {
            static::seedTenants($tenants);
        }

        // 2. Seed roles with permissions
        static::makeRolesWithPermissions($rolesWithPermissions);

        // 3. Seed direct permissions
        static::makeDirectPermissions($directPermissions);

        // 4. Seed users with their roles/permissions (if present)
        if (! blank($users) && $users !== '[]') {
            static::seedUsers($users);
        }

        // 5. Seed user-tenant pivot (if present)
        if (! blank($userTenantPivot) && $userTenantPivot !== '[]') {
            static::seedUserTenantPivot($userTenantPivot);
        }

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function seedTenants(string $tenants): void
    {
        if (blank($tenantData = json_decode($tenants, true))) {
            return;
        }

        $tenantModel = '';
        if (blank($tenantModel)) {
            return;
        }

        foreach ($tenantData as $tenant) {
            $tenantModel::firstOrCreate(
                ['id' => $tenant['id']],
                $tenant
            );
        }
    }

    protected static function seedUsers(string $users): void
    {
        if (blank($userData = json_decode($users, true))) {
            return;
        }

        $userModel = 'App\Models\User';
        $tenancyEnabled = false;

        foreach ($userData as $data) {
            // Extract role/permission data before creating user
            $roles = $data['roles'] ?? [];
            $permissions = $data['permissions'] ?? [];
            $tenantRoles = $data['tenant_roles'] ?? [];
            $tenantPermissions = $data['tenant_permissions'] ?? [];
            unset($data['roles'], $data['permissions'], $data['tenant_roles'], $data['tenant_permissions']);

            $user = $userModel::firstOrCreate(
                ['email' => $data['email']],
                $data
            );

            // Handle tenancy mode - sync roles/permissions per tenant
            if ($tenancyEnabled && (! empty($tenantRoles) || ! empty($tenantPermissions))) {
                foreach ($tenantRoles as $tenantId => $roleNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncRoles($roleNames);
                }

                foreach ($tenantPermissions as $tenantId => $permissionNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncPermissions($permissionNames);
                }
            } else {
                // Non-tenancy mode
                if (! empty($roles)) {
                    $user->syncRoles($roles);
                }

                if (! empty($permissions)) {
                    $user->syncPermissions($permissions);
                }
            }
        }
    }

    protected static function seedUserTenantPivot(string $pivot): void
    {
        if (blank($pivotData = json_decode($pivot, true))) {
            return;
        }

        $pivotTable = '';
        if (blank($pivotTable)) {
            return;
        }

        foreach ($pivotData as $row) {
            $uniqueKeys = [];

            if (isset($row['user_id'])) {
                $uniqueKeys['user_id'] = $row['user_id'];
            }

            $tenantForeignKey = 'team_id';
            if (! blank($tenantForeignKey) && isset($row[$tenantForeignKey])) {
                $uniqueKeys[$tenantForeignKey] = $row[$tenantForeignKey];
            }

            if (! empty($uniqueKeys)) {
                DB::table($pivotTable)->updateOrInsert($uniqueKeys, $row);
            }
        }
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            return;
        }

        /** @var Model $roleModel */
        $roleModel = Utils::getRoleModel();
        /** @var Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        $tenancyEnabled = false;
        $teamForeignKey = 'team_id';

        foreach ($rolePlusPermissions as $rolePlusPermission) {
            $tenantId = $rolePlusPermission[$teamForeignKey] ?? null;

            // Set tenant context for role creation and permission sync
            if ($tenancyEnabled) {
                setPermissionsTeamId($tenantId);
            }

            $roleData = [
                'name' => $rolePlusPermission['name'],
                'guard_name' => $rolePlusPermission['guard_name'],
            ];

            // Include tenant ID in role data (can be null for global roles)
            if ($tenancyEnabled && ! blank($teamForeignKey)) {
                $roleData[$teamForeignKey] = $tenantId;
            }

            $role = $roleModel::firstOrCreate($roleData);

            if (! blank($rolePlusPermission['permissions'])) {
                $permissionModels = collect($rolePlusPermission['permissions'])
                    ->map(fn ($permission) => $permissionModel::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => $rolePlusPermission['guard_name'],
                    ]))
                    ->all();

                $role->syncPermissions($permissionModels);
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (blank($permissions = json_decode($directPermissions, true))) {
            return;
        }

        /** @var Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        foreach ($permissions as $permission) {
            if ($permissionModel::whereName($permission['name'])->doesntExist()) {
                $permissionModel::create([
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ]);
            }
        }
    }
}
