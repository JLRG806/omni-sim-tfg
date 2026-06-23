<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-16 — Desmatricular Alumno.
 *
 * Cubre: eliminación correcta (hard delete) + no aparece en listado + autorización + 404.
 */
class CU16DesmatricularAlumnoTest extends TestCase
{
    use RefreshDatabase;

    private function datos(): array
    {
        $profesor   = User::factory()->profesor()->create();
        $alumno     = User::factory()->create();
        $asignatura = Asignatura::create(['codigo' => 'TST-001', 'nombre' => 'Test', 'descripcion' => '', 'profesor_id' => $profesor->id]);
        $matricula  = Matricula::create(['alumno_id' => $alumno->id, 'asignatura_id' => $asignatura->id, 'fecha_matricula' => now()->toDateString()]);
        return [$profesor, $alumno, $asignatura, $matricula];
    }

    #[Test]
    public function profesor_desmatricula_alumno_correctamente(): void
    {
        [$profesor, $alumno, $asignatura, $matricula] = $this->datos();

        $response = $this->actingAs($profesor, 'sanctum')
            ->deleteJson("/api/v1/matriculas/{$matricula->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Alumno desmatriculado correctamente');

        $this->assertDatabaseMissing('matriculas', ['id' => $matricula->id]);
    }

    #[Test]
    public function alumno_desmatriculado_aparece_con_matriculado_false(): void
    {
        [$profesor, $alumno, $asignatura, $matricula] = $this->datos();

        $this->actingAs($profesor, 'sanctum')
             ->deleteJson("/api/v1/matriculas/{$matricula->id}");

        $response = $this->actingAs($profesor, 'sanctum')
            ->getJson("/api/v1/asignaturas/{$asignatura->id}/alumnos");

        $alumnoData = collect($response->json('data'))->firstWhere('id', $alumno->id);
        $this->assertFalse($alumnoData['matriculado']);
    }

    #[Test]
    public function falla_si_profesor_no_es_titular_de_la_asignatura(): void
    {
        [$profesor, $alumno, $asignatura, $matricula] = $this->datos();
        $otroProfesor = User::factory()->profesor()->create();

        $this->actingAs($otroProfesor, 'sanctum')
             ->deleteJson("/api/v1/matriculas/{$matricula->id}")
             ->assertStatus(403)
             ->assertJsonPath('message', 'No tiene permisos para desmatricular alumnos de esta asignatura.');
    }

    #[Test]
    public function falla_si_no_es_profesor(): void
    {
        [$profesor, $alumno, $asignatura, $matricula] = $this->datos();

        $this->actingAs($alumno, 'sanctum')
             ->deleteJson("/api/v1/matriculas/{$matricula->id}")
             ->assertStatus(403);
    }

    #[Test]
    public function devuelve_404_si_matricula_no_existe(): void
    {
        $profesor = User::factory()->profesor()->create();

        $this->actingAs($profesor, 'sanctum')
             ->deleteJson('/api/v1/matriculas/9999')
             ->assertStatus(404);
    }
}
