<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\CuestionarioIntento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CuestionarioController extends Controller
{
    public function intentos(Request $request, Actividad $actividad): JsonResponse
    {
        $intentos = CuestionarioIntento::where('actividad_id', $actividad->id)
            ->where('estudiante_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        $config = $actividad->config ?? [];
        $intentosMaximos = $config['intentos_maximos'] ?? 1;

        return response()->json([
            'data' => [
                'intentos' => $intentos->map(fn ($i) => [
                    'id' => $i->id,
                    'nota' => (float) $i->nota,
                    'fecha' => $i->created_at?->toIso8601String(),
                ]),
                'intentos_realizados' => $intentos->count(),
                'intentos_maximos' => $intentosMaximos,
                'mejor_nota' => $intentos->max('nota') ? (float) $intentos->max('nota') : null,
            ],
        ]);
    }

    public function intentar(Request $request, Actividad $actividad): JsonResponse
    {
        abort_unless($actividad->tipo?->value === 'cuestionario', 422, 'La actividad no es un cuestionario.');

        $config = $actividad->config ?? [];
        $intentosMaximos = $config['intentos_maximos'] ?? 1;

        $yaRealizados = CuestionarioIntento::where('actividad_id', $actividad->id)
            ->where('estudiante_id', $request->user()->id)
            ->count();

        abort_unless($yaRealizados < $intentosMaximos, 422, 'No te quedan intentos disponibles.');

        $data = $request->validate([
            'respuestas' => ['required', 'array'],
        ]);

        // Calificación automática
        $preguntas = $config['preguntas'] ?? [];
        $notaMaxima = (float) ($actividad->nota_maxima ?? 100);
        $totalPreguntas = count($preguntas);
        $correctas = 0;

        foreach ($preguntas as $idx => $pregunta) {
            $respuestaEstudiante = $data['respuestas'][$idx] ?? null;
            if ($respuestaEstudiante === null) continue;

            $opcionCorrecta = null;
            foreach ($pregunta['opciones'] ?? [] as $opIdx => $op) {
                if (is_array($op) && ($op['es_correcta'] ?? false)) {
                    $opcionCorrecta = $opIdx;
                    break;
                }
            }
            if ($opcionCorrecta !== null && (int) $respuestaEstudiante === $opcionCorrecta) {
                $correctas++;
            }
        }

        $nota = $totalPreguntas > 0 ? round(($correctas / $totalPreguntas) * $notaMaxima, 2) : 0;

        $intento = CuestionarioIntento::create([
            'actividad_id' => $actividad->id,
            'estudiante_id' => $request->user()->id,
            'respuestas' => $data['respuestas'],
            'nota' => $nota,
            'intentos_maximos' => $intentosMaximos,
            'fecha_inicio' => now(),
            'fecha_fin' => now(),
        ]);

        // Registrar la entrega en la tabla de entregas
        $entrega = \App\Models\Entrega::updateOrCreate(
            [
                'actividad_id' => $actividad->id,
                'estudiante_id' => $request->user()->id,
            ],
            [
                'contenido' => [
                    'respuestas' => $data['respuestas'],
                    'nota' => $nota,
                    'intento_id' => $intento->id,
                ],
                'fecha_entrega' => now(),
                'estado' => \App\Enums\EstadoEntrega::Revisado,
                'intento_cuestionario_id' => $intento->id,
            ]
        );

        // Registrar la calificacion correspondiente
        \App\Models\Calificacion::updateOrCreate(
            ['entrega_id' => $entrega->id],
            [
                'actividad_id' => $actividad->id,
                'estudiante_id' => $request->user()->id,
                'curso_id' => $actividad->seccion->curso_id,
                'nota' => $nota,
                'nota_maxima' => $notaMaxima,
                'porcentaje' => $notaMaxima > 0 ? round(($nota / $notaMaxima) * 100, 2) : 0,
                'retroalimentacion' => "Calificacion automatica - Intento #".($yaRealizados + 1)." ({$correctas} de {$totalPreguntas} correctas).",
                'calificado_por' => null,
            ]
        );

        return response()->json([
            'data' => [
                'id' => $intento->id,
                'nota' => (float) $intento->nota,
                'nota_maxima' => $notaMaxima,
                'correctas' => $correctas,
                'total_preguntas' => $totalPreguntas,
                'intentos_realizados' => $yaRealizados + 1,
                'intentos_maximos' => $intentosMaximos,
                'fecha' => $intento->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
