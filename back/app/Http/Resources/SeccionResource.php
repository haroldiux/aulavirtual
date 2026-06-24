<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeccionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'curso_id' => $this->curso_id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'orden' => $this->orden,
            'visible' => $this->visible,
            'actividades' => ActividadResource::collection($this->whenLoaded('actividades')),
        ];
    }
}