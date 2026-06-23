<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida los datos de entrada para CU-06 Modificar Usuario.
 * La contraseña es opcional — si no se envía, no se modifica.
 * El email debe ser único excluyendo el del propio usuario.
 */
class ModificarUsuarioRequest extends FormRequest
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
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'password'              => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['sometimes', 'nullable', 'string'],
            'rol'                   => ['required', 'in:admin,profesor,alumno'],
            'estado'                => ['required', 'in:activo,inactivo'],
        ];
    }
}
