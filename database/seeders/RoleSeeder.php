<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'permissions' => [
                    'manage_events',
                    'manage_users',
                    'manage_speakers',
                    'manage_sessions',
                    'manage_pages',
                    'view_reports'
                ]
            ],
            [
                'name' => 'speaker',
                'permissions' => [
                    'view_events',
                    'manage_own_sessions'
                ]
            ],
            [
                'name' => 'user',
                'permissions' => [
                    'view_events',
                    'register_for_events'
                ]
            ]
        ];

        foreach ($roles as $role) {
            \App\Models\Role::create($role);
        }
    }
}
