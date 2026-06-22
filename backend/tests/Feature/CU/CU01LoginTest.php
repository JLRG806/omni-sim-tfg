<?php

namespace Tests\Feature\CU;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-01 — Iniciar Sesión.
 *
 * Cubre: flujo principal (token + user) + credenciales inválidas + validación.
 */
class CU01LoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function flujo_principal_devuelve_token_y_rol(): void
    {
        $user = User::factory()->admin()->create(['email' => 'admin@test.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user' => ['id', 'name', 'rol']])
                 ->assertJsonPath('user.rol', 'admin');
    }

    #[Test]
    public function falla_con_password_incorrecta(): void
    {
        User::factory()->create(['email' => 'test@test.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@test.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401)
                 ->assertJsonPath('message', 'Credenciales inválidas');
    }

    #[Test]
    public function falla_con_email_inexistente(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'noexiste@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function falla_sin_campos_requeridos(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'password']);
    }
}
