<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'slug' => $this->faker->slug,
            'description' => $this->faker->paragraph(3),
            'start_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'end_date' => $this->faker->dateTimeBetween('+6 months', '+1 year'),
            'location' => $this->faker->address,
            'featured_image' => null,
            'status' => $this->faker->randomElement(['active', 'inactive', 'draft']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function default(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_default' => true,
        ]);
    }
}
