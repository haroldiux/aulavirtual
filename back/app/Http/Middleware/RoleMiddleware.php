<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return response()->json([
                'error' => true,
                'message' => 'No autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $rolActual = $usuario->rol?->value;

        if (! $rolActual || ! in_array($rolActual, $roles, true)) {
            return response()->json([
                'error' => true,
                'message' => 'No tienes permiso para acceder a este recurso.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}