<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        $speakerRole = \App\Models\Role::where('name', 'speaker')->first();
        $userRole = \App\Models\Role::where('name', 'user')->first();

        // Create admin user
        \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@leadershipsummit.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        // Create sample speaker users
        \App\Models\User::create([
            'name' => 'John Speaker',
            'email' => 'john.speaker@example.com',
            'password' => bcrypt('password'),
            'role_id' => $speakerRole->id,
            'email_verified_at' => now(),
        ]);

        \App\Models\User::create([
            'name' => 'Jane Expert',
            'email' => 'jane.expert@example.com',
            'password' => bcrypt('password'),
            'role_id' => $speakerRole->id,
            'email_verified_at' => now(),
        ]);

        // Create sample regular users
        for ($i = 1; $i <= 5; $i++) {
            \App\Models\User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
                'role_id' => $userRole->id,
                'email_verified_at' => now(),
            ]);
        }
    }
}
