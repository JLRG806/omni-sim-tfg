<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Factory para el modelo User. Genera alumnos por defecto.
 * Usa los métodos de estado (profesor(), admin()) para otros roles.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Contraseña reutilizada entre instancias para no re-hashear en cada llamada.
     */
    protected static ?string $password;

    /**
     * Estado por defecto: alumno activo sin avatar.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'               => fake()->name(),
            'email'              => fake()->unique()->safeEmail(),
            'rol'                => 'alumno',
            'estado'             => 'activo',
            'avatar_path'        => null,
            'email_verified_at'  => now(),
            'password'           => static::$password ??= Hash::make('password'),
        ];
    }

    /**
     * Genera un usuario con rol profesor.
     */
    public function profesor(): static
    {
        return $this->state(fn (array $attributes) => ['rol' => 'profesor']);
    }

    /**
     * Genera un usuario con rol administrador.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['rol' => 'admin']);
    }

    /**
     * Genera un usuario inactivo.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => 'inactivo']);
    }

    /**
     * Genera un usuario con email sin verificar.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }
}
