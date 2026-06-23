<?php

namespace App\Http\Controllers;

use App\Models\Asignatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-09 Listar Asignaturas
 *
 * Actor: admin
 * Route: GET /api/v1/asignaturas  (auth:sanctum, role:admin)
 */
class listarAsignaturasController extends Controller
{
    /**
     * Devuelve todas las asignaturas con su nombre, código y profesor asignado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $asignaturas = Asignatura::with(['profesor' => fn ($q) => $q->withTrashed()->select(['id', 'name', 'deleted_at'])])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo', 'descripcion', 'profesor_id']);

        return response()->json([
            'data' => $asignaturas->map(fn ($a) => [
                'id'          => $a->id,
                'nombre'      => $a->nombre,
                'codigo'      => $a->codigo,
                'descripcion' => $a->descripcion,
                'profesor'    => $a->profesor_id
                    ? [
                        'id'       => $a->profesor->id,
                        'name'     => $a->profesor->name,
                        'eliminado' => $a->profesor->deleted_at !== null,
                    ]
                    : null,
            ]),
        ]);
    }
}
