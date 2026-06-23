<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsaDificultadPrompt;
use App\Http\Requests\EditarEscenarioFase1Request;
use App\Http\Requests\EditarEscenarioFase2Request;
use App\Models\Escenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * CU-19 Editar Escenario (dos fases)
 *
 * Actor: profesor
 * Fase 1: PUT /api/v1/escenarios/{id}             → actualiza escenario + objetivos
 * Fase 2: PUT /api/v1/escenarios/{id}/perfil       → actualiza perfil agente + criterios
 *
 * Solo editable si estado=borrador.
 */
class editarEscenarioController extends Controller
{
    use UsaDificultadPrompt;

    /**
     * Fase 1 — Actualiza el escenario base y reemplaza sus objetivos de aprendizaje.
     * Solo editable si estado=borrador.
     *
     * @param  \App\Http\Requests\EditarEscenarioFase1Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function fase1(EditarEscenarioFase1Request $request, int $id): JsonResponse
    {
        $escenario = Escenario::findOrFail($id);

        if ($escenario->profesor_id !== $request->user()->id) {
            return response()->json(['message' => 'No tiene permisos para editar este escenario.'], 403);
        }

        if ($escenario->estado !== 'borrador') {
            return response()->json(['message' => 'Solo se pueden editar escenarios en borrador.'], 422);
        }

        DB::transaction(function () use ($request, $escenario) {
            $escenario->update([
                'titulo'                => $request->titulo,
                'area_conocimiento'     => $request->area_conocimiento,
                'descripcion_situacion' => $request->descripcion_situacion,
            ]);

            $escenario->objetivos()->delete();
            foreach ($request->objetivos as $obj) {
                $escenario->objetivos()->create([
                    'contenido' => $obj['contenido'],
                    'orden'     => $obj['orden'],
                ]);
            }
        });

        return response()->json([
            'message'      => 'Escenario actualizado correctamente.',
            'escenario_id' => $escenario->id,
        ]);
    }

    /**
     * Fase 2 — Actualiza el perfil del agente y reemplaza los criterios de evaluación.
     * Solo editable si estado=borrador.
     *
     * @param  \App\Http\Requests\EditarEscenarioFase2Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function fase2(EditarEscenarioFase2Request $request, int $id): JsonResponse
    {
        $escenario = Escenario::with('perfilAgente')->findOrFail($id);

        if ($escenario->profesor_id !== $request->user()->id) {
            return response()->json(['message' => 'No tiene permisos para editar este escenario.'], 403);
        }

        if ($escenario->estado !== 'borrador') {
            return response()->json(['message' => 'Solo se pueden editar escenarios en borrador.'], 422);
        }

        if (! $escenario->perfilAgente) {
            return response()->json(['message' => 'El escenario no tiene perfil. Usa CU-18 para crearlo primero.'], 404);
        }

        DB::transaction(function () use ($request, $escenario) {
            $escenario->perfilAgente->update([
                'rol_identidad'        => $request->rol_identidad,
                'trasfondo'            => $request->trasfondo,
                'conocimientos'        => $request->conocimientos,
                'mensaje_bienvenida'   => $request->mensaje_bienvenida,
                'comportamiento'       => $request->comportamiento,
                'tono_emocional'       => $request->tono_emocional,
                'nivel_dificultad'     => $request->nivel_dificultad,
                'informacion_explicita' => $request->informacion_explicita,
                'informacion_latente'  => $request->informacion_latente,
                'restricciones'        => self::RESTRICCIONES_DIFICULTAD[$request->nivel_dificultad] ?? [],
            ]);

            $escenario->perfilAgente->criterios()->delete();
            foreach ($request->criterios_evaluacion as $criterio) {
                $escenario->perfilAgente->criterios()->create([
                    'competencia_id' => $criterio['competencia_id'],
                    'contenido'      => $criterio['contenido'],
                ]);
            }
        });

        return response()->json([
            'message'   => 'Perfil del agente actualizado correctamente.',
            'escenario' => ['id' => $escenario->id, 'titulo' => $escenario->titulo, 'estado' => $escenario->estado],
        ]);
    }
}
