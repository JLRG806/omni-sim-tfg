<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-15 — Matricular Alumno.
 *
 * Cubre: matriculación OK + ya matriculado + no-alumno + autorización.
 */
class CU15MatricularAlumnoTest extends TestCase
{
    use RefreshDatabase;

    private function asignatura(User $profesor): Asignatura
    {
        return Asignatura::create(['codigo' => 'TST-001', 'nombre' => 'Test', 'descripcion' => '', 'profesor_id' => $profesor->id]);
    }

    #[Test]
    public function profesor_matricula_alumno_correctamente(): void
    {
        $profesor = User::factory()->profesor()->create();
        $alumno   = User::factory()->create();
        $asig     = $this->asignatura($profesor);

        $response = $this->actingAs($profesor, 'sanctum')
            ->postJson("/api/v1/asignaturas/{$asig->id}/matriculas", ['alumno_id' => $alumno->id]);

        $response->assertStatus(201)
                 ->assertJsonPath('message', 'Alumno matriculado correctamente')
                 ->assertJsonPath('matricula.alumno.name', $alumno->name);

        $this->assertDatabaseHas('matriculas', ['alumno_id' => $alumno->id, 'asignatura_id' => $asig->id]);
    }

    #[Test]
    public function falla_si_alumno_ya_matriculado(): void
    {
        $profesor = User::factory()->profesor()->create();
        $alumno   = User::factory()->create();
        $asig     = $this->asignatura($profesor);

        Matricula::create(['alumno_id' => $alumno->id, 'asignatura_id' => $asig->id, 'fecha_matricula' => now()->toDateString()]);

        $response = $this->actingAs($profesor, 'sanctum')
            ->postJson("/api/v1/asignaturas/{$asig->id}/matriculas", ['alumno_id' => $alumno->id]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['alumno_id']);
    }

    #[Test]
    public function falla_si_alumno_id_no_es_alumno(): void
    {
        $profesor = User::factory()->profesor()->create();
        $otro     = User::factory()->profesor()->create();
        $asig     = $this->asignatura($profesor);

        $this->actingAs($profesor, 'sanctum')
             ->postJson("/api/v1/asignaturas/{$asig->id}/matriculas", ['alumno_id' => $otro->id])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['alumno_id']);
    }

    #[Test]
    public function falla_si_no_es_profesor(): void
    {
        $alumno = User::factory()->create();
        $prof   = User::factory()->profesor()->create();
        $asig   = $this->asignatura($prof);

        $this->actingAs($alumno, 'sanctum')
             ->postJson("/api/v1/asignaturas/{$asig->id}/matriculas", ['alumno_id' => $alumno->id])
             ->assertStatus(403);
    }
}
