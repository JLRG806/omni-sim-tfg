<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-13 — Buscar Asignatura.
 *
 * Cubre: búsqueda por nombre/código/profesor + sin resultados + sin filtro (CU-09).
 */
class CU13BuscarAsignaturaTest extends TestCase
{
    use RefreshDatabase;

    private function crearAsignatura(User $profesor, string $codigo, string $nombre): Asignatura
    {
        return Asignatura::create([
            'codigo'      => $codigo,
            'nombre'      => $nombre,
            'descripcion' => '',
            'profesor_id' => $profesor->id,
        ]);
    }

    #[Test]
    public function sin_q_devuelve_todas_las_asignaturas(): void
    {
        $admin    = User::factory()->admin()->create();
        $profesor = User::factory()->profesor()->create();
        $this->crearAsignatura($profesor, 'PSI-101', 'Psicología Clínica');
        $this->crearAsignatura($profesor, 'INF-201', 'Informática');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/asignaturas');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function busca_por_nombre(): void
    {
        $admin    = User::factory()->admin()->create();
        $profesor = User::factory()->profesor()->create();
        $this->crearAsignatura($profesor, 'PSI-101', 'Psicología Clínica');
        $this->crearAsignatura($profesor, 'INF-201', 'Informática');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/asignaturas?q=psicolog');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.nombre', 'Psicología Clínica');
    }

    #[Test]
    public function busca_por_codigo(): void
    {
        $admin    = User::factory()->admin()->create();
        $profesor = User::factory()->profesor()->create();
        $this->crearAsignatura($profesor, 'PSI-101', 'Psicología Clínica');
        $this->crearAsignatura($profesor, 'INF-201', 'Informática');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/asignaturas?q=INF');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.codigo', 'INF-201');
    }

    #[Test]
    public function busca_por_nombre_de_profesor(): void
    {
        $admin     = User::factory()->admin()->create();
        $garcia    = User::factory()->profesor()->create(['name' => 'Dr. García']);
        $lopez     = User::factory()->profesor()->create(['name' => 'Dra. López']);
        $this->crearAsignatura($garcia, 'ASI-001', 'Asignatura García');
        $this->crearAsignatura($lopez,  'ASI-002', 'Asignatura López');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/asignaturas?q=García');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.codigo', 'ASI-001');
    }

    #[Test]
    public function sin_resultados_devuelve_array_vacio(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/asignaturas?q=zzznoresults');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function falla_si_no_es_admin(): void
    {
        $profesor = User::factory()->profesor()->create();

        $this->actingAs($profesor, 'sanctum')
             ->getJson('/api/v1/asignaturas?q=test')
             ->assertStatus(403);
    }
}
