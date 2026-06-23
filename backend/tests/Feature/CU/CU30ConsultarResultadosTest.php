<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Competencia;
use App\Models\Escenario;
use App\Models\Matricula;
use App\Models\PerfilAgente;
use App\Models\Resultado;
use App\Models\SesionSimulacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-30 — Consultar Resultados.
 *
 * Cubre: resultado evaluado+publicado, resultado pendiente, sin resultado, no propietario.
 */
class CU30ConsultarResultadosTest extends TestCase
{
    use RefreshDatabase;

    private function sesion(User $alumno): SesionSimulacion
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = Asignatura::create(['codigo' => 'T', 'nombre' => 'T', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        Matricula::create(['alumno_id' => $alumno->id, 'asignatura_id' => $asig->id, 'fecha_matricula' => now()->toDateString()]);
        $esc  = Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'T', 'area_conocimiento' => 'T', 'descripcion_situacion' => 'X', 'estado' => 'publicado']);
        PerfilAgente::create(['escenario_id' => $esc->id, 'rol_identidad' => 'X', 'trasfondo' => 'X', 'conocimientos' => 'X', 'mensaje_bienvenida' => 'Hola', 'comportamiento' => 'X', 'tono_emocional' => 'formal', 'nivel_dificultad' => 'facil', 'informacion_explicita' => ['A'], 'informacion_latente' => ['B'], 'restricciones' => []]);
        return SesionSimulacion::create(['escenario_id' => $esc->id, 'alumno_id' => $alumno->id, 'estado' => 'evaluada', 'inicio_at' => now()->subHour(), 'finalizacion_at' => now()->subMinutes(30)]);
    }

    #[Test]
    public function alumno_ve_resultado_publicado(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesion($alumno);
        $comp   = Competencia::create(['nombre' => 'C', 'descripcion' => 'X', 'escenario_id' => null]);

        Resultado::create([
            'sesion_simulacion_id'          => $ses->id,
            'estado'                        => 'evaluado',
            'borrador_resumen'              => 'X',
            'borrador_calificacion'         => 7.0,
            'borrador_feedback'             => 'X',
            'borrador_competencias'         => [],
            'borrador_mapa_descubrimiento'  => ['descubierto' => ['A'], 'no_descubierto' => ['B']],
            'final_calificacion'            => 8.5,
            'final_feedback'                => 'Muy buena entrevista',
            'final_competencias'            => [['competencia_id' => $comp->id, 'puntuacion' => 9.0]],
            'publicado_at'                  => now()->subHour(),
        ]);

        $response = $this->actingAs($alumno, 'sanctum')
            ->getJson("/api/v1/sesiones/{$ses->id}/resultado");

        $response->assertStatus(200)
                 ->assertJsonPath('estado', 'evaluado')
                 ->assertJsonPath('resultado.final_calificacion', 8.5)
                 ->assertJsonPath('resultado.final_feedback', 'Muy buena entrevista')
                 ->assertJsonStructure(['resultado' => ['final_calificacion', 'final_feedback', 'final_competencias', 'mapa_descubrimiento']]);
    }

    #[Test]
    public function alumno_ve_estado_pendiente_si_no_publicado(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesion($alumno);

        Resultado::create([
            'sesion_simulacion_id' => $ses->id,
            'estado'               => 'procesando',
            'borrador_calificacion' => null,
        ]);

        $response = $this->actingAs($alumno, 'sanctum')
            ->getJson("/api/v1/sesiones/{$ses->id}/resultado");

        $response->assertStatus(200)
                 ->assertJsonPath('estado', 'procesando')
                 ->assertJsonMissing(['resultado']);
    }

    #[Test]
    public function sin_resultado_devuelve_estado_pendiente(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesion($alumno);

        $this->actingAs($alumno, 'sanctum')
             ->getJson("/api/v1/sesiones/{$ses->id}/resultado")
             ->assertStatus(200)
             ->assertJsonPath('estado', 'pendiente');
    }

    #[Test]
    public function falla_si_no_es_propietario(): void
    {
        $alumno = User::factory()->create();
        $otro   = User::factory()->create();
        $ses    = $this->sesion($alumno);

        $this->actingAs($otro, 'sanctum')
             ->getJson("/api/v1/sesiones/{$ses->id}/resultado")
             ->assertStatus(403);
    }
}
