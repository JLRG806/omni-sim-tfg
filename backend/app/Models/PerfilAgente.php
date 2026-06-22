<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Perfil del agente IA asociado 1:1 a un escenario.
 * Los campos JSON (informacion_explicita, informacion_latente, restricciones) se castean a array.
 * La lógica de dificultad se inyecta en el system prompt del orquestador IA vía restricciones.
 *
 * @property int         $id
 * @property int         $escenario_id
 * @property string      $rol_identidad
 * @property string      $trasfondo
 * @property string      $conocimientos
 * @property string      $mensaje_bienvenida
 * @property string      $comportamiento
 * @property string      $tono_emocional      formal|amigable|empatico|serio|distante
 * @property string      $nivel_dificultad    facil|medio|dificil
 * @property string|null $avatar_path
 * @property array       $informacion_explicita
 * @property array       $informacion_latente
 * @property array       $restricciones
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PerfilAgente extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'escenario_id',
        'rol_identidad',
        'trasfondo',
        'conocimientos',
        'mensaje_bienvenida',
        'comportamiento',
        'tono_emocional',
        'nivel_dificultad',
        'avatar_path',
        'informacion_explicita',
        'informacion_latente',
        'restricciones',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'informacion_explicita' => 'array',
            'informacion_latente'   => 'array',
            'restricciones'         => 'array',
        ];
    }

    /**
     * Escenario al que pertenece este perfil.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Escenario, PerfilAgente>
     */
    public function escenario(): BelongsTo
    {
        return $this->belongsTo(Escenario::class);
    }

    /**
     * Criterios de evaluación definidos para este perfil.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<CriterioEvaluacion>
     */
    public function criterios(): HasMany
    {
        return $this->hasMany(CriterioEvaluacion::class);
    }
}
