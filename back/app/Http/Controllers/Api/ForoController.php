<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ForoHiloResource;
use App\Models\Actividad;
use App\Models\ForoHilo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForoController extends Controller
{
    public function hilos(Request $request, Actividad $actividad): JsonResponse
    {
        $hilos = $actividad->tipo?->value === 'foro'
            ? ForoHilo::where('actividad_id', $actividad->id)
                ->with(['autor:id,nombre,avatar', 'respuestas.autor:id,nombre,avatar'])
                ->withCount('respuestas')
                ->latest()
                ->get()
            : collect();

        return response()->json([
            'data' => ForoHiloResource::collection($hilos),
        ]);
    }

    public function crearHilo(Request $request, Actividad $actividad): JsonResponse
    {
        abort_unless($actividad->tipo?->value === 'foro', 422, 'La actividad no es un foro.');

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'contenido' => ['required', 'string'],
            'anonimo' => ['nullable', 'boolean'],
        ]);

        $hilo = ForoHilo::create([
            'actividad_id' => $actividad->id,
            'autor_id' => $request->user()->id,
            'titulo' => $data['titulo'],
            'contenido' => $data['contenido'],
            'anonimo' => $data['anonimo'] ?? ($actividad->config['anonimo'] ?? false),
        ]);

        $hilo->load(['autor:id,nombre,avatar', 'respuestas']);

        return response()->json([
            'data' => ForoHiloResource::make($hilo),
        ], 201);
    }

    public function crearRespuesta(Request $request, ForoHilo $hilo): JsonResponse
    {
        $data = $request->validate([
            'contenido' => ['required', 'string'],
            'anonimo' => ['nullable', 'boolean'],
        ]);

        $respuesta = $hilo->respuestas()->create([
            'autor_id' => $request->user()->id,
            'contenido' => $data['contenido'],
            'anonimo' => $data['anonimo'] ?? ($hilo->actividad->config['anonimo'] ?? false),
        ]);

        $respuesta->load('autor:id,nombre,avatar');

        return response()->json([
            'data' => [
                'id' => $respuesta->id,
                'hilo_id' => $respuesta->hilo_id,
                'autor_id' => $respuesta->autor_id,
                'contenido' => $respuesta->contenido,
                'anonimo' => $respuesta->anonimo,
                'autor' => $respuesta->anonimo ? null : [
                    'id' => $respuesta->autor->id,
                    'nombre' => $respuesta->autor->nombre,
                    'avatar' => $respuesta->autor->avatar,
                ],
                'fecha' => $respuesta->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function eliminarHilo(Request $request, ForoHilo $hilo): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->esAdmin() || (int) $hilo->autor_id === (int) $user->id,
            403,
            'No tienes permiso para eliminar este hilo.'
        );

        $hilo->delete();

        return response()->json(['message' => 'Hilo eliminado.']);
    }
}
