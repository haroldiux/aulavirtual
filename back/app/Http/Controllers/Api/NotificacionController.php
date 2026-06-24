<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notificaciones = Notificacion::where('usuario_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $notificaciones,
            'meta' => [
                'no_leidas' => $notificaciones->where('leida', false)->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'tipo' => ['required', 'string', 'max:50'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'icono' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'data' => ['nullable', 'array'],
            'ruta' => ['nullable', 'string', 'max:255'],
        ]);

        $notificacion = Notificacion::create($data);

        return response()->json([
            'data' => $notificacion,
        ], 201);
    }

    public function marcarLeida(Request $request, Notificacion $notificacion): JsonResponse
    {
        abort_unless((int) $notificacion->usuario_id === (int) $request->user()->id || $request->user()->esAdmin(), 403);

        $notificacion->update(['leida' => true]);

        return response()->json(['message' => 'Notificacion marcada como leida.']);
    }

    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        Notificacion::where('usuario_id', $request->user()->id)
            ->where('leida', false)
            ->update(['leida' => true]);

        return response()->json(['message' => 'Todas las notificaciones marcadas como leidas.']);
    }

    public function destroy(Request $request, Notificacion $notificacion): JsonResponse
    {
        abort_unless((int) $notificacion->usuario_id === (int) $request->user()->id || $request->user()->esAdmin(), 403);

        $notificacion->delete();

        return response()->json(['message' => 'Notificacion eliminada.']);
    }
}
