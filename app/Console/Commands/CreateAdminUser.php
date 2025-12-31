<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
                            {--phone= : Phone number for the admin}
                            {--password= : Password for the admin}
                            {--first-name= : First name for the admin}
                            {--last-name= : Last name for the admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Use interactive mode if options not provided
        $phone = $this->option('phone') ?? $this->ask('Enter phone number (10 digits starting with 09)');
        $password = $this->option('password') ?? $this->secret('Enter password (min 6 chars)');
        $firstName = $this->option('first-name') ?? $this->ask('Enter first name');
        $lastName = $this->option('last-name') ?? $this->ask('Enter last name');

        // Validate phone number
        if (!preg_match('/^09\d{8}$/', $phone)) {
            $this->error('Invalid phone number format. Must be 10 digits starting with 09.');
            return 1;
        }

        // Validate password
        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters.');
            return 1;
        }

        // Check if phone already exists
        if (User::where('phone_number', $phone)->exists()) {
            $this->error('Phone number already registered.');
            return 1;
        }

        // Create admin user
        $admin = User::create([
            'phone_number' => $phone,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'user_type' => 'admin',
            'phone_verified_at' => now(),
            'status' => 'approved',
            'profile_completed_at' => now(),
            'date_of_birth' => '1990-01-01' // Default date
        ]);

        $this->info('✅ Admin user created successfully!');
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $admin->id],
                ['Phone', $admin->phone_number],
                ['Name', $admin->first_name . ' ' . $admin->last_name],
                ['User Type', $admin->user_type],
                ['Status', $admin->status],
            ]
        );

        return 0;
    }
}
