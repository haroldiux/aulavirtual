<?php

namespace Database\Seeders;

use App\Enums\EstadoEntrega;
use App\Enums\EstadoCurso;
use App\Enums\Rol;
use App\Enums\TipoActividad;
use App\Models\Actividad;
use App\Models\Calificacion;
use App\Models\Configuracion;
use App\Models\Curso;
use App\Models\CuestionarioIntento;
use App\Models\EncuestaRespuesta;
use App\Models\Entrega;
use App\Models\ForoHilo;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\Seccion;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seedando Aula Virtual UNITEPC...');

        $usuarios = $this->seedUsuarios();
        $this->seedCursos($usuarios);
        $this->seedParticipaciones($usuarios);
        $this->seedEntregasCalificaciones($usuarios);
        $this->seedNotificaciones($usuarios);
        $this->seedConfiguraciones();
        $this->seedCalendarioMensajeria($usuarios);
        $this->seedPlantillas($usuarios);

        $this->command->info('Seed finalizado.');
        $this->command->line('Credenciales locales (password): clave-aula-2026');
    }

    private function seedUsuarios(): array
    {
        $clave = bcrypt('clave-aula-2026');

        $definiciones = [
            ['sisa_id' => 5001, 'nombre' => 'Dr. Carlos Mendoza', 'email' => 'carlos.mendoza@unitepc.edu', 'rol' => Rol::Docente, 'carrera_id' => 1],
            ['sisa_id' => 5002, 'nombre' => 'Ing. Lucia Fernandez', 'email' => 'lucia.fernandez@unitepc.edu', 'rol' => Rol::Docente, 'carrera_id' => 1],
            ['sisa_id' => 3001, 'nombre' => 'Lic. Roberto Suarez', 'email' => 'roberto.suarez@unitepc.edu', 'rol' => Rol::Director, 'carrera_id' => 1],
            ['sisa_id' => null, 'nombre' => 'Administrador UNITEPC', 'email' => 'admin@unitepc.edu', 'rol' => Rol::Admin, 'carrera_id' => null],
            ['sisa_id' => 1001, 'nombre' => 'Ana Vargas', 'email' => 'ana.vargas@estudiante.unitepc.edu', 'rol' => Rol::Estudiante, 'carrera_id' => 1],
            ['sisa_id' => 1002, 'nombre' => 'Bruno Calle', 'email' => 'bruno.calle@estudiante.unitepc.edu', 'rol' => Rol::Estudiante, 'carrera_id' => 1],
            ['sisa_id' => 1003, 'nombre' => 'Camila Paz', 'email' => 'camila.paz@estudiante.unitepc.edu', 'rol' => Rol::Estudiante, 'carrera_id' => 1],
            ['sisa_id' => 1004, 'nombre' => 'Diego Rojas', 'email' => 'diego.rojas@estudiante.unitepc.edu', 'rol' => Rol::Estudiante, 'carrera_id' => 1],
            ['sisa_id' => 1005, 'nombre' => 'Eliana Quispe', 'email' => 'eliana.quispe@estudiante.unitepc.edu', 'rol' => Rol::Estudiante, 'carrera_id' => 1],
            ['sisa_id' => 1006, 'nombre' => 'Felix Mamani', 'email' => 'felix.mamani@estudiante.unitepc.edu', 'rol' => Rol::Estudiante, 'carrera_id' => 1],
        ];

        $map = [];
        foreach ($definiciones as $d) {
            $map[$d['nombre']] = Usuario::updateOrCreate(
                ['email' => $d['email']],
                [
                    ...Arr::except($d, ['nombre']),
                    'nombre' => $d['nombre'],
                    'password' => $clave,
                    'avatar' => null,
                    'sede_id' => 1,
                    'activo' => true,
                ]
            );
        }

        return $map;
    }

    private function seedCursos(array $usuarios): void
    {
        $docente1 = $usuarios['Dr. Carlos Mendoza'];
        $docente2 = $usuarios['Ing. Lucia Fernandez'];

        $cursosData = [
            [
                'docente' => $docente1,
                'codigo' => 'SIS-401',
                'nombre' => 'Programacion Avanzada',
                'descripcion' => 'Patrones de diseno, arquitectura y buenas practicas en backend.',
                'gestion' => '1-2026',
                'estado' => EstadoCurso::Publicado,
                'secciones' => [
                    ['titulo' => 'Unidad I - Introduccion', 'actividades' => [
                        ['tipo' => TipoActividad::Tarea, 'titulo' => 'Tarea 1: Refactor', 'config' => [
                            'fecha_entrega' => now()->addDays(7)->toIso8601String(),
                            'fecha_limite' => now()->addDays(14)->toIso8601String(),
                            'archivos_permitidos' => ['pdf', 'zip', 'txt'],
                            'tamano_max_mb' => 25,
                            'instrucciones' => 'Refactoriza el codigo legacy aplicando patrones.',
                        ]],
                        ['tipo' => TipoActividad::Leccion, 'titulo' => 'Leccion: Principios SOLID', 'config' => [
                            'contenido_html' => '<h2>Principios SOLID</h2><p>Los principios SOLID son cinco reglas de diseno...</p>',
                            'archivos_adjuntos' => [],
                            'seguimiento_requerido' => true,
                        ]],
                        ['tipo' => TipoActividad::Cuestionario, 'titulo' => 'Cuestionario inicial', 'config' => [
                            'tiempo_limite_minutos' => 15,
                            'intentos_maximos' => 2,
                            'aleatorio' => false,
                            'preguntas' => [
                                ['tipo' => 'opcion_multiple', 'enunciado' => 'Que significa la S en SOLID?',
                                 'opciones' => [
                                    ['texto' => 'Single Responsibility', 'es_correcta' => true],
                                    ['texto' => 'Simple Architecture', 'es_correcta' => false],
                                    ['texto' => 'Standard Output', 'es_correcta' => false],
                                 ], 'puntaje' => 50],
                                ['tipo' => 'opcion_multiple', 'enunciado' => 'El patron Factory pertenece a que categoria?',
                                 'opciones' => [
                                    ['texto' => 'Creacional', 'es_correcta' => true],
                                    ['texto' => 'Estructural', 'es_correcta' => false],
                                    ['texto' => 'Comportamiento', 'es_correcta' => false],
                                 ], 'puntaje' => 50],
                            ],
                        ]],
                    ]],
                    ['titulo' => 'Unidad II - Patrones', 'actividades' => [
                        ['tipo' => TipoActividad::Foro, 'titulo' => 'Foro: Patrones creacionales', 'config' => [
                            'tipo_foro' => 'normal',
                            'moderado' => false,
                            'anonimo' => false,
                        ]],
                        ['tipo' => TipoActividad::Tarea, 'titulo' => 'Tarea 2: Implementar Factory', 'config' => [
                            'fecha_entrega' => now()->addDays(10)->toIso8601String(),
                            'fecha_limite' => now()->addDays(20)->toIso8601String(),
                            'archivos_permitidos' => ['zip', 'rar'],
                            'tamano_max_mb' => 50,
                            'instrucciones' => 'Implementa el patron Factory Method en Java o PHP.',
                        ]],
                    ]],
                ],
            ],
            [
                'docente' => $docente2,
                'codigo' => 'SIS-305',
                'nombre' => 'Base de Datos II',
                'descripcion' => 'Optimizacion, transacciones y diseno avanzado.',
                'gestion' => '1-2026',
                'estado' => EstadoCurso::Publicado,
                'secciones' => [
                    ['titulo' => 'Unidad I - Transacciones', 'actividades' => [
                        ['tipo' => TipoActividad::Leccion, 'titulo' => 'Leccion: ACID', 'config' => [
                            'contenido_html' => '<h2>Propiedades ACID</h2><p>Atomicidad, Consistencia, Aislamiento, Durabilidad.</p>',
                            'archivos_adjuntos' => [],
                            'seguimiento_requerido' => true,
                        ]],
                        ['tipo' => TipoActividad::Cuestionario, 'titulo' => 'Cuestionario SQL', 'config' => [
                            'tiempo_limite_minutos' => 10,
                            'intentos_maximos' => 3,
                            'aleatorio' => true,
                            'preguntas' => [
                                ['tipo' => 'opcion_multiple', 'enunciado' => 'Que comando SQL se usa para iniciar una transaccion?',
                                 'opciones' => [
                                    ['texto' => 'BEGIN TRANSACTION', 'es_correcta' => true],
                                    ['texto' => 'START DATABASE', 'es_correcta' => false],
                                    ['texto' => 'INIT SQL', 'es_correcta' => false],
                                 ], 'puntaje' => 50],
                                ['tipo' => 'opcion_multiple', 'enunciado' => 'El nivel de aislamiento mas estricto es?',
                                 'opciones' => [
                                    ['texto' => 'SERIALIZABLE', 'es_correcta' => true],
                                    ['texto' => 'READ UNCOMMITTED', 'es_correcta' => false],
                                    ['texto' => 'READ COMMITTED', 'es_correcta' => false],
                                 ], 'puntaje' => 50],
                            ],
                        ]],
                    ]],
                ],
            ],
            [
                'docente' => $docente1,
                'codigo' => 'MAT-201',
                'nombre' => 'Calculo I (borrador)',
                'descripcion' => 'Limites y derivadas.',
                'gestion' => '1-2026',
                'estado' => EstadoCurso::Borrador,
                'secciones' => [
                    ['titulo' => 'Unidad I', 'actividades' => [
                        ['tipo' => TipoActividad::Encuesta, 'titulo' => 'Encuesta diagnostica', 'config' => [
                            'anonima' => true,
                            'fecha_cierre' => now()->addDays(30)->toIso8601String(),
                            'preguntas' => [
                                ['tipo' => 'opcion_multiple', 'enunciado' => 'Que tan comodo te sientes con el calculo?',
                                 'opciones' => ['Muy comodo', 'Comodo', 'Neutral', 'Incomodo', 'Muy incomodo'],
                                 'obligatorio' => true],
                                ['tipo' => 'escala', 'enunciado' => 'Califica tu nivel de preparacion (1-5)',
                                 'opciones' => ['1', '2', '3', '4', '5'],
                                 'obligatorio' => true],
                            ],
                        ]],
                    ]],
                ],
            ],
        ];

        $estudiantes = collect($usuarios)->filter(fn (Usuario $u) => $u->esEstudiante())->values();

        foreach ($cursosData as $c) {
            $curso = Curso::updateOrCreate(
                ['codigo' => $c['codigo'], 'gestion' => $c['gestion']],
                [
                    'nombre' => $c['nombre'],
                    'descripcion' => $c['descripcion'],
                    'docente_id' => $c['docente']->id,
                    'carrera_id' => 1,
                    'sede_id' => 1,
                    'estado' => $c['estado']->value,
                    'config' => null,
                ]
            );

            $ordenSeccion = 1;
            foreach ($c['secciones'] as $s) {
                $seccion = Seccion::firstOrCreate(
                    ['curso_id' => $curso->id, 'titulo' => $s['titulo']],
                    ['orden' => $ordenSeccion++, 'visible' => true]
                );

                $ordenAct = 1;
                foreach ($s['actividades'] as $a) {
                    Actividad::firstOrCreate(
                        ['seccion_id' => $seccion->id, 'titulo' => $a['titulo']],
                        [
                            'tipo' => $a['tipo']->value,
                            'orden' => $ordenAct++,
                            'tiene_nota' => ! in_array($a['tipo']->value, ['leccion', 'encuesta'], true),
                            'nota_maxima' => 100,
                            'peso' => 1.0,
                            'config' => $a['config'] ?? null,
                            'visible' => true,
                        ]
                    );
                }
            }

            // Matricula a los primeros 4 estudiantes de demostracion
            foreach ($estudiantes->take(4) as $estudiante) {
                Matricula::firstOrCreate(
                    ['curso_id' => $curso->id, 'estudiante_id' => $estudiante->id],
                    ['estado' => 'activo', 'fecha_matricula' => now()->toDateString()]
                );
            }
        }
    }

    private function seedParticipaciones(array $usuarios): void
    {
        $estudiantes = collect($usuarios)->filter(fn (Usuario $u) => $u->esEstudiante())->values();
        $docente1 = $usuarios['Dr. Carlos Mendoza'] ?? null;

        // Foro: Patrones creacionales (curso SIS-401) — 2 hilos con respuestas
        $foro = Actividad::where('titulo', 'Foro: Patrones creacionales')->first();
        if ($foro && $estudiantes->count() >= 2) {
            $hilo1 = ForoHilo::firstOrCreate(
                ['actividad_id' => $foro->id, 'titulo' => 'Duda sobre Singleton'],
                [
                    'autor_id' => $estudiantes->first()->id,
                    'contenido' => 'Alguien me podria explicar cuando usar Singleton y cuando no?',
                    'fijado' => false,
                    'anonimo' => false,
                ]
            );
            $hilo1->respuestas()->firstOrCreate(
                ['autor_id' => $estudiantes->get(1)->id, 'contenido' => 'El Singleton es util cuando necesitas una unica instancia, pero evitalo si no es estrictamente necesario.'],
                ['anonimo' => false]
            );

            ForoHilo::firstOrCreate(
                ['actividad_id' => $foro->id, 'titulo' => 'Ejemplo de Factory Method'],
                [
                    'autor_id' => $estudiantes->get(1)->id,
                    'contenido' => 'Comparto un ejemplo de Factory Method en PHP que me funciono.',
                    'fijado' => false,
                    'anonimo' => false,
                ]
            );
        }

        // Encuesta diagnostica (curso MAT-201) — 2 respuestas
        $encuesta = Actividad::where('titulo', 'Encuesta diagnostica')->first();
        if ($encuesta) {
            foreach ($estudiantes->take(2) as $idx => $est) {
                EncuestaRespuesta::firstOrCreate(
                    ['actividad_id' => $encuesta->id, 'estudiante_id' => $est->id],
                    ['respuestas' => [0 => $idx, 1 => (string) ($idx + 3)]]
                );
            }
        }

        // Cuestionario inicial (curso SIS-401) — 1 intento con nota 100
        $cuestionario = Actividad::where('titulo', 'Cuestionario inicial')->first();
        if ($cuestionario && $estudiantes->isNotEmpty()) {
            CuestionarioIntento::firstOrCreate(
                ['actividad_id' => $cuestionario->id, 'estudiante_id' => $estudiantes->first()->id],
                [
                    'respuestas' => [0 => 0, 1 => 0],
                    'nota' => 100,
                    'intentos_maximos' => 2,
                    'fecha_inicio' => now()->subHour(),
                    'fecha_fin' => now()->subMinutes(45),
                ]
            );
        }
    }

    private function seedEntregasCalificaciones(array $usuarios): void
    {
        $estudiantes = collect($usuarios)->filter(fn (Usuario $u) => $u->esEstudiante())->values();
        $docente1 = $usuarios['Dr. Carlos Mendoza'] ?? null;

        // Entrega + calificacion para "Tarea 1: Refactor" (curso SIS-401, actividad tipo tarea)
        $tarea1 = Actividad::where('titulo', 'Tarea 1: Refactor')->first();
        if ($tarea1 && $estudiantes->count() >= 2) {
            $est1 = $estudiantes->first();
            $est2 = $estudiantes->get(1);

            $entrega1 = Entrega::firstOrCreate(
                ['actividad_id' => $tarea1->id, 'estudiante_id' => $est1->id],
                [
                    'contenido' => ['texto' => 'Aqui mi refactor del codigo legacy aplicando Factory y Singleton.', 'archivos' => []],
                    'fecha_entrega' => now()->subDays(2),
                    'estado' => EstadoEntrega::Revisado,
                ]
            );

            Calificacion::firstOrCreate(
                ['entrega_id' => $entrega1->id],
                [
                    'actividad_id' => $tarea1->id,
                    'estudiante_id' => $est1->id,
                    'curso_id' => $tarea1->seccion->curso_id,
                    'nota' => 85,
                    'nota_maxima' => 100,
                    'porcentaje' => 85,
                    'retroalimentacion' => 'Buen trabajo. El patron Factory esta bien aplicado. Mejorar nombres de variables.',
                    'calificado_por' => $docente1?->id,
                ]
            );

            // Entrega sin calificar (pendiente de revision)
            Entrega::firstOrCreate(
                ['actividad_id' => $tarea1->id, 'estudiante_id' => $est2->id],
                [
                    'contenido' => ['texto' => 'Mi entrega de la tarea.', 'archivos' => []],
                    'fecha_entrega' => now()->subHours(5),
                    'estado' => EstadoEntrega::Entregado,
                ]
            );
        }

        // Entrega + calificacion para "Cuestionario inicial" (auto-calificado via Fase B)
        $cuestionario = Actividad::where('titulo', 'Cuestionario inicial')->first();
        if ($cuestionario && $estudiantes->isNotEmpty()) {
            $est = $estudiantes->first();
            $entrega = Entrega::firstOrCreate(
                ['actividad_id' => $cuestionario->id, 'estudiante_id' => $est->id],
                [
                    'contenido' => ['respuestas' => [0 => 0, 1 => 0], 'nota' => 100],
                    'fecha_entrega' => now()->subHour(),
                    'estado' => EstadoEntrega::Revisado,
                ]
            );

            Calificacion::firstOrCreate(
                ['entrega_id' => $entrega->id],
                [
                    'actividad_id' => $cuestionario->id,
                    'estudiante_id' => $est->id,
                    'curso_id' => $cuestionario->seccion->curso_id,
                    'nota' => 100,
                    'nota_maxima' => 100,
                    'porcentaje' => 100,
                    'retroalimentacion' => 'Calificacion automatica - todas las respuestas correctas.',
                    'calificado_por' => $docente1?->id,
                ]
            );
        }

        // Calificacion para "Cuestionario SQL" (curso SIS-305)
        $cuestionarioSql = Actividad::where('titulo', 'Cuestionario SQL')->first();
        if ($cuestionarioSql && $estudiantes->count() >= 2) {
            $est = $estudiantes->get(1);
            $entrega = Entrega::firstOrCreate(
                ['actividad_id' => $cuestionarioSql->id, 'estudiante_id' => $est->id],
                [
                    'contenido' => ['respuestas' => [0 => 0, 1 => 2], 'nota' => 50],
                    'fecha_entrega' => now()->subDay(),
                    'estado' => EstadoEntrega::Revisado,
                ]
            );

            Calificacion::firstOrCreate(
                ['entrega_id' => $entrega->id],
                [
                    'actividad_id' => $cuestionarioSql->id,
                    'estudiante_id' => $est->id,
                    'curso_id' => $cuestionarioSql->seccion->curso_id,
                    'nota' => 50,
                    'nota_maxima' => 100,
                    'porcentaje' => 50,
                    'retroalimentacion' => 'Calificacion automatica - 1 de 2 correctas.',
                    'calificado_por' => $usuarios['Ing. Lucia Fernandez']?->id,
                ]
            );
        }
    }

    private function seedNotificaciones(array $usuarios): void
    {
        $estudiantes = collect($usuarios)->filter(fn (Usuario $u) => $u->esEstudiante())->values();
        $docente1 = $usuarios['Dr. Carlos Mendoza'] ?? null;

        $notificaciones = [
            [
                'usuario_id' => $estudiantes->first()->id ?? 5,
                'tipo' => 'calificacion',
                'titulo' => 'Nueva calificacion disponible',
                'descripcion' => 'Tu tarea "Tarea 1: Refactor" ha sido calificada con 85/100',
                'icono' => 'grade',
                'color' => 'positive',
                'data' => ['actividad_id' => 1, 'nota' => 85],
                'ruta' => '/estudiante/notas',
                'leida' => false,
            ],
            [
                'usuario_id' => $estudiantes->first()->id ?? 5,
                'tipo' => 'recordatorio',
                'titulo' => 'Recordatorio: fecha limite proxima',
                'descripcion' => 'La "Tarea 2: Implementar Factory" vence en 3 dias',
                'icono' => 'schedule',
                'color' => 'warning',
                'data' => ['actividad_id' => 5],
                'ruta' => '/estudiante/cursos',
                'leida' => false,
            ],
            [
                'usuario_id' => $docente1?->id ?? 1,
                'tipo' => 'entrega',
                'titulo' => 'Nueva entrega para revisar',
                'descripcion' => 'Bruno Calle entrego la "Tarea 1: Refactor"',
                'icono' => 'assignment_turned_in',
                'color' => 'info',
                'data' => ['estudiante_id' => 6, 'actividad_id' => 1],
                'ruta' => '/docente/calificar',
                'leida' => false,
            ],
            [
                'usuario_id' => $docente1?->id ?? 1,
                'tipo' => 'sistema',
                'titulo' => 'Sincronizacion SISA completada',
                'descripcion' => 'Las matriculas se sincronizaron correctamente',
                'icono' => 'sync',
                'color' => 'positive',
                'data' => [],
                'ruta' => '/admin/gestion',
                'leida' => true,
            ],
        ];

        foreach ($notificaciones as $notif) {
            Notificacion::firstOrCreate(
                ['usuario_id' => $notif['usuario_id'], 'titulo' => $notif['titulo']],
                $notif
            );
        }
    }

    private function seedConfiguraciones(): void
    {
        foreach ([
            'sisa' => ['estado' => 'online', 'ultimo_sync' => now()->toIso8601String()],
            'estudiantes' => ['estado' => 'online'],
            'notas' => ['estado' => 'online'],
            'politica_aprobacion' => ['minimo' => 60],
        ] as $id => $valor) {
            Configuracion::updateOrCreate(['id' => $id], ['valor' => $valor, 'estado' => 'online']);
        }
    }

    private function seedCalendarioMensajeria(array $usuarios): void
    {
        $docente1 = $usuarios['Dr. Carlos Mendoza'] ?? null;
        $docente2 = $usuarios['Ing. Lucia Fernandez'] ?? null;
        $estudiante1 = $usuarios['Ana Vargas'] ?? null;
        $estudiante2 = $usuarios['Bruno Calle'] ?? null;

        // Cursos
        $curso1 = Curso::where('codigo', 'SIS-401')->first();
        $curso2 = Curso::where('codigo', 'SIS-305')->first();

        // 1. Seed eventos de calendario
        if ($curso1 && $docente1) {
            // Clases regulares los lunes
            \App\Models\EventoCalendario::firstOrCreate(
                ['titulo' => 'Clase teórica: Arquitectura Software', 'curso_id' => $curso1->id],
                [
                    'descripcion' => 'Revisión de patrones estructurales y MVC.',
                    'tipo' => 'clase',
                    'fecha_inicio' => Carbon::now()->startOfWeek()->addHours(18), // 18:00
                    'fecha_fin' => Carbon::now()->startOfWeek()->addHours(20), // 20:00
                    'todo_el_dia' => false,
                    'creado_por' => $docente1->id,
                ]
            );

            // Examen parcial manual
            \App\Models\EventoCalendario::firstOrCreate(
                ['titulo' => 'Primer Examen Parcial', 'curso_id' => $curso1->id],
                [
                    'descripcion' => 'Examen presencial en laboratorios.',
                    'tipo' => 'evaluacion',
                    'fecha_inicio' => Carbon::now()->addDays(5)->setTime(18, 0, 0),
                    'fecha_fin' => Carbon::now()->addDays(5)->setTime(21, 0, 0),
                    'todo_el_dia' => false,
                    'creado_por' => $docente1->id,
                ]
            );
        }

        // Evento global/institucional
        \App\Models\EventoCalendario::firstOrCreate(
            ['titulo' => 'Aniversario UNITEPC (Feriado)'],
            [
                'descripcion' => 'Actividades conmemorativas por el aniversario institucional.',
                'tipo' => 'evento_institucional',
                'fecha_inicio' => Carbon::now()->addDays(3)->startOfDay(),
                'fecha_fin' => Carbon::now()->addDays(3)->endOfDay(),
                'todo_el_dia' => true,
                'creado_por' => null,
            ]
        );

        // 2. Seed conversaciones y mensajes
        if ($docente1 && $estudiante1) {
            // Conversación privada Docente - Estudiante (Tutoría)
            $conv = \App\Models\Conversacion::create([
                'asunto' => 'Consulta sobre Tarea 1',
            ]);
            $conv->participantes()->attach([$docente1->id, $estudiante1->id]);

            \App\Models\Mensaje::create([
                'conversacion_id' => $conv->id,
                'remitente_id' => $estudiante1->id,
                'contenido' => 'Buenas tardes Dr. Mendoza, tengo una duda sobre la aplicación del patrón Factory en el ejercicio 3.',
                'leido' => true,
                'created_at' => Carbon::now()->subHours(4),
            ]);

            \App\Models\Mensaje::create([
                'conversacion_id' => $conv->id,
                'remitente_id' => $docente1->id,
                'contenido' => 'Hola Ana. Recuerda que la clase creadora no debe conocer las clases concretas de los productos, sino usar la interfaz común.',
                'leido' => false,
                'created_at' => Carbon::now()->subHours(2),
            ]);
        }

        if ($docente2 && $estudiante2) {
            // Conversación privada Docente 2 - Estudiante 2
            $conv2 = \App\Models\Conversacion::create([
                'asunto' => 'Duda Proyecto BD',
            ]);
            $conv2->participantes()->attach([$docente2->id, $estudiante2->id]);

            \App\Models\Mensaje::create([
                'conversacion_id' => $conv2->id,
                'remitente_id' => $estudiante2->id,
                'contenido' => 'Ing. Lucía, ¿el informe del proyecto de BD debe incluir el diagrama relacional completo?',
                'leido' => true,
                'created_at' => Carbon::now()->subDays(1),
            ]);

            \App\Models\Mensaje::create([
                'conversacion_id' => $conv2->id,
                'remitente_id' => $docente2->id,
                'contenido' => 'Sí Bruno, es indispensable adjuntar el diagrama físico y las sentencias DDL.',
                'leido' => true,
                'created_at' => Carbon::now()->subHours(20),
            ]);
        }
    }

    private function seedPlantillas(array $usuarios): void
    {
        $docente1 = $usuarios['Dr. Carlos Mendoza'] ?? null;
        if (!$docente1) return;

        // Plantilla 1: Actividad Tarea
        \App\Models\Plantilla::create([
            'docente_id' => $docente1->id,
            'categoria' => 'actividad',
            'tipo' => 'tarea',
            'nombre' => 'Plantilla de Proyecto Final de Programacion',
            'descripcion' => 'Estructura recomendada para proyectos finales de programacion, incluyendo entregables y rubrica base.',
            'datos' => [
                'titulo' => 'Proyecto Final - Desarrollo del Sistema',
                'tiene_nota' => true,
                'nota_maxima' => 100,
                'peso' => 1.0,
                'config' => [
                    'instrucciones' => 'El proyecto final consiste en disenar y desarrollar un sistema completo que cumpla con los requisitos estudiados. Debe incluir: 1. Diagrama de arquitectura, 2. Repositorio de codigo, 3. Manual de usuario.',
                    'archivos_permitidos' => ['pdf', 'zip'],
                    'tamano_max_mb' => 50,
                    'reintentos' => false,
                ]
            ],
            'uso_count' => 12,
            'publica' => true
        ]);

        // Plantilla 2: Rubrica
        \App\Models\Plantilla::create([
            'docente_id' => $docente1->id,
            'categoria' => 'rubrica',
            'tipo' => 'rubrica',
            'nombre' => 'Rubrica Estandar de Proyectos Tecnologicos',
            'descripcion' => 'Evaluacion de proyectos en base a funcionalidad, calidad de codigo, presentacion y documentacion.',
            'datos' => [
                'criterios' => [
                    [
                        'nombre' => 'Funcionalidad',
                        'descripcion' => 'El sistema cumple con todos los requisitos funcionales planteados.',
                        'puntos' => 40,
                        'niveles' => [
                            ['nombre' => 'Excelente', 'puntos' => 40, 'descripcion' => 'Funciona al 100%'],
                            ['nombre' => 'Aceptable', 'puntos' => 25, 'descripcion' => 'Errores menores'],
                            ['nombre' => 'Insuficiente', 'puntos' => 10, 'descripcion' => 'No cumple requerimientos clave']
                        ]
                    ],
                    [
                        'nombre' => 'Calidad de Codigo',
                        'descripcion' => 'Aplicacion de patrones de diseno, legibilidad y buenas practicas.',
                        'puntos' => 30,
                        'niveles' => [
                            ['nombre' => 'Excelente', 'puntos' => 30, 'descripcion' => 'SOLID y patrones correctamente aplicados'],
                            ['nombre' => 'Regular', 'puntos' => 15, 'descripcion' => 'Legible pero sin patrones definidos'],
                            ['nombre' => 'Pobre', 'puntos' => 5, 'descripcion' => 'Codigo desordenado y repetitivo']
                        ]
                    ],
                    [
                        'nombre' => 'Documentacion',
                        'descripcion' => 'Informe tecnico, diagramas y manuales.',
                        'puntos' => 30,
                        'niveles' => [
                            ['nombre' => 'Completo', 'puntos' => 30, 'descripcion' => 'Todos los manuales y diagramas incluidos'],
                            ['nombre' => 'Incompleto', 'puntos' => 15, 'descripcion' => 'Faltan diagramas o manuales'],
                            ['nombre' => 'Sin Documentacion', 'puntos' => 0, 'descripcion' => 'No presenta informes']
                        ]
                    ]
                ]
            ],
            'uso_count' => 8,
            'publica' => true
        ]);

        // Plantilla 3: Cuestionario
        \App\Models\Plantilla::create([
            'docente_id' => $docente1->id,
            'categoria' => 'preguntas',
            'tipo' => 'cuestionario',
            'nombre' => 'Examen Parcial de Teoria General',
            'descripcion' => 'Banco de preguntas teoricas para examenes parciales del area de sistemas.',
            'datos' => [
                'tiempo_limite_minutos' => 20,
                'intentos_maximos' => 1,
                'aleatorio' => true,
                'preguntas' => [
                    [
                        'tipo' => 'opcion_multiple',
                        'enunciado' => '¿Cual de los siguientes patrones es de tipo Creacional?',
                        'opciones' => [
                            ['texto' => 'Singleton', 'es_correcta' => true],
                            ['texto' => 'Adapter', 'es_correcta' => false],
                            ['texto' => 'Observer', 'es_correcta' => false]
                        ],
                        'puntaje' => 10
                    ],
                    [
                        'tipo' => 'opcion_multiple',
                        'enunciado' => '¿Que principio SOLID establece que una clase debe ser abierta para la extension pero cerrada para la modificacion?',
                        'opciones' => [
                            ['texto' => 'Principio Abierto/Cerrado (OCP)', 'es_correcta' => true],
                            ['texto' => 'Principio de Responsabilidad Unica (SRP)', 'es_correcta' => false],
                            ['texto' => 'Principio de Inversion de Dependencias (DIP)', 'es_correcta' => false]
                        ],
                        'puntaje' => 10
                    ]
                ]
            ],
            'uso_count' => 5,
            'publica' => false
        ]);

        // Plantilla de Curso 1: Estructura por Competencias (UNITEPC)
        \App\Models\Plantilla::create([
            'docente_id' => $docente1->id,
            'categoria' => 'curso',
            'tipo' => 'curso',
            'nombre' => 'Estructura por Competencias (UNITEPC)',
            'descripcion' => 'Diseño por competencias de la materia. Dividido en 4 Unidades e incluye diagnostico, foros de debate y proyectos de hito.',
            'datos' => [
                [
                    'titulo' => 'Unidad I - Fundamentación y Diagnóstico',
                    'descripcion' => 'Introducción a la materia y diagnóstico de conocimientos previos.',
                    'actividades' => [
                        [
                            'tipo' => 'encuesta',
                            'titulo' => 'Evaluación Diagnóstica',
                            'descripcion' => 'Responde sinceramente para medir tus conocimientos base.',
                            'tiene_nota' => false,
                            'nota_maxima' => 0,
                            'peso' => 0,
                            'config' => ['anonima' => true]
                        ],
                        [
                            'tipo' => 'leccion',
                            'titulo' => 'Introducción y Silabo de la Materia',
                            'descripcion' => 'Lectura del plan analítico, silabo y ponderaciones.',
                            'tiene_nota' => false,
                            'nota_maxima' => 0,
                            'peso' => 0,
                            'config' => ['contenido_html' => '<h2>Sílabo de la Materia</h2><p>Bienvenido. En esta unidad revisaremos la introducción...</p>']
                        ]
                    ]
                ],
                [
                    'titulo' => 'Unidad II - Desarrollo de Saberes',
                    'descripcion' => 'Avance principal de los conceptos teóricos y prácticos base.',
                    'actividades' => [
                        [
                            'tipo' => 'leccion',
                            'titulo' => 'Conceptos Clave y Material de Estudio',
                            'descripcion' => 'Diapositivas y lecturas complementarias.',
                            'tiene_nota' => false,
                            'nota_maxima' => 0,
                            'peso' => 0,
                            'config' => ['contenido_html' => '<h2>Material de la Unidad II</h2><p>Estudia el siguiente contenido sobre buenas prácticas...</p>']
                        ],
                        [
                            'tipo' => 'tarea',
                            'titulo' => 'Práctica de Aplicación Teórica',
                            'descripcion' => 'Aplica los conceptos en base al caso de estudio proporcionado.',
                            'tiene_nota' => true,
                            'nota_maxima' => 100,
                            'peso' => 1.0,
                            'config' => ['instrucciones' => 'Resuelve el problema práctico en un informe en formato PDF y súbelo al sistema.']
                        ]
                    ]
                ],
                [
                    'titulo' => 'Unidad III - Hito Evaluativo Intermedio',
                    'descripcion' => 'Control de comprensión intermedio mediante evaluaciones grupales y cuestionarios.',
                    'actividades' => [
                        [
                            'tipo' => 'foro',
                            'titulo' => 'Foro Académico: Debate Temático',
                            'descripcion' => 'Participa discutiendo las ventajas y desventajas de la metodología.',
                            'tiene_nota' => true,
                            'nota_maxima' => 20,
                            'peso' => 0.5,
                            'config' => ['tipo_foro' => 'debate']
                        ],
                        [
                            'tipo' => 'cuestionario',
                            'titulo' => 'Examen del Primer Hito',
                            'descripcion' => 'Evaluación de los conceptos asimilados en las unidades I y II.',
                            'tiene_nota' => true,
                            'nota_maxima' => 100,
                            'peso' => 1.5,
                            'config' => ['tiempo_limite_minutos' => 30, 'intentos_maximos' => 1]
                        ]
                    ]
                ],
                [
                    'titulo' => 'Unidad IV - Proyecto Integrador y Cierre',
                    'descripcion' => 'Consolidación de lo aprendido mediante el desarrollo del proyecto final de la materia.',
                    'actividades' => [
                        [
                            'tipo' => 'tarea',
                            'titulo' => 'Entrega del Proyecto Integrador',
                            'descripcion' => 'Código fuente, manual técnico e informe ejecutivo.',
                            'tiene_nota' => true,
                            'nota_maxima' => 100,
                            'peso' => 2.0,
                            'config' => ['instrucciones' => 'Comprime tu código fuente y el reporte técnico en un archivo .zip y súbelo.']
                        ],
                        [
                            'tipo' => 'encuesta',
                            'titulo' => 'Encuesta de Satisfacción del Estudiante',
                            'descripcion' => 'Dinos tu opinión sobre la materia y el docente para seguir mejorando.',
                            'tiene_nota' => false,
                            'nota_maxima' => 0,
                            'peso' => 0,
                            'config' => ['anonima' => true]
                        ]
                    ]
                ]
            ],
            'uso_count' => 32,
            'publica' => true
        ]);

        // Plantilla de Curso 2: Estructura Modular Semanal
        \App\Models\Plantilla::create([
            'docente_id' => $docente1->id,
            'categoria' => 'curso',
            'tipo' => 'curso',
            'nombre' => 'Estructura Modular Semanal (4 Semanas)',
            'descripcion' => 'Organización lineal y cronológica. Adecuada para asignaturas prácticas o intensivas de un mes.',
            'datos' => [
                [
                    'titulo' => 'Semana 1 - Fundamentos e Inicio',
                    'descripcion' => 'Introducción y nivelación rápida de la materia.',
                    'actividades' => [
                        [
                            'tipo' => 'leccion',
                            'titulo' => 'Guía Semanal de Aprendizaje',
                            'descripcion' => 'Lección introductoria del curso y directrices.',
                            'tiene_nota' => false,
                            'nota_maxima' => 0,
                            'peso' => 0,
                            'config' => ['contenido_html' => '<h2>Semana 1</h2><p>Estudia la guía de aprendizaje...</p>']
                        ],
                        [
                            'tipo' => 'tarea',
                            'titulo' => 'Entregable Semanal N° 1',
                            'descripcion' => 'Práctica introductoria individual.',
                            'tiene_nota' => true,
                            'nota_maxima' => 100,
                            'peso' => 1.0,
                            'config' => ['instrucciones' => 'Realiza los ejercicios de la sección 1.']
                        ]
                    ]
                ],
                [
                    'titulo' => 'Semana 2 - Profundización Teórica',
                    'descripcion' => 'Desarrollo de los aspectos teóricos complejos de la materia.',
                    'actividades' => [
                        [
                            'tipo' => 'leccion',
                            'titulo' => 'Conceptos Avanzados de la Materia',
                            'descripcion' => 'Material interactivo de lectura.',
                            'tiene_nota' => false,
                            'nota_maxima' => 0,
                            'peso' => 0,
                            'config' => ['contenido_html' => '<h2>Semana 2</h2><p>Teoría detallada de la semana...</p>']
                        ],
                        [
                            'tipo' => 'foro',
                            'titulo' => 'Foro de Discusión Semanal',
                            'descripcion' => 'Comparte tus dudas y reflexiones.',
                            'tiene_nota' => false,
                            'nota_maxima' => 0,
                            'peso' => 0,
                            'config' => ['tipo_foro' => 'normal']
                        ],
                        [
                            'tipo' => 'tarea',
                            'titulo' => 'Entregable Semanal N° 2',
                            'descripcion' => 'Práctica sobre los temas avanzados.',
                            'tiene_nota' => true,
                            'nota_maxima' => 100,
                            'peso' => 1.0,
                            'config' => ['instrucciones' => 'Desarrolla el entregable 2.']
                        ]
                    ]
                ],
                [
                    'titulo' => 'Semana 3 - Taller Práctico Integrado',
                    'descripcion' => 'Laboratorio o taller grupal enfocado en el desarrollo aplicativo.',
                    'actividades' => [
                        [
                            'tipo' => 'leccion',
                            'titulo' => 'Casos de Estudio Reales',
                            'descripcion' => 'Material para la resolución del taller.',
                            'tiene_nota' => false,
                            'nota_maxima' => 0,
                            'peso' => 0,
                            'config' => ['contenido_html' => '<h2>Semana 3</h2><p>Casos prácticos de estudio...</p>']
                        ],
                        [
                            'tipo' => 'tarea',
                            'titulo' => 'Taller de Laboratorio Evaluado',
                            'descripcion' => 'Práctica en equipos de trabajo.',
                            'tiene_nota' => true,
                            'nota_maxima' => 100,
                            'peso' => 1.0,
                            'config' => ['instrucciones' => 'Resuelvan el taller en grupos de hasta 3 personas.']
                        ]
                    ]
                ],
                [
                    'titulo' => 'Semana 4 - Evaluación Final y Retroalimentación',
                    'descripcion' => 'Cierre del curso, examen y entrega final.',
                    'actividades' => [
                        [
                            'tipo' => 'cuestionario',
                            'titulo' => 'Examen Final Teórico',
                            'descripcion' => 'Cuestionario de 20 preguntas.',
                            'tiene_nota' => true,
                            'nota_maxima' => 100,
                            'peso' => 2.0,
                            'config' => ['tiempo_limite_minutos' => 45, 'intentos_maximos' => 1]
                        ],
                        [
                            'tipo' => 'tarea',
                            'titulo' => 'Presentación de Proyecto de Curso',
                            'descripcion' => 'Sube aquí las diapositivas y el código final de tu proyecto.',
                            'tiene_nota' => true,
                            'nota_maxima' => 100,
                            'peso' => 3.0,
                            'config' => ['instrucciones' => 'Adjunta tu presentación final.']
                        ]
                    ]
                ]
            ],
            'uso_count' => 18,
            'publica' => true
        ]);
    }
}