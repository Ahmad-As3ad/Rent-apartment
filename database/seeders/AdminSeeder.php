<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء حساب المدير
        User::create([
            'phone_number' => '0911111111',
            'password' => 'admin123',
            'first_name' => 'Admin',
            'last_name' => 'System',
            'user_type' => 'admin',
            'phone_verified_at' => now(),
            'status' => 'approved',
            'profile_completed_at' => now(),
            'date_of_birth' => '1990-01-01'
        ]);

        // إنشاء مالك تجريبي
        User::create([
            'phone_number' => '0922222222',
            'password' => 'owner123',
            'first_name' => 'أحمد',
            'last_name' => 'محمد',
            'user_type' => 'owner',
            'phone_verified_at' => now(),
            'status' => 'approved',
            'profile_completed_at' => now(),
            'date_of_birth' => '1985-05-20'
        ]);

        // إنشاء مستأجر تجريبي
        User::create([
            'phone_number' => '0933333333',
            'password' => 'tenant123',
            'first_name' => 'سامر',
            'last_name' => 'الحسن',
            'user_type' => 'tenant',
            'phone_verified_at' => now(),
            'status' => 'approved',
            'profile_completed_at' => now(),
            'date_of_birth' => '1992-08-15'
        ]);

        $this->command->info('✅ تم إنشاء 3 حسابات تجريبية بنجاح!');
        $this->command->info('📱 المدير: 0911111111 / admin123');
        $this->command->info('🏠 المالك: 0922222222 / owner123');
        $this->command->info('👤 المستأجر: 0933333333 / tenant123');
    }
}
