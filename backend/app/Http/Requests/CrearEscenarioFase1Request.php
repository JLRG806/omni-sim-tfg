<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

/**
 * Valida los datos de la Fase 1 de CU-18 Crear Escenario.
 * La asignatura debe existir. El ownership check (profesor titular)
 * se realiza en el controller.
 */
class CrearEscenarioFase1Request extends FormRequest
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
            'asignatura_id'       => ['required', Rule::exists('asignaturas', 'id')->whereNull('deleted_at')],
            'titulo'              => ['required', 'string', 'max:255'],
            'area_conocimiento'   => ['required', 'string', 'max:255'],
            'descripcion_situacion' => ['required', 'string'],
            'objetivos'           => ['required', 'array', 'min:1'],
            'objetivos.*.contenido' => ['required', 'string'],
            'objetivos.*.orden'   => ['required', 'integer', 'min:1'],
        ];
    }
}
