<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-14 — Nav Dashboard Profesor.
 *
 * Cubre: asignaturas propias con stats + sin asignaturas + autorización.
 */
class CU14NavDashboardProfesorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function profesor_obtiene_sus_asignaturas_con_stats(): void
    {
        $profesor = User::factory()->profesor()->create(['name' => 'Dr. Test']);
        $alumno   = User::factory()->create();

        $asig = Asignatura::create([
            'codigo'      => 'TST-101',
            'nombre'      => 'Test Asignatura',
            'descripcion' => '',
            'profesor_id' => $profesor->id,
        ]);

        Matricula::create([
            'alumno_id'       => $alumno->id,
            'asignatura_id'   => $asig->id,
            'fecha_matricula' => now()->toDateString(),
        ]);

        $response = $this->actingAs($profesor, 'sanctum')
            ->getJson('/api/v1/profesor/dashboard');

        $response->assertStatus(200)
                 ->assertJsonPath('profesor.name', 'Dr. Test')
                 ->assertJsonCount(1, 'asignaturas')
                 ->assertJsonPath('asignaturas.0.codigo', 'TST-101')
                 ->assertJsonPath('asignaturas.0.stats.alumnos', 1)
                 ->assertJsonPath('asignaturas.0.stats.escenarios', 0)
                 ->assertJsonPath('asignaturas.0.stats.evaluaciones_pendientes', 0); // 0 hasta CU-29
    }

    #[Test]
    public function solo_devuelve_asignaturas_propias(): void
    {
        $profesor1 = User::factory()->profesor()->create();
        $profesor2 = User::factory()->profesor()->create();

        Asignatura::create(['codigo' => 'P1-001', 'nombre' => 'P1 Asig', 'descripcion' => '', 'profesor_id' => $profesor1->id]);
        Asignatura::create(['codigo' => 'P2-001', 'nombre' => 'P2 Asig', 'descripcion' => '', 'profesor_id' => $profesor2->id]);

        $response = $this->actingAs($profesor1, 'sanctum')
            ->getJson('/api/v1/profesor/dashboard');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'asignaturas')
                 ->assertJsonPath('asignaturas.0.codigo', 'P1-001');
    }

    #[Test]
    public function sin_asignaturas_devuelve_array_vacio(): void
    {
        $profesor = User::factory()->profesor()->create();

        $response = $this->actingAs($profesor, 'sanctum')
            ->getJson('/api/v1/profesor/dashboard');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'asignaturas');
    }

    #[Test]
    public function falla_si_no_es_profesor(): void
    {
        $alumno = User::factory()->create();

        $this->actingAs($alumno, 'sanctum')
             ->getJson('/api/v1/profesor/dashboard')
             ->assertStatus(403);
    }
}
