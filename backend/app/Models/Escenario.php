<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Escenario de simulación diseñado por un profesor para una asignatura.
 * Estados: borrador (editable) → publicado (visible para alumnos).
 *
 * @property int         $id
 * @property int         $asignatura_id
 * @property int         $profesor_id
 * @property string      $titulo
 * @property string      $area_conocimiento
 * @property string      $descripcion_situacion
 * @property string      $estado              borrador|publicado
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon       $created_at
 * @property \Carbon\Carbon       $updated_at
 */
class Escenario extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'asignatura_id',
        'profesor_id',
        'titulo',
        'area_conocimiento',
        'descripcion_situacion',
        'estado',
    ];

    /**
     * Asignatura a la que pertenece el escenario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Asignatura, Escenario>
     */
    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }

    /**
     * Profesor que diseñó el escenario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Escenario>
     */
    public function profesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profesor_id');
    }

    /**
     * Perfil del agente IA asociado (1:1).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<PerfilAgente>
     */
    public function perfilAgente(): HasOne
    {
        return $this->hasOne(PerfilAgente::class);
    }

    /**
     * Objetivos de aprendizaje ordenados del escenario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<ObjetivoAprendizaje>
     */
    public function objetivos(): HasMany
    {
        return $this->hasMany(ObjetivoAprendizaje::class)->orderBy('orden');
    }

    /**
     * Competencias personalizadas añadidas por el profesor para este escenario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Competencia>
     */
    public function competenciasPersonalizadas(): HasMany
    {
        return $this->hasMany(Competencia::class);
    }

    /**
     * Sesiones de simulación que usan este escenario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SesionSimulacion>
     */
    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionSimulacion::class);
    }
}
