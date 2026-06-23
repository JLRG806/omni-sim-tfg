<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Escenario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-22 — Buscar Escenario.
 *
 * Cubre: listado completo, filtro por título/área/estado, no-titular 403.
 */
class CU22BuscarEscenarioTest extends TestCase
{
    use RefreshDatabase;

    private function setup22(): array
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = Asignatura::create(['codigo' => 'TST-001', 'nombre' => 'Test', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'Entrevista clínica', 'area_conocimiento' => 'Psicología', 'descripcion_situacion' => 'X', 'estado' => 'borrador']);
        Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'Consulta nutricional', 'area_conocimiento' => 'Dietética', 'descripcion_situacion' => 'X', 'estado' => 'publicado']);
        return [$profesor, $asig];
    }

    #[Test]
    public function sin_q_devuelve_todos_los_escenarios(): void
    {
        [$profesor, $asig] = $this->setup22();

        $response = $this->actingAs($profesor, 'sanctum')
            ->getJson("/api/v1/asignaturas/{$asig->id}/escenarios");

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data')
                 ->assertJsonStructure(['data' => [['id', 'titulo', 'area_conocimiento', 'estado']]]);
    }

    #[Test]
    public function filtra_por_titulo(): void
    {
        [$profesor, $asig] = $this->setup22();

        $response = $this->actingAs($profesor, 'sanctum')
            ->getJson("/api/v1/asignaturas/{$asig->id}/escenarios?q=clínica");

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.titulo', 'Entrevista clínica');
    }

    #[Test]
    public function filtra_por_estado(): void
    {
        [$profesor, $asig] = $this->setup22();

        $response = $this->actingAs($profesor, 'sanctum')
            ->getJson("/api/v1/asignaturas/{$asig->id}/escenarios?q=publicado");

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.estado', 'publicado');
    }

    #[Test]
    public function falla_si_no_es_titular(): void
    {
        [, $asig] = $this->setup22();
        $otro = User::factory()->profesor()->create();

        $this->actingAs($otro, 'sanctum')
             ->getJson("/api/v1/asignaturas/{$asig->id}/escenarios")
             ->assertStatus(403);
    }
}
