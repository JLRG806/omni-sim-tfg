<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-11 — Modificar Asignatura.
 *
 * Cubre: actualización exitosa + código duplicado + profesor inválido + autorización.
 */
class CU11ModificarAsignaturaTest extends TestCase
{
    use RefreshDatabase;

    private function crearAsignatura(User $profesor, string $codigo = 'TST-001'): Asignatura
    {
        return Asignatura::create([
            'codigo'      => $codigo,
            'nombre'      => 'Test',
            'descripcion' => '',
            'profesor_id' => $profesor->id,
        ]);
    }

    #[Test]
    public function admin_modifica_asignatura_correctamente(): void
    {
        $admin    = User::factory()->admin()->create();
        $profesor = User::factory()->profesor()->create(['name' => 'Dr. Nuevo']);
        $asig     = $this->crearAsignatura($profesor);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/asignaturas/{$asig->id}", [
                'codigo'      => 'TST-002',
                'nombre'      => 'Nombre Modificado',
                'descripcion' => 'Nueva descripcion',
                'profesor_id' => $profesor->id,
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Asignatura modificada correctamente')
                 ->assertJsonPath('data.codigo', 'TST-002')
                 ->assertJsonPath('data.nombre', 'Nombre Modificado');

        $this->assertDatabaseHas('asignaturas', ['id' => $asig->id, 'codigo' => 'TST-002']);
    }

    #[Test]
    public function puede_mantener_su_propio_codigo(): void
    {
        $admin    = User::factory()->admin()->create();
        $profesor = User::factory()->profesor()->create();
        $asig     = $this->crearAsignatura($profesor, 'MISMO-001');

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/asignaturas/{$asig->id}", [
                'codigo'      => 'MISMO-001',
                'nombre'      => 'Nombre distinto',
                'profesor_id' => $profesor->id,
            ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function falla_con_codigo_duplicado_de_otra_asignatura(): void
    {
        $admin    = User::factory()->admin()->create();
        $profesor = User::factory()->profesor()->create();
        $this->crearAsignatura($profesor, 'OTRO-001');
        $asig = $this->crearAsignatura($profesor, 'MIO-001');

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/asignaturas/{$asig->id}", [
                'codigo'      => 'OTRO-001',
                'nombre'      => 'Test',
                'profesor_id' => $profesor->id,
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['codigo']);
    }

    #[Test]
    public function falla_si_no_es_admin(): void
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = $this->crearAsignatura($profesor);

        $this->actingAs($profesor, 'sanctum')
             ->putJson("/api/v1/asignaturas/{$asig->id}", [])
             ->assertStatus(403);
    }

    #[Test]
    public function devuelve_404_si_asignatura_no_existe(): void
    {
        $admin = User::factory()->admin()->create();
        $prof  = User::factory()->profesor()->create();

        $this->actingAs($admin, 'sanctum')
             ->putJson('/api/v1/asignaturas/9999', [
                 'codigo' => 'X', 'nombre' => 'X', 'profesor_id' => $prof->id,
             ])
             ->assertStatus(404);
    }
}
