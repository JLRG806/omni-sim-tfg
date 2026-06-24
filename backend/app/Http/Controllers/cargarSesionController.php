<?php

namespace App\Http\Controllers;

use App\Models\SesionSimulacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cargar Sesión Activa — WF-16 SimulacionChatView
 *
 * Actor: alumno
 * Route: GET /api/v1/sesiones/{id}  (auth:sanctum, role:alumno)
 *
 * Devuelve la sesión completa con escenario (descripción, objetivos, perfil)
 * y todos los mensajes para que SimulacionChatView pueda cargarse en frío
 * (refresh de página, deep link, retomar desde VistaAsignaturaAlumnoView).
 *
 * Regla de aislamiento: solo el alumno propietario puede acceder.
 */
class cargarSesionController extends Controller
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $sesion = SesionSimulacion::with([
            'mensajes',
            'escenario.objetivos',
            'escenario.perfilAgente:id,escenario_id,rol_identidad,nivel_dificultad,mensaje_bienvenida',
        ])->where('tipo', 'real')->findOrFail($id);

        if ($sesion->alumno_id !== $request->user()->id) {
            return response()->json(['message' => 'No tienes permisos para acceder a esta sesión.'], 403);
        }

        if (! in_array($sesion->estado, ['en_curso', 'pausada'])) {
            return response()->json(['message' => 'Esta sesión no está activa.'], 422);
        }

        return response()->json([
            'sesion' => [
                'id'         => $sesion->id,
                'estado'     => $sesion->estado,
                'inicio_at'  => $sesion->inicio_at->toISOString(),
                'pausado_at' => $sesion->pausado_at?->toISOString(),
                'escenario'  => [
                    'id'                    => $sesion->escenario->id,
                    'titulo'                => $sesion->escenario->titulo,
                    'descripcion_situacion' => $sesion->escenario->descripcion_situacion,
                    'objetivos'             => $sesion->escenario->objetivos->map(fn ($o) => [
                        'contenido' => $o->contenido,
                        'orden'     => $o->orden,
                    ])->values(),
                    'perfil' => $sesion->escenario->perfilAgente ? [
                        'rol_identidad'    => $sesion->escenario->perfilAgente->rol_identidad,
                        'nivel_dificultad' => $sesion->escenario->perfilAgente->nivel_dificultad,
                    ] : null,
                ],
                'mensajes' => $sesion->mensajes->map(fn ($m) => [
                    'id'        => $m->id,
                    'emisor'    => $m->emisor,
                    'contenido' => $m->contenido,
                    'orden'     => $m->orden,
                ])->values(),
            ],
        ]);
    }
}
