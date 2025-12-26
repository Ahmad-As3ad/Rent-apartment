<?php

namespace Database\Seeders;

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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

User::create([
    'phone_number' => '0911111111',
    'password' => 'admin123',
    'user_type' => 'admin',
    'phone_verified_at' => now(),
    'first_name' => 'Admin',
    'last_name' => 'System',
    'status' => 'approved',
    'profile_completed_at' => now()
]);
    }
}
