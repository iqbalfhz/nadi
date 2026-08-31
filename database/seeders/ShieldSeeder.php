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
        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["Create:Area","Create:Role","Create:Room","Create:RoomBooking","Delete:Area","Delete:Role","Delete:Room","Delete:RoomBooking","DeleteAny:Area","DeleteAny:Role","DeleteAny:Room","DeleteAny:RoomBooking","ForceDelete:Area","ForceDelete:Role","ForceDelete:Room","ForceDelete:RoomBooking","ForceDeleteAny:Area","ForceDeleteAny:Role","ForceDeleteAny:Room","ForceDeleteAny:RoomBooking","Reorder:Area","Reorder:Role","Reorder:Room","Reorder:RoomBooking","Replicate:Area","Replicate:Role","Replicate:Room","Replicate:RoomBooking","Restore:Area","Restore:Role","Restore:Room","Restore:RoomBooking","RestoreAny:Area","RestoreAny:Role","RestoreAny:Room","RestoreAny:RoomBooking","Update:Area","Update:Role","Update:Room","Update:RoomBooking","View:Area","View:Role","View:Room","View:RoomBooking","ViewAny:Area","ViewAny:Role","ViewAny:Room","ViewAny:RoomBooking","ViewAny:User","View:User","Create:User","Update:User","Delete:User","DeleteAny:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","ViewAny:QueueCategory","View:QueueCategory","Create:QueueCategory","Update:QueueCategory","Delete:QueueCategory","DeleteAny:QueueCategory","Restore:QueueCategory","ForceDelete:QueueCategory","ForceDeleteAny:QueueCategory","RestoreAny:QueueCategory","Replicate:QueueCategory","Reorder:QueueCategory","ViewAny:QueueTicket","View:QueueTicket","Create:QueueTicket","Update:QueueTicket","Delete:QueueTicket","DeleteAny:QueueTicket","Restore:QueueTicket","ForceDelete:QueueTicket","ForceDeleteAny:QueueTicket","RestoreAny:QueueTicket","Replicate:QueueTicket","Reorder:QueueTicket","ViewAny:Advertisement","View:Advertisement","Create:Advertisement","Update:Advertisement","Delete:Advertisement","DeleteAny:Advertisement","Restore:Advertisement","ForceDelete:Advertisement","ForceDeleteAny:Advertisement","RestoreAny:Advertisement","Replicate:Advertisement","Reorder:Advertisement","View:ManageQueueKioskSettings","View:QueueTicketsOverview","ViewAny:Company","View:Company","Create:Company","Update:Company","Delete:Company","DeleteAny:Company","Restore:Company","ForceDelete:Company","ForceDeleteAny:Company","RestoreAny:Company","Replicate:Company","Reorder:Company","ViewAny:Department","View:Department","Create:Department","Update:Department","Delete:Department","DeleteAny:Department","Restore:Department","ForceDelete:Department","ForceDeleteAny:Department","RestoreAny:Department","Replicate:Department","Reorder:Department","ViewAny:DocumentType","View:DocumentType","Create:DocumentType","Update:DocumentType","Delete:DocumentType","DeleteAny:DocumentType","Restore:DocumentType","ForceDelete:DocumentType","ForceDeleteAny:DocumentType","RestoreAny:DocumentType","Replicate:DocumentType","Reorder:DocumentType","ViewAny:Document","View:Document","Create:Document","Update:Document","Delete:Document","DeleteAny:Document","Restore:Document","ForceDelete:Document","ForceDeleteAny:Document","RestoreAny:Document","Replicate:Document","Reorder:Document","View:ManageBackupSettings","ViewAny:ObArea","View:ObArea","Create:ObArea","Update:ObArea","Delete:ObArea","DeleteAny:ObArea","Restore:ObArea","ForceDelete:ObArea","ForceDeleteAny:ObArea","RestoreAny:ObArea","Replicate:ObArea","Reorder:ObArea","ViewAny:ObChecklist","View:ObChecklist","Create:ObChecklist","Update:ObChecklist","Delete:ObChecklist","DeleteAny:ObChecklist","Restore:ObChecklist","ForceDelete:ObChecklist","ForceDeleteAny:ObChecklist","RestoreAny:ObChecklist","Replicate:ObChecklist","Reorder:ObChecklist","ViewAny:SecurityCheckpoint","View:SecurityCheckpoint","Create:SecurityCheckpoint","Update:SecurityCheckpoint","Delete:SecurityCheckpoint","DeleteAny:SecurityCheckpoint","Restore:SecurityCheckpoint","ForceDelete:SecurityCheckpoint","ForceDeleteAny:SecurityCheckpoint","RestoreAny:SecurityCheckpoint","Replicate:SecurityCheckpoint","Reorder:SecurityCheckpoint","ViewAny:SecurityPatrol","View:SecurityPatrol","Create:SecurityPatrol","Update:SecurityPatrol","Delete:SecurityPatrol","DeleteAny:SecurityPatrol","Restore:SecurityPatrol","ForceDelete:SecurityPatrol","ForceDeleteAny:SecurityPatrol","RestoreAny:SecurityPatrol","Replicate:SecurityPatrol","Reorder:SecurityPatrol","ViewAny:MessengerDelivery","View:MessengerDelivery","Create:MessengerDelivery","Update:MessengerDelivery","Delete:MessengerDelivery","DeleteAny:MessengerDelivery","Restore:MessengerDelivery","ForceDelete:MessengerDelivery","ForceDeleteAny:MessengerDelivery","RestoreAny:MessengerDelivery","Replicate:MessengerDelivery","Reorder:MessengerDelivery","ViewAny:Event","View:Event","Create:Event","Update:Event","Delete:Event","DeleteAny:Event","Restore:Event","ForceDelete:Event","ForceDeleteAny:Event","RestoreAny:Event","Replicate:Event","Reorder:Event","ViewAny:Ticket","View:Ticket","Create:Ticket","Update:Ticket","Delete:Ticket","DeleteAny:Ticket","Restore:Ticket","ForceDelete:Ticket","ForceDeleteAny:Ticket","RestoreAny:Ticket","Replicate:Ticket","Reorder:Ticket","View:TicketsOverview","View:MessengerTasks","View:QueueOperator","View:SecurityScan","View:SellTicket","View:BookingCalendarWidget","ViewAny:Barcode","View:Barcode","Create:Barcode","Update:Barcode","Delete:Barcode","DeleteAny:Barcode","Restore:Barcode","ForceDelete:Barcode","ForceDeleteAny:Barcode","RestoreAny:Barcode","Replicate:Barcode","Reorder:Barcode","ViewAny:ShortLink","View:ShortLink","Create:ShortLink","Update:ShortLink","Delete:ShortLink","DeleteAny:ShortLink","Restore:ShortLink","ForceDelete:ShortLink","ForceDeleteAny:ShortLink","RestoreAny:ShortLink","Replicate:ShortLink","Reorder:ShortLink","View:GenerateBarcode","ViewAny:Bazaar","View:Bazaar","Create:Bazaar","Update:Bazaar","Delete:Bazaar","DeleteAny:Bazaar","Restore:Bazaar","ForceDelete:Bazaar","ForceDeleteAny:Bazaar","RestoreAny:Bazaar","Replicate:Bazaar","Reorder:Bazaar","ViewAny:VendorSale","View:VendorSale","Create:VendorSale","Update:VendorSale","Delete:VendorSale","DeleteAny:VendorSale","Restore:VendorSale","ForceDelete:VendorSale","ForceDeleteAny:VendorSale","RestoreAny:VendorSale","Replicate:VendorSale","Reorder:VendorSale","View:VendorSalesOverview","View:VendorSettlementOverview","View:SellVendorProduct","View:BookingCalendar","View:QuickLinksWidget","View:DashboardStatsWidget","ViewAny:ActivityLog","View:ActivityLog","Create:ActivityLog","Update:ActivityLog","Delete:ActivityLog","DeleteAny:ActivityLog","Restore:ActivityLog","ForceDelete:ActivityLog","ForceDeleteAny:ActivityLog","RestoreAny:ActivityLog","Replicate:ActivityLog","Reorder:ActivityLog","ViewAny:HkArea","View:HkArea","Create:HkArea","Update:HkArea","Delete:HkArea","DeleteAny:HkArea","Restore:HkArea","ForceDelete:HkArea","ForceDeleteAny:HkArea","RestoreAny:HkArea","Replicate:HkArea","Reorder:HkArea","ViewAny:HkCategory","View:HkCategory","Create:HkCategory","Update:HkCategory","Delete:HkCategory","DeleteAny:HkCategory","Restore:HkCategory","ForceDelete:HkCategory","ForceDeleteAny:HkCategory","RestoreAny:HkCategory","Replicate:HkCategory","Reorder:HkCategory","ViewAny:HkInspection","View:HkInspection","Create:HkInspection","Update:HkInspection","Delete:HkInspection","DeleteAny:HkInspection","Restore:HkInspection","ForceDelete:HkInspection","ForceDeleteAny:HkInspection","RestoreAny:HkInspection","Replicate:HkInspection","Reorder:HkInspection","View:Dashboard","View:ManageTelegramSettings","View:OperationalOverviewStats","View:SalesOverviewStats","View:ModuleActivityChart","View:RevenueChart","View:QueueByCategoryChart","View:DocumentsByTypeChart","View:MessengerStatusChart"]},{"name":"karyawan","guard_name":"web","permissions":["Create:MessengerDelivery","Create:RoomBooking","Create:ShortLink","View:BookingCalendar","View:GenerateBarcode","ViewAny:Barcode","ViewAny:MessengerDelivery","ViewAny:RoomBooking","ViewAny:ShortLink"]},{"name":"ob","guard_name":"web","permissions":["Create:ObChecklist","ViewAny:ObChecklist"]},{"name":"security","guard_name":"web","permissions":["View:SecurityScan"]},{"name":"kurir","guard_name":"web","permissions":["View:MessengerTasks"]},{"name":"operator-antrian","guard_name":"web","permissions":["View:QueueOperator"]},{"name":"kasir-event","guard_name":"web","permissions":["View:SellTicket","ViewAny:Ticket"]},{"name":"kasir-bazar","guard_name":"web","permissions":["View:SellVendorProduct","ViewAny:VendorSale"]},{"name":"admin-dokumen","guard_name":"web","permissions":["Create:Company","Create:Department","Create:Document","Create:DocumentType","Delete:Company","Delete:Department","Delete:Document","Delete:DocumentType","DeleteAny:Company","DeleteAny:Department","DeleteAny:Document","DeleteAny:DocumentType","ForceDelete:Company","ForceDelete:Department","ForceDelete:Document","ForceDelete:DocumentType","ForceDeleteAny:Company","ForceDeleteAny:Department","ForceDeleteAny:Document","ForceDeleteAny:DocumentType","Reorder:Company","Reorder:Department","Reorder:Document","Reorder:DocumentType","Replicate:Company","Replicate:Department","Replicate:Document","Replicate:DocumentType","Restore:Company","Restore:Department","Restore:Document","Restore:DocumentType","RestoreAny:Company","RestoreAny:Department","RestoreAny:Document","RestoreAny:DocumentType","Update:Company","Update:Department","Update:Document","Update:DocumentType","View:Company","View:Department","View:Document","View:DocumentType","View:ManageBackupSettings","ViewAny:Company","ViewAny:Department","ViewAny:Document","ViewAny:DocumentType"]},{"name":"admin-fasilitas","guard_name":"web","permissions":["Create:Area","Create:Room","Create:RoomBooking","Delete:Area","Delete:Room","Delete:RoomBooking","DeleteAny:Area","DeleteAny:Room","DeleteAny:RoomBooking","ForceDelete:Area","ForceDelete:Room","ForceDelete:RoomBooking","ForceDeleteAny:Area","ForceDeleteAny:Room","ForceDeleteAny:RoomBooking","Reorder:Area","Reorder:Room","Reorder:RoomBooking","Replicate:Area","Replicate:Room","Replicate:RoomBooking","Restore:Area","Restore:Room","Restore:RoomBooking","RestoreAny:Area","RestoreAny:Room","RestoreAny:RoomBooking","Update:Area","Update:Room","Update:RoomBooking","View:Area","View:Room","View:RoomBooking","ViewAny:Area","ViewAny:Room","ViewAny:RoomBooking"]},{"name":"admin-antrian","guard_name":"web","permissions":["Create:Advertisement","Create:QueueCategory","Create:QueueTicket","Delete:Advertisement","Delete:QueueCategory","Delete:QueueTicket","DeleteAny:Advertisement","DeleteAny:QueueCategory","DeleteAny:QueueTicket","ForceDelete:Advertisement","ForceDelete:QueueCategory","ForceDelete:QueueTicket","ForceDeleteAny:Advertisement","ForceDeleteAny:QueueCategory","ForceDeleteAny:QueueTicket","Reorder:Advertisement","Reorder:QueueCategory","Reorder:QueueTicket","Replicate:Advertisement","Replicate:QueueCategory","Replicate:QueueTicket","Restore:Advertisement","Restore:QueueCategory","Restore:QueueTicket","RestoreAny:Advertisement","RestoreAny:QueueCategory","RestoreAny:QueueTicket","Update:Advertisement","Update:QueueCategory","Update:QueueTicket","View:Advertisement","View:ManageQueueKioskSettings","View:QueueCategory","View:QueueTicket","ViewAny:Advertisement","ViewAny:QueueCategory","ViewAny:QueueTicket"]},{"name":"admin-ob","guard_name":"web","permissions":["Create:ObArea","Create:ObChecklist","Delete:ObArea","Delete:ObChecklist","DeleteAny:ObArea","DeleteAny:ObChecklist","ForceDelete:ObArea","ForceDelete:ObChecklist","ForceDeleteAny:ObArea","ForceDeleteAny:ObChecklist","Reorder:ObArea","Reorder:ObChecklist","Replicate:ObArea","Replicate:ObChecklist","Restore:ObArea","Restore:ObChecklist","RestoreAny:ObArea","RestoreAny:ObChecklist","Update:ObArea","Update:ObChecklist","View:ObArea","View:ObChecklist","ViewAny:ObArea","ViewAny:ObChecklist"]},{"name":"admin-security","guard_name":"web","permissions":["Create:SecurityCheckpoint","Create:SecurityPatrol","Delete:SecurityCheckpoint","Delete:SecurityPatrol","DeleteAny:SecurityCheckpoint","DeleteAny:SecurityPatrol","ForceDelete:SecurityCheckpoint","ForceDelete:SecurityPatrol","ForceDeleteAny:SecurityCheckpoint","ForceDeleteAny:SecurityPatrol","Reorder:SecurityCheckpoint","Reorder:SecurityPatrol","Replicate:SecurityCheckpoint","Replicate:SecurityPatrol","Restore:SecurityCheckpoint","Restore:SecurityPatrol","RestoreAny:SecurityCheckpoint","RestoreAny:SecurityPatrol","Update:SecurityCheckpoint","Update:SecurityPatrol","View:SecurityCheckpoint","View:SecurityPatrol","ViewAny:SecurityCheckpoint","ViewAny:SecurityPatrol"]},{"name":"admin-messenger","guard_name":"web","permissions":["Create:MessengerDelivery","Delete:MessengerDelivery","DeleteAny:MessengerDelivery","ForceDelete:MessengerDelivery","ForceDeleteAny:MessengerDelivery","Reorder:MessengerDelivery","Replicate:MessengerDelivery","Restore:MessengerDelivery","RestoreAny:MessengerDelivery","Update:MessengerDelivery","View:MessengerDelivery","ViewAny:MessengerDelivery"]},{"name":"admin-event","guard_name":"web","permissions":["Create:Event","Create:Ticket","Delete:Event","Delete:Ticket","DeleteAny:Event","DeleteAny:Ticket","ForceDelete:Event","ForceDelete:Ticket","ForceDeleteAny:Event","ForceDeleteAny:Ticket","Reorder:Event","Reorder:Ticket","Replicate:Event","Replicate:Ticket","Restore:Event","Restore:Ticket","RestoreAny:Event","RestoreAny:Ticket","Update:Event","Update:Ticket","View:Event","View:Ticket","ViewAny:Event","ViewAny:Ticket"]},{"name":"admin-bazar","guard_name":"web","permissions":["Create:Bazaar","Create:VendorSale","Delete:Bazaar","Delete:VendorSale","DeleteAny:Bazaar","DeleteAny:VendorSale","ForceDelete:Bazaar","ForceDelete:VendorSale","ForceDeleteAny:Bazaar","ForceDeleteAny:VendorSale","Reorder:Bazaar","Reorder:VendorSale","Replicate:Bazaar","Replicate:VendorSale","Restore:Bazaar","Restore:VendorSale","RestoreAny:Bazaar","RestoreAny:VendorSale","Update:Bazaar","Update:VendorSale","View:Bazaar","View:VendorSale","ViewAny:Bazaar","ViewAny:VendorSale"]},{"name":"admin-pengguna","guard_name":"web","permissions":["Create:User","Delete:User","DeleteAny:User","ForceDelete:User","ForceDeleteAny:User","Reorder:User","Replicate:User","Restore:User","RestoreAny:User","Update:User","View:User","ViewAny:User"]},{"name":"pengawas-hk","guard_name":"web","permissions":["Create:HkInspection","ViewAny:HkInspection"]},{"name":"admin-hk","guard_name":"web","permissions":["Create:HkArea","Create:HkCategory","Create:HkInspection","Delete:HkArea","Delete:HkCategory","Delete:HkInspection","DeleteAny:HkArea","DeleteAny:HkCategory","DeleteAny:HkInspection","ForceDelete:HkArea","ForceDelete:HkCategory","ForceDelete:HkInspection","ForceDeleteAny:HkArea","ForceDeleteAny:HkCategory","ForceDeleteAny:HkInspection","Reorder:HkArea","Reorder:HkCategory","Reorder:HkInspection","Replicate:HkArea","Replicate:HkCategory","Replicate:HkInspection","Restore:HkArea","Restore:HkCategory","Restore:HkInspection","RestoreAny:HkArea","RestoreAny:HkCategory","RestoreAny:HkInspection","Update:HkArea","Update:HkCategory","Update:HkInspection","View:HkArea","View:HkCategory","View:HkInspection","ViewAny:HkArea","ViewAny:HkCategory","ViewAny:HkInspection"]}]';
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
