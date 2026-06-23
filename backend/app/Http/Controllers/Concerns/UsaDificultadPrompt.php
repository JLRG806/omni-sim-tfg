<?php

namespace App\Http\Controllers\Concerns;

/**
 * Provee las instrucciones de prompt según el nivel de dificultad del escenario.
 * Compartido entre crearEscenarioController (CU-18) y editarEscenarioController (CU-19).
 */
trait UsaDificultadPrompt
{
    /**
     * Instrucciones de comportamiento del agente según dificultad.
     * Se inyectan en el campo `restricciones` (JSON) del PerfilAgente
     * para que el orquestador IA las incluya en el system prompt.
     *
     * @var array<string, array<string>>
     */
    private const RESTRICCIONES_DIFICULTAD = [
        'facil'  => [
            'Sé cooperativo y comparte información con facilidad.',
            'Revela información latente con preguntas básicas.',
            'Muéstrate dispuesto a colaborar con el entrevistador.',
        ],
        'medio'  => [
            'Compórtate de forma natural y profesional.',
            'Solo revela información latente ante buenas preguntas de seguimiento.',
            'Sé selectivo con lo que compartes espontáneamente.',
        ],
        'dificil' => [
            'Sé evasivo y da respuestas vagas cuando sea posible.',
            'Revela información latente únicamente ante preguntas muy específicas y directas.',
            'Crea cierta resistencia natural al compartir información sensible.',
        ],
    ];
}
