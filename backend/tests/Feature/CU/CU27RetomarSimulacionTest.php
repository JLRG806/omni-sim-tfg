<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Escenario;
use App\Models\Matricula;
use App\Models\Mensaje;
use App\Models\PerfilAgente;
use App\Models\SesionSimulacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-27 — Retomar Simulación.
 *
 * Cubre: retomar pausada → en_curso + ya en_curso OK + finalizada 422 + no propietario 403.
 */
class CU27RetomarSimulacionTest extends TestCase
{
    use RefreshDatabase;

    private function sesion(User $alumno, string $estado): SesionSimulacion
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = Asignatura::create(['codigo' => 'T', 'nombre' => 'T', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        Matricula::create(['alumno_id' => $alumno->id, 'asignatura_id' => $asig->id, 'fecha_matricula' => now()->toDateString()]);
        $esc  = Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'T', 'area_conocimiento' => 'T', 'descripcion_situacion' => 'X', 'estado' => 'publicado']);
        PerfilAgente::create(['escenario_id' => $esc->id, 'rol_identidad' => 'X', 'trasfondo' => 'X', 'conocimientos' => 'X', 'mensaje_bienvenida' => 'Hola', 'comportamiento' => 'X', 'tono_emocional' => 'formal', 'nivel_dificultad' => 'facil', 'informacion_explicita' => ['A'], 'informacion_latente' => ['B'], 'restricciones' => []]);
        $ses  = SesionSimulacion::create(['escenario_id' => $esc->id, 'alumno_id' => $alumno->id, 'estado' => $estado, 'inicio_at' => now()->subHour()]);
        Mensaje::create(['sesion_simulacion_id' => $ses->id, 'emisor' => 'agente', 'contenido' => 'Hola', 'orden' => 1]);
        return $ses;
    }

    #[Test]
    public function retomar_sesion_pausada_la_pone_en_curso(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesion($alumno, 'pausada');

        $response = $this->actingAs($alumno, 'sanctum')
            ->patchJson("/api/v1/sesiones/{$ses->id}/retomar");

        $response->assertStatus(200)
                 ->assertJsonPath('sesion.estado', 'en_curso')
                 ->assertJsonCount(1, 'sesion.mensajes');

        $this->assertDatabaseHas('sesiones_simulacion', ['id' => $ses->id, 'estado' => 'en_curso']);
    }

    #[Test]
    public function retomar_sesion_en_curso_devuelve_historial(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesion($alumno, 'en_curso');

        $response = $this->actingAs($alumno, 'sanctum')
            ->patchJson("/api/v1/sesiones/{$ses->id}/retomar");

        $response->assertStatus(200)
                 ->assertJsonPath('sesion.estado', 'en_curso')
                 ->assertJsonCount(1, 'sesion.mensajes');
    }

    #[Test]
    public function falla_si_sesion_finalizada(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesion($alumno, 'finalizada');

        $this->actingAs($alumno, 'sanctum')
             ->patchJson("/api/v1/sesiones/{$ses->id}/retomar")
             ->assertStatus(422);
    }

    #[Test]
    public function falla_si_no_es_el_propietario(): void
    {
        $alumno = User::factory()->create();
        $otro   = User::factory()->create();
        $ses    = $this->sesion($alumno, 'pausada');

        $this->actingAs($otro, 'sanctum')
             ->patchJson("/api/v1/sesiones/{$ses->id}/retomar")
             ->assertStatus(403);
    }
}
