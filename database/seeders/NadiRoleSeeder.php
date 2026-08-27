<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The role structure for NADI, written down rather than clicked together.
 *
 * Roles describe a *job*, not a department. Departments are already stored as
 * data (`departments` table, `users.department_id`), and duplicating them as
 * roles fits permissions badly: two people in the same department can have
 * completely different jobs, while people in different departments who both
 * only book meeting rooms need exactly the same access.
 *
 * They stack. Spatie allows a user to hold several roles, so an OB is
 * `karyawan` + `ob`, and a courier is `karyawan` + `kurir`. Nobody needs a
 * bespoke role of their own.
 *
 * Three layers:
 *   1. `karyawan`     — everyone. Only /app self-service permissions.
 *   2. job add-ons    — the extra /app screens a particular job uses.
 *   3. `admin-*`      — module administration. Holding ANY of these is what
 *                       opens /admin at all (see User::canAccessPanel()).
 *
 * NOT wired into the deploy entrypoint on purpose: it is a starting point, and
 * re-running it on every deploy would wipe out whatever the admin has since
 * adjusted in the Roles UI. Run it by hand, once:
 *
 *     php artisan db:seed --class=NadiRoleSeeder
 *
 * Re-running is safe — it only ever adds. It never removes a permission an
 * admin granted, and never touches roles it doesn't know about.
 */
class NadiRoleSeeder extends Seeder
{
    /**
     * Layer 1 — held by every employee. Deliberately small: this is what a
     * person can do about their own work, and every item here is scoped to
     * the person themselves inside /app.
     *
     * @var array<int, string>
     */
    private const KARYAWAN = [
        'ViewAny:RoomBooking',
        'Create:RoomBooking',
        'View:BookingCalendar',
        'ViewAny:MessengerDelivery',
        'Create:MessengerDelivery',
        'ViewAny:ShortLink',
        'Create:ShortLink',
        'ViewAny:Barcode',
        'View:GenerateBarcode',
    ];

    /**
     * Layer 2 — stacked on top of `karyawan`, one per job.
     *
     * @var array<string, array<int, string>>
     */
    private const JOBS = [
        'ob' => [
            'ViewAny:ObChecklist',
            'Create:ObChecklist',
        ],
        'security' => [
            'View:SecurityScan',
        ],
        'kurir' => [
            'View:MessengerTasks',
        ],
        'operator-antrian' => [
            'View:QueueOperator',
        ],
        // The two cashier roles also get the matching report, which is
        // deliberately company-wide rather than per-cashier: closing the till
        // after an event means totalling every shift, not just your own.
        'kasir-event' => [
            'View:SellTicket',
            'ViewAny:Ticket',
        ],
        'kasir-bazar' => [
            'View:SellVendorProduct',
            'ViewAny:VendorSale',
        ],
    ];

    /**
     * Layer 3 — module administration. Each entry lists the *models* it
     * administers; the seeder expands those into the full permission set
     * Shield generates for them, so a model added to a module here picks up
     * every action without this list spelling them out.
     *
     * @var array<string, array<int, string>>
     */
    private const ADMIN_MODULES = [
        'admin-dokumen' => ['Document', 'DocumentType', 'Company', 'Department'],
        'admin-fasilitas' => ['RoomBooking', 'Room', 'Area'],
        'admin-antrian' => ['QueueTicket', 'QueueCategory', 'Advertisement'],
        'admin-ob' => ['ObChecklist', 'ObArea'],
        'admin-security' => ['SecurityPatrol', 'SecurityCheckpoint'],
        'admin-messenger' => ['MessengerDelivery'],
        'admin-event' => ['Ticket', 'Event'],
        'admin-bazar' => ['VendorSale', 'Bazaar'],
        'admin-pengguna' => ['User'],
    ];

    /**
     * Admin pages aren't model-backed, so they're attached by hand.
     *
     * @var array<string, array<int, string>>
     */
    private const ADMIN_PAGES = [
        'admin-antrian' => ['View:ManageQueueKioskSettings'],
        'admin-dokumen' => ['View:ManageBackupSettings'],
    ];

    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        $this->attach('karyawan', self::KARYAWAN, $guard);

        foreach (self::JOBS as $role => $permissions) {
            $this->attach($role, $permissions, $guard);
        }

        foreach (self::ADMIN_MODULES as $role => $models) {
            $permissions = [];

            foreach ($models as $model) {
                // Every permission Shield generated for this model, whatever
                // its actions turned out to be — safer than listing
                // "ViewAny, View, Create, Update, Delete, …" and quietly
                // missing one such as Restore or ForceDelete.
                $permissions = [
                    ...$permissions,
                    ...Permission::query()
                        ->where('guard_name', $guard)
                        ->where('name', 'like', '%:'.$model)
                        ->pluck('name')
                        ->all(),
                ];
            }

            $this->attach($role, [...$permissions, ...(self::ADMIN_PAGES[$role] ?? [])], $guard);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function attach(string $roleName, array $permissions, string $guard): void
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);

        // Only the permissions that actually exist: a name that no longer
        // matches anything Shield generated should be reported, not created
        // as a dead permission nothing will ever check.
        $existing = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $permissions)
            ->pluck('name');

        $missing = array_diff(array_unique($permissions), $existing->all());

        // $this->command is always set here: this seeder is run through the
        // console (`db:seed`), as its class docblock spells out.
        if ($missing !== []) {
            $this->command->warn("  [{$roleName}] permission not found, skipped: ".implode(', ', $missing));
        }

        // givePermissionTo, not syncPermissions: re-running must not undo
        // adjustments an admin has since made in the Roles UI.
        $role->givePermissionTo($existing);

        $this->command->info("  {$roleName}: ".$role->fresh()->permissions()->count().' permission(s)');
    }
}
