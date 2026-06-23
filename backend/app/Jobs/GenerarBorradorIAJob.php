<?php

namespace App\Jobs;

use App\Models\Resultado;
use App\Models\SesionSimulacion;
use App\Services\OrquestadorIAInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Job asíncrono — CU-29 Finalizar Sesión.
 *
 * Se encola cuando el alumno finaliza la sesión.
 * Llama al OrquestadorIA para generar el borrador de evaluación
 * y lo persiste en el Resultado de la sesión.
 *
 * Driver de cola: database (sin Redis — decisión de diseño TFG).
 */
class GenerarBorradorIAJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  int  $sesionId  ID de la SesionSimulacion a evaluar
     */
    public function __construct(public readonly int $sesionId)
    {
    }

    /**
     * Genera el borrador de evaluación via OrquestadorIA y lo persiste.
     * El driver de cola 'database' garantiza que se ejecuta en background.
     *
     * @param  \App\Services\OrquestadorIAInterface  $orquestador
     */
    public function handle(OrquestadorIAInterface $orquestador): void
    {
        $sesion = SesionSimulacion::with([
            'mensajes',
            'escenario.perfilAgente',
            'escenario.objetivos',
        ])->findOrFail($this->sesionId);

        $borrador = $orquestador->generarBorrador($sesion);

        Resultado::updateOrCreate(
            ['sesion_simulacion_id' => $sesion->id],
            array_merge($borrador, [
                'estado'               => 'procesando',
                'borrador_generado_at' => now(),
            ])
        );

        $sesion->update(['estado' => 'finalizada']);
    }
}
