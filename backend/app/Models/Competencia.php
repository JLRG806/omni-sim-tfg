<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Competencia de evaluación. Tipo 'universal' (escenario_id null, 5 fijas globales)
 * o 'personalizada' (escenario_id presente, añadida por el profesor).
 *
 * Las 5 universales: Técnica de preguntas, Cobertura, Descubrimiento latente,
 * Empatía, Gestión del tiempo.
 *
 * @property int         $id
 * @property int|null    $escenario_id  null para universales
 * @property string      $nombre
 * @property string      $descripcion
 * @property string      $tipo          universal|personalizada
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Competencia extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['escenario_id', 'nombre', 'descripcion', 'tipo'];

    /**
     * Escenario al que pertenece la competencia personalizada (null si es universal).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Escenario, Competencia>
     */
    public function escenario(): BelongsTo
    {
        return $this->belongsTo(Escenario::class);
    }

    /**
     * Criterios de evaluación que referencian esta competencia.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<CriterioEvaluacion>
     */
    public function criterios(): HasMany
    {
        return $this->hasMany(CriterioEvaluacion::class);
    }
}
