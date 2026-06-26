<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActividadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seccion_id' => $this->seccion_id,
            'tipo' => $this->tipo?->value,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'orden' => $this->orden,
            'tiene_nota' => $this->tiene_nota,
            'nota_maxima' => (float) $this->nota_maxima,
            'peso' => (float) $this->peso,
            'config' => $this->config,
            'visible' => $this->visible,
            'tipo_actividad' => $this->tipo_actividad,
            'grupo_calificacion' => $this->grupo_calificacion,
            'parcial' => $this->parcial,
        ];
    }
}