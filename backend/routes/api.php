<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Transversal — sin autenticación ──────────────────────────────────────
    // CU-01 Iniciar Sesión
    Route::post('/auth/login', \App\Http\Controllers\loginController::class);

    // CU-02 / CU-03 se añaden en el mismo módulo (Día 2)

    // ── Transversal — requiere autenticación ─────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Restauración de sesión: devuelve el usuario autenticado por su token.
        // Lo llama main.js al arrancar la SPA si hay token en localStorage.
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

        // CU-02 Cerrar Sesión — se añade en el mismo módulo (Día 2)
    });
});
