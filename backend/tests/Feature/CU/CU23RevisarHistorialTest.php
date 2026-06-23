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
 * Tests para CU-23 — Revisar Historial de sesiones.
 *
 * Cubre: listado de sesiones por escenario + sin escenario_id + no-titular 403.
 */
class CU23RevisarHistorialTest extends TestCase
{
    use RefreshDatabase;

    private function escenarioConSesion(User $profesor, User $alumno): array
    {
        $asig = Asignatura::create(['codigo' => 'TST-001', 'nombre' => 'Test', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        $esc  = Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'Test', 'area_conocimiento' => 'Test', 'descripcion_situacion' => 'X', 'estado' => 'publicado']);
        $ses  = SesionSimulacion::create(['escenario_id' => $esc->id, 'alumno_id' => $alumno->id, 'estado' => 'finalizada', 'inicio_at' => now()->subHour(), 'finalizacion_at' => now()]);
        return [$esc, $ses];
    }

    #[Test]
    public function profesor_obtiene_historial_del_escenario(): void
    {
        $profesor = User::factory()->profesor()->create();
        $alumno   = User::factory()->create();
        [$esc, $ses] = $this->escenarioConSesion($profesor, $alumno);

        $response = $this->actingAs($profesor, 'sanctum')
            ->getJson("/api/v1/sesiones?escenario_id={$esc->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('escenario.id', $esc->id)
                 ->assertJsonCount(1, 'sesiones')
                 ->assertJsonPath('sesiones.0.estado', 'finalizada')
                 ->assertJsonStructure(['sesiones' => [['id', 'alumno', 'estado', 'inicio_at', 'num_mensajes']]]);
    }

    #[Test]
    public function falla_sin_escenario_id(): void
    {
        $profesor = User::factory()->profesor()->create();

        $this->actingAs($profesor, 'sanctum')
             ->getJson('/api/v1/sesiones')
             ->assertStatus(422);
    }

    #[Test]
    public function falla_si_no_es_titular(): void
    {
        $p1     = User::factory()->profesor()->create();
        $p2     = User::factory()->profesor()->create();
        $alumno = User::factory()->create();
        [$esc, ] = $this->escenarioConSesion($p1, $alumno);

        $this->actingAs($p2, 'sanctum')
             ->getJson("/api/v1/sesiones?escenario_id={$esc->id}")
             ->assertStatus(403);
    }
}
