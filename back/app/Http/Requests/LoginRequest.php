<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sisa_token' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'password' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'El correo electronico no es valido.',
        ];
    }

    public function esSso(): bool
    {
        return filled($this->input('sisa_token'));
    }

    public function esLocal(): bool
    {
        return filled($this->input('email')) && filled($this->input('password'));
    }
}