<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MensajeController extends Controller
{
    public function contactos(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->esEstudiante()) {
            // Estudiante puede mensajear a los docentes de sus cursos
            $docentesIds = $user->cursosComoEstudiante()->pluck('docente_id')->unique();
            $contactos = Usuario::whereIn('id', $docentesIds)->get();
        } else {
            // Docente/Director/Admin puede mensajear a todos los estudiantes de sus cursos u otros
            $cursosIds = $user->esDocente() 
                ? $user->cursosComoDocente()->pluck('id') 
                : Curso::pluck('id');
            $estudiantesIds = \App\Models\Matricula::whereIn('curso_id', $cursosIds)->pluck('estudiante_id')->unique();
            $contactos = Usuario::whereIn('id', $estudiantesIds)->get();
        }

        return response()->json([
            'data' => $contactos->map(fn($u) => [
                'id' => $u->id,
                'nombre' => $u->nombre,
                'email' => $u->email,
                'rol' => $u->rol,
                'avatar' => $u->avatar,
            ]),
        ]);
    }

    public function conversaciones(Request $request): JsonResponse
    {
        $user = $request->user();

        // Obtener conversaciones del usuario ordenadas por última actualización
        $conversaciones = $user->conversaciones()
            ->with(['participantes', 'mensajes' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->get();

        $datos = $conversaciones->map(function ($c) use ($user) {
            $ultimo = $c->mensajes->first();
            $otro = $c->participantes->first(fn ($p) => (int)$p->id !== (int)$user->id);

            // Contador de no leídos
            $noLeidos = $c->mensajes()->where('remitente_id', '!=', $user->id)
                ->where('leido', false)
                ->count();

            return [
                'id' => $c->id,
                'asunto' => $c->asunto ?? ($otro ? $otro->nombre : 'Chat sin nombre'),
                'curso_id' => $c->curso_id,
                'no_leidos' => $noLeidos,
                'updated_at' => $c->updated_at->toIso8601String(),
                'ultimo_mensaje' => $ultimo ? [
                    'contenido' => $ultimo->contenido,
                    'remitente_id' => $ultimo->remitente_id,
                    'leido' => (bool)$ultimo->leido,
                    'created_at' => $ultimo->created_at->toIso8601String(),
                ] : null,
                'otro_participante' => $otro ? [
                    'id' => $otro->id,
                    'nombre' => $otro->nombre,
                    'email' => $otro->email,
                    'rol' => $otro->rol,
                    'avatar' => $otro->avatar,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $datos,
        ]);
    }

    public function mensajes(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversacion->participantes->contains($user->id), 403, 'No autorizado.');

        $mensajes = $conversacion->mensajes()
            ->with('remitente')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'conversacion_id' => $m->conversacion_id,
                    'remitente_id' => $m->remitente_id,
                    'remitente_nombre' => $m->remitente->nombre,
                    'remitente_avatar' => $m->remitente->avatar,
                    'contenido' => $m->contenido,
                    'adjuntos' => $m->adjuntos,
                    'leido' => (bool)$m->leido,
                    'created_at' => $m->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $mensajes,
        ]);
    }

    public function enviarMensaje(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversacion->participantes->contains($user->id), 403, 'No autorizado.');

        $data = $request->validate([
            'contenido' => ['required', 'string'],
            'adjuntos' => ['nullable', 'array'],
        ]);

        $mensaje = Mensaje::create([
            'conversacion_id' => $conversacion->id,
            'remitente_id' => $user->id,
            'contenido' => $data['contenido'],
            'adjuntos' => $data['adjuntos'] ?? null,
            'leido' => false,
        ]);

        // Actualizar timestamp de la conversación para ordenar primero
        $conversacion->touch();

        return response()->json([
            'data' => [
                'id' => $mensaje->id,
                'conversacion_id' => $mensaje->conversacion_id,
                'remitente_id' => $mensaje->remitente_id,
                'remitente_nombre' => $user->nombre,
                'remitente_avatar' => $user->avatar,
                'contenido' => $mensaje->contenido,
                'adjuntos' => $mensaje->adjuntos,
                'leido' => false,
                'created_at' => $mensaje->created_at->toIso8601String(),
            ],
        ], 201);
    }

    public function iniciarConversacion(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'curso_id' => ['nullable', 'integer', 'exists:cursos,id'],
            'asunto' => ['nullable', 'string', 'max:255'],
            'mensaje' => ['required', 'string'],
        ]);

        // Evitar chat consigo mismo
        abort_if((int)$data['usuario_id'] === (int)$user->id, 422, 'No puedes iniciar un chat contigo mismo.');

        // Buscar si ya existe una conversación directa privada sin curso
        $conversacion = $user->conversaciones()
            ->whereNull('curso_id')
            ->whereHas('participantes', function ($q) use ($data) {
                $q->where('usuarios.id', $data['usuario_id']);
            })
            ->first();

        if (!$conversacion) {
            $conversacion = Conversacion::create([
                'curso_id' => $data['curso_id'] ?? null,
                'asunto' => $data['asunto'] ?? null,
            ]);

            $conversacion->participantes()->attach([$user->id, $data['usuario_id']]);
        }

        $mensaje = Mensaje::create([
            'conversacion_id' => $conversacion->id,
            'remitente_id' => $user->id,
            'contenido' => $data['mensaje'],
            'leido' => false,
        ]);

        $conversacion->touch();

        return response()->json([
            'data' => [
                'conversacion_id' => $conversacion->id,
                'mensaje_id' => $mensaje->id,
            ],
        ], 201);
    }

    public function marcarLeida(Request $request, Conversacion $conversacion): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversacion->participantes->contains($user->id), 403, 'No autorizado.');

        $conversacion->mensajes()
            ->where('remitente_id', '!=', $user->id)
            ->where('leido', false)
            ->update(['leido' => true]);

        return response()->json([
            'message' => 'Conversación marcada como leída.',
        ]);
    }
}
