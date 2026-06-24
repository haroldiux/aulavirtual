<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoEntrega;
use App\Http\Controllers\Controller;
use App\Http\Resources\EntregaResource;
use App\Models\Actividad;
use App\Models\Entrega;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntregaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Entrega::query()->with(['calificacion', 'estudiante']);

        if ($request->filled('actividad_id')) {
            $query->where('actividad_id', $request->integer('actividad_id'));
        }

        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->integer('estudiante_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        $entregas = $query->latest()->get();

        return response()->json([
            'data' => EntregaResource::collection($entregas),
        ]);
    }

    public function misEntregas(Request $request): JsonResponse
    {
        $entregas = Entrega::where('estudiante_id', $request->user()->id)
            ->with(['calificacion', 'actividad'])
            ->latest()
            ->get();

        return response()->json([
            'data' => EntregaResource::collection($entregas),
        ]);
    }

    public function show(Request $request, Entrega $entrega): JsonResponse
    {
        $this->authorizeAccess($request->user(), $entrega);

        $entrega->load(['calificacion', 'estudiante']);

        return response()->json([
            'data' => EntregaResource::make($entrega),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'actividad_id' => ['required', 'integer', 'exists:actividades,id'],
            'contenido' => ['nullable', 'array'],
            'contenido.texto' => ['nullable', 'string'],
            'contenido.archivos' => ['nullable', 'array'],
            'contenido.nota' => ['nullable', 'numeric'],
        ]);

        $actividad = Actividad::findOrFail($data['actividad_id']);

        $estado = EstadoEntrega::Entregado;
        if ($actividad->tipo?->value === 'h5p' && isset($data['contenido']['nota'])) {
            $estado = EstadoEntrega::Revisado;
        }

        // Verificar que el estudiante está matriculado o que la actividad existe
        $entrega = Entrega::updateOrCreate(
            [
                'actividad_id' => $actividad->id,
                'estudiante_id' => $request->user()->id,
            ],
            [
                'contenido' => $data['contenido'] ?? null,
                'fecha_entrega' => now(),
                'estado' => $estado,
            ]
        );

        // Si es H5P y viene con nota, registrar calificacion
        if ($actividad->tipo?->value === 'h5p' && isset($data['contenido']['nota'])) {
            $nota = (float) $data['contenido']['nota'];
            $notaMaxima = (float) ($actividad->nota_maxima ?? 100);
            $porcentaje = $notaMaxima > 0 ? round(($nota / $notaMaxima) * 100, 2) : 0;

            \App\Models\Calificacion::updateOrCreate(
                ['entrega_id' => $entrega->id],
                [
                    'actividad_id' => $actividad->id,
                    'estudiante_id' => $request->user()->id,
                    'curso_id' => $actividad->seccion->curso_id,
                    'nota' => $nota,
                    'nota_maxima' => $notaMaxima,
                    'porcentaje' => $porcentaje,
                    'retroalimentacion' => 'Calificacion automatica por contenido H5P: ' . ($data['contenido']['texto'] ?? 'Completado'),
                    'calificado_por' => null,
                ]
            );
        }

        $entrega->load(['calificacion', 'estudiante']);

        return response()->json([
            'data' => EntregaResource::make($entrega),
        ], 201);
    }

    public function update(Request $request, Entrega $entrega): JsonResponse
    {
        $this->authorizeAccess($request->user(), $entrega);

        $data = $request->validate([
            'contenido' => ['nullable', 'array'],
            'contenido.texto' => ['nullable', 'string'],
            'contenido.archivos' => ['nullable', 'array'],
            'estado' => ['nullable', 'in:pendiente,entregado,revisado,rechazado'],
        ]);

        $entrega->update([
            ...$data,
            'fecha_entrega' => now(),
        ]);

        $entrega->load(['calificacion', 'estudiante']);

        return response()->json([
            'data' => EntregaResource::make($entrega),
        ]);
    }

    private function authorizeAccess($usuario, Entrega $entrega): void
    {
        if ($usuario->esAdmin()) return;
        if ($usuario->esEstudiante() && (int) $entrega->estudiante_id === (int) $usuario->id) return;
        if ($usuario->esDocente()) {
            $curso = $entrega->actividad?->seccion?->curso;
            if ($curso && (int) $curso->docente_id === (int) $usuario->id) return;
        }
        if ($usuario->esDirector()) {
            $curso = $entrega->actividad?->seccion?->curso;
            if ($curso && (int) $curso->carrera_id === (int) $usuario->carrera_id) return;
        }
        abort(403, 'No tienes permiso sobre esta entrega.');
    }
}
