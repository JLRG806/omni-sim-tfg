<?php

namespace App\Http\Controllers;

use App\Models\Escenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Obtiene los datos completos de un escenario para el formulario de edición (CU-19).
 * Incluye objetivos y perfil del agente si existe.
 *
 * Actor: profesor
 * Route: GET /api/v1/escenarios/{id}  (auth:sanctum, role:profesor)
 */
class obtenerEscenarioController extends Controller
{
    /**
     * Devuelve el escenario con objetivos y perfil para pre-rellenar el formulario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $escenario = Escenario::with([
            'objetivos',
            'perfilAgente.criterios.competencia',
        ])->findOrFail($id);

        if ($escenario->profesor_id !== $request->user()->id) {
            return response()->json(['message' => 'No tiene permisos para ver este escenario.'], 403);
        }

        $perfil = $escenario->perfilAgente;

        return response()->json([
            'data' => [
                'id'                    => $escenario->id,
                'asignatura_id'         => $escenario->asignatura_id,
                'titulo'                => $escenario->titulo,
                'area_conocimiento'     => $escenario->area_conocimiento,
                'descripcion_situacion' => $escenario->descripcion_situacion,
                'estado'                => $escenario->estado,
                'objetivos'             => $escenario->objetivos->map(fn ($o) => [
                    'contenido' => $o->contenido,
                    'orden'     => $o->orden,
                ])->values(),
                'perfil'                => $perfil ? [
                    'rol_identidad'        => $perfil->rol_identidad,
                    'trasfondo'            => $perfil->trasfondo,
                    'conocimientos'        => $perfil->conocimientos,
                    'mensaje_bienvenida'   => $perfil->mensaje_bienvenida,
                    'comportamiento'       => $perfil->comportamiento,
                    'tono_emocional'       => $perfil->tono_emocional,
                    'nivel_dificultad'     => $perfil->nivel_dificultad,
                    'informacion_explicita' => $perfil->informacion_explicita,
                    'informacion_latente'  => $perfil->informacion_latente,
                    'criterios_evaluacion' => $perfil->criterios->map(fn ($c) => [
                        'competencia_id' => $c->competencia_id,
                        'contenido'      => $c->contenido,
                    ])->values(),
                ] : null,
            ],
        ]);
    }
}
