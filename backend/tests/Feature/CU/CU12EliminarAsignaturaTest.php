<?php

namespace Tests\Feature\CU;

use App\Models\Asignatura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-12 — Eliminar Asignatura.
 *
 * Cubre: soft delete + no aparece en listado + autorización + 404.
 */
class CU12EliminarAsignaturaTest extends TestCase
{
    use RefreshDatabase;

    private function crearAsignatura(User $profesor): Asignatura
    {
        return Asignatura::create([
            'codigo'      => 'TST-001',
            'nombre'      => 'Test',
            'descripcion' => '',
            'profesor_id' => $profesor->id,
        ]);
    }

    #[Test]
    public function admin_elimina_asignatura_correctamente(): void
    {
        $admin    = User::factory()->admin()->create();
        $profesor = User::factory()->profesor()->create();
        $asig     = $this->crearAsignatura($profesor);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/asignaturas/{$asig->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Asignatura eliminada correctamente');

        $this->assertSoftDeleted('asignaturas', ['id' => $asig->id]);
    }

    #[Test]
    public function asignatura_eliminada_no_aparece_en_listado(): void
    {
        $admin    = User::factory()->admin()->create();
        $profesor = User::factory()->profesor()->create();
        $asig     = $this->crearAsignatura($profesor);

        $this->actingAs($admin, 'sanctum')
             ->deleteJson("/api/v1/asignaturas/{$asig->id}");

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/asignaturas');

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($asig->id, $ids);
    }

    #[Test]
    public function falla_si_no_es_admin(): void
    {
        $profesor = User::factory()->profesor()->create();
        $asig     = $this->crearAsignatura($profesor);

        $this->actingAs($profesor, 'sanctum')
             ->deleteJson("/api/v1/asignaturas/{$asig->id}")
             ->assertStatus(403);
    }

    #[Test]
    public function devuelve_404_si_asignatura_no_existe(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
             ->deleteJson('/api/v1/asignaturas/9999')
             ->assertStatus(404);
    }
}
