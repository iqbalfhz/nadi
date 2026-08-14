<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ShieldSeeder::class);
        $this->call(AdminUserSeeder::class);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        collect([
            'Basement 2' => ['Ruang Dealing 1', 'Ruang Meeting 1', 'Ruang Dealing 2', 'Ruang Meeting 2', 'Ruang Training'],
            'Developer' => ['Ruang Meeting Developer'],
            'Tenant Lounge' => ['Ruang Meeting Besar'],
        ])->each(function (array $rooms, string $areaName) {
            $area = Area::firstOrCreate(['name' => $areaName]);

            foreach ($rooms as $roomName) {
                $area->rooms()->firstOrCreate(['name' => $roomName]);
            }
        });
    }
}
