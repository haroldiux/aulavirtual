<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->rol?->value, ['docente', 'director', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['leccion', 'tarea', 'foro', 'cuestionario', 'encuesta', 'h5p'])],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'orden' => ['nullable', 'integer', 'min:1'],
            'tiene_nota' => ['nullable', 'boolean'],
            'nota_maxima' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'peso' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'config' => ['nullable', 'array'],
            'visible' => ['nullable', 'boolean'],
            'tipo_actividad' => ['nullable', 'string', 'in:teorica,practica'],
            'grupo_calificacion' => ['nullable', 'string', 'in:formativa_teorica,formativa_practica,examen_parcial,examen_final'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El titulo de la actividad es obligatorio.',
            'tipo.required' => 'El tipo de actividad es obligatorio.',
        ];
    }
}
