<?php

namespace App\Http\Controllers;

use App\Models\Asignatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-12 Eliminar Asignatura
 *
 * Actor: admin
 * Route: DELETE /api/v1/asignaturas/{id}  (auth:sanctum, role:admin)
 */
class eliminarAsignaturaController extends Controller
{
    /**
     * Elimina (soft delete) la asignatura indicada.
     * La confirmación previa la gestiona el frontend con ModalConfirmacion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $asignatura = Asignatura::findOrFail($id);
        $asignatura->delete();

        return response()->json([
            'message' => 'Asignatura eliminada correctamente',
        ]);
    }
}
