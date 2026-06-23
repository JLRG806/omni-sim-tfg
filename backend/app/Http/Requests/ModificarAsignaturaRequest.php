<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

/**
 * Valida los datos de entrada para CU-11 Modificar Asignatura.
 * El código debe ser único excluyendo la asignatura que se edita.
 */
class ModificarAsignaturaRequest extends FormRequest
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
        $id = (int) $this->route('id');

        return [
            'codigo'      => ['required', 'string', 'max:20', Rule::unique('asignaturas', 'codigo')->ignore($id)],
            'nombre'      => ['required', 'string', 'max:255'],
            'descripcion' => ['sometimes', 'string', 'max:1000'],
            'profesor_id' => ['required', Rule::exists('users', 'id')->where('rol', 'profesor')->whereNull('deleted_at')],
        ];
    }
}
