<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // تعليق أو حذف factory الافتراضي
        // \App\Models\User::factory(10)->create();

        // استدعاء seeder الخاص بك
        $this->call([
            AdminSeeder::class,
        ]);
    }
}
