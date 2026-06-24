<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Curso;
use App\Models\EventoCalendario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'curso_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $desde = $request->has('desde') ? Carbon::parse($request->input('desde')) : Carbon::now()->startOfMonth();
        $hasta = $request->has('hasta') ? Carbon::parse($request->input('hasta')) : Carbon::now()->endOfMonth();
        $cursoIdFiltro = $request->input('curso_id');

        // Obtener IDs de cursos según rol
        if ($user->esAdmin() || $user->esDirector()) {
            $cursosIds = $cursoIdFiltro ? [$cursoIdFiltro] : Curso::pluck('id')->toArray();
        } else if ($user->esDocente()) {
            $cursosIds = $user->cursosComoDocente()->pluck('id')->toArray();
            if ($cursoIdFiltro && in_array((int)$cursoIdFiltro, $cursosIds, true)) {
                $cursosIds = [(int)$cursoIdFiltro];
            } else if ($cursoIdFiltro) {
                $cursosIds = []; // No tiene acceso a este curso
            }
        } else {
            // Estudiante
            $cursosIds = $user->cursosComoEstudiante()->pluck('cursos.id')->toArray();
            if ($cursoIdFiltro && in_array((int)$cursoIdFiltro, $cursosIds, true)) {
                $cursosIds = [(int)$cursoIdFiltro];
            } else if ($cursoIdFiltro) {
                $cursosIds = []; // No tiene acceso a este curso
            }
        }

        // 1. Obtener eventos manuales de la base de datos
        $queryEventos = EventoCalendario::where(function ($q) use ($cursosIds) {
            $q->whereIn('curso_id', $cursosIds)
              ->orWhereNull('curso_id'); // Eventos globales/institucionales
        })
        ->whereBetween('fecha_inicio', [$desde, $hasta]);

        if ($cursoIdFiltro) {
            $queryEventos = EventoCalendario::where('curso_id', (int)$cursoIdFiltro)
                ->whereBetween('fecha_inicio', [$desde, $hasta]);
        }

        $eventosManuales = $queryEventos->with(['curso', 'creadoPor'])->get()->map(function ($ev) {
            return [
                'id' => $ev->id,
                'curso_id' => $ev->curso_id,
                'curso_nombre' => $ev->curso?->nombre,
                'actividad_id' => $ev->actividad_id,
                'titulo' => $ev->titulo,
                'descripcion' => $ev->descripcion,
                'tipo' => $ev->tipo, // entrega, evaluacion, clase, evento_institucional
                'fecha_inicio' => $ev->fecha_inicio->toIso8601String(),
                'fecha_fin' => $ev->fecha_fin ? $ev->fecha_fin->toIso8601String() : null,
                'todo_el_dia' => (bool)$ev->todo_el_dia,
                'creado_por_nombre' => $ev->creadoPor?->nombre,
            ];
        });

        // 2. Obtener actividades dinámicamente y fusionarlas como eventos (Tareas, Cuestionarios, Encuestas)
        $actividadesEventos = collect();
        if (count($cursosIds) > 0) {
            $actividades = Actividad::whereHas('seccion', function ($q) use ($cursosIds) {
                $q->whereIn('curso_id', $cursosIds);
            })
            ->with('seccion.curso')
            ->get();

            foreach ($actividades as $act) {
                $fechaLimite = null;
                $config = $act->config ?? [];

                if ($act->tipo->value === 'tarea' && !empty($config['fecha_entrega'])) {
                    $fechaLimite = Carbon::parse($config['fecha_entrega']);
                } else if ($act->tipo->value === 'cuestionario' && !empty($config['fecha_cierre'])) {
                    $fechaLimite = Carbon::parse($config['fecha_cierre']);
                } else if ($act->tipo->value === 'encuesta' && !empty($config['fecha_cierre'])) {
                    $fechaLimite = Carbon::parse($config['fecha_cierre']);
                }

                if ($fechaLimite && $fechaLimite->between($desde, $hasta)) {
                    $tipoEv = ($act->tipo->value === 'cuestionario') ? 'evaluacion' : 'entrega';
                    $actividadesEventos->push([
                        'id' => 'act_' . $act->id,
                        'curso_id' => $act->seccion->curso_id,
                        'curso_nombre' => $act->seccion->curso->nombre,
                        'actividad_id' => $act->id,
                        'titulo' => $act->titulo,
                        'descripcion' => $act->descripcion,
                        'tipo' => $tipoEv,
                        'fecha_inicio' => $fechaLimite->toIso8601String(),
                        'fecha_fin' => null,
                        'todo_el_dia' => false,
                        'creado_por_nombre' => 'Sistema',
                    ]);
                }
            }
        }

        // Fusionar y retornar
        $eventosMerged = $eventosManuales->concat($actividadesEventos);

        return response()->json([
            'data' => $eventosMerged,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user->esEstudiante(), 403, 'No autorizado.');

        $data = $request->validate([
            'curso_id' => ['nullable', 'integer', 'exists:cursos,id'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'tipo' => ['required', 'string', 'in:entrega,evaluacion,clase,evento_institucional'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'todo_el_dia' => ['required', 'boolean'],
        ]);

        // Si es docente, validar que el curso le pertenezca
        if ($user->esDocente() && isset($data['curso_id'])) {
            $curso = Curso::find($data['curso_id']);
            abort_unless($curso && (int)$curso->docente_id === (int)$user->id, 403, 'Curso no asignado a este docente.');
        }

        $data['creado_por'] = $user->id;
        $evento = EventoCalendario::create($data);

        return response()->json([
            'data' => $evento,
        ], 201);
    }

    public function update(Request $request, EventoCalendario $evento): JsonResponse
    {
        $user = $request->user();
        abort_if($user->esEstudiante(), 403, 'No autorizado.');

        // Si es docente, validar que sea creador o dueño del curso
        if ($user->esDocente()) {
            abort_unless((int)$evento->creado_por === (int)$user->id || 
                ($evento->curso_id && (int)Curso::find($evento->curso_id)?->docente_id === (int)$user->id), 
                403, 
                'No autorizado.'
            );
        }

        $data = $request->validate([
            'curso_id' => ['nullable', 'integer', 'exists:cursos,id'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'tipo' => ['required', 'string', 'in:entrega,evaluacion,clase,evento_institucional'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'todo_el_dia' => ['required', 'boolean'],
        ]);

        if ($user->esDocente() && isset($data['curso_id'])) {
            $curso = Curso::find($data['curso_id']);
            abort_unless($curso && (int)$curso->docente_id === (int)$user->id, 403, 'Curso no asignado a este docente.');
        }

        $evento->update($data);

        return response()->json([
            'data' => $evento,
        ]);
    }

    public function destroy(Request $request, EventoCalendario $evento): JsonResponse
    {
        $user = $request->user();
        abort_if($user->esEstudiante(), 403, 'No autorizado.');

        if ($user->esDocente()) {
            abort_unless((int)$evento->creado_por === (int)$user->id || 
                ($evento->curso_id && (int)Curso::find($evento->curso_id)?->docente_id === (int)$user->id), 
                403, 
                'No autorizado.'
            );
        }

        $evento->delete();

        return response()->json([
            'message' => 'Evento eliminado con éxito.',
        ]);
    }
}
