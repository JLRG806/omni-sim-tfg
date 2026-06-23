<?php

namespace App\Http\Controllers;

use App\Http\Requests\CrearUsuarioRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
/**
 * CU-05 Crear Usuario
 *
 * Actor: admin
 * Route: POST /api/v1/usuarios  (auth:sanctum, role:admin)
 */
class crearUsuarioController extends Controller
{
    /**
     * Crea un nuevo usuario en el sistema.
     * Devuelve 201 con los datos del usuario creado.
     *
     * @param  \App\Http\Requests\CrearUsuarioRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(CrearUsuarioRequest $request): JsonResponse
    {
        $usuario = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'rol'      => $request->rol,
            'estado'   => $request->estado ?? 'activo',
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'data'    => [
                'id'     => $usuario->id,
                'name'   => $usuario->name,
                'email'  => $usuario->email,
                'rol'    => $usuario->rol,
                'estado' => $usuario->estado,
            ],
        ], 201);
    }
}
