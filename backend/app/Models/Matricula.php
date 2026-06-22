<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de matrícula de un alumno en una asignatura.
 * Sin soft delete (decisión de diseño: las matrículas no se borran lógicamente).
 *
 * @property int         $id
 * @property int         $alumno_id
 * @property int         $asignatura_id
 * @property \Carbon\Carbon $fecha_matricula
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Matricula extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['alumno_id', 'asignatura_id', 'fecha_matricula'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['fecha_matricula' => 'date'];
    }

    /**
     * Alumno matriculado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Matricula>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alumno_id');
    }

    /**
     * Asignatura en la que está matriculado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Asignatura, Matricula>
     */
    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }
}
