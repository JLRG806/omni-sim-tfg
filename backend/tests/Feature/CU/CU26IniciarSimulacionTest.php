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
 * Tests para CU-26 — Iniciar Simulación.
 *
 * Cubre: sesión creada + mensaje bienvenida + no matriculado + sesión activa + escenario no publicado.
 */
class CU26IniciarSimulacionTest extends TestCase
{
    use RefreshDatabase;

    private function escenarioPublicado(User $alumno): array
    {
        $profesor  = User::factory()->profesor()->create();
        $asig      = Asignatura::create(['codigo' => 'TST', 'nombre' => 'Test', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        Matricula::create(['alumno_id' => $alumno->id, 'asignatura_id' => $asig->id, 'fecha_matricula' => now()->toDateString()]);
        $esc       = Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'T', 'area_conocimiento' => 'T', 'descripcion_situacion' => 'X', 'estado' => 'publicado']);
        PerfilAgente::create(['escenario_id' => $esc->id, 'rol_identidad' => 'X', 'trasfondo' => 'X', 'conocimientos' => 'X', 'mensaje_bienvenida' => 'Hola, soy el paciente.', 'comportamiento' => 'X', 'tono_emocional' => 'formal', 'nivel_dificultad' => 'facil', 'informacion_explicita' => ['A'], 'informacion_latente' => ['B'], 'restricciones' => []]);
        return [$esc, $asig];
    }

    #[Test]
    public function alumno_inicia_simulacion_correctamente(): void
    {
        $alumno = User::factory()->create();
        [$esc, ] = $this->escenarioPublicado($alumno);

        $response = $this->actingAs($alumno, 'sanctum')
            ->postJson('/api/v1/sesiones', ['escenario_id' => $esc->id]);

        $response->assertStatus(201)
                 ->assertJsonPath('sesion.estado', 'en_curso')
                 ->assertJsonPath('sesion.mensajes.0.emisor', 'agente')
                 ->assertJsonPath('sesion.mensajes.0.contenido', 'Hola, soy el paciente.');

        $this->assertDatabaseHas('sesiones_simulacion', [
            'escenario_id' => $esc->id,
            'alumno_id'    => $alumno->id,
            'estado'       => 'en_curso',
        ]);
    }

    #[Test]
    public function falla_si_no_matriculado(): void
    {
        $alumno = User::factory()->create();
        $otro   = User::factory()->create();
        [$esc, ] = $this->escenarioPublicado($alumno);

        $this->actingAs($otro, 'sanctum')
             ->postJson('/api/v1/sesiones', ['escenario_id' => $esc->id])
             ->assertStatus(403);
    }

    #[Test]
    public function falla_si_escenario_en_borrador(): void
    {
        $alumno   = User::factory()->create();
        $profesor = User::factory()->profesor()->create();
        $asig     = Asignatura::create(['codigo' => 'B', 'nombre' => 'B', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        Matricula::create(['alumno_id' => $alumno->id, 'asignatura_id' => $asig->id, 'fecha_matricula' => now()->toDateString()]);
        $esc = Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'B', 'area_conocimiento' => 'B', 'descripcion_situacion' => 'X', 'estado' => 'borrador']);

        $this->actingAs($alumno, 'sanctum')
             ->postJson('/api/v1/sesiones', ['escenario_id' => $esc->id])
             ->assertStatus(422);
    }

    #[Test]
    public function falla_si_ya_hay_sesion_activa(): void
    {
        $alumno = User::factory()->create();
        [$esc, ] = $this->escenarioPublicado($alumno);

        SesionSimulacion::create(['escenario_id' => $esc->id, 'alumno_id' => $alumno->id, 'estado' => 'en_curso', 'inicio_at' => now()]);

        $this->actingAs($alumno, 'sanctum')
             ->postJson('/api/v1/sesiones', ['escenario_id' => $esc->id])
             ->assertStatus(409);
    }
}
