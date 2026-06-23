<?php

namespace App\Http\Controllers;

use App\Models\Escenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-20 Publicar Escenario
 *
 * Actor: profesor
 * Route: PATCH /api/v1/escenarios/{id}/publicar  (auth:sanctum, role:profesor)
 *
 * Cambia el estado de borrador a publicado.
 * Requiere que el PerfilAgente esté configurado.
 */
class publicarEscenarioController extends Controller
{
    /**
     * Publica el escenario haciéndolo visible para los alumnos matriculados.
     * Valida que el perfil del agente esté completo antes de publicar.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $escenario = Escenario::with('perfilAgente')->findOrFail($id);

        if ($escenario->profesor_id !== $request->user()->id) {
            return response()->json(['message' => 'No tiene permisos para publicar este escenario.'], 403);
        }

        if ($escenario->estado === 'publicado') {
            return response()->json(['message' => 'El escenario ya está publicado.'], 422);
        }

        $perfil = $escenario->perfilAgente;

        if (! $perfil) {
            return response()->json(['message' => 'El perfil del agente está incompleto. Configúralo antes de publicar.'], 422);
        }

        if (empty($perfil->rol_identidad) || empty($perfil->mensaje_bienvenida) || empty($perfil->informacion_explicita)) {
            return response()->json(['message' => 'El perfil del agente está incompleto. Configúralo antes de publicar.'], 422);
        }

        $escenario->update(['estado' => 'publicado']);

        return response()->json([
            'message'   => 'Escenario publicado correctamente.',
            'escenario' => ['id' => $escenario->id, 'titulo' => $escenario->titulo, 'estado' => 'publicado'],
        ]);
    }
}
