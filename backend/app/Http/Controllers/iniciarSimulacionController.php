<?php

namespace App\Http\Controllers;

use App\Http\Requests\IniciarSimulacionRequest;
use App\Models\Escenario;
use App\Models\Mensaje;
use App\Models\SesionSimulacion;
use Illuminate\Http\JsonResponse;

/**
 * CU-26 Iniciar Simulación
 *
 * Actor: alumno
 * Route: POST /api/v1/sesiones  (auth:sanctum, role:alumno)
 *
 * Valida que el escenario esté publicado y el alumno matriculado.
 * Crea la sesión en estado en_curso y envía el mensaje de bienvenida del agente.
 */
class iniciarSimulacionController extends Controller
{
    /**
     * Crea una nueva sesión de simulación e inyecta el mensaje de bienvenida.
     *
     * @param  \App\Http\Requests\IniciarSimulacionRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(IniciarSimulacionRequest $request): JsonResponse
    {
        $alumno    = $request->user();
        $escenario = Escenario::with(['perfilAgente', 'asignatura'])->findOrFail($request->escenario_id);

        // Validar que el alumno está matriculado en la asignatura del escenario
        $matriculado = $escenario->asignatura->matriculas()
            ->where('alumno_id', $alumno->id)
            ->exists();

        if (! $matriculado) {
            return response()->json(['message' => 'No estás matriculado en la asignatura de este escenario.'], 403);
        }

        // No permitir iniciar si ya hay una sesión real en curso o pausada
        $sesionActiva = SesionSimulacion::where('escenario_id', $escenario->id)
            ->where('alumno_id', $alumno->id)
            ->where('tipo', 'real')
            ->whereIn('estado', ['en_curso', 'pausada'])
            ->first();

        if ($sesionActiva) {
            return response()->json([
                'message'   => 'Ya tienes una sesión activa en este escenario. Usa Retomar.',
                'sesion_id' => $sesionActiva->id,
            ], 409);
        }

        $sesion = SesionSimulacion::create([
            'escenario_id' => $escenario->id,
            'alumno_id'    => $alumno->id,
            'estado'       => 'en_curso',
            'tipo'         => 'real',
            'inicio_at'    => now(),
        ]);

        // Mensaje de bienvenida del agente (orden 1)
        Mensaje::create([
            'sesion_simulacion_id' => $sesion->id,
            'emisor'               => 'agente',
            'contenido'            => $escenario->perfilAgente->mensaje_bienvenida,
            'orden'                => 1,
        ]);

        $sesion->load('mensajes');

        return response()->json([
            'message' => 'Simulación iniciada correctamente.',
            'sesion'  => [
                'id'         => $sesion->id,
                'estado'     => $sesion->estado,
                'inicio_at'  => $sesion->inicio_at->toISOString(),
                'escenario'  => [
                    'id'     => $escenario->id,
                    'titulo' => $escenario->titulo,
                ],
                'mensajes'   => $sesion->mensajes->map(fn ($m) => [
                    'id'        => $m->id,
                    'emisor'    => $m->emisor,
                    'contenido' => $m->contenido,
                    'orden'     => $m->orden,
                ])->values(),
            ],
        ], 201);
    }
}
