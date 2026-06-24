<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoCurso;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCursoRequest;
use App\Http\Requests\UpdateCursoRequest;
use App\Http\Resources\CursoResource;
use App\Models\Curso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $query = Curso::query()
            ->with(['docente:id,nombre,email,avatar,rol'])
            ->withCount('matriculas');

        $query->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->when($request->filled('gestion'), fn ($q) => $q->where('gestion', $request->string('gestion')));

        // Filtro por rol
        match (true) {
            $usuario->esDocente() => $query->where('docente_id', $usuario->id),
            $usuario->esEstudiante() => $query->whereHas('matriculas', fn ($q) => $q
                ->where('estudiante_id', $usuario->id)
                ->where('estado', 'activo')),
            $usuario->esDirector() => $query->when($usuario->carrera_id, fn ($q) => $q
                ->where('carrera_id', $usuario->carrera_id)),
            $usuario->esAdmin() => null,
            default => $query->where('id', -1), // sin acceso
        };

        $cursos = $query->latest()->paginate($request->integer('per_page', 25));

        return response()->json([
            'data' => CursoResource::collection($cursos),
            'meta' => [
                'page' => $cursos->currentPage(),
                'per_page' => $cursos->perPage(),
                'total' => $cursos->total(),
                'last_page' => $cursos->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, Curso $curso): JsonResponse
    {
        $curso->load([
            'docente:id,nombre,email,avatar,rol',
            'secciones.actividades' => fn ($q) => $q->orderBy('orden'),
        ])->loadCount('matriculas');

        return response()->json([
            'data' => CursoResource::make($curso),
        ]);
    }

    public function store(StoreCursoRequest $request): JsonResponse
    {
        $usuario = $request->user();

        $curso = Curso::create([
            ...$request->validated(),
            'docente_id' => $usuario->id,
            'estado' => $request->input('estado', EstadoCurso::Borrador->value),
        ]);

        return response()->json([
            'data' => CursoResource::make($curso),
        ], 201);
    }

    public function update(UpdateCursoRequest $request, Curso $curso): JsonResponse
    {
        $curso->update($request->validated());

        return response()->json([
            'data' => CursoResource::make($curso->fresh()),
        ]);
    }

    public function publicar(Request $request, Curso $curso): JsonResponse
    {
        $this->authorizeCurso($request->user(), $curso);

        $curso->update(['estado' => EstadoCurso::Publicado]);

        return response()->json([
            'data' => CursoResource::make($curso),
        ]);
    }

    public function archivar(Request $request, Curso $curso): JsonResponse
    {
        $this->authorizeCurso($request->user(), $curso);

        $curso->update(['estado' => EstadoCurso::Archivado]);

        return response()->json([
            'message' => 'Curso archivado.',
        ]);
    }

    public function destroy(Request $request, Curso $curso): JsonResponse
    {
        return $this->archivar($request, $curso);
    }

    private function authorizeCurso($usuario, Curso $curso): void
    {
        if ($usuario->esAdmin()) {
            return;
        }

        if ($usuario->esDocente() && (int) $curso->docente_id === (int) $usuario->id) {
            return;
        }

        if ($usuario->esDirector() && (int) $curso->carrera_id === (int) $usuario->carrera_id) {
            return;
        }

        abort(403, 'No tienes permiso sobre este curso.');
    }
}