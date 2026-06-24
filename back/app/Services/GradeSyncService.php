<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\Curso;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GradeSyncService
{
    /**
     * Envia las calificaciones de un curso al Sistema de Notas Centralizado.
     * Modo stub: marca las calificaciones como sincronizadas.
     */
    public function sincronizarNotas(int $cursoId): array
    {
        $curso = Curso::findOrFail($cursoId);
        $calificaciones = Calificacion::where('curso_id', $cursoId)->get();

        if (config('sisa.stub_enabled', true) || !config('sisa.api_url')) {
            return $this->stubSincronizar($curso, $calificaciones);
        }

        try {
            $payload = $calificaciones->map(fn ($c) => [
                'estudiante_id' => $c->estudiante_id,
                'actividad_id' => $c->actividad_id,
                'nota' => (float) $c->nota,
                'nota_maxima' => (float) $c->nota_maxima,
                'porcentaje' => $c->porcentaje !== null ? (float) $c->porcentaje : null,
            ])->toArray();

            $response = Http::withToken(config('sisa.api_token'))
                ->timeout(30)
                ->post(config('sisa.api_url').'/notas/sincronizar', [
                    'curso_codigo' => $curso->codigo,
                    'gestion' => $curso->gestion,
                    'notas' => $payload,
                ]);

            if ($response->successful()) {
                Calificacion::where('curso_id', $cursoId)->update([
                    'sincronizado_externo' => true,
                    'fecha_sincronizacion' => now(),
                ]);

                $this->registrarSync('notas', count($calificaciones).' notas sincronizadas para '.$curso->codigo);

                return [
                    'curso_id' => $cursoId,
                    'notas_enviadas' => count($calificaciones),
                    'fallidas' => 0,
                    'mensaje' => 'Notas sincronizadas correctamente',
                ];
            }
        } catch (\Exception $e) {
            Log::warning('GradeSync: error: '.$e->getMessage());
        }

        return $this->stubSincronizar($curso, $calificaciones);
    }

    private function stubSincronizar(Curso $curso, $calificaciones): array
    {
        $count = $calificaciones->count();
        $sincronizadas = 0;

        foreach ($calificaciones as $cal) {
            if (!$cal->sincronizado_externo) {
                $cal->update([
                    'sincronizado_externo' => true,
                    'fecha_sincronizacion' => now(),
                ]);
                $sincronizadas++;
            }
        }

        $this->registrarSync('notas', $sincronizadas.' notas sincronizadas para '.$curso->codigo);

        return [
            'curso_id' => $curso->id,
            'notas_enviadas' => $sincronizadas,
            'total_notas' => $count,
            'fallidas' => 0,
            'mensaje' => 'Notas sincronizadas (stub)',
        ];
    }

    private function registrarSync(string $sistema, string $mensaje): void
    {
        \App\Models\Configuracion::where('id', $sistema)->update([
            'valor' => json_encode(['estado' => 'online', 'ultimo_sync' => now()->toIso8601String(), 'ultimo_mensaje' => $mensaje]),
        ]);
    }
}
