<?php

namespace App\Http\Controllers;

use App\Models\Asignatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-22 Buscar Escenario
 *
 * Actor: profesor
 * Route: GET /api/v1/asignaturas/{id}/escenarios[?q=término]  (auth:sanctum, role:profesor)
 *
 * Devuelve los escenarios de una asignatura, filtrados opcionalmente por título, área o estado.
 */
class buscarEscenarioController extends Controller
{
    /**
     * Lista los escenarios de la asignatura indicada.
     * El profesor debe ser titular de la asignatura.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  ID de la asignatura
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $asignatura = Asignatura::findOrFail($id);

        if ($asignatura->profesor_id !== $request->user()->id) {
            return response()->json(['message' => 'No tiene permisos para ver los escenarios de esta asignatura.'], 403);
        }

        $q = $request->query('q');

        $escenarios = $asignatura->escenarios()
            ->when($q, function ($query, $termino) {
                $query->where(function ($q2) use ($termino) {
                    $q2->where('titulo',            'ilike', "%{$termino}%")
                       ->orWhere('area_conocimiento', 'ilike', "%{$termino}%")
                       ->orWhere('estado',             'ilike', "%{$termino}%");
                });
            })
            ->orderBy('titulo')
            ->get(['id', 'titulo', 'area_conocimiento', 'estado', 'created_at']);

        return response()->json([
            'data' => $escenarios->map(fn ($e) => [
                'id'                => $e->id,
                'titulo'            => $e->titulo,
                'area_conocimiento' => $e->area_conocimiento,
                'estado'            => $e->estado,
                'created_at'        => $e->created_at?->toDateString(),
            ]),
        ]);
    }
}
