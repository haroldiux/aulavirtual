<?php

namespace App\Services;

use App\Enums\EstadoCurso;
use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SisaSyncService
{
    /**
     * Obtiene las asignaturas disponibles en SISA para un docente (PAC).
     * Modo stub: devuelve datos de demostracion.
     */
    public function asignaturasDisponibles(int $docenteId, string $gestion = '1-2026'): array
    {
        if (config('sisa.stub_enabled', true) || !config('sisa.api_url')) {
            return $this->stubAsignaturas($docenteId, $gestion);
        }

        try {
            $response = Http::withToken(config('sisa.api_token'))
                ->timeout(10)
                ->get(config('sisa.api_url').'/docentes/'.$docenteId.'/asignaturas', [
                    'gestion' => $gestion,
                ]);

            if ($response->successful()) {
                return $response->json('data', []);
            }
        } catch (\Exception $e) {
            Log::warning('SisaSync: error al obtener asignaturas: '.$e->getMessage());
        }

        return [];
    }

    /**
     * Genera un curso desde una asignatura SISA (PAC).
     * Modo stub: crea el curso con datos de demostracion.
     */
    public function generarCursoDesdeSisa(string $codigo, string $gestion, int $docenteId): array
    {
        if (config('sisa.stub_enabled', true) || !config('sisa.api_url')) {
            return $this->stubGenerarCurso($codigo, $gestion, $docenteId);
        }

        try {
            $response = Http::withToken(config('sisa.api_token'))
                ->timeout(15)
                ->post(config('sisa.api_url').'/asignaturas/generar-curso', [
                    'codigo' => $codigo,
                    'gestion' => $gestion,
                    'docente_id' => $docenteId,
                ]);

            if ($response->successful()) {
                $data = $response->json('data', []);
                return $this->crearCursoDesdeDatosSisa($data, $docenteId);
            }
        } catch (\Exception $e) {
            Log::warning('SisaSync: error al generar curso: '.$e->getMessage());
        }

        return $this->stubGenerarCurso($codigo, $gestion, $docenteId);
    }

    private function stubAsignaturas(int $docenteId, string $gestion): array
    {
        return [
            ['codigo' => 'SIS-401', 'nombre' => 'Programacion Avanzada', 'gestion' => $gestion, 'pac_disponible' => true, 'carrera' => 'Ingenieria de Sistemas'],
            ['codigo' => 'SIS-305', 'nombre' => 'Base de Datos II', 'gestion' => $gestion, 'pac_disponible' => true, 'carrera' => 'Ingenieria de Sistemas'],
            ['codigo' => 'SIS-210', 'nombre' => 'Ingenieria de Software', 'gestion' => $gestion, 'pac_disponible' => true, 'carrera' => 'Ingenieria de Sistemas'],
            ['codigo' => 'SIS-410', 'nombre' => 'Inteligencia Artificial', 'gestion' => $gestion, 'pac_disponible' => false, 'carrera' => 'Ingenieria de Sistemas'],
        ];
    }

    private function stubGenerarCurso(string $codigo, string $gestion, int $docenteId): array
    {
        $pac = [
            'SIS-401' => ['nombre' => 'Programacion Avanzada', 'descripcion' => 'Patrones de diseno, arquitectura y buenas practicas en backend.', 'secciones' => 4],
            'SIS-305' => ['nombre' => 'Base de Datos II', 'descripcion' => 'Optimizacion, transacciones y diseno avanzado.', 'secciones' => 3],
            'SIS-210' => ['nombre' => 'Ingenieria de Software', 'descripcion' => 'Ciclo de vida del software, metodologias agiles.', 'secciones' => 4],
            'SIS-410' => ['nombre' => 'Inteligencia Artificial', 'descripcion' => 'Machine learning, redes neuronales, NLP.', 'secciones' => 5],
        ];

        $datos = $pac[$codigo] ?? ['nombre' => 'Curso '.$codigo, 'descripcion' => 'Curso importado desde SISA.', 'secciones' => 3];

        $curso = Curso::updateOrCreate(
            ['codigo' => $codigo, 'gestion' => $gestion],
            [
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'],
                'docente_id' => $docenteId,
                'carrera_id' => 1,
                'sede_id' => 1,
                'estado' => EstadoCurso::Borrador->value,
                'config' => ['generado_desde_sisa' => true, 'pac_gestion' => $gestion],
            ]
        );

        // Generar secciones base del PAC
        $nombresUnidades = ['Unidad I - Introduccion', 'Unidad II - Desarrollo', 'Unidad III - Aplicacion', 'Unidad IV - Evaluacion', 'Unidad V - Proyecto'];
        for ($i = 0; $i < $datos['secciones']; $i++) {
            $curso->secciones()->firstOrCreate(
                ['curso_id' => $curso->id, 'titulo' => $nombresUnidades[$i] ?? "Unidad ".($i + 1)],
                ['orden' => $i + 1, 'visible' => true]
            );
        }

        $this->registrarSync('sisa', 'Curso '.$codigo.' generado desde SISA');

        return [
            'curso_id' => $curso->id,
            'codigo' => $codigo,
            'secciones_creadas' => $datos['secciones'],
            'mensaje' => 'Curso generado desde SISA (stub)',
        ];
    }

    private function crearCursoDesdeDatosSisa(array $data, int $docenteId): array
    {
        $curso = Curso::updateOrCreate(
            ['codigo' => $data['codigo'] ?? 'SISA-'.time(), 'gestion' => $data['gestion'] ?? '1-2026'],
            [
                'nombre' => $data['nombre'] ?? 'Curso SISA',
                'descripcion' => $data['descripcion'] ?? '',
                'docente_id' => $docenteId,
                'carrera_id' => $data['carrera_id'] ?? 1,
                'sede_id' => $data['sede_id'] ?? 1,
                'estado' => EstadoCurso::Borrador->value,
            ]
        );

        return [
            'curso_id' => $curso->id,
            'secciones_creadas' => $data['secciones'] ?? 0,
            'mensaje' => 'Curso generado desde SISA',
        ];
    }

    private function registrarSync(string $sistema, string $mensaje): void
    {
        \App\Models\Configuracion::where('id', $sistema)->update([
            'valor' => json_encode(['estado' => 'online', 'ultimo_sync' => now()->toIso8601String(), 'ultimo_mensaje' => $mensaje]),
        ]);
    }

    public function bancoPreguntas(string $asignaturaCodigo): array
    {
        $bancos = [
            'SIS-401' => [
                [
                    'id' => 101,
                    'tipo' => 'opcion_multiple',
                    'enunciado' => '¿Qué patrón se enfoca en crear una única instancia de una clase?',
                    'opciones' => [
                        ['texto' => 'Prototype', 'es_correcta' => false],
                        ['texto' => 'Singleton', 'es_correcta' => true],
                        ['texto' => 'Builder', 'es_correcta' => false],
                        ['texto' => 'Factory', 'es_correcta' => false]
                    ],
                    'puntaje' => 20
                ],
                [
                    'id' => 102,
                    'tipo' => 'verdadero_falso',
                    'enunciado' => 'El patrón Observer permite notificar cambios a múltiples suscriptores automáticamente.',
                    'opciones' => [
                        ['texto' => 'Verdadero', 'es_correcta' => true],
                        ['texto' => 'Falso', 'es_correcta' => false]
                    ],
                    'puntaje' => 20
                ],
                [
                    'id' => 103,
                    'tipo' => 'opcion_multiple',
                    'enunciado' => '¿Cuál es la función del patrón Decorator?',
                    'opciones' => [
                        ['texto' => 'Añadir responsabilidades a objetos dinámicamente', 'es_correcta' => true],
                        ['texto' => 'Definir una familia de algoritmos', 'es_correcta' => false],
                        ['texto' => 'Proporcionar una interfaz unificada', 'es_correcta' => false]
                    ],
                    'puntaje' => 20
                ]
            ],
            'SIS-305' => [
                [
                    'id' => 201,
                    'tipo' => 'opcion_multiple',
                    'enunciado' => '¿Qué propiedad de las transacciones ACID asegura que los datos sean guardados permanentemente?',
                    'opciones' => [
                        ['texto' => 'Atomicity', 'es_correcta' => false],
                        ['texto' => 'Consistency', 'es_correcta' => false],
                        ['texto' => 'Isolation', 'es_correcta' => false],
                        ['texto' => 'Durability', 'es_correcta' => true]
                    ],
                    'puntaje' => 25
                ],
                [
                    'id' => 202,
                    'tipo' => 'verdadero_falso',
                    'enunciado' => 'Un índice agrupado (clustered index) ordena físicamente las filas de la tabla en el disco.',
                    'opciones' => [
                        ['texto' => 'Verdadero', 'es_correcta' => true],
                        ['texto' => 'Falso', 'es_correcta' => false]
                    ],
                    'puntaje' => 25
                ]
            ]
        ];

        return $bancos[$asignaturaCodigo] ?? [
            [
                'id' => 901,
                'tipo' => 'opcion_multiple',
                'enunciado' => '¿Cuál es la principal ventaja de utilizar pruebas unitarias?',
                'opciones' => [
                    ['texto' => 'Detectar errores temprano en el desarrollo', 'es_correcta' => true],
                    ['texto' => 'Aumentar la velocidad de carga en producción', 'es_correcta' => false],
                    ['texto' => 'Reemplazar por completo el testing manual', 'es_correcta' => false]
                ],
                'puntaje' => 50
            ],
            [
                'id' => 902,
                'tipo' => 'verdadero_falso',
                'enunciado' => 'La refactorización altera el comportamiento externo del código para hacerlo más legible.',
                'opciones' => [
                    ['texto' => 'Verdadero', 'es_correcta' => false],
                    ['texto' => 'Falso', 'es_correcta' => true]
                ],
                'puntaje' => 50
            ]
        ];
    }
}
