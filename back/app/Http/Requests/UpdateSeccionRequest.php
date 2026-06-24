<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->rol?->value, ['docente', 'director', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'orden' => ['nullable', 'integer', 'min:1'],
            'visible' => ['nullable', 'boolean'],
        ];
    }
}
