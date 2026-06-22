<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Asignatura coordinada por un profesor. Contiene N escenarios y N matrículas de alumnos.
 *
 * @property int         $id
 * @property int         $profesor_id
 * @property string      $codigo       Código único de la asignatura
 * @property string      $nombre
 * @property string      $descripcion
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon       $created_at
 * @property \Carbon\Carbon       $updated_at
 */
class Asignatura extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = ['profesor_id', 'codigo', 'nombre', 'descripcion'];

    /**
     * Profesor que coordina la asignatura.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Asignatura>
     */
    public function profesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profesor_id');
    }

    /**
     * Matrículas de alumnos en esta asignatura.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Matricula>
     */
    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    /**
     * Escenarios de simulación pertenecientes a esta asignatura.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Escenario>
     */
    public function escenarios(): HasMany
    {
        return $this->hasMany(Escenario::class);
    }
}
