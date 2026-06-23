<?php

namespace Tests\Feature\CU;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-05 — Crear Usuario.
 *
 * Cubre: creación exitosa + correo duplicado + validación + autorización.
 */
class CU05CrearUsuarioTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_crea_usuario_correctamente(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/usuarios', [
                'name'                  => 'María López',
                'email'                 => 'maria@test.com',
                'password'              => 'Password123',
                'password_confirmation' => 'Password123',
                'rol'                   => 'profesor',
                'estado'                => 'activo',
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('message', 'Usuario creado correctamente')
                 ->assertJsonPath('data.email', 'maria@test.com')
                 ->assertJsonPath('data.rol', 'profesor');

        $this->assertDatabaseHas('users', ['email' => 'maria@test.com']);
    }

    #[Test]
    public function falla_con_correo_duplicado(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'existe@test.com']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/usuarios', [
                'name'                  => 'Otro Usuario',
                'email'                 => 'existe@test.com',
                'password'              => 'Password123',
                'password_confirmation' => 'Password123',
                'rol'                   => 'alumno',
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function falla_con_rol_invalido(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/usuarios', [
                'name'                  => 'Test',
                'email'                 => 'test@test.com',
                'password'              => 'Password123',
                'password_confirmation' => 'Password123',
                'rol'                   => 'superadmin',
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['rol']);
    }

    #[Test]
    public function falla_si_no_es_admin(): void
    {
        $profesor = User::factory()->profesor()->create();

        $this->actingAs($profesor, 'sanctum')
             ->postJson('/api/v1/usuarios', [])
             ->assertStatus(403);
    }

    #[Test]
    public function estado_por_defecto_es_activo(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/usuarios', [
                'name'                  => 'Sin Estado',
                'email'                 => 'sinestado@test.com',
                'password'              => 'Password123',
                'password_confirmation' => 'Password123',
                'rol'                   => 'alumno',
            ]);

        $this->assertDatabaseHas('users', [
            'email'  => 'sinestado@test.com',
            'estado' => 'activo',
        ]);
    }
}
