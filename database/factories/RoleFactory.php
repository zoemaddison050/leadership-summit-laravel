<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['admin', 'user', 'speaker', 'moderator']),
            'permissions' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'admin',
            'permissions' => ['*'],
        ]);
    }

    public function user(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'user',
            'permissions' => ['view_events', 'register_events'],
        ]);
    }

    public function speaker(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'speaker',
            'permissions' => ['view_events', 'manage_sessions'],
        ]);
    }
}
