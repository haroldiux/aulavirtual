<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForoRespuestaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $autor = $this->whenLoaded('autor');

        return [
            'id' => $this->id,
            'hilo_id' => $this->hilo_id,
            'autor_id' => $this->autor_id,
            'contenido' => $this->contenido,
            'anonimo' => $this->anonimo,
            'autor' => $this->anonimo ? null : [
                'id' => $autor?->id,
                'nombre' => $autor?->nombre,
                'avatar' => $autor?->avatar,
            ],
            'fecha' => $this->created_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
