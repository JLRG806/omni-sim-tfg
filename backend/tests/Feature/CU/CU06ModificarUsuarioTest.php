<?php

namespace Tests\Feature\CU;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-06 — Modificar Usuario.
 *
 * Cubre: actualización exitosa + contraseña opcional + email duplicado + autorización.
 */
class CU06ModificarUsuarioTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_modifica_usuario_correctamente(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create(['name' => 'Original', 'rol' => 'alumno']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/usuarios/{$usuario->id}", [
                'name'   => 'Modificado',
                'email'  => $usuario->email,
                'rol'    => 'profesor',
                'estado' => 'activo',
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Usuario modificado correctamente')
                 ->assertJsonPath('data.name', 'Modificado')
                 ->assertJsonPath('data.rol', 'profesor');

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'name' => 'Modificado']);
    }

    #[Test]
    public function password_opcional_no_se_cambia_si_no_se_envia(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create();
        $hashAnterior = $usuario->password;

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/usuarios/{$usuario->id}", [
                'name'   => $usuario->name,
                'email'  => $usuario->email,
                'rol'    => $usuario->rol,
                'estado' => $usuario->estado,
            ]);

        $this->assertDatabaseHas('users', [
            'id'       => $usuario->id,
            'password' => $hashAnterior,
        ]);
    }

    #[Test]
    public function falla_con_email_duplicado_de_otro_usuario(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create();
        $otro    = User::factory()->create(['email' => 'otro@test.com']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/usuarios/{$usuario->id}", [
                'name'   => $usuario->name,
                'email'  => 'otro@test.com',
                'rol'    => $usuario->rol,
                'estado' => $usuario->estado,
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function puede_mantener_su_propio_email(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create(['email' => 'mismo@test.com']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/usuarios/{$usuario->id}", [
                'name'   => 'Nuevo nombre',
                'email'  => 'mismo@test.com',
                'rol'    => $usuario->rol,
                'estado' => $usuario->estado,
            ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function falla_si_no_es_admin(): void
    {
        $profesor = User::factory()->profesor()->create();
        $usuario  = User::factory()->create();

        $this->actingAs($profesor, 'sanctum')
             ->putJson("/api/v1/usuarios/{$usuario->id}", [])
             ->assertStatus(403);
    }

    #[Test]
    public function devuelve_404_si_usuario_no_existe(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
             ->putJson('/api/v1/usuarios/9999', [
                 'name' => 'X', 'email' => 'x@test.com',
                 'rol' => 'alumno', 'estado' => 'activo',
             ])
             ->assertStatus(404);
    }
}
