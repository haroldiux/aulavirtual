<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CursoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sisa_asignatura_id' => $this->sisa_asignatura_id,
            'sisa_grupo_id' => $this->sisa_grupo_id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'docente_id' => $this->docente_id,
            'docente' => new UsuarioResource($this->whenLoaded('docente')),
            'carrera_id' => $this->carrera_id,
            'sede_id' => $this->sede_id,
            'gestion' => $this->gestion,
            'estado' => $this->estado?->value,
            'imagen_portada' => $this->imagen_portada,
            'config' => $this->config,
            'total_estudiantes' => $this->whenCounted('matriculas'),
            'total_actividades' => $this->when(fn () => isset($this->resource->total_actividades), $this->resource->total_actividades),
            'secciones' => SeccionResource::collection($this->whenLoaded('secciones')),
        ];
    }
}