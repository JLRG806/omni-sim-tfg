<?php

namespace App\Http\Controllers;

use App\Jobs\GenerarBorradorIAJob;
use App\Models\Resultado;
use App\Models\SesionSimulacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-29 Finalizar Sesión (ASÍNCRONO — devuelve 202)
 *
 * Actor: alumno
 * Route: PATCH /api/v1/sesiones/{id}/finalizar  (auth:sanctum, role:alumno)
 *
 * Flujo asíncrono:
 * 1. Cambia sesión a estado 'procesando' + registra finalizacion_at
 * 2. Crea o actualiza Resultado en estado 'pendiente'
 * 3. Encola GenerarBorradorIAJob (database driver)
 * 4. Devuelve HTTP 202 Accepted — el alumno NO espera el borrador
 *
 * El queue worker ejecuta el job en background (CU-29 puml: "Orquestador IA procesa en background").
 */
class finalizarSesionController extends Controller
{
    /**
     * Finaliza la sesión y encola la generación del borrador IA.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $sesion = SesionSimulacion::findOrFail($id);

        if ($sesion->alumno_id !== $request->user()->id) {
            return response()->json(['message' => 'No tienes permisos para finalizar esta sesión.'], 403);
        }

        if ($sesion->estado !== 'en_curso') {
            return response()->json(['message' => "No se puede finalizar una sesión en estado '{$sesion->estado}'."], 422);
        }

        $sesion->update([
            'estado'          => 'procesando',
            'finalizacion_at' => now(),
        ]);

        Resultado::firstOrCreate(
            ['sesion_simulacion_id' => $sesion->id],
            ['estado' => 'pendiente']
        );

        GenerarBorradorIAJob::dispatch($sesion->id);

        return response()->json([
            'message'   => 'Sesión finalizada. La evaluación se generará en breve.',
            'sesion_id' => $sesion->id,
            'estado'    => 'procesando',
        ], 202);
    }
}
