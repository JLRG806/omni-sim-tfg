<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Escenario;
use App\Models\SesionSimulacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-21 — Despublicar Escenario.
 *
 * Cubre: despublicar OK + ya borrador 422 + no-titular 403.
 */
class CU21DespublicarEscenarioTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(User $profesor, string $estado = 'publicado'): Escenario
    {
        $asig = Asignatura::create(['codigo' => 'TST-001', 'nombre' => 'Test', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        return Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'Test', 'area_conocimiento' => 'Test', 'descripcion_situacion' => 'X', 'estado' => $estado]);
    }

    #[Test]
    public function profesor_despublica_escenario_correctamente(): void
    {
        $profesor = User::factory()->profesor()->create();
        $esc      = $this->escenario($profesor, 'publicado');

        $response = $this->actingAs($profesor, 'sanctum')
            ->patchJson("/api/v1/escenarios/{$esc->id}/despublicar");

        $response->assertStatus(200)
                 ->assertJsonPath('escenario.estado', 'borrador');

        $this->assertDatabaseHas('escenarios', ['id' => $esc->id, 'estado' => 'borrador']);
    }

    #[Test]
    public function falla_si_ya_en_borrador(): void
    {
        $profesor = User::factory()->profesor()->create();
        $esc      = $this->escenario($profesor, 'borrador');

        $this->actingAs($profesor, 'sanctum')
             ->patchJson("/api/v1/escenarios/{$esc->id}/despublicar")
             ->assertStatus(422);
    }

    #[Test]
    public function falla_si_hay_sesiones_activas(): void
    {
        $profesor = User::factory()->profesor()->create();
        $alumno   = User::factory()->create();
        $esc      = $this->escenario($profesor, 'publicado');

        SesionSimulacion::create(['escenario_id' => $esc->id, 'alumno_id' => $alumno->id, 'estado' => 'en_curso', 'inicio_at' => now()]);

        $this->actingAs($profesor, 'sanctum')
             ->patchJson("/api/v1/escenarios/{$esc->id}/despublicar")
             ->assertStatus(422)
             ->assertJsonPath('message', 'No se puede despublicar: hay 1 sesión(es) activa(s). Espera a que finalicen.');
    }

    #[Test]
    public function falla_si_no_es_titular(): void
    {
        $p1  = User::factory()->profesor()->create();
        $p2  = User::factory()->profesor()->create();
        $esc = $this->escenario($p1, 'publicado');

        $this->actingAs($p2, 'sanctum')
             ->patchJson("/api/v1/escenarios/{$esc->id}/despublicar")
             ->assertStatus(403);
    }
}
