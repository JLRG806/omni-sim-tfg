<?php

namespace Tests\Feature\CU;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-04 — Listar Usuarios.
 *
 * Cubre: listado completo para admin + rechazo por rol + sin autenticación.
 */
class CU04ListarUsuariosTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_obtiene_lista_con_campos_correctos(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->profesor()->create();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/usuarios');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [['id', 'name', 'email', 'rol', 'estado']],
                 ])
                 ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function profesor_recibe_403(): void
    {
        $profesor = User::factory()->profesor()->create();

        $this->actingAs($profesor, 'sanctum')
             ->getJson('/api/v1/usuarios')
             ->assertStatus(403)
             ->assertJsonPath('message', 'No tiene permisos para esta acción');
    }

    #[Test]
    public function alumno_recibe_403(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
             ->getJson('/api/v1/usuarios')
             ->assertStatus(403);
    }

    #[Test]
    public function no_autenticado_recibe_401(): void
    {
        $this->getJson('/api/v1/usuarios')
             ->assertStatus(401);
    }
}
