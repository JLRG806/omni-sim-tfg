<?php

namespace App\Http\Controllers;

use App\Models\SesionSimulacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-27 Retomar Simulación
 *
 * Actor: alumno
 * Route: PATCH /api/v1/sesiones/{id}/retomar  (auth:sanctum, role:alumno)
 *
 * Restaura una sesión pausada o en_curso.
 * Si estaba pausada, la pone en_curso.
 * Devuelve la sesión completa con todo el historial de mensajes.
 */
class retomarSimulacionController extends Controller
{
    /**
     * Retoma la sesión indicada y devuelve el historial completo de mensajes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $sesion = SesionSimulacion::with(['mensajes', 'escenario:id,titulo,area_conocimiento'])
            ->findOrFail($id);

        if ($sesion->alumno_id !== $request->user()->id) {
            return response()->json(['message' => 'No tienes permisos para retomar esta sesión.'], 403);
        }

        if (! in_array($sesion->estado, ['en_curso', 'pausada'])) {
            return response()->json([
                'message' => "No se puede retomar una sesión en estado '{$sesion->estado}'.",
            ], 422);
        }

        if ($sesion->estado === 'pausada') {
            $sesion->update(['estado' => 'en_curso']);
        }

        return response()->json([
            'message' => 'Sesión retomada correctamente.',
            'sesion'  => [
                'id'        => $sesion->id,
                'estado'    => $sesion->estado,
                'inicio_at' => $sesion->inicio_at->toISOString(),
                'escenario' => [
                    'id'     => $sesion->escenario->id,
                    'titulo' => $sesion->escenario->titulo,
                ],
                'mensajes'  => $sesion->mensajes->map(fn ($m) => [
                    'id'        => $m->id,
                    'emisor'    => $m->emisor,
                    'contenido' => $m->contenido,
                    'orden'     => $m->orden,
                ])->values(),
            ],
        ]);
    }
}
