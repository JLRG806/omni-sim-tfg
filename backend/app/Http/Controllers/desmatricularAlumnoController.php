<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-16 Desmatricular Alumno
 *
 * Actor: profesor
 * Route: DELETE /api/v1/matriculas/{id}  (auth:sanctum, role:profesor)
 *
 * Hard delete (matriculas NO tienen soft delete — CLAUDE.md).
 * La confirmación previa la gestiona el frontend con ModalConfirmacion.
 */
class desmatricularAlumnoController extends Controller
{
    /**
     * Elimina permanentemente la matrícula indicada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $matricula = Matricula::with('asignatura')->findOrFail($id);

        if ($matricula->asignatura->profesor_id !== $request->user()->id) {
            return response()->json(['message' => 'No tiene permisos para desmatricular alumnos de esta asignatura.'], 403);
        }

        $matricula->delete();

        return response()->json([
            'message' => 'Alumno desmatriculado correctamente',
        ]);
    }
}
