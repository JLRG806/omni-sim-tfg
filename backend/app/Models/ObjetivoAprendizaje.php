<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Objetivo de aprendizaje de un escenario. Se almacenan ordenados por la columna `orden`.
 *
 * @property int    $id
 * @property int    $escenario_id
 * @property string $contenido
 * @property int    $orden
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ObjetivoAprendizaje extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['escenario_id', 'contenido', 'orden'];

    /**
     * Escenario al que pertenece este objetivo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Escenario, ObjetivoAprendizaje>
     */
    public function escenario(): BelongsTo
    {
        return $this->belongsTo(Escenario::class);
    }
}
