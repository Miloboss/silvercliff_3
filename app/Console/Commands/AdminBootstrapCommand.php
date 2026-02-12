<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminBootstrapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:bootstrap {--token= : The bootstrap token from .env}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bootstrap the initial Super Admin user (one-time use)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $envToken = env('ADMIN_BOOTSTRAP_TOKEN');

        if (!$envToken) {
            $this->error('ADMIN_BOOTSTRAP_TOKEN is not set in .env file.');
            return 1;
        }

        $inputToken = $this->option('token') ?: $this->secret('Enter the ADMIN_BOOTSTRAP_TOKEN');

        if (!hash_equals($envToken, $inputToken)) {
            $this->error('Invalid bootstrap token.');
            return 1;
        }

        // Check if any super_admin already exists
        $superAdminExists = User::role('super_admin')->exists();

        if ($superAdminExists) {
            $this->error('A Super Admin already exists. This command can only be run once for initial setup.');
            return 1;
        }

        $this->info('Starting Super Admin Bootstrap...');

        $name = $this->ask('Full Name', 'Super Admin');
        $email = $this->ask('Email Address');
        $password = $this->secret('Password');
        $confirmPassword = $this->secret('Confirm Password');

        if ($password !== $confirmPassword) {
            $this->error('Passwords do not match.');
            return 1;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('User with this email already exists.');
            return 1;
        }

        // Ensure super_admin role exists
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $user->assignRole($role);

        $this->info('Super Admin created successfully!');
        $this->line('');
        $this->info('Next steps:');
        $this->line('1. Login at /admin');
        $this->line('2. Rotate or remove ADMIN_BOOTSTRAP_TOKEN from .env for security.');
        
        return 0;
    }
}
