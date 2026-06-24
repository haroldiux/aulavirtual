<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->rol?->value, ['docente', 'director', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'carrera_id' => ['nullable', 'integer'],
            'sede_id' => ['nullable', 'integer'],
            'gestion' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable', Rule::in(['borrador', 'publicado', 'archivado'])],
            'imagen_portada' => ['nullable', 'string'],
            'config' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del curso es obligatorio.',
        ];
    }
}