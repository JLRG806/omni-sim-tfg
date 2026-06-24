<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sesión de simulación realizada por un alumno sobre un escenario.
 *
 * Ciclo de vida del estado:
 *   en_curso → pausada (opcional) → procesando (CU-29 asíncrono) → finalizada → evaluada
 *
 * @property int         $id
 * @property int         $alumno_id
 * @property int         $escenario_id
 * @property string      $estado   en_curso|pausada|procesando|finalizada|evaluada
 * @property \Carbon\Carbon       $inicio_at
 * @property \Carbon\Carbon|null  $finalizacion_at
 * @property \Carbon\Carbon|null  $deleted_at
 * @property \Carbon\Carbon       $created_at
 * @property \Carbon\Carbon       $updated_at
 */
class SesionSimulacion extends Model
{
    /** La tabla no sigue la convención de pluralización de Laravel. */
    protected $table = 'sesiones_simulacion';

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'alumno_id',
        'escenario_id',
        'estado',
        'inicio_at',
        'finalizacion_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inicio_at'       => 'datetime',
            'finalizacion_at' => 'datetime',
        ];
    }

    /**
     * Alumno que realiza la sesión.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, SesionSimulacion>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alumno_id');
    }

    /**
     * Escenario sobre el que se realiza la sesión.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Escenario, SesionSimulacion>
     */
    public function escenario(): BelongsTo
    {
        return $this->belongsTo(Escenario::class);
    }

    /**
     * Mensajes intercambiados durante la sesión, ordenados por posición.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Mensaje>
     */
    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class)->orderBy('orden');
    }

    /**
     * Resultado de evaluación generado al finalizar la sesión (1:1).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<Resultado>
     */
    public function resultado(): HasOne
    {
        return $this->hasOne(Resultado::class);
    }
}
