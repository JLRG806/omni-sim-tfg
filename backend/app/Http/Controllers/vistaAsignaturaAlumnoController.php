<?php

namespace App\Http\Controllers;

use App\Models\Asignatura;
use App\Models\Escenario;
use App\Models\Resultado;
use App\Models\SesionSimulacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vista Asignatura Alumno — WF-15
 *
 * Actor: alumno
 * Route: GET /api/v1/alumno/asignaturas/{id}  (auth:sanctum, role:alumno)
 *
 * Devuelve los escenarios publicados de la asignatura con datos del perfil
 * y las sesiones del alumno para mostrar los 3 tabs: Disponibles / En Curso / Completados.
 *
 * Verificación de matrícula: el alumno debe estar matriculado en la asignatura.
 */
class vistaAsignaturaAlumnoController extends Controller
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  ID de la asignatura
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $alumno = $request->user();

        $asignatura = Asignatura::with('profesor:id,name')->findOrFail($id);

        // Verificar matrícula
        $matriculado = $asignatura->matriculas()->where('alumno_id', $alumno->id)->exists();
        if (! $matriculado) {
            return response()->json(['message' => 'No estás matriculado en esta asignatura.'], 403);
        }

        $escenarios = Escenario::with([
            'perfilAgente:id,escenario_id,rol_identidad,nivel_dificultad',
            'objetivos:id,escenario_id',
        ])
            ->where('asignatura_id', $id)
            ->where('estado', 'publicado')
            ->orderBy('titulo')
            ->get();

        $resultado = [];

        foreach ($escenarios as $esc) {
            // Sesión activa del alumno (en_curso o pausada)
            $sesionActiva = SesionSimulacion::with('mensajes')
                ->where('escenario_id', $esc->id)
                ->where('alumno_id', $alumno->id)
                ->where('tipo', 'real')
                ->whereIn('estado', ['en_curso', 'pausada'])
                ->first();

            // Sesiones completadas (finalizada, procesando, evaluada)
            $sesionesCompletadas = SesionSimulacion::where('escenario_id', $esc->id)
                ->where('alumno_id', $alumno->id)
                ->where('tipo', 'real')
                ->whereIn('estado', ['finalizada', 'procesando', 'evaluada'])
                ->orderByDesc('finalizacion_at')
                ->get();

            // Resultado de cada sesión completada
            $completadasConResultado = $sesionesCompletadas->map(function ($s) {
                $res = Resultado::where('sesion_simulacion_id', $s->id)->first();
                return [
                    'id'             => $s->id,
                    'estado'         => $s->estado,
                    'finalizacion_at' => $s->finalizacion_at?->toISOString(),
                    'resultado'      => $res ? [
                        'id'                => $res->id,
                        'estado'            => $res->estado,
                        'final_calificacion' => $res->final_calificacion,
                        'publicado_at'      => $res->publicado_at?->toISOString(),
                    ] : null,
                ];
            });

            $resultado[] = [
                'id'           => $esc->id,
                'titulo'       => $esc->titulo,
                'perfil'       => $esc->perfilAgente ? [
                    'rol_identidad'    => $esc->perfilAgente->rol_identidad,
                    'nivel_dificultad' => $esc->perfilAgente->nivel_dificultad,
                ] : null,
                'num_objetivos' => $esc->objetivos->count(),
                'sesion_activa' => $sesionActiva ? [
                    'id'           => $sesionActiva->id,
                    'estado'       => $sesionActiva->estado,
                    'num_mensajes' => $sesionActiva->mensajes->count(),
                    'pausado_at'   => $sesionActiva->pausado_at?->toISOString(),
                ] : null,
                'sesiones_completadas' => $completadasConResultado->values(),
            ];
        }

        return response()->json([
            'asignatura' => [
                'id'          => $asignatura->id,
                'codigo'      => $asignatura->codigo,
                'nombre'      => $asignatura->nombre,
                'descripcion' => $asignatura->descripcion,
                'profesor'    => ['name' => $asignatura->profesor?->name ?? 'Profesor eliminado'],
            ],
            'escenarios' => $resultado,
        ]);
    }
}
