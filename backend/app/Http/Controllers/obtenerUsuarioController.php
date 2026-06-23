<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Obtiene los datos de un usuario por ID.
 * Usado por UsuarioFormView para pre-rellenar el formulario de edición (CU-06).
 *
 * Actor: admin
 * Route: GET /api/v1/usuarios/{id}  (auth:sanctum, role:admin)
 */
class obtenerUsuarioController extends Controller
{
    /**
     * Devuelve los campos editables del usuario indicado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $u = User::findOrFail($id);

        return response()->json([
            'data' => [
                'id'     => $u->id,
                'name'   => $u->name,
                'email'  => $u->email,
                'rol'    => $u->rol,
                'estado' => $u->estado,
            ],
        ]);
    }
}
