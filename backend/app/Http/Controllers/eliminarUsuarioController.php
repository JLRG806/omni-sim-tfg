<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-07 Eliminar Usuario
 *
 * Actor: admin
 * Route: DELETE /api/v1/usuarios/{id}  (auth:sanctum, role:admin)
 */
class eliminarUsuarioController extends Controller
{
    /**
     * Elimina (soft delete) el usuario indicado.
     * La confirmación previa la gestiona el frontend con ModalConfirmacionView.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $usuario = User::findOrFail($id);

        if ($usuario->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta.'], 422);
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente',
        ]);
    }
}
