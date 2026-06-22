<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

/**
 * CU-03 Recuperar Cuenta
 *
 * Actor: público (sin autenticación)
 * Routes:
 *   POST /api/v1/auth/forgot-password  — envía enlace de recuperación
 *   POST /api/v1/auth/reset-password   — restablece la contraseña
 */
class recuperarCuentaController extends Controller
{
    /**
     * Envía el enlace de recuperación de contraseña al correo registrado.
     * En entorno de desarrollo el enlace se registra en el log (driver: log).
     *
     * @param  \App\Http\Requests\ForgotPasswordRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => 'Enlace de recuperación enviado. Revisa tu correo.',
        ]);
    }

    /**
     * Restablece la contraseña usando el token recibido por correo.
     *
     * @param  \App\Http\Requests\ResetPasswordRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => bcrypt($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }
}
