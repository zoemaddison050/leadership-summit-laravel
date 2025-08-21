<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'role_id' => null, // Will be set by states
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role_id' => 1, // Assuming admin role has ID 1
        ]);
    }

    public function speaker(): static
    {
        return $this->state(fn(array $attributes) => [
            'role_id' => 2, // Assuming speaker role has ID 2
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
