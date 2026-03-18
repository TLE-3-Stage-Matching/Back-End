<?php

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => UserRole::Student,
            'email' => \fake()->unique()->safeEmail(),
            'password_hash' => 'password',
            'first_name' => \fake()->firstName(),
            'middle_name' => null,
            'last_name' => \fake()->lastName(),
            'phone' => \fake()->phoneNumber(),
            'profile_photo_url' => null,
        ];
    }

    /**
     * Configure the model as a student.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Student,
        ]);
    }

    /**
     * Configure the model as a coordinator.
     */
    public function coordinator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Coordinator,
        ]);
    }

    /**
     * Configure the model as a company user.
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Company,
        ]);
    }
}
