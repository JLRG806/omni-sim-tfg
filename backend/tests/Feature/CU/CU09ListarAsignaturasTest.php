<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-09 — Listar Asignaturas.
 *
 * Cubre: listado con profesor eager-loaded + autorización.
 */
class CU09ListarAsignaturasTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_obtiene_lista_con_nombre_codigo_y_profesor(): void
    {
        $admin    = User::factory()->admin()->create();
        $profesor = User::factory()->profesor()->create(['name' => 'Dr. López']);

        Asignatura::create([
            'nombre'      => 'Psicología Clínica',
            'codigo'      => 'PSI-101',
            'descripcion' => 'Introducción a la psicología clínica',
            'profesor_id' => $profesor->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/asignaturas');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.nombre', 'Psicología Clínica')
                 ->assertJsonPath('data.0.codigo', 'PSI-101')
                 ->assertJsonPath('data.0.profesor.name', 'Dr. López');
    }

    #[Test]
    public function lista_ordenada_por_nombre(): void
    {
        $admin    = User::factory()->admin()->create();
        $profesor = User::factory()->profesor()->create();

        Asignatura::create(['nombre' => 'Zoología', 'codigo' => 'ZOO-100', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        Asignatura::create(['nombre' => 'Anatomía', 'codigo' => 'ANA-100', 'descripcion' => '', 'profesor_id' => $profesor->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/asignaturas');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data')
                 ->assertJsonPath('data.0.nombre', 'Anatomía')
                 ->assertJsonPath('data.1.nombre', 'Zoología');
    }

    #[Test]
    public function devuelve_lista_vacia_si_no_hay_asignaturas(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/asignaturas');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function falla_si_no_es_admin(): void
    {
        $profesor = User::factory()->profesor()->create();

        $this->actingAs($profesor, 'sanctum')
             ->getJson('/api/v1/asignaturas')
             ->assertStatus(403);
    }
}
