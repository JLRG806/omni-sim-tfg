<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Competencia;
use App\Models\CriterioEvaluacion;
use App\Models\Escenario;
use App\Models\ObjetivoAprendizaje;
use App\Models\PerfilAgente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-19 — Editar Escenario (dos fases).
 *
 * Cubre: editar datos, editar perfil, no-borrador 422, no-titular 403.
 */
class CU19EditarEscenarioTest extends TestCase
{
    use RefreshDatabase;

    private function escenarioConPerfil(User $profesor): array
    {
        $asig = Asignatura::create(['codigo' => 'TST-001', 'nombre' => 'Test', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        $esc  = Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'Original', 'area_conocimiento' => 'Test', 'descripcion_situacion' => 'Desc', 'estado' => 'borrador']);
        ObjetivoAprendizaje::create(['escenario_id' => $esc->id, 'contenido' => 'Obj 1', 'orden' => 1]);
        $perfil = PerfilAgente::create(['escenario_id' => $esc->id, 'rol_identidad' => 'X', 'trasfondo' => 'X', 'conocimientos' => 'X', 'mensaje_bienvenida' => 'X', 'comportamiento' => 'X', 'tono_emocional' => 'formal', 'nivel_dificultad' => 'facil', 'informacion_explicita' => ['A'], 'informacion_latente' => ['B'], 'restricciones' => []]);
        $comp = Competencia::create(['nombre' => 'Test', 'descripcion' => 'X', 'escenario_id' => null]);
        CriterioEvaluacion::create(['perfil_agente_id' => $perfil->id, 'competencia_id' => $comp->id, 'contenido' => 'Old']);
        return [$esc, $comp];
    }

    #[Test]
    public function profesor_edita_datos_del_escenario(): void
    {
        $profesor = User::factory()->profesor()->create();
        [$esc, $comp] = $this->escenarioConPerfil($profesor);

        $response = $this->actingAs($profesor, 'sanctum')
            ->putJson("/api/v1/escenarios/{$esc->id}", [
                'titulo'                => 'Título Editado',
                'area_conocimiento'     => 'Área Editada',
                'descripcion_situacion' => 'Descripción editada',
                'objetivos'             => [['contenido' => 'Nuevo objetivo', 'orden' => 1]],
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('escenario_id', $esc->id);

        $this->assertDatabaseHas('escenarios', ['id' => $esc->id, 'titulo' => 'Título Editado']);
        $this->assertDatabaseMissing('objetivos_aprendizaje', ['contenido' => 'Obj 1', 'escenario_id' => $esc->id]);
        $this->assertDatabaseHas('objetivos_aprendizaje', ['contenido' => 'Nuevo objetivo']);
    }

    #[Test]
    public function profesor_edita_perfil_del_agente(): void
    {
        $profesor = User::factory()->profesor()->create();
        [$esc, $comp] = $this->escenarioConPerfil($profesor);

        $response = $this->actingAs($profesor, 'sanctum')
            ->putJson("/api/v1/escenarios/{$esc->id}/perfil", [
                'rol_identidad'        => 'Rol Editado',
                'trasfondo'            => 'Trasfondo',
                'conocimientos'        => 'Conocimientos',
                'mensaje_bienvenida'   => 'Hola editado',
                'comportamiento'       => 'Nervioso',
                'tono_emocional'       => 'amigable',
                'nivel_dificultad'     => 'dificil',
                'informacion_explicita' => ['Nueva info'],
                'informacion_latente'  => ['Latente nuevo'],
                'criterios_evaluacion' => [['competencia_id' => $comp->id, 'contenido' => 'Criterio nuevo']],
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('escenario.estado', 'borrador');

        $this->assertDatabaseHas('perfiles_agente', ['escenario_id' => $esc->id, 'rol_identidad' => 'Rol Editado']);
        $this->assertDatabaseMissing('criterios_evaluacion', ['contenido' => 'Old']);
        $this->assertDatabaseHas('criterios_evaluacion', ['contenido' => 'Criterio nuevo']);
    }

    #[Test]
    public function falla_si_escenario_publicado(): void
    {
        $profesor = User::factory()->profesor()->create();
        [$esc, ] = $this->escenarioConPerfil($profesor);
        $esc->update(['estado' => 'publicado']);

        $this->actingAs($profesor, 'sanctum')
             ->putJson("/api/v1/escenarios/{$esc->id}", ['titulo' => 'X', 'area_conocimiento' => 'X', 'descripcion_situacion' => 'X', 'objetivos' => [['contenido' => 'X', 'orden' => 1]]])
             ->assertStatus(422)
             ->assertJsonPath('message', 'Solo se pueden editar escenarios en borrador.');
    }

    #[Test]
    public function falla_si_no_es_titular(): void
    {
        $p1 = User::factory()->profesor()->create();
        $p2 = User::factory()->profesor()->create();
        [$esc, ] = $this->escenarioConPerfil($p1);

        $this->actingAs($p2, 'sanctum')
             ->putJson("/api/v1/escenarios/{$esc->id}", ['titulo' => 'X', 'area_conocimiento' => 'X', 'descripcion_situacion' => 'X', 'objetivos' => [['contenido' => 'X', 'orden' => 1]]])
             ->assertStatus(403);
    }
}
