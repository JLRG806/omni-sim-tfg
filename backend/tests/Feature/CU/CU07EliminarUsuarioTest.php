<?php

namespace Tests\Feature\CU;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-07 — Eliminar Usuario.
 *
 * Cubre: eliminación correcta (soft delete) + autorización + usuario inexistente.
 */
class CU07EliminarUsuarioTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_elimina_usuario_correctamente(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/usuarios/{$usuario->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Usuario eliminado correctamente');

        $this->assertSoftDeleted('users', ['id' => $usuario->id]);
    }

    #[Test]
    public function usuario_eliminado_no_aparece_en_listado(): void
    {
        $admin   = User::factory()->admin()->create();
        $usuario = User::factory()->create(['email' => 'eliminar@test.com']);

        $this->actingAs($admin, 'sanctum')
             ->deleteJson("/api/v1/usuarios/{$usuario->id}");

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/usuarios');

        $emails = collect($response->json('data'))->pluck('email')->toArray();
        $this->assertNotContains('eliminar@test.com', $emails);
    }

    #[Test]
    public function falla_si_no_es_admin(): void
    {
        $profesor = User::factory()->profesor()->create();
        $usuario  = User::factory()->create();

        $this->actingAs($profesor, 'sanctum')
             ->deleteJson("/api/v1/usuarios/{$usuario->id}")
             ->assertStatus(403);
    }

    #[Test]
    public function devuelve_404_si_usuario_no_existe(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
             ->deleteJson('/api/v1/usuarios/9999')
             ->assertStatus(404);
    }
}
