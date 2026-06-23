<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Criterio de evaluación que asocia un PerfilAgente con una Competencia.
 * El contenido describe cómo aplicar esa competencia en el contexto del escenario.
 *
 * @property int    $id
 * @property int    $perfil_agente_id
 * @property int    $competencia_id
 * @property string $contenido
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CriterioEvaluacion extends Model
{
    /** La tabla no sigue la convención de pluralización de Laravel. */
    protected $table = 'criterios_evaluacion';

    /**
     * @var list<string>
     */
    protected $fillable = ['perfil_agente_id', 'competencia_id', 'contenido'];

    /**
     * Perfil del agente al que pertenece este criterio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<PerfilAgente, CriterioEvaluacion>
     */
    public function perfilAgente(): BelongsTo
    {
        return $this->belongsTo(PerfilAgente::class);
    }

    /**
     * Competencia que evalúa este criterio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Competencia, CriterioEvaluacion>
     */
    public function competencia(): BelongsTo
    {
        return $this->belongsTo(Competencia::class);
    }
}
