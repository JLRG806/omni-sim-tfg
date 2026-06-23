<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModificarAsignaturaRequest;
use App\Models\Asignatura;
use Illuminate\Http\JsonResponse;

/**
 * CU-11 Modificar Asignatura
 *
 * Actor: admin
 * Route: PUT /api/v1/asignaturas/{id}  (auth:sanctum, role:admin)
 */
class modificarAsignaturaController extends Controller
{
    /**
     * Actualiza los datos de una asignatura existente.
     *
     * @param  \App\Http\Requests\ModificarAsignaturaRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ModificarAsignaturaRequest $request, int $id): JsonResponse
    {
        $asignatura = Asignatura::findOrFail($id);

        $asignatura->update([
            'codigo'      => $request->codigo,
            'nombre'      => $request->nombre,
            'descripcion' => $request->descripcion ?? $asignatura->descripcion ?? '',
            'profesor_id' => $request->profesor_id,
        ]);

        $asignatura->load(['profesor' => fn ($q) => $q->withTrashed()->select(['id', 'name'])]);

        return response()->json([
            'message' => 'Asignatura modificada correctamente',
            'data'    => [
                'id'          => $asignatura->id,
                'codigo'      => $asignatura->codigo,
                'nombre'      => $asignatura->nombre,
                'descripcion' => $asignatura->descripcion,
                'profesor'    => $asignatura->profesor
                ? ['id' => $asignatura->profesor->id, 'name' => $asignatura->profesor->name]
                : null,
            ],
        ]);
    }
}
