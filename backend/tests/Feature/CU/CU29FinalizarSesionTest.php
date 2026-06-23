<?php

namespace Tests\Feature\CU;

use App\Jobs\GenerarBorradorIAJob;
use App\Models\Asignatura;
use App\Models\Escenario;
use App\Models\Matricula;
use App\Models\PerfilAgente;
use App\Models\SesionSimulacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-29 — Finalizar Sesión (ASYNC).
 *
 * Cubre: 202 + job encolado + estado procesando + no en_curso + no propietario.
 */
class CU29FinalizarSesionTest extends TestCase
{
    use RefreshDatabase;

    private function sesionEnCurso(User $alumno): SesionSimulacion
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = Asignatura::create(['codigo' => 'T', 'nombre' => 'T', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        Matricula::create(['alumno_id' => $alumno->id, 'asignatura_id' => $asig->id, 'fecha_matricula' => now()->toDateString()]);
        $esc  = Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'T', 'area_conocimiento' => 'T', 'descripcion_situacion' => 'X', 'estado' => 'publicado']);
        PerfilAgente::create(['escenario_id' => $esc->id, 'rol_identidad' => 'X', 'trasfondo' => 'X', 'conocimientos' => 'X', 'mensaje_bienvenida' => 'Hola', 'comportamiento' => 'X', 'tono_emocional' => 'formal', 'nivel_dificultad' => 'facil', 'informacion_explicita' => ['A'], 'informacion_latente' => ['B'], 'restricciones' => []]);
        return SesionSimulacion::create(['escenario_id' => $esc->id, 'alumno_id' => $alumno->id, 'estado' => 'en_curso', 'inicio_at' => now()]);
    }

    #[Test]
    public function finalizar_sesion_devuelve_202_y_encola_job(): void
    {
        Queue::fake();
        $alumno = User::factory()->create();
        $ses    = $this->sesionEnCurso($alumno);

        $response = $this->actingAs($alumno, 'sanctum')
            ->patchJson("/api/v1/sesiones/{$ses->id}/finalizar");

        $response->assertStatus(202)
                 ->assertJsonPath('estado', 'procesando');

        Queue::assertPushed(GenerarBorradorIAJob::class, fn ($job) => $job->sesionId === $ses->id);

        $this->assertDatabaseHas('sesiones_simulacion', ['id' => $ses->id, 'estado' => 'procesando']);
        $this->assertDatabaseHas('resultados', ['sesion_simulacion_id' => $ses->id, 'estado' => 'pendiente']);
    }

    #[Test]
    public function falla_si_sesion_no_en_curso(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesionEnCurso($alumno);
        $ses->update(['estado' => 'pausada']);

        $this->actingAs($alumno, 'sanctum')
             ->patchJson("/api/v1/sesiones/{$ses->id}/finalizar")
             ->assertStatus(422);
    }

    #[Test]
    public function falla_si_no_es_propietario(): void
    {
        $alumno = User::factory()->create();
        $otro   = User::factory()->create();
        $ses    = $this->sesionEnCurso($alumno);

        $this->actingAs($otro, 'sanctum')
             ->patchJson("/api/v1/sesiones/{$ses->id}/finalizar")
             ->assertStatus(403);
    }
}
