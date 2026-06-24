<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Escenario;
use App\Models\Matricula;
use App\Models\PerfilAgente;
use App\Models\SesionSimulacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-31 — Pausar Simulación.
 *
 * Cubre: pausar en_curso → pausada + no propietario + ya pausada + tipo=prueba.
 */
class CU31PausarSimulacionTest extends TestCase
{
    use RefreshDatabase;

    private function sesion(User $alumno, string $estado = 'en_curso', string $tipo = 'real'): SesionSimulacion
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = Asignatura::create(['codigo' => 'T', 'nombre' => 'T', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        Matricula::create(['alumno_id' => $alumno->id, 'asignatura_id' => $asig->id, 'fecha_matricula' => now()->toDateString()]);
        $esc  = Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'T', 'area_conocimiento' => 'T', 'descripcion_situacion' => 'X', 'estado' => 'publicado']);
        PerfilAgente::create(['escenario_id' => $esc->id, 'rol_identidad' => 'X', 'trasfondo' => 'X', 'conocimientos' => 'X', 'mensaje_bienvenida' => 'Hola', 'comportamiento' => 'X', 'tono_emocional' => 'formal', 'nivel_dificultad' => 'facil', 'informacion_explicita' => ['A'], 'informacion_latente' => ['B'], 'restricciones' => []]);
        return SesionSimulacion::create(['escenario_id' => $esc->id, 'alumno_id' => $alumno->id, 'estado' => $estado, 'tipo' => $tipo, 'inicio_at' => now()]);
    }

    #[Test]
    public function alumno_pausa_sesion_en_curso(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesion($alumno);

        $response = $this->actingAs($alumno, 'sanctum')
            ->patchJson("/api/v1/sesiones/{$ses->id}/pausar");

        $response->assertStatus(200)
                 ->assertJsonPath('estado', 'pausada');

        $this->assertDatabaseHas('sesiones_simulacion', ['id' => $ses->id, 'estado' => 'pausada']);
    }

    #[Test]
    public function falla_si_ya_pausada(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesion($alumno, 'pausada');

        $this->actingAs($alumno, 'sanctum')
             ->patchJson("/api/v1/sesiones/{$ses->id}/pausar")
             ->assertStatus(422);
    }

    #[Test]
    public function falla_si_no_es_propietario(): void
    {
        $alumno = User::factory()->create();
        $otro   = User::factory()->create();
        $ses    = $this->sesion($alumno);

        $this->actingAs($otro, 'sanctum')
             ->patchJson("/api/v1/sesiones/{$ses->id}/pausar")
             ->assertStatus(403);
    }

    #[Test]
    public function falla_si_sesion_finalizada(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesion($alumno, 'finalizada');

        $this->actingAs($alumno, 'sanctum')
             ->patchJson("/api/v1/sesiones/{$ses->id}/pausar")
             ->assertStatus(422);
    }
}
