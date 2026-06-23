<?php

namespace App\Http\Controllers;

use App\Models\Asignatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Obtiene los datos de una asignatura por ID.
 * Usado por AsignaturaFormView para pre-rellenar el formulario de edición (CU-11).
 *
 * Actor: admin
 * Route: GET /api/v1/asignaturas/{id}  (auth:sanctum, role:admin)
 */
class obtenerAsignaturaController extends Controller
{
    /**
     * Devuelve los campos editables de la asignatura indicada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $a = Asignatura::with(['profesor' => fn ($q) => $q->withTrashed()->select(['id', 'name', 'deleted_at'])])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id'          => $a->id,
                'codigo'      => $a->codigo,
                'nombre'      => $a->nombre,
                'descripcion' => $a->descripcion,
                'profesor'    => $a->profesor_id
                    ? ['id' => $a->profesor->id, 'name' => $a->profesor->name, 'eliminado' => $a->profesor->deleted_at !== null]
                    : null,
            ],
        ]);
    }
}
