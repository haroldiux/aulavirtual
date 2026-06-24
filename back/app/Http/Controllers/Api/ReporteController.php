<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Usuario;
use App\Models\Calificacion;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function exportarCursoCalificaciones(Curso $curso): Response
    {
        $curso->load(['matriculas.estudiante', 'secciones.actividades' => fn($q) => $q->where('tiene_nota', true)]);

        $actividades = $curso->secciones->flatMap->actividades;
        $matriculas = $curso->matriculas->filter(fn($m) => $m->estado === 'activo');

        $headers = ['Estudiante', 'Email'];
        foreach ($actividades as $act) {
            $headers[] = "{$act->titulo} (Max {$act->nota_maxima})";
        }
        $headers[] = 'Promedio General';

        $output = fopen('php://temp', 'r+');
        // Agregar UTF-8 BOM para Excel
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, $headers, ';');

        foreach ($matriculas as $mat) {
            $estudiante = $mat->estudiante;
            if (!$estudiante) continue;

            $row = [$estudiante->nombre, $estudiante->email];
            $sumNotas = 0;
            $countNotas = 0;

            foreach ($actividades as $act) {
                // Obtener calificación del estudiante para esta actividad
                $calif = Calificacion::where('estudiante_id', $estudiante->id)
                    ->where('actividad_id', $act->id)
                    ->first();

                if ($calif) {
                    $row[] = $calif->nota;
                    $sumNotas += $calif->porcentaje; // Usar porcentaje para promedio uniforme
                    $countNotas++;
                } else {
                    $row[] = '0';
                }
            }

            $promedio = $countNotas > 0 ? round($sumNotas / $countNotas, 2) : 0;
            $row[] = "{$promedio}%";

            fputcsv($output, $row, ';');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        $filename = "calificaciones_curso_" . str_replace(' ', '_', $curso->codigo) . ".csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    public function exportarCumplimientoDocente(): Response
    {
        $docentes = Usuario::where('rol', 'docente')->get();

        $headers = ['Docente', 'Email', 'Total Cursos', 'Cursos Publicados', 'Total Actividades', 'Calificaciones Pendientes'];

        $output = fopen('php://temp', 'r+');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers, ';');

        foreach ($docentes as $docente) {
            $cursos = Curso::where('docente_id', $docente->id)->get();
            $totalCursos = $cursos->count();
            $publicados = $cursos->where('estado', 'publicado')->count();

            $cursoIds = $cursos->pluck('id');
            
            // Total actividades en sus cursos
            $totalActividades = DB::table('actividades')
                ->join('secciones', 'actividades.seccion_id', '=', 'secciones.id')
                ->whereIn('secciones.curso_id', $cursoIds)
                ->count();

            // Calificaciones pendientes de revisión (entregadas pero no calificadas)
            $pendientes = DB::table('entregas')
                ->join('actividades', 'entregas.actividad_id', '=', 'actividades.id')
                ->join('secciones', 'actividades.seccion_id', '=', 'secciones.id')
                ->leftJoin('calificaciones', function ($join) {
                    $join->on('entregas.actividad_id', '=', 'calificaciones.actividad_id')
                         ->on('entregas.estudiante_id', '=', 'calificaciones.estudiante_id');
                })
                ->whereIn('secciones.curso_id', $cursoIds)
                ->where('entregas.estado', 'entregado')
                ->whereNull('calificaciones.id')
                ->count();

            fputcsv($output, [
                $docente->nombre,
                $docente->email,
                $totalCursos,
                $publicados,
                $totalActividades,
                $pendientes
            ], ';');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_cumplimiento_docente.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    public function exportarRendimientoEstudiantes(): Response
    {
        $estudiantes = Usuario::where('rol', 'estudiante')->get();

        $headers = ['Estudiante', 'Email', 'Cursos Inscritos', 'Promedio General (%)', 'Alerta Académica'];

        $output = fopen('php://temp', 'r+');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers, ';');

        foreach ($estudiantes as $est) {
            $cursoIds = DB::table('matriculas')
                ->where('estudiante_id', $est->id)
                ->where('estado', 'activo')
                ->pluck('curso_id');

            $totalCursos = $cursoIds->count();

            // Calcular promedio general del estudiante
            $promedio = DB::table('calificaciones')
                ->where('estudiante_id', $est->id)
                ->avg('porcentaje');

            $promedio = $promedio ? round($promedio, 2) : 0;
            $alerta = ($promedio > 0 && $promedio < 60) ? 'En Riesgo (<60%)' : 'Normal';

            fputcsv($output, [
                $est->nombre,
                $est->email,
                $totalCursos,
                $promedio > 0 ? "{$promedio}%" : 'Sin notas',
                $alerta
            ], ';');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_rendimiento_estudiantes.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }
}
