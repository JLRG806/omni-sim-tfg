<?php

namespace App\Http\Controllers;

use App\Models\Escenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-21 Despublicar Escenario
 *
 * Actor: profesor
 * Route: PATCH /api/v1/escenarios/{id}/despublicar  (auth:sanctum, role:profesor)
 *
 * Revierte el escenario de publicado a borrador.
 * El spec indica que pide confirmación (modal en frontend).
 */
class despublicarEscenarioController extends Controller
{
    /**
     * Despublica el escenario, volviéndolo a estado borrador.
     * Las sesiones en curso NO se interrumpen (work future).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $escenario = Escenario::findOrFail($id);

        if ($escenario->profesor_id !== $request->user()->id) {
            return response()->json(['message' => 'No tiene permisos para despublicar este escenario.'], 403);
        }

        if ($escenario->estado === 'borrador') {
            return response()->json(['message' => 'El escenario ya está en borrador.'], 422);
        }

        // Solo las sesiones reales bloquean el despublicado — las de prueba no
        $sesionesActivas = $escenario->sesiones()
            ->where('tipo', 'real')
            ->whereIn('estado', ['en_curso', 'pausada'])
            ->count();

        if ($sesionesActivas > 0) {
            return response()->json([
                'message' => "No se puede despublicar: hay {$sesionesActivas} sesión(es) activa(s). Espera a que finalicen.",
            ], 422);
        }

        $escenario->update(['estado' => 'borrador']);

        return response()->json([
            'message'   => 'Escenario despublicado correctamente.',
            'escenario' => ['id' => $escenario->id, 'titulo' => $escenario->titulo, 'estado' => 'borrador'],
        ]);
    }
}
