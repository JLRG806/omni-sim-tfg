<?php

namespace Tests\Feature\CU;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-08 — Buscar Usuario.
 *
 * Cubre: búsqueda por nombre/correo/rol + sin resultados + sin filtro (CU-04).
 */
class CU08BuscarUsuarioTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function sin_q_devuelve_todos_los_usuarios(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/usuarios');

        $response->assertStatus(200)
                 ->assertJsonCount(4, 'data');
    }

    #[Test]
    public function busca_por_nombre(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Juan García']);
        User::factory()->create(['name' => 'María López']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/usuarios?q=juan');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.name', 'Juan García');
    }

    #[Test]
    public function busca_por_correo(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'juan@universidad.edu']);
        User::factory()->create(['email' => 'maria@universidad.edu']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/usuarios?q=juan@');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.email', 'juan@universidad.edu');
    }

    #[Test]
    public function busca_por_rol(): void
    {
        $admin    = User::factory()->admin()->create();
        User::factory()->profesor()->create();
        User::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/usuarios?q=profesor');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.rol', 'profesor');
    }

    #[Test]
    public function sin_resultados_devuelve_array_vacio(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/usuarios?q=zzznoresults');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function falla_si_no_es_admin(): void
    {
        $profesor = User::factory()->profesor()->create();

        $this->actingAs($profesor, 'sanctum')
             ->getJson('/api/v1/usuarios?q=test')
             ->assertStatus(403);
    }
}
