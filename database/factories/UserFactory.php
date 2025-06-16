<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(), // Add UUID for id
            'name' => fake()->name(),
            'email' => fake()->safeEmail(), // Removed unique() to prevent Faker overflow in tests
            'role' => fake()->randomElement(['User', 'Manager']), // Add a default role
        ];
    }
}
