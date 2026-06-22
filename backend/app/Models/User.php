<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo de usuario con STI (rol: admin, profesor, alumno).
 *
 * @property int         $id
 * @property string      $name
 * @property string      $email
 * @property string      $rol           admin|profesor|alumno
 * @property string      $estado        activo|inactivo
 * @property string|null $avatar_path
 * @property string      $password
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon       $created_at
 * @property \Carbon\Carbon       $updated_at
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'estado',
        'avatar_path',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ─── Scopes STI ──────────────────────────────────────────────────────────

    /**
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProfesores($query)
    {
        return $query->where('rol', 'profesor');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAlumnos($query)
    {
        return $query->where('rol', 'alumno');
    }

    // ─── Relaciones Profesor ──────────────────────────────────────────────────

    /**
     * Asignaturas coordinadas por este profesor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Asignatura>
     */
    public function asignaturas(): HasMany
    {
        return $this->hasMany(Asignatura::class, 'profesor_id');
    }

    /**
     * Escenarios diseñados por este profesor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Escenario>
     */
    public function escenariosCreados(): HasMany
    {
        return $this->hasMany(Escenario::class, 'profesor_id');
    }

    // ─── Relaciones Alumno ────────────────────────────────────────────────────

    /**
     * Matrículas del alumno en asignaturas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Matricula>
     */
    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'alumno_id');
    }

    /**
     * Sesiones de simulación realizadas por el alumno.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SesionSimulacion>
     */
    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionSimulacion::class, 'alumno_id');
    }
}
