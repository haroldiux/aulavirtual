<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->rol?->value, ['docente', 'director', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'orden' => ['nullable', 'integer', 'min:1'],
            'visible' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El titulo de la seccion es obligatorio.',
        ];
    }
}
