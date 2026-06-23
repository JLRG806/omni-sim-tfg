<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la Fase 1 de CU-19 Editar Escenario.
 * No requiere asignatura_id (el escenario ya existe).
 */
class EditarEscenarioFase1Request extends FormRequest
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
            'titulo'                => ['required', 'string', 'max:255'],
            'area_conocimiento'     => ['required', 'string', 'max:255'],
            'descripcion_situacion' => ['required', 'string'],
            'objetivos'             => ['required', 'array', 'min:1'],
            'objetivos.*.contenido' => ['required', 'string'],
            'objetivos.*.orden'     => ['required', 'integer', 'min:1'],
        ];
    }
}
