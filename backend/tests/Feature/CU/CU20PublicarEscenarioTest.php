<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Escenario;
use App\Models\PerfilAgente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-20 — Publicar Escenario.
 *
 * Cubre: publicar OK + sin perfil 422 + ya publicado 422 + no-titular 403.
 */
class CU20PublicarEscenarioTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(User $profesor, string $estado = 'borrador', bool $conPerfil = true): Escenario
    {
        $asig = Asignatura::create(['codigo' => 'TST-001', 'nombre' => 'Test', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        $esc  = Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'Test', 'area_conocimiento' => 'Test', 'descripcion_situacion' => 'X', 'estado' => $estado]);
        if ($conPerfil) {
            PerfilAgente::create(['escenario_id' => $esc->id, 'rol_identidad' => 'X', 'trasfondo' => 'X', 'conocimientos' => 'X', 'mensaje_bienvenida' => 'X', 'comportamiento' => 'X', 'tono_emocional' => 'formal', 'nivel_dificultad' => 'facil', 'informacion_explicita' => ['A'], 'informacion_latente' => ['B'], 'restricciones' => []]);
        }
        return $esc;
    }

    #[Test]
    public function profesor_publica_escenario_correctamente(): void
    {
        $profesor = User::factory()->profesor()->create();
        $esc      = $this->escenario($profesor);

        $response = $this->actingAs($profesor, 'sanctum')
            ->patchJson("/api/v1/escenarios/{$esc->id}/publicar");

        $response->assertStatus(200)
                 ->assertJsonPath('escenario.estado', 'publicado');

        $this->assertDatabaseHas('escenarios', ['id' => $esc->id, 'estado' => 'publicado']);
    }

    #[Test]
    public function falla_si_perfil_incompleto(): void
    {
        $profesor = User::factory()->profesor()->create();
        $esc      = $this->escenario($profesor, 'borrador', false);

        $this->actingAs($profesor, 'sanctum')
             ->patchJson("/api/v1/escenarios/{$esc->id}/publicar")
             ->assertStatus(422)
             ->assertJsonPath('message', 'El perfil del agente está incompleto. Configúralo antes de publicar.');
    }

    #[Test]
    public function falla_si_ya_publicado(): void
    {
        $profesor = User::factory()->profesor()->create();
        $esc      = $this->escenario($profesor, 'publicado');

        $this->actingAs($profesor, 'sanctum')
             ->patchJson("/api/v1/escenarios/{$esc->id}/publicar")
             ->assertStatus(422);
    }

    #[Test]
    public function falla_si_no_es_titular(): void
    {
        $p1 = User::factory()->profesor()->create();
        $p2 = User::factory()->profesor()->create();
        $esc = $this->escenario($p1);

        $this->actingAs($p2, 'sanctum')
             ->patchJson("/api/v1/escenarios/{$esc->id}/publicar")
             ->assertStatus(403);
    }
}
