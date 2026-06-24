<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeccionRequest;
use App\Http\Requests\UpdateSeccionRequest;
use App\Http\Resources\SeccionResource;
use App\Models\Curso;
use App\Models\Seccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeccionController extends Controller
{
    public function index(Request $request, Curso $curso): JsonResponse
    {
        $this->authorizeCurso($request->user(), $curso);

        $secciones = $curso->secciones()->orderBy('orden')->get();

        return response()->json([
            'data' => SeccionResource::collection($secciones),
        ]);
    }

    public function store(StoreSeccionRequest $request, Curso $curso): JsonResponse
    {
        $this->authorizeCurso($request->user(), $curso);

        $orden = $request->integer('orden', $curso->secciones()->count() + 1);

        $seccion = $curso->secciones()->create([
            ...$request->validated(),
            'orden' => $orden,
            'visible' => $request->boolean('visible', true),
        ]);

        return response()->json([
            'data' => SeccionResource::make($seccion),
        ], 201);
    }

    public function update(UpdateSeccionRequest $request, Curso $curso, Seccion $seccion): JsonResponse
    {
        $this->authorizeCurso($request->user(), $curso);
        abort_unless((int) $seccion->curso_id === (int) $curso->id, 404);

        $seccion->update($request->validated());

        return response()->json([
            'data' => SeccionResource::make($seccion->fresh()),
        ]);
    }

    public function destroy(Request $request, Curso $curso, Seccion $seccion): JsonResponse
    {
        $this->authorizeCurso($request->user(), $curso);
        abort_unless((int) $seccion->curso_id === (int) $curso->id, 404);

        $seccion->delete();

        return response()->json([
            'message' => 'Seccion eliminada.',
        ]);
    }

    public function reordenar(Request $request, Curso $curso): JsonResponse
    {
        $this->authorizeCurso($request->user(), $curso);

        $data = $request->validate([
            'orden' => ['required', 'array'],
            'orden.*' => ['integer'],
        ]);

        foreach ($data['orden'] as $index => $seccionId) {
            $curso->secciones()->where('id', $seccionId)->update(['orden' => $index + 1]);
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
