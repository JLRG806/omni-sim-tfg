<?php

namespace Database\Seeders;

use App\Models\Competencia;
use Illuminate\Database\Seeder;

/**
 * Siembra las 5 competencias universales fijas del sistema.
 * Estas competencias tienen escenario_id null y aplican a todos los escenarios.
 * Solo deben insertarse una vez; el seeder es idempotente (usa firstOrCreate).
 */
class CompetenciasUniversalesSeeder extends Seeder
{
    /**
     * @var array<int, array{nombre: string, descripcion: string}>
     */
    private const COMPETENCIAS = [
        [
            'nombre'      => 'Técnica de preguntas',
            'descripcion' => 'Capacidad para formular preguntas abiertas, cerradas y de seguimiento de forma estratégica, adaptando el tipo de pregunta al momento y objetivo de la entrevista.',
        ],
        [
            'nombre'      => 'Cobertura',
            'descripcion' => 'Grado en que el alumno identifica y aborda todos los temas clave del escenario sin dejar áreas relevantes sin explorar durante la entrevista.',
        ],
        [
            'nombre'      => 'Descubrimiento latente',
            'descripcion' => 'Habilidad para revelar información que el agente no comparte espontáneamente, mediante preguntas directas, gestión de silencios y manejo de respuestas evasivas.',
        ],
        [
            'nombre'      => 'Empatía',
            'descripcion' => 'Capacidad de establecer una relación de confianza, adaptar el tono de la conversación al estado emocional del agente y demostrar escucha activa.',
        ],
        [
            'nombre'      => 'Gestión del tiempo',
            'descripcion' => 'Eficiencia en el uso del tiempo disponible para la entrevista: cubre los objetivos sin divagaciones y prioriza la información más relevante.',
        ],
    ];

    /**
     * Inserta las competencias universales si no existen aún.
     */
    public function run(): void
    {
        foreach (self::COMPETENCIAS as $datos) {
            Competencia::firstOrCreate(
                ['nombre' => $datos['nombre'], 'tipo' => 'universal'],
                [
                    'descripcion'  => $datos['descripcion'],
                    'escenario_id' => null,
                ]
            );
        }

        $this->command->info('5 competencias universales sembradas correctamente.');
    }
}
