<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnviarMensajeRequest;
use App\Models\Mensaje;
use App\Models\SesionSimulacion;
use App\Services\OrquestadorIAInterface;
use Illuminate\Http\JsonResponse;

/**
 * CU-28 Enviar Mensaje (síncrono con IA)
 *
 * Actor: alumno
 * Route: POST /api/v1/sesiones/{id}/mensajes  (auth:sanctum, role:alumno)
 *
 * Flujo síncrono:
 * 1. Guarda el mensaje del alumno
 * 2. Llama al OrquestadorIA (n8n → Ollama) y espera la respuesta
 * 3. Guarda la respuesta del agente
 * 4. Devuelve ambos mensajes
 */
class enviarMensajeController extends Controller
{
    /**
     * @param  \App\Services\OrquestadorIAInterface  $orquestador
     */
    public function __construct(private readonly OrquestadorIAInterface $orquestador)
    {
    }

    /**
     * Procesa el turno de conversación: alumno envía → IA responde.
     *
     * @param  \App\Http\Requests\EnviarMensajeRequest  $request
     * @param  int  $id  ID de la SesionSimulacion
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(EnviarMensajeRequest $request, int $id): JsonResponse
    {
        $sesion = SesionSimulacion::with([
            'mensajes',
            'escenario.perfilAgente',
            'escenario.objetivos',
        ])->findOrFail($id);

        if ($sesion->alumno_id !== $request->user()->id) {
            return response()->json(['message' => 'No tienes permisos para enviar mensajes en esta sesión.'], 403);
        }

        if ($sesion->estado !== 'en_curso') {
            return response()->json(['message' => "No se puede enviar mensajes en una sesión con estado '{$sesion->estado}'."], 422);
        }

        $orden = $sesion->mensajes->count() + 1;

        // 1. Guardar mensaje del alumno
        $mensajeAlumno = Mensaje::create([
            'sesion_simulacion_id' => $sesion->id,
            'emisor'               => 'alumno',
            'contenido'            => $request->texto,
            'orden'                => $orden,
        ]);

        // 2. Llamar al orquestador IA (bloqueante)
        $respuestaTexto = $this->orquestador->solicitarRespuesta($sesion, $request->texto);

        // 3. Guardar respuesta del agente
        $mensajeAgente = Mensaje::create([
            'sesion_simulacion_id' => $sesion->id,
            'emisor'               => 'agente',
            'contenido'            => $respuestaTexto,
            'orden'                => $orden + 1,
        ]);

        return response()->json([
            'mensajes' => [
                [
                    'id'        => $mensajeAlumno->id,
                    'emisor'    => $mensajeAlumno->emisor,
                    'contenido' => $mensajeAlumno->contenido,
                    'orden'     => $mensajeAlumno->orden,
                ],
                [
                    'id'        => $mensajeAgente->id,
                    'emisor'    => $mensajeAgente->emisor,
                    'contenido' => $mensajeAgente->contenido,
                    'orden'     => $mensajeAgente->orden,
                ],
            ],
        ]);
    }
}
