<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test authentication and authorization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Role-Based Authorization...');

        // Test admin user
        $admin = User::where('role_id', 1)->first();
        if ($admin) {
            $this->info("Admin User: {$admin->name}");
            $this->info("Has admin role: " . ($admin->hasRole('admin') ? 'Yes' : 'No'));
            $this->info("Has manage_events permission: " . ($admin->hasPermission('manage_events') ? 'Yes' : 'No'));
            $this->info("Has manage_users permission: " . ($admin->hasPermission('manage_users') ? 'Yes' : 'No'));
        }

        $this->newLine();

        // Test speaker user
        $speaker = User::where('role_id', 2)->first();
        if ($speaker) {
            $this->info("Speaker User: {$speaker->name}");
            $this->info("Has speaker role: " . ($speaker->hasRole('speaker') ? 'Yes' : 'No'));
            $this->info("Has view_events permission: " . ($speaker->hasPermission('view_events') ? 'Yes' : 'No'));
            $this->info("Has manage_events permission: " . ($speaker->hasPermission('manage_events') ? 'Yes' : 'No'));
        }

        $this->newLine();

        // Test regular user
        $user = User::where('role_id', 3)->first();
        if ($user) {
            $this->info("Regular User: {$user->name}");
            $this->info("Has user role: " . ($user->hasRole('user') ? 'Yes' : 'No'));
            $this->info("Has view_events permission: " . ($user->hasPermission('view_events') ? 'Yes' : 'No'));
            $this->info("Has manage_events permission: " . ($user->hasPermission('manage_events') ? 'Yes' : 'No'));
        }

        $this->info('Authorization test completed!');
    }
}
