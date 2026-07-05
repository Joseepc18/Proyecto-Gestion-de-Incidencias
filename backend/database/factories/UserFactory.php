<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

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

    // Asigna el rol por nombre (id_rol es NOT NULL); los roles son datos de referencia sembrados.
    public function conRol(string $nombreRol = 'normal'): static
    {
        return $this->state(fn (array $attributes) => [
            'id_rol' => Rol::where('nombre_rol', $nombreRol)->value('id_rol'),
        ]);
    }

    // Usuario con 2FA ya confirmado (secreto de ejemplo); los roles privilegiados lo requieren.
    public function conDosFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['ABCD-1234', 'EFGH-5678'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
