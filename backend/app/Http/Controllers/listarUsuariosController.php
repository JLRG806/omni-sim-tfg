<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-04 Listar Usuarios
 *
 * Actor: admin
 * Route: GET /api/v1/usuarios  (auth:sanctum, role:admin)
 */
class listarUsuariosController extends Controller
{
    /**
     * Devuelve la lista completa de usuarios registrados.
     * Campos: id, name, email, rol, estado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $usuarios = User::orderBy('name')
            ->get(['id', 'name', 'email', 'rol', 'estado']);

        return response()->json(['data' => $usuarios]);
    }
}
