<?php

namespace App\Services;

use App\Models\SesionSimulacion;

/**
 * Interfaz del orquestador IA.
 * El backend siempre habla con n8n via REST — nunca directamente con Ollama.
 * En desarrollo se usa OrquestadorIAService (mock); en producción se sustituye
 * apuntando a n8n real sin tocar el backend.
 */
interface OrquestadorIAInterface
{
    /**
     * Envía el mensaje del alumno al orquestador y espera la respuesta del agente.
     * Operación SÍNCRONA — CU-28.
     *
     * @param  \App\Models\SesionSimulacion  $sesion
     * @param  string  $mensajeAlumno
     * @return string  Contenido de la respuesta del agente
     */
    public function solicitarRespuesta(SesionSimulacion $sesion, string $mensajeAlumno): string;

    /**
     * Solicita al orquestador que genere el borrador de evaluación.
     * Operación ASÍNCRONA — llamada desde el job de CU-29.
     *
     * @param  \App\Models\SesionSimulacion  $sesion
     * @return array<string, mixed>  Campos borrador_* del Resultado
     */
    public function generarBorrador(SesionSimulacion $sesion): array;
}
