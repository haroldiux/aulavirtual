<?php

namespace App\Services;

use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Usuario;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StudentSyncService
{
    /**
     * Sincroniza matriculas de un curso desde el Sistema de Estudiantes.
     * Modo stub: matricula a los estudiantes disponibles en la BD.
     */
    public function sincronizarMatriculas(int $cursoId): array
    {
        $curso = Curso::findOrFail($cursoId);

        if (config('sisa.stub_enabled', true) || !config('sisa.api_url')) {
            return $this->stubSincronizar($curso);
        }

        try {
            $response = Http::withToken(config('sisa.api_token'))
                ->timeout(15)
                ->get(config('sisa.api_url').'/cursos/'.$curso->codigo.'/estudiantes', [
                    'gestion' => $curso->gestion,
                ]);

            if ($response->successful()) {
                $estudiantes = $response->json('data', []);
                return $this->matricularDesdeDatos($curso, $estudiantes);
            }
        } catch (\Exception $e) {
            Log::warning('StudentSync: error: '.$e->getMessage());
        }

        return $this->stubSincronizar($curso);
    }

    private function stubSincronizar(Curso $curso): array
    {
        $estudiantes = Usuario::where('rol', 'estudiante')->where('activo', true)->get();
        $matriculados = 0;

        foreach ($estudiantes as $est) {
            $matricula = Matricula::firstOrCreate(
                ['curso_id' => $curso->id, 'estudiante_id' => $est->id],
                ['estado' => 'activo', 'fecha_matricula' => now()->toDateString()]
            );
            if ($matricula->wasRecentlyCreated) $matriculados++;
        }

        $this->registrarSync('estudiantes', $matriculados.' estudiantes matriculados en '.$curso->codigo);

        return [
            'curso_id' => $curso->id,
            'estudiantes_matriculados' => $matriculados,
            'total_matriculas' => $estudiantes->count(),
            'mensaje' => 'Sincronizacion completada (stub)',
        ];
    }

    private function matricularDesdeDatos(Curso $curso, array $estudiantes): array
    {
        $matriculados = 0;
        foreach ($estudiantes as $data) {
            $usuario = Usuario::updateOrCreate(
                ['email' => $data['email']],
                [
                    'sisa_id' => $data['sisa_id'] ?? null,
                    'nombre' => $data['nombre'],
                    'rol' => 'estudiante',
                    'carrera_id' => $curso->carrera_id,
                    'sede_id' => $curso->sede_id,
                    'activo' => true,
                ]
            );

            $matricula = Matricula::firstOrCreate(
                ['curso_id' => $curso->id, 'estudiante_id' => $usuario->id],
                ['estado' => 'activo', 'fecha_matricula' => now()->toDateString()]
            );
            if ($matricula->wasRecentlyCreated) $matriculados++;
        }

        return [
            'curso_id' => $curso->id,
            'estudiantes_matriculados' => $matriculados,
            'total_matriculas' => count($estudiantes),
            'mensaje' => 'Sincronizacion completada',
        ];
    }

    private function registrarSync(string $sistema, string $mensaje): void
    {
        \App\Models\Configuracion::where('id', $sistema)->update([
            'valor' => json_encode(['estado' => 'online', 'ultimo_sync' => now()->toIso8601String(), 'ultimo_mensaje' => $mensaje]),
        ]);
    }
}
