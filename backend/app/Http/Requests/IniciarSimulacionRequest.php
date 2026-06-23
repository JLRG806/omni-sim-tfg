<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

/**
 * Valida el inicio de una simulación (CU-26).
 * El escenario debe existir y estar publicado.
 */
class IniciarSimulacionRequest extends FormRequest
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
            'escenario_id' => [
                'required',
                Rule::exists('escenarios', 'id')->where('estado', 'publicado')->whereNull('deleted_at'),
            ],
        ];
    }
}
