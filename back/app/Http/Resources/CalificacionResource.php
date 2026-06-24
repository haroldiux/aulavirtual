<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalificacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entrega_id' => $this->entrega_id,
            'actividad_id' => $this->actividad_id,
            'estudiante_id' => $this->estudiante_id,
            'curso_id' => $this->curso_id,
            'nota' => (float) $this->nota,
            'nota_maxima' => (float) $this->nota_maxima,
            'porcentaje' => $this->porcentaje !== null ? (float) $this->porcentaje : null,
            'retroalimentacion' => $this->retroalimentacion,
            'rubrica' => $this->rubrica,
            'calificado_por' => $this->calificado_por,
            'fecha_calificacion' => $this->updated_at?->toIso8601String(),
        ];
    }
}
