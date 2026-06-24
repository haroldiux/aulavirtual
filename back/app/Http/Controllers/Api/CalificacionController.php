<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CalificacionResource;
use App\Http\Resources\EntregaResource;
use App\Models\Actividad;
use App\Models\Calificacion;
use App\Models\Curso;
use App\Models\Entrega;
use App\Models\Matricula;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalificacionController extends Controller
{
    public function libroCurso(Request $request, Curso $curso): JsonResponse
    {
        $this->authorizeCurso($request->user(), $curso);

        $actividades = Actividad::whereHas('seccion', fn ($q) => $q->where('curso_id', $curso->id))
            ->where('tiene_nota', true)
            ->orderBy('orden')
            ->get();

        $matriculas = Matricula::where('curso_id', $curso->id)
            ->where('estado', 'activo')
            ->with('estudiante:id,nombre,email,avatar')
            ->get();

        $calificaciones = Calificacion::where('curso_id', $curso->id)
            ->get()
            ->keyBy(fn ($c) => "{$c->estudiante_id}-{$c->actividad_id}");

        $estudiantesData = $matriculas->map(function ($matricula) use ($actividades, $calificaciones) {
            $notas = [];
            $sumaPorcentaje = 0;
            $countNotas = 0;

            foreach ($actividades as $act) {
                $key = "{$matricula->estudiante_id}-{$act->id}";
                $cal = $calificaciones->get($key);
                $entrega = Entrega::where('actividad_id', $act->id)
                    ->where('estudiante_id', $matricula->estudiante_id)
                    ->first();

                if ($cal) {
                    $porcentaje = $cal->porcentaje !== null
                        ? (float) $cal->porcentaje
                        : ($act->nota_maxima > 0 ? round(($cal->nota / $act->nota_maxima) * 100, 2) : 0);
                    $notas[$act->id] = [
                        'nota' => (float) $cal->nota,
                        'nota_maxima' => (float) $act->nota_maxima,
                        'porcentaje' => $porcentaje,
                        'retroalimentacion' => $cal->retroalimentacion,
                    ];
                    $sumaPorcentaje += $porcentaje;
                    $countNotas++;
                } elseif ($entrega) {
                    $notas[$act->id] = [
                        'nota' => null,
                        'estado_entrega' => $entrega->estado?->value,
                        'pendiente_calificar' => true,
                    ];
                } else {
                    $notas[$act->id] = null;
                }
            }

            $promedio = $countNotas > 0 ? round($sumaPorcentaje / $countNotas, 2) : null;

            return [
                'id' => $matricula->estudiante->id,
                'nombre' => $matricula->estudiante->nombre,
                'email' => $matricula->estudiante->email,
                'avatar' => $matricula->estudiante->avatar,
                'notas' => $notas,
                'promedio' => $promedio,
            ];
        });

        $promedioGeneral = $estudiantesData->avg('promedio');

        $pendientesCalificar = Entrega::whereHas('actividad.seccion', fn ($q) => $q->where('curso_id', $curso->id))
            ->where('estado', 'entregado')
            ->whereDoesntHave('calificacion')
            ->count();

        return response()->json([
            'data' => [
                'curso' => [
                    'id' => $curso->id,
                    'nombre' => $curso->nombre,
                    'codigo' => $curso->codigo,
                ],
                'actividades' => $actividades->map(fn ($a) => [
                    'id' => $a->id,
                    'titulo' => $a->titulo,
                    'tipo' => $a->tipo?->value,
                    'nota_maxima' => (float) $a->nota_maxima,
                    'peso' => (float) $a->peso,
                ]),
                'estudiantes' => $estudiantesData,
                'promedio_general' => $promedioGeneral ? round($promedioGeneral, 2) : null,
                'pendientes_calificar' => $pendientesCalificar,
            ],
        ]);
    }

    public function calificar(Request $request, Entrega $entrega): JsonResponse
    {
        $this->authorizeEntrega($request->user(), $entrega);

        $data = $request->validate([
            'nota' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'retroalimentacion' => ['nullable', 'string'],
            'rubrica' => ['nullable', 'array'],
        ]);

        $actividad = $entrega->actividad;
        $curso = $actividad?->seccion?->curso;
        $notaMaxima = (float) ($actividad?->nota_maxima ?? 100);
        $porcentaje = $notaMaxima > 0 ? round(($data['nota'] / $notaMaxima) * 100, 2) : 0;

        $calificacion = Calificacion::updateOrCreate(
            ['entrega_id' => $entrega->id],
            [
                'actividad_id' => $actividad->id,
                'estudiante_id' => $entrega->estudiante_id,
                'curso_id' => $curso?->id,
                'nota' => $data['nota'],
                'nota_maxima' => $notaMaxima,
                'porcentaje' => $porcentaje,
                'retroalimentacion' => $data['retroalimentacion'] ?? null,
                'rubrica' => $data['rubrica'] ?? null,
                'calificado_por' => $request->user()->id,
            ]
        );

        $entrega->update(['estado' => 'revisado']);

        return response()->json([
            'data' => CalificacionResource::make($calificacion),
        ]);
    }

    public function actualizar(Request $request, Calificacion $calificacion): JsonResponse
    {
        $this->authorizeCalificacion($request->user(), $calificacion);

        $data = $request->validate([
            'nota' => ['sometimes', 'numeric', 'min:0', 'max:999.99'],
            'retroalimentacion' => ['nullable', 'string'],
            'rubrica' => ['nullable', 'array'],
        ]);

        if (isset($data['nota'])) {
            $notaMaxima = (float) $calificacion->nota_maxima;
            $data['porcentaje'] = $notaMaxima > 0 ? round(($data['nota'] / $notaMaxima) * 100, 2) : 0;
        }

        $calificacion->update($data);

        return response()->json([
            'data' => CalificacionResource::make($calificacion->fresh()),
        ]);
    }

    public function misNotas(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $calificaciones = Calificacion::where('estudiante_id', $userId)
            ->with(['actividad.seccion.curso'])
            ->get();

        $porCurso = $calificaciones->groupBy(fn ($c) => $c->actividad?->seccion?->curso_id)->map(function ($notas, $cursoId) {
            $curso = $notas->first()->actividad?->seccion?->curso;
            $listaNotas = $notas->map(fn ($c) => [
                'actividad_id' => $c->actividad_id,
                'actividad_titulo' => $c->actividad?->titulo,
                'tipo' => $c->actividad?->tipo?->value,
                'nota' => (float) $c->nota,
                'nota_maxima' => (float) $c->nota_maxima,
                'porcentaje' => $c->porcentaje !== null ? (float) $c->porcentaje : null,
                'retroalimentacion' => $c->retroalimentacion,
                'fecha' => $c->updated_at?->toIso8601String(),
            ]);

            $promedio = $notas->avg('porcentaje');

            return [
                'curso_id' => (int) $cursoId,
                'curso_nombre' => $curso?->nombre,
                'curso_codigo' => $curso?->codigo,
                'notas' => $listaNotas,
                'promedio' => $promedio ? round($promedio, 2) : null,
            ];
        })->values();

        return response()->json([
            'data' => [
                'cursos' => $porCurso,
                'promedio_general' => $calificaciones->avg('porcentaje') ? round($calificaciones->avg('porcentaje'), 2) : null,
            ],
        ]);
    }

    private function authorizeCurso($usuario, Curso $curso): void
    {
        if ($usuario->esAdmin()) return;
        if ($usuario->esDocente() && (int) $curso->docente_id === (int) $usuario->id) return;
        if ($usuario->esDirector() && (int) $curso->carrera_id === (int) $usuario->carrera_id) return;
        abort(403, 'No tienes permiso sobre este curso.');
    }

    private function authorizeEntrega($usuario, Entrega $entrega): void
    {
        if ($usuario->esAdmin()) return;
        if ($usuario->esDocente()) {
            $curso = $entrega->actividad?->seccion?->curso;
            if ($curso && (int) $curso->docente_id === (int) $usuario->id) return;
        }
        if ($usuario->esDirector()) {
            $curso = $entrega->actividad?->seccion?->curso;
            if ($curso && (int) $curso->carrera_id === (int) $usuario->carrera_id) return;
        }
        abort(403, 'No tienes permiso para calificar esta entrega.');
    }

    private function authorizeCalificacion($usuario, Calificacion $calificacion): void
    {
        if ($usuario->esAdmin()) return;
        if ($usuario->esDocente() && (int) $calificacion->calificado_por === (int) $usuario->id) return;
        $curso = $calificacion->curso;
        if ($usuario->esDocente() && $curso && (int) $curso->docente_id === (int) $usuario->id) return;
        abort(403, 'No tienes permiso sobre esta calificacion.');
    }
}
