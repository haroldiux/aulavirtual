<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Usuario;
use App\Services\SisaAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly SisaAuthService $sisa) {}

    public function login(LoginRequest $request): JsonResponse
    {
        if ($request->esSso()) {
            return $this->loginSso($request->string('sisa_token')->toString());
        }

        if ($request->esLocal()) {
            return $this->loginLocal($request->input('email'), $request->input('password'));
        }

        throw ValidationException::withMessages([
            'sisa_token' => ['Debe enviar un token SISA o credenciales (email/password) validas.'],
        ]);
    }

    private function loginSso(string $sisaToken): JsonResponse
    {
        $datos = $this->sisa->validarToken($sisaToken);

        if (! $datos) {
            return response()->json([
                'error' => true,
                'message' => 'Token SISA invalido.',
            ], 401);
        }

        $usuario = $this->upsertDesdeSisa($datos);

        return $this->emitirToken($usuario);
    }

    private function loginLocal(string $email, string $password): JsonResponse
    {
        $usuario = Usuario::where('email', $email)->first();

        if (! $usuario || ! Hash::check($password, (string) $usuario->getAuthPassword())) {
            return response()->json([
                'error' => true,
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        if (! $usuario->activo) {
            return response()->json([
                'error' => true,
                'message' => 'Usuario inactivo.',
            ], 403);
        }

        return $this->emitirToken($usuario);
    }

    private function upsertDesdeSisa(array $datos): Usuario
    {
        return Usuario::updateOrCreate(
            ['email' => $datos['email']],
            [
                'sisa_id' => $datos['sisa_id'] ?? null,
                'nombre' => $datos['nombre'],
                'rol' => $this->sisa->normalizarRol($datos['rol'] ?? null)->value,
                'carrera_id' => $datos['carrera_id'] ?? null,
                'sede_id' => $datos['sede_id'] ?? null,
                'activo' => true,
                'ultimo_sync' => now(),
            ]
        );
    }

    private function emitirToken(Usuario $usuario): JsonResponse
    {
        $usuario->tokens()->where('name', 'lms')->delete();
        $token = $usuario->createToken('lms', ['*'], now()->addDays(7))->plainTextToken;

        return response()->json([
            'data' => [
                'usuario' => UsuarioResource::make($usuario),
                'token' => $token,
                'rol' => $usuario->rol?->value,
            ],
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => UsuarioResource::make($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesion cerrada.',
        ]);
    }
}