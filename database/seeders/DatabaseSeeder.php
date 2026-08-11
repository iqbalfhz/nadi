<?php

namespace Database\Seeders;

use App\Models\Room;
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

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        collect([
            ['name' => 'Ruang Rapat A', 'capacity' => 8, 'location' => 'Lantai 2'],
            ['name' => 'Ruang Rapat B', 'capacity' => 6, 'location' => 'Lantai 2'],
            ['name' => 'Ruang Meeting Besar', 'capacity' => 20, 'location' => 'Lantai 3'],
        ])->each(fn (array $room) => Room::firstOrCreate(['name' => $room['name']], $room));
    }
}
