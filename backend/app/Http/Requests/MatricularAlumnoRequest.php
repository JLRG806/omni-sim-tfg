<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

/**
 * Valida los datos de entrada para CU-15 Matricular Alumno.
 * El alumno debe tener rol=alumno y no estar ya matriculado en la asignatura.
 */
class MatricularAlumnoRequest extends FormRequest
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
        $asignaturaId = (int) $this->route('id');

        return [
            'alumno_id' => [
                'required',
                Rule::exists('users', 'id')->where('rol', 'alumno')->whereNull('deleted_at'),
                Rule::unique('matriculas', 'alumno_id')->where('asignatura_id', $asignaturaId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alumno_id.unique' => 'El alumno ya está matriculado en esta asignatura.',
        ];
    }
}
