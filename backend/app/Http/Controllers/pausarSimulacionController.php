<?php

namespace App\Http\Controllers;

use App\Models\SesionSimulacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-31 Pausar Simulación
 *
 * Actor: alumno
 * Route: PATCH /api/v1/sesiones/{id}/pausar  (auth:sanctum, role:alumno)
 *
 * Pausa una sesión en_curso. El tiempo sigue corriendo (no hay pausado_at).
 * El alumno puede retomar la sesión más tarde via CU-27.
 * Si el alumno cierra el navegador sin pausar, la sesión queda en_curso
 * y también puede retomarse via CU-27.
 */
class pausarSimulacionController extends Controller
{
    /**
     * Pausa la sesión activa del alumno.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $sesion = SesionSimulacion::findOrFail($id);

        if ($sesion->alumno_id !== $request->user()->id) {
            return response()->json(['message' => 'No tienes permisos para pausar esta sesión.'], 403);
        }

        if ($sesion->estado !== 'en_curso') {
            return response()->json(['message' => "No se puede pausar una sesión en estado '{$sesion->estado}'."], 422);
        }

        $sesion->update(['estado' => 'pausada']);

        return response()->json([
            'message'   => 'Sesión pausada correctamente. Puedes retomar cuando quieras.',
            'sesion_id' => $sesion->id,
            'estado'    => 'pausada',
        ]);
    }
}
