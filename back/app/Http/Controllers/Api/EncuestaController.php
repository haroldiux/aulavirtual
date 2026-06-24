<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\EncuestaRespuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EncuestaController extends Controller
{
    public function miRespuesta(Request $request, Actividad $actividad): JsonResponse
    {
        $respuesta = EncuestaRespuesta::where('actividad_id', $actividad->id)
            ->where('estudiante_id', $request->user()->id)
            ->first();

        return response()->json([
            'data' => $respuesta ? [
                'respondida' => true,
                'respuestas' => $respuesta->respuestas,
                'fecha' => $respuesta->created_at?->toIso8601String(),
            ] : ['respondida' => false],
        ]);
    }

    public function responder(Request $request, Actividad $actividad): JsonResponse
    {
        abort_unless($actividad->tipo?->value === 'encuesta', 422, 'La actividad no es una encuesta.');

        $data = $request->validate([
            'respuestas' => ['required', 'array'],
        ]);

        $respuesta = EncuestaRespuesta::updateOrCreate(
            [
                'actividad_id' => $actividad->id,
                'estudiante_id' => $request->user()->id,
            ],
            [
                'respuestas' => $data['respuestas'],
            ]
        );

        return response()->json([
            'data' => [
                'respondida' => true,
                'respuestas' => $respuesta->respuestas,
                'fecha' => $respuesta->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function resultados(Request $request, Actividad $actividad): JsonResponse
    {
        $respuestas = EncuestaRespuesta::where('actividad_id', $actividad->id)->get();
        $preguntas = $actividad->config['preguntas'] ?? [];

        $resumen = [];
        foreach ($preguntas as $idx => $pregunta) {
            $resumen[$idx] = [
                'enunciado' => $pregunta['enunciado'] ?? '',
                'tipo' => $pregunta['tipo'] ?? 'opcion_multiple',
                'total' => 0,
                'opciones' => [],
            ];
            foreach ($pregunta['opciones'] ?? [] as $opIdx => $op) {
                $resumen[$idx]['opciones'][$opIdx] = [
                    'texto' => is_array($op) ? ($op['texto'] ?? $op) : $op,
                    'conteo' => 0,
                ];
            }
        }

        foreach ($respuestas as $r) {
            foreach ($r->respuestas as $pIdx => $valor) {
                if (!isset($resumen[$pIdx])) continue;
                $resumen[$pIdx]['total']++;
                if (is_array($valor)) {
                    foreach ($valor as $v) {
                        if (isset($resumen[$pIdx]['opciones'][$v])) {
                            $resumen[$pIdx]['opciones'][$v]['conteo']++;
                        }
                    }
                } elseif (isset($resumen[$pIdx]['opciones'][$valor])) {
                    $resumen[$pIdx]['opciones'][$valor]['conteo']++;
                }
            }
        }

        return response()->json([
            'data' => [
                'total_respondientes' => $respuestas->count(),
                'preguntas' => $resumen,
            ],
        ]);
    }
}
