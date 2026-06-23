<?php

namespace App\Http\Controllers;

use App\Http\Requests\CrearAsignaturaRequest;
use App\Models\Asignatura;
use Illuminate\Http\JsonResponse;

/**
 * CU-10 Crear Asignatura
 *
 * Actor: admin
 * Route: POST /api/v1/asignaturas  (auth:sanctum, role:admin)
 */
class crearAsignaturaController extends Controller
{
    /**
     * Crea una nueva asignatura con su código único y profesor asignado.
     * Devuelve 201 con los datos de la asignatura creada.
     *
     * @param  \App\Http\Requests\CrearAsignaturaRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(CrearAsignaturaRequest $request): JsonResponse
    {
        $asignatura = Asignatura::create([
            'codigo'      => $request->codigo,
            'nombre'      => $request->nombre,
            'descripcion' => $request->descripcion ?? '',
            'profesor_id' => $request->profesor_id,
        ]);

        $asignatura->load('profesor:id,name');

        return response()->json([
            'message' => 'Asignatura creada correctamente',
            'data'    => [
                'id'          => $asignatura->id,
                'codigo'      => $asignatura->codigo,
                'nombre'      => $asignatura->nombre,
                'descripcion' => $asignatura->descripcion,
                'profesor'    => ['id' => $asignatura->profesor->id, 'name' => $asignatura->profesor->name],
            ],
        ], 201);
    }
}
