<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Escenario;
use App\Models\Matricula;
use App\Models\Mensaje;
use App\Models\PerfilAgente;
use App\Models\SesionSimulacion;
use App\Models\User;
use App\Services\OrquestadorIAInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-28 — Enviar Mensaje (síncrono con IA).
 *
 * Usa mock del OrquestadorIA para no depender de n8n.
 * Cubre: turno OK + sesión no en_curso + no propietario + texto vacío.
 */
class CU28EnviarMensajeTest extends TestCase
{
    use RefreshDatabase;

    private function sesionEnCurso(User $alumno): SesionSimulacion
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = Asignatura::create(['codigo' => 'T', 'nombre' => 'T', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        Matricula::create(['alumno_id' => $alumno->id, 'asignatura_id' => $asig->id, 'fecha_matricula' => now()->toDateString()]);
        $esc  = Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'T', 'area_conocimiento' => 'T', 'descripcion_situacion' => 'X', 'estado' => 'publicado']);
        PerfilAgente::create(['escenario_id' => $esc->id, 'rol_identidad' => 'X', 'trasfondo' => 'X', 'conocimientos' => 'X', 'mensaje_bienvenida' => 'Hola', 'comportamiento' => 'X', 'tono_emocional' => 'formal', 'nivel_dificultad' => 'facil', 'informacion_explicita' => ['A'], 'informacion_latente' => ['B'], 'restricciones' => []]);
        $ses  = SesionSimulacion::create(['escenario_id' => $esc->id, 'alumno_id' => $alumno->id, 'estado' => 'en_curso', 'inicio_at' => now()]);
        Mensaje::create(['sesion_simulacion_id' => $ses->id, 'emisor' => 'agente', 'contenido' => 'Hola', 'orden' => 1]);
        return $ses;
    }

    #[Test]
    public function alumno_envia_mensaje_y_recibe_respuesta_ia(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesionEnCurso($alumno);

        $this->mock(OrquestadorIAInterface::class, function ($mock) {
            $mock->shouldReceive('solicitarRespuesta')
                 ->once()
                 ->andReturn('Respuesta de prueba del agente mock.');
        });

        $response = $this->actingAs($alumno, 'sanctum')
            ->postJson("/api/v1/sesiones/{$ses->id}/mensajes", ['texto' => '¿Cómo se encuentra usted?']);

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'mensajes')
                 ->assertJsonPath('mensajes.0.emisor', 'alumno')
                 ->assertJsonPath('mensajes.0.contenido', '¿Cómo se encuentra usted?')
                 ->assertJsonPath('mensajes.1.emisor', 'agente')
                 ->assertJsonPath('mensajes.1.contenido', 'Respuesta de prueba del agente mock.');

        $this->assertDatabaseCount('mensajes', 3); // 1 bienvenida + 1 alumno + 1 agente
    }

    #[Test]
    public function falla_si_sesion_no_en_curso(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesionEnCurso($alumno);
        $ses->update(['estado' => 'pausada']);

        $this->actingAs($alumno, 'sanctum')
             ->postJson("/api/v1/sesiones/{$ses->id}/mensajes", ['texto' => 'Hola'])
             ->assertStatus(422);
    }

    #[Test]
    public function falla_si_no_es_propietario(): void
    {
        $alumno = User::factory()->create();
        $otro   = User::factory()->create();
        $ses    = $this->sesionEnCurso($alumno);

        $this->actingAs($otro, 'sanctum')
             ->postJson("/api/v1/sesiones/{$ses->id}/mensajes", ['texto' => 'Hola'])
             ->assertStatus(403);
    }

    #[Test]
    public function falla_sin_texto(): void
    {
        $alumno = User::factory()->create();
        $ses    = $this->sesionEnCurso($alumno);

        $this->actingAs($alumno, 'sanctum')
             ->postJson("/api/v1/sesiones/{$ses->id}/mensajes", [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['texto']);
    }
}
