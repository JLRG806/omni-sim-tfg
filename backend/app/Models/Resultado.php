<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Resultado de evaluación de una sesión de simulación (1:1 con SesionSimulacion).
 *
 * Ciclo de vida:
 *   pendiente → procesando (job CU-29 en cola) → evaluado (profesor publica, CU-24)
 *
 * Campos borrador_*: generados por la IA (orquestador n8n/Ollama).
 * Campos final_*:    validados y ajustados por el profesor antes de publicar.
 *
 * @property int         $id
 * @property int         $sesion_simulacion_id
 * @property string      $estado              pendiente|procesando|evaluado
 * @property string|null $borrador_resumen
 * @property array|null  $borrador_mapa_descubrimiento
 * @property array|null  $borrador_competencias
 * @property float|null  $borrador_calificacion
 * @property string|null $borrador_feedback
 * @property \Carbon\Carbon|null $borrador_generado_at
 * @property float|null  $final_calificacion
 * @property string|null $final_feedback
 * @property array|null  $final_competencias
 * @property \Carbon\Carbon|null $publicado_at
 * @property \Carbon\Carbon       $created_at
 * @property \Carbon\Carbon       $updated_at
 */
class Resultado extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sesion_simulacion_id',
        'estado',
        'borrador_resumen',
        'borrador_mapa_descubrimiento',
        'borrador_competencias',
        'borrador_calificacion',
        'borrador_feedback',
        'borrador_generado_at',
        'final_calificacion',
        'final_feedback',
        'final_competencias',
        'publicado_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'borrador_mapa_descubrimiento' => 'array',
            'borrador_competencias'         => 'array',
            'final_competencias'            => 'array',
            'borrador_generado_at'          => 'datetime',
            'publicado_at'                  => 'datetime',
            'borrador_calificacion'         => 'float',
            'final_calificacion'            => 'float',
        ];
    }

    /**
     * Sesión de simulación a la que pertenece este resultado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<SesionSimulacion, Resultado>
     */
    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionSimulacion::class, 'sesion_simulacion_id');
    }
}
