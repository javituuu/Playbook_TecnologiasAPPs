<?php

namespace Database\Factories;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRoleEnum::JUGADOR,
            'activo' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Asigna un rol específico del ecosistema Playbook.
     */
    public function role(UserRoleEnum $role): static
    {
        return $this->state(fn (array $attributes) => ['role' => $role]);
    }

    public function coach(): static
    {
        return $this->role(UserRoleEnum::COACH);
    }

    public function adminClub(): static
    {
        return $this->role(UserRoleEnum::ADMIN_CLUB);
    }

    public function adminLiga(): static
    {
        return $this->role(UserRoleEnum::ADMIN_LIGA);
    }
}
