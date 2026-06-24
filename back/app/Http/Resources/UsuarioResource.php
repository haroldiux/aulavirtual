<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sisa_id' => $this->sisa_id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'rol' => $this->rol?->value,
            'carrera_id' => $this->carrera_id,
            'sede_id' => $this->sede_id,
            'activo' => $this->activo,
        ];
    }
}