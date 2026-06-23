<?php

namespace App\Http\Controllers;

use App\Models\Escenario;
use App\Models\SesionSimulacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-23 Revisar Historial (incluido por CU-24)
 *
 * Actor: profesor
 * Route: GET /api/v1/sesiones?escenario_id=  (auth:sanctum, role:profesor)
 *
 * Devuelve las sesiones de un escenario con sus datos básicos.
 * El profesor debe ser titular del escenario.
 * Usado también por CU-24 para mostrar el historial junto al borrador de evaluación.
 */
class revisarHistorialController extends Controller
{
    /**
     * Lista las sesiones de simulación de un escenario.
     * Incluye alumno, estado, fechas y número de mensajes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $escenarioId = $request->query('escenario_id');

        if (! $escenarioId) {
            return response()->json(['message' => 'El parámetro escenario_id es obligatorio.'], 422);
        }

        $escenario = Escenario::findOrFail($escenarioId);

        if ($escenario->profesor_id !== $request->user()->id) {
            return response()->json(['message' => 'No tiene permisos para ver el historial de este escenario.'], 403);
        }

        $sesiones = SesionSimulacion::with(['alumno:id,name,email', 'mensajes'])
            ->where('escenario_id', $escenarioId)
            ->orderByDesc('inicio_at')
            ->get();

        return response()->json([
            'escenario' => [
                'id'     => $escenario->id,
                'titulo' => $escenario->titulo,
                'estado' => $escenario->estado,
            ],
            'sesiones' => $sesiones->map(fn ($s) => [
                'id'              => $s->id,
                'alumno'          => ['id' => $s->alumno->id, 'name' => $s->alumno->name, 'email' => $s->alumno->email],
                'estado'          => $s->estado,
                'inicio_at'       => $s->inicio_at?->toISOString(),
                'finalizacion_at' => $s->finalizacion_at?->toISOString(),
                'num_mensajes'    => $s->mensajes->count(),
            ]),
        ]);
    }
}
