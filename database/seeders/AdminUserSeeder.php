<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * The account that must always be able to get back into /admin. Kept here
     * rather than in config because it identifies a specific row this seeder
     * owns, and `nadi:admin-password` needs the same value.
     */
    public const EMAIL = 'iqbal.it@tangcity.com';

    /**
     * Runs on every deploy (see deploy/entrypoint.sh).
     *
     * It used to call updateOrCreate() with a hardcoded password, which meant
     * every single deploy quietly reset the super admin's password back to a
     * value written in this file — so a password change never survived the next
     * push, and that password sat in the repository in plain text.
     *
     * What is worth repeating on every deploy is the *role*, not the password:
     * restoring super_admin is what rescued this account once when the role was
     * deleted by accident. So the role is synced every time, and the password is
     * only ever set when the account does not exist yet.
     *
     * To set or recover the password deliberately, run:
     *
     *     php artisan nadi:admin-password --generate
     */
    public function run(): void
    {
        $user = User::query()->firstOrNew(['email' => self::EMAIL]);

        if (! $user->exists) {
            $user->fill([
                'name' => 'Iqbal',
                // Random on purpose: nobody, including this repository, should
                // know it. Set a real one with nadi:admin-password.
                'password' => Str::password(32),
                'email_verified_at' => now(),
            ]);
        }

        $user->save();

        $user->syncRoles(['super_admin']);
    }
}
