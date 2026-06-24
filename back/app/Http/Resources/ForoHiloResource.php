<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForoHiloResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $autor = $this->whenLoaded('autor');

        return [
            'id' => $this->id,
            'actividad_id' => $this->actividad_id,
            'autor_id' => $this->autor_id,
            'titulo' => $this->titulo,
            'contenido' => $this->contenido,
            'fijado' => $this->fijado,
            'anonimo' => $this->anonimo,
            'autor' => $this->anonimo ? null : [
                'id' => $autor?->id,
                'nombre' => $autor?->nombre,
                'avatar' => $autor?->avatar,
            ],
            'respuestas' => ForoRespuestaResource::collection($this->whenLoaded('respuestas')),
            'respuestas_count' => $this->when(isset($this->resource->respuestas_count), $this->resource->respuestas_count),
            'fecha' => $this->created_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
