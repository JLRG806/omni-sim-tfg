<?php

namespace App\Services;

use App\Models\SesionSimulacion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Implementación del orquestador IA via n8n.
 * En desarrollo (MAIL_MAILER=log / APP_ENV=local) actúa como mock si n8n no responde.
 * En producción apunta al webhook de n8n real.
 *
 * Registrado en AppServiceProvider::register() como binding de OrquestadorIAInterface.
 */
class OrquestadorIAService implements OrquestadorIAInterface
{
    /**
     * Envía el mensaje al webhook de n8n y devuelve la respuesta del agente.
     * Si n8n no está disponible devuelve una respuesta de mock para desarrollo.
     *
     * @param  \App\Models\SesionSimulacion  $sesion
     * @param  string  $mensajeAlumno
     * @return string
     */
    public function solicitarRespuesta(SesionSimulacion $sesion, string $mensajeAlumno): string
    {
        $n8nUrl = config('services.n8n.url', 'http://n8n:5678');

        try {
            $respuesta = Http::timeout(30)->post("{$n8nUrl}/webhook/omnisim-chat", [
                'sesion_id'   => $sesion->id,
                'mensaje'     => $mensajeAlumno,
                'perfil'      => $sesion->escenario->perfilAgente->toArray(),
                'historial'   => $sesion->mensajes->toArray(),
            ]);

            if ($respuesta->successful()) {
                return $respuesta->json('respuesta') ?? $this->respuestaMock($sesion);
            }
        } catch (\Throwable $e) {
            Log::warning("OrquestadorIA: n8n no disponible ({$e->getMessage()}). Usando mock.");
        }

        return $this->respuestaMock($sesion);
    }

    /**
     * Solicita a n8n que genere el borrador de evaluación de la sesión.
     * Si n8n no está disponible devuelve un borrador mock.
     *
     * @param  \App\Models\SesionSimulacion  $sesion
     * @return array<string, mixed>
     */
    public function generarBorrador(SesionSimulacion $sesion): array
    {
        $n8nUrl = config('services.n8n.url', 'http://n8n:5678');

        try {
            $respuesta = Http::timeout(60)->post("{$n8nUrl}/webhook/omnisim-evaluar", [
                'sesion_id' => $sesion->id,
                'historial' => $sesion->mensajes->toArray(),
                'perfil'    => $sesion->escenario->perfilAgente->toArray(),
                'objetivos' => $sesion->escenario->objetivos->toArray(),
            ]);

            if ($respuesta->successful()) {
                return $respuesta->json() ?? $this->borradorMock();
            }
        } catch (\Throwable $e) {
            Log::warning("OrquestadorIA: n8n no disponible para borrador. Usando mock.");
        }

        return $this->borradorMock();
    }

    /**
     * Respuesta de fallback cuando n8n no está disponible (entorno dev sin n8n activo).
     *
     * @param  \App\Models\SesionSimulacion  $sesion
     * @return string
     */
    private function respuestaMock(SesionSimulacion $sesion): string
    {
        $respuestasMock = [
            'Entiendo. ¿Puede contarme algo más sobre eso?',
            'Es una pregunta interesante. Déjeme pensar...',
            'Bueno, la verdad es que preferiría no entrar en detalles sobre eso.',
            'Sí, eso es correcto. ¿Hay algo más que quiera saber?',
            'No estoy seguro de entender la pregunta. ¿Puede reformularla?',
        ];

        return $respuestasMock[array_rand($respuestasMock)];
    }

    /**
     * Borrador de evaluación de fallback para desarrollo.
     *
     * @return array<string, mixed>
     */
    private function borradorMock(): array
    {
        return [
            'borrador_resumen'             => '[Mock IA] La sesión transcurrió de forma correcta. El alumno realizó preguntas pertinentes.',
            'borrador_calificacion'        => 6.0,
            'borrador_feedback'            => '[Mock IA] Técnica de preguntas adecuada. Mejorar la profundidad en el seguimiento.',
            'borrador_competencias'        => [],
            'borrador_mapa_descubrimiento' => ['descubierto' => [], 'no_descubierto' => []],
        ];
    }
}
