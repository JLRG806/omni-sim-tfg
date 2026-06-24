<?php

namespace App\Http\Controllers;

use App\Models\Resultado;
use App\Models\SesionSimulacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-30 Consultar Resultados
 *
 * Actor: alumno
 * Route: GET /api/v1/resultados/{sesion_id}  (auth:sanctum, role:alumno)
 *
 * Devuelve el resultado de una sesión.
 * Dos caminos:
 *   - Si estado != evaluado O publicado_at IS NULL → solo devuelve el estado (sin datos)
 *   - Si estado == evaluado Y publicado_at IS NOT NULL → devuelve toda la calificación final
 *
 * Solo el alumno propietario de la sesión puede consultar su resultado.
 */
class consultarResultadosController extends Controller
{
    /**
     * Devuelve el resultado de la sesión indicada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $sesionId  ID de la SesionSimulacion
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $sesionId): JsonResponse
    {
        $sesion = SesionSimulacion::with([
            'escenario:id,titulo,area_conocimiento,asignatura_id',
            'escenario.asignatura:id,nombre,codigo',
            'escenario.perfilAgente:id,escenario_id,nivel_dificultad',
            'mensajes',
        ])->findOrFail($sesionId);

        if ($sesion->alumno_id !== $request->user()->id) {
            return response()->json(['message' => 'No tienes permisos para ver los resultados de esta sesión.'], 403);
        }

        $resultado = Resultado::where('sesion_simulacion_id', $sesionId)->first();

        if (! $resultado) {
            return response()->json([
                'sesion_id' => $sesionId,
                'estado'    => 'pendiente',
                'mensaje'   => 'La sesión aún no ha sido finalizada.',
            ]);
        }

        // Resultado pendiente o procesando — IA aún no ha terminado o el profesor no ha calificado
        if ($resultado->estado !== 'evaluado' || ! $resultado->publicado_at) {
            return response()->json([
                'sesion_id' => $sesionId,
                'estado'    => $resultado->estado,
                'mensaje'   => 'La calificación aún no está disponible.',
            ]);
        }

        // Resultado evaluado y publicado — devolver calificación completa con metadatos
        return response()->json([
            'sesion_id'       => $sesionId,
            'estado'          => 'evaluado',
            'finalizacion_at' => $sesion->finalizacion_at?->toISOString(),
            'num_mensajes'    => $sesion->mensajes->count(),
            'escenario' => [
                'id'               => $sesion->escenario->id,
                'titulo'           => $sesion->escenario->titulo,
                'area_conocimiento' => $sesion->escenario->area_conocimiento,
                'nivel_dificultad' => $sesion->escenario->perfilAgente?->nivel_dificultad,
                'asignatura'       => [
                    'nombre' => $sesion->escenario->asignatura?->nombre,
                    'codigo' => $sesion->escenario->asignatura?->codigo,
                ],
            ],
            'resultado' => [
                'final_calificacion'  => $resultado->final_calificacion,
                'final_feedback'      => $resultado->final_feedback,
                'final_competencias'  => $resultado->final_competencias,
                'mapa_descubrimiento' => $resultado->borrador_mapa_descubrimiento,
                'publicado_at'        => $resultado->publicado_at->toISOString(),
            ],
            'mensajes' => $sesion->mensajes->map(fn ($m) => [
                'id'       => $m->id,
                'emisor'   => $m->emisor,
                'contenido' => $m->contenido,
                'orden'    => $m->orden,
            ])->values(),
        ]);
    }
}
