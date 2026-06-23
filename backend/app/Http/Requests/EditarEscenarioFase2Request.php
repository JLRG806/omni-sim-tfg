<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

/**
 * Valida la Fase 2 de CU-19 Editar Escenario (actualizar perfil del agente).
 * Idéntica a CrearEscenarioFase2Request.
 */
class EditarEscenarioFase2Request extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rol_identidad'           => ['required', 'string'],
            'trasfondo'               => ['required', 'string'],
            'conocimientos'           => ['required', 'string'],
            'mensaje_bienvenida'      => ['required', 'string'],
            'comportamiento'          => ['required', 'string'],
            'tono_emocional'          => ['required', Rule::in(['formal', 'amigable', 'empatico', 'serio', 'distante'])],
            'nivel_dificultad'        => ['required', Rule::in(['facil', 'medio', 'dificil'])],
            'informacion_explicita'   => ['required', 'array', 'min:1'],
            'informacion_explicita.*' => ['required', 'string'],
            'informacion_latente'     => ['required', 'array', 'min:1'],
            'informacion_latente.*'   => ['required', 'string'],
            'criterios_evaluacion'    => ['required', 'array', 'min:1'],
            'criterios_evaluacion.*.competencia_id' => ['required', Rule::exists('competencias', 'id')],
            'criterios_evaluacion.*.contenido'      => ['required', 'string'],
        ];
    }
}
