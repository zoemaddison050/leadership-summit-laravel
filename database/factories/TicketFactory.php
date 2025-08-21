<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => $this->faker->randomElement(['General Admission', 'VIP', 'Early Bird', 'Student']),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 25, 500),
            'quantity' => $this->faker->numberBetween(10, 200),
            'available' => $this->faker->numberBetween(10, 200),
            'max_per_order' => $this->faker->numberBetween(1, 10),
            'sale_start' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'sale_end' => $this->faker->dateTimeBetween('now', '+6 months'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
