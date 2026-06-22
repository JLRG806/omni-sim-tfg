<?php

namespace Tests\Feature\CU;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-02 — Cerrar Sesión.
 *
 * Cubre: token invalidado tras logout + rechazo sin autenticación.
 */
class CU02LogoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function flujo_principal_invalida_el_token(): void
    {
        $user        = User::factory()->create();
        $plainToken  = $user->createToken('omnisim')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer {$plainToken}"])
                         ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Sesión cerrada correctamente');

        $this->assertSame(0, PersonalAccessToken::count());
    }

    #[Test]
    public function falla_sin_autenticacion(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }
}
