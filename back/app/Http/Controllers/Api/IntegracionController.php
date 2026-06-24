<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use App\Models\Curso;
use App\Services\GradeSyncService;
use App\Services\SisaSyncService;
use App\Services\StudentSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegracionController extends Controller
{
    public function estado(): JsonResponse
    {
        $sistemas = ['sisa', 'estudiantes', 'notas'];
        $integraciones = [];

        foreach ($sistemas as $id) {
            $config = Configuracion::find($id);
            $valor = $config?->valor ?? [];
            $integraciones[] = [
                'id' => $id,
                'nombre' => ucfirst($id),
                'estado' => $valor['estado'] ?? 'desconocido',
                'ultimo_sync' => $valor['ultimo_sync'] ?? null,
                'ultimo_mensaje' => $valor['ultimo_mensaje'] ?? null,
                'stub' => config('sisa.stub_enabled', true),
            ];
        }

        $politica = Configuracion::find('politica_aprobacion');

        return response()->json([
            'data' => [
                'integraciones' => $integraciones,
                'politica_aprobacion' => $politica?->valor ?? ['minimo' => 60],
                'sisa_stub' => config('sisa.stub_enabled', true),
            ],
        ]);
    }

    public function asignaturasSisa(Request $request): JsonResponse
    {
        $request->validate([
            'docente_id' => ['nullable', 'integer'],
            'gestion' => ['nullable', 'string'],
        ]);

        $docenteId = $request->integer('docente_id', $request->user()->id);
        $gestion = $request->string('gestion', '1-2026')->toString();

        $service = new SisaSyncService();
        $asignaturas = $service->asignaturasDisponibles($docenteId, $gestion);

        return response()->json([
            'data' => $asignaturas,
        ]);
    }

    public function generarCursoSisa(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:50'],
            'gestion' => ['nullable', 'string', 'max:20'],
            'docente_id' => ['nullable', 'integer'],
        ]);

        $docenteId = $data['docente_id'] ?? $request->user()->id;
        $gestion = $data['gestion'] ?? '1-2026';

        $service = new SisaSyncService();
        $resultado = $service->generarCursoDesdeSisa($data['codigo'], $gestion, $docenteId);

        return response()->json([
            'data' => $resultado,
        ], 201);
    }

    public function sincronizarEstudiantes(Request $request, Curso $curso): JsonResponse
    {
        $service = new StudentSyncService();
        $resultado = $service->sincronizarMatriculas($curso->id);

        return response()->json([
            'data' => $resultado,
        ]);
    }

    public function sincronizarNotas(Request $request, Curso $curso): JsonResponse
    {
        $service = new GradeSyncService();
        $resultado = $service->sincronizarNotas($curso->id);

        return response()->json([
            'data' => $resultado,
        ]);
    }
}
