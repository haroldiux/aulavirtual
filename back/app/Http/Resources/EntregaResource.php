<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntregaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'actividad_id' => $this->actividad_id,
            'estudiante_id' => $this->estudiante_id,
            'contenido' => $this->contenido,
            'fecha_entrega' => $this->fecha_entrega?->toIso8601String(),
            'estado' => $this->estado?->value,
            'intento_cuestionario_id' => $this->intento_cuestionario_id,
            'calificacion' => $this->whenLoaded('calificacion', fn () => [
                'id' => $this->calificacion->id,
                'nota' => (float) $this->calificacion->nota,
                'nota_maxima' => (float) $this->calificacion->nota_maxima,
                'porcentaje' => $this->calificacion->porcentaje !== null ? (float) $this->calificacion->porcentaje : null,
                'retroalimentacion' => $this->calificacion->retroalimentacion,
                'rubrica' => $this->calificacion->rubrica,
                'calificado_por' => $this->calificacion->calificado_por,
                'fecha_calificacion' => $this->calificacion->updated_at?->toIso8601String(),
            ]),
            'estudiante' => $this->whenLoaded('estudiante', fn () => [
                'id' => $this->estudiante->id,
                'nombre' => $this->estudiante->nombre,
                'email' => $this->estudiante->email,
                'avatar' => $this->estudiante->avatar,
            ]),
        ];
    }
}
