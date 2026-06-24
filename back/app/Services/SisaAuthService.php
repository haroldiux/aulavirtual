<?php

namespace App\Services;

use App\Enums\Rol;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SisaAuthService
 *
 * Valida un token SSO de SISA contra el sistema externo y devuelve los datos
 * basicos del usuario. Mientras no se obtengan las credenciales reales de SISA
 * (SISA_API_URL / SISA_API_TOKEN), funciona en modo "stub" con un par de
 * tokens de demostracion para permitir el flujo completo del frontend.
 *
 * Cuando se activen las credenciales reales, basta con:
 *   - SISA_STUB_ENABLED=false
 *   - SISA_API_URL=https://sisa.unitepc.edu/api
 *   - SISA_API_TOKEN=<token estatico de programas>
 */
class SisaAuthService
{
    /**
     * Token SISA -> datos simulados del usuario (solo modo stub).
     */
    private const STUB_USUARIOS = [
        'sisa-token-docente-1' => [
            'sisa_id' => 5001,
            'nombre' => 'Dr. Carlos Mendoza',
            'email' => 'carlos.mendoza@unitepc.edu',
            'rol' => 'docente',
            'carrera_id' => 1,
            'sede_id' => 1,
        ],
        'sisa-token-docente-2' => [
            'sisa_id' => 5002,
            'nombre' => 'Ing. Lucia Fernández',
            'email' => 'lucia.fernandez@unitepc.edu',
            'rol' => 'docente',
            'carrera_id' => 1,
            'sede_id' => 1,
        ],
        'sisa-token-estudiante-1' => [
            'sisa_id' => 1001,
            'nombre' => 'Ana Vargas',
            'email' => 'ana.vargas@estudiante.unitepc.edu',
            'rol' => 'estudiante',
            'carrera_id' => 1,
            'sede_id' => 1,
        ],
        'sisa-token-director-1' => [
            'sisa_id' => 3001,
            'nombre' => 'Lic. Roberto Suárez',
            'email' => 'roberto.suarez@unitepc.edu',
            'rol' => 'director',
            'carrera_id' => 1,
            'sede_id' => 1,
        ],
    ];

    /**
     * Valida el token SISA y retorna los datos del usuario o null.
     *
     * @return array{name:sisa_id:int,nombre:string,email:string,rol:string,carrera_id:?int,sede_id:?int}|null
     */
    public function validarToken(string $sisaToken): ?array
    {
        if ($this->stubEnabled()) {
            return $this->validarStub($sisaToken);
        }

        return $this->validarReal($sisaToken);
    }

    private function stubEnabled(): bool
    {
        return (bool) config('sisa.stub_enabled', true)
            || empty(config('sisa.api_url'));
    }

    private function validarStub(string $sisaToken): ?array
    {
        return self::STUB_USUARIOS[$sisaToken] ?? null;
    }

    /**
     * Llamada HTTP al endpoint real de SISA (SSO).
     * El contrato exacto debe confirmarse con UNITEPC; aqui se asume
     * GET {SISA_API_URL}/auth/verify con Authorization: Bearer {sisa_token}
     * y un token estatico de la app en X-Programas-Token.
     */
    private function validarReal(string $sisaToken): ?array
    {
        $url = rtrim((string) config('sisa.api_url'), '/').'/auth/verify';

        try {
            $response = Http::withToken($sisaToken)
                ->withHeaders(['X-Programas-Token' => (string) config('sisa.api_token')])
                ->timeout(10)
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('SisaAuthService: fallo de conexion con SISA', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (! is_array($data) || empty($data['email'])) {
            return null;
        }

        return [
            'sisa_id' => $data['sisa_id'] ?? null,
            'nombre' => $data['nombre'] ?? ($data['name'] ?? 'Usuario SISA'),
            'email' => $data['email'],
            'rol' => $data['rol'] ?? 'estudiante',
            'carrera_id' => $data['carrera_id'] ?? null,
            'sede_id' => $data['sede_id'] ?? null,
        ];
    }

    /**
     * Normaliza el rol devuelto por SISA al enum interno.
     */
    public function normalizarRol(?string $rol): Rol
    {
        return Rol::tryFrom((string) $rol) ?? Rol::Estudiante;
    }
}