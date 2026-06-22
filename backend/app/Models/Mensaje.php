<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mensaje intercambiado durante una sesión de simulación.
 * Los mensajes son inmutables: solo tienen created_at, sin updated_at.
 * El valor de created_at lo establece la base de datos (DEFAULT CURRENT_TIMESTAMP).
 *
 * @property int    $id
 * @property int    $sesion_simulacion_id
 * @property string $emisor   alumno|agente
 * @property string $contenido
 * @property int    $orden    Posición del mensaje en la conversación
 * @property \Carbon\Carbon $created_at
 */
class Mensaje extends Model
{
    /**
     * Sin updated_at — los mensajes no se modifican una vez enviados.
     * El created_at lo gestiona la BD mediante DEFAULT CURRENT_TIMESTAMP.
     */
    const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['sesion_simulacion_id', 'emisor', 'contenido', 'orden'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /**
     * Sesión de simulación a la que pertenece el mensaje.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<SesionSimulacion, Mensaje>
     */
    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionSimulacion::class, 'sesion_simulacion_id');
    }
}
