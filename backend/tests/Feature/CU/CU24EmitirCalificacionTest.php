<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Competencia;
use App\Models\Escenario;
use App\Models\Mensaje;
use App\Models\Resultado;
use App\Models\SesionSimulacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-24 — Emitir Calificación.
 *
 * Cubre: cargar borrador + publicar calificación + ya evaluado + no-titular.
 */
class CU24EmitirCalificacionTest extends TestCase
{
    use RefreshDatabase;

    private function setup24(): array
    {
        $profesor  = User::factory()->profesor()->create();
        $alumno    = User::factory()->create();
        $comp      = Competencia::create(['nombre' => 'Test', 'descripcion' => 'X', 'escenario_id' => null]);
        $asig      = Asignatura::create(['codigo' => 'TST-001', 'nombre' => 'T', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        $esc       = Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'T', 'area_conocimiento' => 'T', 'descripcion_situacion' => 'X', 'estado' => 'publicado']);
        $sesion    = SesionSimulacion::create(['escenario_id' => $esc->id, 'alumno_id' => $alumno->id, 'estado' => 'finalizada', 'inicio_at' => now()->subHour(), 'finalizacion_at' => now()]);
        Mensaje::create(['sesion_simulacion_id' => $sesion->id, 'emisor' => 'alumno', 'contenido' => 'Hola', 'orden' => 1]);
        $resultado = Resultado::create(['sesion_simulacion_id' => $sesion->id, 'estado' => 'procesando', 'borrador_resumen' => 'Buen trabajo', 'borrador_calificacion' => 7.5, 'borrador_feedback' => 'Mejorar escucha activa', 'borrador_competencias' => [['competencia_id' => $comp->id, 'puntuacion' => 7]], 'borrador_mapa_descubrimiento' => []]);
        return [$profesor, $resultado, $comp];
    }

    private function payload(int $compId): array
    {
        return [
            'final_calificacion' => 8.0,
            'final_feedback'     => 'Excelente entrevista',
            'final_competencias' => [['competencia_id' => $compId, 'puntuacion' => 8.0, 'comentario' => 'Buena técnica']],
        ];
    }

    #[Test]
    public function profesor_carga_borrador_con_historial(): void
    {
        [$profesor, $resultado, ] = $this->setup24();

        $response = $this->actingAs($profesor, 'sanctum')
            ->getJson("/api/v1/resultados/{$resultado->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('resultado.borrador_calificacion', 7.5)
                 ->assertJsonPath('resultado.borrador_resumen', 'Buen trabajo')
                 ->assertJsonStructure(['resultado', 'sesion' => ['alumno', 'mensajes']]);
    }

    #[Test]
    public function profesor_publica_calificacion_correctamente(): void
    {
        [$profesor, $resultado, $comp] = $this->setup24();

        $response = $this->actingAs($profesor, 'sanctum')
            ->postJson("/api/v1/resultados/{$resultado->id}/publicar", $this->payload($comp->id));

        $response->assertStatus(200)
                 ->assertJsonPath('resultado.estado', 'evaluado')
                 ->assertJsonPath('resultado.final_calificacion', 8);

        $this->assertDatabaseHas('resultados', ['id' => $resultado->id, 'estado' => 'evaluado']);
        $this->assertDatabaseHas('sesiones_simulacion', ['id' => $resultado->sesion_simulacion_id, 'estado' => 'evaluada']);
    }

    #[Test]
    public function falla_si_ya_evaluado(): void
    {
        [$profesor, $resultado, $comp] = $this->setup24();
        $resultado->update(['estado' => 'evaluado', 'publicado_at' => now()]);

        $this->actingAs($profesor, 'sanctum')
             ->postJson("/api/v1/resultados/{$resultado->id}/publicar", $this->payload($comp->id))
             ->assertStatus(422);
    }

    #[Test]
    public function falla_si_no_es_titular(): void
    {
        [, $resultado, $comp] = $this->setup24();
        $otro = User::factory()->profesor()->create();

        $this->actingAs($otro, 'sanctum')
             ->postJson("/api/v1/resultados/{$resultado->id}/publicar", $this->payload($comp->id))
             ->assertStatus(403);
    }
}
