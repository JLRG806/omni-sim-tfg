<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Escenario;
use App\Models\Matricula;
use App\Models\PerfilAgente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-25 — Nav Dashboard Alumno.
 *
 * Cubre: asignaturas matriculadas con escenarios publicados + solo escenarios publicados + no-alumno 403.
 */
class CU25NavDashboardAlumnoTest extends TestCase
{
    use RefreshDatabase;

    private function setup25(): array
    {
        $profesor = User::factory()->profesor()->create();
        $alumno   = User::factory()->create();
        $asig     = Asignatura::create(['codigo' => 'TST-001', 'nombre' => 'Test', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        Matricula::create(['alumno_id' => $alumno->id, 'asignatura_id' => $asig->id, 'fecha_matricula' => now()->toDateString()]);
        $escPublicado = Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'Publicado', 'area_conocimiento' => 'T', 'descripcion_situacion' => 'X', 'estado' => 'publicado']);
        Escenario::create(['asignatura_id' => $asig->id, 'profesor_id' => $profesor->id, 'titulo' => 'Borrador', 'area_conocimiento' => 'T', 'descripcion_situacion' => 'X', 'estado' => 'borrador']);
        return [$alumno, $asig, $escPublicado];
    }

    #[Test]
    public function alumno_obtiene_asignaturas_con_escenarios_publicados(): void
    {
        [$alumno, $asig, $escPublicado] = $this->setup25();

        $response = $this->actingAs($alumno, 'sanctum')
            ->getJson('/api/v1/alumno/dashboard');

        $response->assertStatus(200)
                 ->assertJsonPath('alumno.name', $alumno->name)
                 ->assertJsonCount(1, 'asignaturas')
                 ->assertJsonPath('asignaturas.0.codigo', 'TST-001')
                 ->assertJsonCount(1, 'asignaturas.0.escenarios')
                 ->assertJsonPath('asignaturas.0.escenarios.0.titulo', 'Publicado');
    }

    #[Test]
    public function solo_devuelve_escenarios_publicados(): void
    {
        [$alumno, , ] = $this->setup25();

        $response = $this->actingAs($alumno, 'sanctum')
            ->getJson('/api/v1/alumno/dashboard');

        $titulos = collect($response->json('asignaturas.0.escenarios'))->pluck('titulo');
        $this->assertNotContains('Borrador', $titulos);
    }

    #[Test]
    public function sin_matriculas_devuelve_array_vacio(): void
    {
        $alumno = User::factory()->create();

        $response = $this->actingAs($alumno, 'sanctum')
            ->getJson('/api/v1/alumno/dashboard');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'asignaturas');
    }

    #[Test]
    public function falla_si_no_es_alumno(): void
    {
        $profesor = User::factory()->profesor()->create();

        $this->actingAs($profesor, 'sanctum')
             ->getJson('/api/v1/alumno/dashboard')
             ->assertStatus(403);
    }
}
