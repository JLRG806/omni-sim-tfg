<?php

namespace Tests\Feature\CU;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-10 — Crear Asignatura.
 *
 * Cubre: creación exitosa + código duplicado + profesor inválido + autorización.
 */
class CU10CrearAsignaturaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_crea_asignatura_correctamente(): void
    {
        $admin    = User::factory()->admin()->create();
        $profesor = User::factory()->profesor()->create(['name' => 'Dr. García']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/asignaturas', [
                'codigo'      => 'PSI-101',
                'nombre'      => 'Psicología Clínica',
                'descripcion' => 'Introducción a la psicología clínica',
                'profesor_id' => $profesor->id,
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('message', 'Asignatura creada correctamente')
                 ->assertJsonPath('data.codigo', 'PSI-101')
                 ->assertJsonPath('data.profesor.name', 'Dr. García');

        $this->assertDatabaseHas('asignaturas', ['codigo' => 'PSI-101']);
    }

    #[Test]
    public function falla_con_codigo_duplicado(): void
    {
        $admin    = User::factory()->admin()->create();
        $profesor = User::factory()->profesor()->create();

        \App\Models\Asignatura::create(['codigo' => 'DUP-001', 'nombre' => 'Existente', 'descripcion' => '', 'profesor_id' => $profesor->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/asignaturas', [
                'codigo'      => 'DUP-001',
                'nombre'      => 'Nueva',
                'profesor_id' => $profesor->id,
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['codigo']);
    }

    #[Test]
    public function falla_si_profesor_id_no_es_profesor(): void
    {
        $admin  = User::factory()->admin()->create();
        $alumno = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/asignaturas', [
                'codigo'      => 'TST-001',
                'nombre'      => 'Test',
                'profesor_id' => $alumno->id,
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['profesor_id']);
    }

    #[Test]
    public function falla_sin_campos_requeridos(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
             ->postJson('/api/v1/asignaturas', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['codigo', 'nombre', 'profesor_id']);
    }

    #[Test]
    public function falla_si_no_es_admin(): void
    {
        $profesor = User::factory()->profesor()->create();

        $this->actingAs($profesor, 'sanctum')
             ->postJson('/api/v1/asignaturas', [])
             ->assertStatus(403);
    }
}
