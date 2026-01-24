<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        $username = strtolower(preg_replace('/[^a-z0-9._]/', '', fake()->unique()->userName()));
        $username = strlen($username) < 3 ? 'u' . fake()->unique()->numberBetween(10000, 99999) : substr($username, 0, 30);

        return [
            'name' => fake()->name(),
            'username' => $username,
            'phone' => fake()->e164PhoneNumber(),
        ];
    }
}
