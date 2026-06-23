<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-14 Nav Dashboard Profesor
 *
 * Actor: profesor
 * Route: GET /api/v1/profesor/dashboard  (auth:sanctum, role:profesor)
 */
class navDashboardProfesorController extends Controller
{
    /**
     * Devuelve las asignaturas del profesor autenticado con estadísticas.
     * Estadísticas: alumnos matriculados, escenarios totales,
     * evaluaciones pendientes de revisar.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $profesor = $request->user();

        $asignaturas = $profesor->asignaturas()
            ->withCount(['matriculas', 'escenarios'])
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'descripcion']);

        return response()->json([
            'profesor'    => ['id' => $profesor->id, 'name' => $profesor->name],
            'asignaturas' => $asignaturas->map(fn ($a) => [
                'id'          => $a->id,
                'codigo'      => $a->codigo,
                'nombre'      => $a->nombre,
                'descripcion' => $a->descripcion,
                'stats'       => [
                    'alumnos'                 => $a->matriculas_count,
                    'escenarios'              => $a->escenarios_count,
                    // Calculado en CU-29 cuando las sesiones estén implementadas
                    'evaluaciones_pendientes' => 0,
                ],
            ]),
        ]);
    }
}
