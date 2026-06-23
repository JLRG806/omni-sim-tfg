<?php

namespace App\Http\Controllers;

use App\Http\Requests\CrearEscenarioFase1Request;
use App\Http\Requests\CrearEscenarioFase2Request;
use App\Models\CriterioEvaluacion;
use App\Models\Escenario;
use App\Models\PerfilAgente;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * CU-18 Crear Escenario (dos fases)
 *
 * Actor: profesor
 * Fase 1: POST /api/v1/escenarios              → crea escenario + objetivos
 * Fase 2: POST /api/v1/escenarios/{id}/perfil  → crea perfil agente + criterios
 */
class crearEscenarioController extends Controller
{
    /**
     * Instrucciones de prompt según nivel de dificultad.
     *
     * @var array<string, array<string>>
     */
    private const RESTRICCIONES_DIFICULTAD = [
        'facil'  => [
            'Sé cooperativo y comparte información con facilidad.',
            'Revela información latente con preguntas básicas.',
            'Muéstrate dispuesto a colaborar con el entrevistador.',
        ],
        'medio'  => [
            'Compórtate de forma natural y profesional.',
            'Solo revela información latente ante buenas preguntas de seguimiento.',
            'Sé selectivo con lo que compartes espontáneamente.',
        ],
        'dificil' => [
            'Sé evasivo y da respuestas vagas cuando sea posible.',
            'Revela información latente únicamente ante preguntas muy específicas y directas.',
            'Crea cierta resistencia natural al compartir información sensible.',
        ],
    ];

    /**
     * Fase 1 — Crea el escenario base y sus objetivos de aprendizaje.
     * El escenario queda en estado borrador hasta completar la fase 2.
     *
     * @param  \App\Http\Requests\CrearEscenarioFase1Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fase1(CrearEscenarioFase1Request $request): JsonResponse
    {
        $asignatura = \App\Models\Asignatura::findOrFail($request->asignatura_id);

        if ($asignatura->profesor_id !== $request->user()->id) {
            return response()->json(['message' => 'No tiene permisos para crear escenarios en esta asignatura.'], 403);
        }

        $escenario = DB::transaction(function () use ($request, $asignatura) {
            $escenario = Escenario::create([
                'asignatura_id'         => $asignatura->id,
                'profesor_id'           => $request->user()->id,
                'titulo'                => $request->titulo,
                'area_conocimiento'     => $request->area_conocimiento,
                'descripcion_situacion' => $request->descripcion_situacion,
                'estado'                => 'borrador',
            ]);

            foreach ($request->objetivos as $obj) {
                $escenario->objetivos()->create([
                    'contenido' => $obj['contenido'],
                    'orden'     => $obj['orden'],
                ]);
            }

            return $escenario;
        });

        return response()->json([
            'message'      => 'Escenario creado. Configura ahora el perfil del agente.',
            'escenario_id' => $escenario->id,
        ], 201);
    }

    /**
     * Fase 2 — Crea el perfil del agente y los criterios de evaluación.
     * Auto-genera las restricciones de prompt según el nivel de dificultad.
     *
     * @param  \App\Http\Requests\CrearEscenarioFase2Request  $request
     * @param  int  $id  ID del escenario creado en fase 1
     * @return \Illuminate\Http\JsonResponse
     */
    public function fase2(CrearEscenarioFase2Request $request, int $id): JsonResponse
    {
        $escenario = Escenario::with('perfilAgente')->findOrFail($id);

        if ($escenario->profesor_id !== $request->user()->id) {
            return response()->json(['message' => 'No tiene permisos para configurar este escenario.'], 403);
        }

        if ($escenario->perfilAgente) {
            return response()->json(['message' => 'Este escenario ya tiene un perfil configurado. Usa CU-19 para editarlo.'], 409);
        }

        DB::transaction(function () use ($request, $escenario) {
            $perfil = PerfilAgente::create([
                'escenario_id'         => $escenario->id,
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

            foreach ($request->criterios_evaluacion as $criterio) {
                CriterioEvaluacion::create([
                    'perfil_agente_id' => $perfil->id,
                    'competencia_id'   => $criterio['competencia_id'],
                    'contenido'        => $criterio['contenido'],
                ]);
            }
        });

        $escenario->load(['objetivos', 'perfilAgente.criterios.competencia']);

        return response()->json([
            'message'   => 'Perfil del agente configurado. El escenario está listo para publicar.',
            'escenario' => [
                'id'     => $escenario->id,
                'titulo' => $escenario->titulo,
                'estado' => $escenario->estado,
            ],
        ]);
    }
}
