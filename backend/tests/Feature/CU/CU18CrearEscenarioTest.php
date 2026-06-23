<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Competencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-18 — Crear Escenario (dos fases).
 *
 * Cubre: fase 1 crea escenario+objetivos, fase 2 crea perfil+criterios,
 * ownership checks, y escenario ya tiene perfil.
 */
class CU18CrearEscenarioTest extends TestCase
{
    use RefreshDatabase;

    private function asignatura(User $profesor): Asignatura
    {
        return Asignatura::create(['codigo' => 'TST-001', 'nombre' => 'Test', 'descripcion' => '', 'profesor_id' => $profesor->id]);
    }

    private function competencia(): Competencia
    {
        return Competencia::create(['nombre' => 'Técnica de preguntas', 'descripcion' => 'Test', 'escenario_id' => null]);
    }

    private function payloadFase1(int $asignaturaId): array
    {
        return [
            'asignatura_id'         => $asignaturaId,
            'titulo'                => 'Entrevista con paciente ansioso',
            'area_conocimiento'     => 'Psicología Clínica',
            'descripcion_situacion' => 'El paciente llega a consulta con signos de ansiedad.',
            'objetivos'             => [
                ['contenido' => 'Identificar síntomas de ansiedad', 'orden' => 1],
                ['contenido' => 'Aplicar técnicas de escucha activa', 'orden' => 2],
            ],
        ];
    }

    private function payloadFase2(int $competenciaId): array
    {
        return [
            'rol_identidad'         => 'Paciente adulto con trastorno de ansiedad generalizada',
            'trasfondo'             => 'Trabaja como contable. Sufre ansiedad desde hace 2 años.',
            'conocimientos'         => 'Conoce su diagnóstico pero no los tratamientos posibles.',
            'mensaje_bienvenida'    => 'Buenos días, tengo cita con usted.',
            'comportamiento'        => 'Habla rápido, interrumpe, gesticula con las manos.',
            'tono_emocional'        => 'serio',
            'nivel_dificultad'      => 'medio',
            'informacion_explicita' => ['Tiene ansiedad diagnosticada', 'No duerme bien'],
            'informacion_latente'   => ['Conflicto laboral no resuelto', 'Problemas familiares'],
            'criterios_evaluacion'  => [
                ['competencia_id' => $competenciaId, 'contenido' => 'Formula preguntas abiertas'],
            ],
        ];
    }

    #[Test]
    public function fase1_crea_escenario_y_objetivos(): void
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = $this->asignatura($profesor);

        $response = $this->actingAs($profesor, 'sanctum')
            ->postJson('/api/v1/escenarios', $this->payloadFase1($asig->id));

        $response->assertStatus(201)
                 ->assertJsonStructure(['message', 'escenario_id']);

        $this->assertDatabaseHas('escenarios', [
            'titulo'  => 'Entrevista con paciente ansioso',
            'estado'  => 'borrador',
        ]);
        $this->assertDatabaseCount('objetivos_aprendizaje', 2);
    }

    #[Test]
    public function fase2_crea_perfil_y_criterios(): void
    {
        $profesor  = User::factory()->profesor()->create();
        $asig      = $this->asignatura($profesor);
        $comp      = $this->competencia();

        $r1         = $this->actingAs($profesor, 'sanctum')->postJson('/api/v1/escenarios', $this->payloadFase1($asig->id));
        $escId      = $r1->json('escenario_id');

        $response = $this->actingAs($profesor, 'sanctum')
            ->postJson("/api/v1/escenarios/{$escId}/perfil", $this->payloadFase2($comp->id));

        $response->assertStatus(200)
                 ->assertJsonPath('escenario.estado', 'borrador');

        $this->assertDatabaseHas('perfiles_agente', ['escenario_id' => $escId, 'nivel_dificultad' => 'medio']);
        $this->assertDatabaseHas('criterios_evaluacion', ['contenido' => 'Formula preguntas abiertas']);
    }

    #[Test]
    public function fase2_falla_si_perfil_ya_existe(): void
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = $this->asignatura($profesor);
        $comp     = $this->competencia();

        $r1    = $this->actingAs($profesor, 'sanctum')->postJson('/api/v1/escenarios', $this->payloadFase1($asig->id));
        $escId = $r1->json('escenario_id');

        $this->actingAs($profesor, 'sanctum')->postJson("/api/v1/escenarios/{$escId}/perfil", $this->payloadFase2($comp->id));

        $response = $this->actingAs($profesor, 'sanctum')
            ->postJson("/api/v1/escenarios/{$escId}/perfil", $this->payloadFase2($comp->id));

        $response->assertStatus(409);
    }

    #[Test]
    public function falla_si_asignatura_de_otro_profesor(): void
    {
        $p1   = User::factory()->profesor()->create();
        $p2   = User::factory()->profesor()->create();
        $asig = $this->asignatura($p1);

        $this->actingAs($p2, 'sanctum')
             ->postJson('/api/v1/escenarios', $this->payloadFase1($asig->id))
             ->assertStatus(403);
    }

    #[Test]
    public function falla_si_no_es_profesor(): void
    {
        $alumno = User::factory()->create();
        $prof   = User::factory()->profesor()->create();
        $asig   = $this->asignatura($prof);

        $this->actingAs($alumno, 'sanctum')
             ->postJson('/api/v1/escenarios', $this->payloadFase1($asig->id))
             ->assertStatus(403);
    }
}
