<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create-user {--email=admin@globaleadershipacademy.com} {--password=AdminPassword123!} {--name=Admin User}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update admin user with specified credentials';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        try {
            // Get or create admin role
            $adminRole = Role::where('name', 'admin')->first();
            
            if (!$adminRole) {
                $adminRole = Role::create([
                    'name' => 'admin',
                    'permissions' => [
                        'manage_events',
                        'manage_users', 
                        'manage_speakers',
                        'manage_sessions',
                        'manage_pages',
                        'view_reports'
                    ]
                ]);
                $this->info('✅ Admin role created');
            }

            // Create or update admin user
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role_id' => $adminRole->id,
                    'email_verified_at' => now(),
                ]
            );

            $this->info('✅ Admin user created/updated successfully!');
            $this->info('📧 Email: ' . $email);
            $this->info('🔑 Password: ' . $password);
            $this->info('👤 Name: ' . $name);
            $this->info('🔗 Login URL: https://globaleadershipacademy.com/login');
            $this->info('🏠 Admin Panel: https://globaleadershipacademy.com/admin');
            
            // Verify the user was created/updated
            $verifyUser = User::where('email', $email)->first();
            if ($verifyUser && Hash::check($password, $verifyUser->password)) {
                $this->info('✅ Password verification successful');
            } else {
                $this->error('❌ Password verification failed');
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
