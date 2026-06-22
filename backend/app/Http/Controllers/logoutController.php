<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-02 Cerrar Sesión
 *
 * Actor: cualquier usuario autenticado
 * Route: POST /api/v1/auth/logout  (auth:sanctum)
 */
class logoutController extends Controller
{
    /**
     * Invalida el token de acceso actual del usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }
}
