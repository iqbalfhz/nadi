<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'iqbal.it@tangcity.com'],
            [
                'name' => 'Iqbal',
                'password' => 'Pwnd@2022',
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles(['super_admin']);
    }
}
