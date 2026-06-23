<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

/**
 * Valida los datos de entrada para CU-10 Crear Asignatura.
 */
class CrearAsignaturaRequest extends FormRequest
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
            'codigo'      => ['required', 'string', 'max:20', Rule::unique('asignaturas', 'codigo')],
            'nombre'      => ['required', 'string', 'max:255'],
            'descripcion' => ['sometimes', 'string', 'max:1000'],
            'profesor_id' => ['required', Rule::exists('users', 'id')->where('rol', 'profesor')->whereNull('deleted_at')],
        ];
    }
}
