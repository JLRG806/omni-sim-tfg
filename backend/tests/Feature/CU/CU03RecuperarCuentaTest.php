<?php

namespace Tests\Feature\CU;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para CU-03 — Recuperar Cuenta.
 *
 * Cubre: envío de enlace + restablecimiento de contraseña + errores.
 */
class CU03RecuperarCuentaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function forgot_password_envia_enlace_a_correo_registrado(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'test@test.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@test.com',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Enlace de recuperación enviado. Revisa tu correo.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function forgot_password_falla_con_correo_no_registrado(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'noexiste@test.com',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function reset_password_actualiza_contrasena_con_token_valido(): void
    {
        $user  = User::factory()->create(['email' => 'test@test.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token'                 => $token,
            'email'                 => 'test@test.com',
            'password'              => 'NuevaPassword123',
            'password_confirmation' => 'NuevaPassword123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Contraseña actualizada correctamente.');
    }

    #[Test]
    public function reset_password_falla_con_token_invalido(): void
    {
        User::factory()->create(['email' => 'test@test.com']);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token'                 => 'token-invalido',
            'email'                 => 'test@test.com',
            'password'              => 'NuevaPassword123',
            'password_confirmation' => 'NuevaPassword123',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function reset_password_falla_si_contrasena_muy_corta(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token'                 => 'cualquier-token',
            'email'                 => 'test@test.com',
            'password'              => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }
}
