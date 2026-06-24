<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActividadRequest;
use App\Http\Requests\UpdateActividadRequest;
use App\Http\Resources\ActividadResource;
use App\Models\Actividad;
use App\Models\Curso;
use App\Models\Seccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActividadController extends Controller
{
    public function index(Request $request, Seccion $seccion): JsonResponse
    {
        $this->authorizeCurso($request->user(), $seccion->curso);

        $actividades = $seccion->actividades()->orderBy('orden')->get();

        return response()->json([
            'data' => ActividadResource::collection($actividades),
        ]);
    }

    public function store(StoreActividadRequest $request, Seccion $seccion): JsonResponse
    {
        $this->authorizeCurso($request->user(), $seccion->curso);

        $orden = $request->integer('orden', $seccion->actividades()->count() + 1);

        $actividad = $seccion->actividades()->create([
            ...$request->validated(),
            'orden' => $orden,
            'tiene_nota' => $request->boolean('tiene_nota', true),
            'nota_maxima' => $request->input('nota_maxima', 100),
            'peso' => $request->input('peso', 1.0),
            'visible' => $request->boolean('visible', true),
        ]);

        return response()->json([
            'data' => ActividadResource::make($actividad->fresh()),
        ], 201);
    }

    public function show(Request $request, Actividad $actividad): JsonResponse
    {
        $this->authorizeCurso($request->user(), $actividad->seccion->curso);

        return response()->json([
            'data' => ActividadResource::make($actividad),
        ]);
    }

    public function update(UpdateActividadRequest $request, Actividad $actividad): JsonResponse
    {
        $this->authorizeCurso($request->user(), $actividad->seccion->curso);

        $actividad->update($request->validated());

        return response()->json([
            'data' => ActividadResource::make($actividad->fresh()),
        ]);
    }

    public function destroy(Request $request, Actividad $actividad): JsonResponse
    {
        $this->authorizeCurso($request->user(), $actividad->seccion->curso);

        $actividad->delete();

        return response()->json([
            'message' => 'Actividad eliminada.',
        ]);
    }

    public function reordenar(Request $request, Seccion $seccion): JsonResponse
    {
        $this->authorizeCurso($request->user(), $seccion->curso);

        $data = $request->validate([
            'orden' => ['required', 'array'],
            'orden.*' => ['integer'],
        ]);

        foreach ($data['orden'] as $index => $actividadId) {
            $seccion->actividades()->where('id', $actividadId)->update(['orden' => $index + 1]);
        }

        return response()->json(['message' => 'Orden actualizado.']);
    }

    private function authorizeCurso($usuario, Curso $curso): void
    {
        if ($usuario->esAdmin()) return;
        if ($usuario->esDocente() && (int) $curso->docente_id === (int) $usuario->id) return;
        if ($usuario->esDirector() && (int) $curso->carrera_id === (int) $usuario->carrera_id) return;
        abort(403, 'No tienes permiso sobre este curso.');
    }
}
