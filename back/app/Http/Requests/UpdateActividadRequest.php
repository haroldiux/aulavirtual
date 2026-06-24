<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->rol?->value, ['docente', 'director', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'tipo' => ['sometimes', 'required', Rule::in(['leccion', 'tarea', 'foro', 'cuestionario', 'encuesta', 'h5p'])],
            'seccion_id' => ['sometimes', 'required', 'exists:secciones,id'],
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'orden' => ['nullable', 'integer', 'min:1'],
            'tiene_nota' => ['nullable', 'boolean'],
            'nota_maxima' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'peso' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'config' => ['nullable', 'array'],
            'visible' => ['nullable', 'boolean'],
        ];
    }
}
