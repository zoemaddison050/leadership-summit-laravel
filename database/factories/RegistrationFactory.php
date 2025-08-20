<?php

namespace Database\Factories;

use App\Models\Registration;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_id' => Ticket::factory(),
            'user_id' => null, // Direct registrations don't require users
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'cancelled']),
            'payment_status' => $this->faker->randomElement(['unpaid', 'paid', 'refunded']),
            'registration_status' => $this->faker->randomElement(['pending', 'confirmed', 'cancelled']),
            'attendee_name' => $this->faker->name,
            'attendee_email' => $this->faker->unique()->safeEmail,
            'attendee_phone' => $this->faker->phoneNumber,
            'emergency_contact' => $this->faker->name,
            'emergency_contact_name' => $this->faker->name,
            'emergency_contact_phone' => $this->faker->phoneNumber,
            'total_amount' => $this->faker->randomFloat(2, 50, 500),
            'terms_accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'confirmed',
            'registration_status' => 'confirmed',
            'payment_status' => 'paid',
            'confirmed_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'registration_status' => 'pending',
            'payment_status' => 'unpaid',
        ]);
    }

    public function cardPayment(): static
    {
        return $this->state(fn(array $attributes) => [
            'payment_status' => 'paid',
            'total_amount' => $this->faker->randomFloat(2, 50, 500),
        ]);
    }

    public function cryptoPayment(): static
    {
        return $this->state(fn(array $attributes) => [
            'payment_status' => 'unpaid',
        ]);
    }

    public function withUser(): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => User::factory(),
        ]);
    }
}
