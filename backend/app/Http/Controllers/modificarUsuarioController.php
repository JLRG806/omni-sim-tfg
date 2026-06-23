<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModificarUsuarioRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * CU-06 Modificar Usuario
 *
 * Actor: admin
 * Route: PUT /api/v1/usuarios/{id}  (auth:sanctum, role:admin)
 */
class modificarUsuarioController extends Controller
{
    /**
     * Actualiza los datos de un usuario existente.
     * La contraseña solo se modifica si se envía en el request.
     *
     * @param  \App\Http\Requests\ModificarUsuarioRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ModificarUsuarioRequest $request, int $id): JsonResponse
    {
        $usuario = User::findOrFail($id);

        $datos = [
            'name'   => $request->name,
            'email'  => $request->email,
            'rol'    => $request->rol,
            'estado' => $request->estado,
        ];

        if ($request->filled('password')) {
            $datos['password'] = Hash::make($request->password);
        }

        $usuario->update($datos);

        return response()->json([
            'message' => 'Usuario modificado correctamente',
            'data'    => [
                'id'     => $usuario->id,
                'name'   => $usuario->name,
                'email'  => $usuario->email,
                'rol'    => $usuario->rol,
                'estado' => $usuario->estado,
            ],
        ]);
    }
}
