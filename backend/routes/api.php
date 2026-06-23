<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Transversal — sin autenticación ──────────────────────────────────────
    // CU-01 Iniciar Sesión
    Route::post('/auth/login', \App\Http\Controllers\loginController::class);

    // CU-03 Recuperar Cuenta
    Route::post('/auth/forgot-password', [\App\Http\Controllers\recuperarCuentaController::class, 'forgot']);
    Route::post('/auth/reset-password',  [\App\Http\Controllers\recuperarCuentaController::class, 'reset']);

    // ── Transversal — requiere autenticación ─────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Restauración de sesión: devuelve el usuario autenticado por su token.
        Route::get('/auth/me', function (Request $request) {
            $user = $request->user();
            return response()->json([
                'user' => [
                    'id'   => $user->id,
                    'name' => $user->name,
                    'rol'  => $user->rol,
                ],
            ]);
        });

        // CU-02 Cerrar Sesión
        Route::post('/auth/logout', \App\Http\Controllers\logoutController::class);
    });

    // ── Admin ─────────────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

        // CU-04 Listar Usuarios
        Route::get('/usuarios', \App\Http\Controllers\listarUsuariosController::class);

        // CU-05 Crear Usuario
        Route::post('/usuarios', \App\Http\Controllers\crearUsuarioController::class);
    });
});
