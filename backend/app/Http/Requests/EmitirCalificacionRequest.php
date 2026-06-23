<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los datos finales de CU-24 Emitir Calificación.
 * El profesor puede ajustar la calificación y feedback generados por la IA.
 */
class EmitirCalificacionRequest extends FormRequest
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
            'final_calificacion'  => ['required', 'numeric', 'min:0', 'max:10'],
            'final_feedback'      => ['required', 'string'],
            'final_competencias'  => ['required', 'array'],
            'final_competencias.*.competencia_id' => ['required', 'integer', 'exists:competencias,id'],
            'final_competencias.*.puntuacion'     => ['required', 'numeric', 'min:0', 'max:10'],
            'final_competencias.*.comentario'     => ['sometimes', 'nullable', 'string'],
        ];
    }
}
