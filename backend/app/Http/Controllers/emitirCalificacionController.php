<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmitirCalificacionRequest;
use App\Models\Resultado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CU-24 Emitir Calificación  (incluye CU-23 Revisar Historial)
 *
 * Actor: profesor
 * GET:  GET  /api/v1/resultados/{id}          → carga borrador + mensajes (CU-23 incluido)
 * POST: POST /api/v1/resultados/{id}/publicar → publica calificación final
 */
class emitirCalificacionController extends Controller
{
    /**
     * Carga el borrador de calificación generado por la IA junto con el historial
     * de mensajes de la sesión (CU-23 incluido).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  ID del Resultado
     * @return \Illuminate\Http\JsonResponse
     */
    public function cargar(Request $request, int $id): JsonResponse
    {
        $resultado = Resultado::with([
            'sesion.mensajes',
            'sesion.alumno:id,name,email',
            'sesion.escenario:id,titulo,area_conocimiento,profesor_id',
        ])->findOrFail($id);

        $sesion = $resultado->sesion;

        if ($sesion->escenario->profesor_id !== $request->user()->id) {
            return response()->json(['message' => 'No tiene permisos para calificar esta sesión.'], 403);
        }

        if (in_array($resultado->estado, ['pendiente', 'procesando']) && ! $resultado->borrador_resumen) {
            return response()->json(['message' => 'La IA aún no ha generado el borrador. Inténtalo más tarde.'], 422);
        }

        return response()->json([
            'resultado' => [
                'id'                          => $resultado->id,
                'estado'                      => $resultado->estado,
                'borrador_resumen'            => $resultado->borrador_resumen,
                'borrador_mapa_descubrimiento' => $resultado->borrador_mapa_descubrimiento,
                'borrador_competencias'       => $resultado->borrador_competencias,
                'borrador_calificacion'       => $resultado->borrador_calificacion,
                'borrador_feedback'           => $resultado->borrador_feedback,
                'final_calificacion'          => $resultado->final_calificacion,
                'final_feedback'              => $resultado->final_feedback,
                'final_competencias'          => $resultado->final_competencias,
                'publicado_at'               => $resultado->publicado_at?->toISOString(),
            ],
            'sesion' => [
                'id'      => $sesion->id,
                'alumno'  => [
                    'id'   => $sesion->alumno?->id,
                    'name' => $sesion->alumno?->name ?? 'Alumno eliminado',
                ],
                'escenario' => ['id' => $sesion->escenario->id, 'titulo' => $sesion->escenario->titulo],
                'mensajes' => $sesion->mensajes->map(fn ($m) => [
                    'id'         => $m->id,
                    'emisor'     => $m->emisor,
                    'contenido'  => $m->contenido,
                    'created_at' => $m->created_at?->toISOString(),
                ])->values(),
            ],
        ]);
    }

    /**
     * Publica la calificación final del profesor sobre la sesión.
     * Actualiza Resultado(estado=evaluado, publicado_at) y SesionSimulacion(estado=evaluada).
     *
     * @param  \App\Http\Requests\EmitirCalificacionRequest  $request
     * @param  int  $id  ID del Resultado
     * @return \Illuminate\Http\JsonResponse
     */
    public function publicar(EmitirCalificacionRequest $request, int $id): JsonResponse
    {
        $resultado = Resultado::with('sesion.escenario')->findOrFail($id);
        $sesion    = $resultado->sesion;

        if ($sesion->escenario->profesor_id !== $request->user()->id) {
            return response()->json(['message' => 'No tiene permisos para calificar esta sesión.'], 403);
        }

        if ($resultado->estado === 'evaluado') {
            return response()->json(['message' => 'Esta calificación ya fue publicada.'], 422);
        }

        if ($resultado->estado === 'pendiente') {
            return response()->json(['message' => 'El borrador de la IA aún no está disponible.'], 422);
        }

        DB::transaction(function () use ($resultado, $sesion, $request) {
            $resultado->update([
                'final_calificacion' => $request->final_calificacion,
                'final_feedback'     => $request->final_feedback,
                'final_competencias' => $request->final_competencias,
                'estado'             => 'evaluado',
                'publicado_at'       => now(),
            ]);
            $sesion->update(['estado' => 'evaluada']);
        });

        return response()->json([
            'message'    => 'Calificación publicada correctamente.',
            'resultado'  => [
                'id'                 => $resultado->id,
                'estado'             => 'evaluado',
                'final_calificacion' => $resultado->final_calificacion,
                'publicado_at'      => $resultado->publicado_at->toISOString(),
            ],
        ]);
    }
}
