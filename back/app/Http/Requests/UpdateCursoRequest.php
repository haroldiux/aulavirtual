<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $curso = $this->route('curso');

        if (! $user) {
            return false;
        }

        if ($user->esAdmin()) {
            return true;
        }

        // Docente/director solo editan cursos propios (director: de su carrera)
        return $curso && (int) $curso->docente_id === (int) $user->id;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'string', 'max:50'],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'carrera_id' => ['nullable', 'integer'],
            'sede_id' => ['nullable', 'integer'],
            'gestion' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable', Rule::in(['borrador', 'publicado', 'archivado'])],
            'imagen_portada' => ['nullable', 'string'],
            'config' => ['nullable', 'array'],
        ];
    }
}