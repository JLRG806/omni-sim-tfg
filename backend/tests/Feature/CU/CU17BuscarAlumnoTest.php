<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-17 — Buscar Alumno (incluido por CU-15).
 *
 * Cubre: búsqueda con campo matriculado + sin q + filtro nombre/email.
 */
class CU17BuscarAlumnoTest extends TestCase
{
    use RefreshDatabase;

    private function asignatura(User $profesor): Asignatura
    {
        return Asignatura::create(['codigo' => 'TST-001', 'nombre' => 'Test', 'descripcion' => '', 'profesor_id' => $profesor->id]);
    }

    #[Test]
    public function devuelve_alumnos_con_campo_matriculado(): void
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = $this->asignatura($profesor);
        $a1       = User::factory()->create(['name' => 'Ana García']);
        $a2       = User::factory()->create(['name' => 'Bea López']);

        Matricula::create(['alumno_id' => $a1->id, 'asignatura_id' => $asig->id, 'fecha_matricula' => now()->toDateString()]);

        $response = $this->actingAs($profesor, 'sanctum')
            ->getJson("/api/v1/asignaturas/{$asig->id}/alumnos");

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');

        $ana = collect($response->json('data'))->firstWhere('name', 'Ana García');
        $bea = collect($response->json('data'))->firstWhere('name', 'Bea López');

        $this->assertTrue($ana['matriculado']);
        $this->assertFalse($bea['matriculado']);
    }

    #[Test]
    public function filtra_por_nombre(): void
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = $this->asignatura($profesor);
        User::factory()->create(['name' => 'Carlos Ruiz']);
        User::factory()->create(['name' => 'Diana Sanz']);

        $response = $this->actingAs($profesor, 'sanctum')
            ->getJson("/api/v1/asignaturas/{$asig->id}/alumnos?q=carlos");

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.name', 'Carlos Ruiz');
    }

    #[Test]
    public function no_devuelve_profesores_ni_admins(): void
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = $this->asignatura($profesor);
        User::factory()->admin()->create(['name' => 'Admin Uno']);
        User::factory()->create(['name' => 'Alumno Uno']);

        $response = $this->actingAs($profesor, 'sanctum')
            ->getJson("/api/v1/asignaturas/{$asig->id}/alumnos");

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.name', 'Alumno Uno');
    }

    #[Test]
    public function falla_si_no_es_profesor(): void
    {
        $prof = User::factory()->profesor()->create();
        $asig = $this->asignatura($prof);
        $alu  = User::factory()->create();

        $this->actingAs($alu, 'sanctum')
             ->getJson("/api/v1/asignaturas/{$asig->id}/alumnos")
             ->assertStatus(403);
    }
}
