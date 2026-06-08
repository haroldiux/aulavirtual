# Plan: Plataforma LMS UNITEPC

> Documento de planificación para un nuevo proyecto independiente.
> Fecha: Junio 2026
> Stack: Vue 3 / Quasar + Laravel 12 + MySQL

---

## 1. Visión General

Plataforma LMS (Learning Management System) independiente, liviana y extensible, que se conecta vía API a 3 sistemas externos:

```
┌─────────────────────────────────────────────────────────┐
│                    NUEVO LMS UNITEPC                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │ Builder  │  │ Activid. │  │ Calific. │  │ Report. │ │
│  │ Curso    │  │ (plugin) │  │ (rúbric) │  │ Dashbo. │ │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬────┘ │
│       │              │              │              │      │
│  ┌────┴──────────────┴──────────────┴──────────────┴───┐ │
│  │              CAPA DE INTEGRACIÓN (Services)         │ │
│  │  SisaSync  │  StudentSync  │  GradeSync  │  Auth   │ │
│  └────┬───────┴──────┬────────┴──────┬──────┴────┬────┘ │
└───────┼────────────────┼───────────────┼────────────┼────┘
        │                │               │            │
   ┌────▼────┐   ┌───────▼──────┐  ┌─────▼─────┐ ┌──▼──┐
   │  SISA   │   │Sist. Estud. │  │Sist. Notas│ │ SSO │
   │ (existe)│   │  (externo)  │  │ (externo) │ │SISA │
   └─────────┘   └──────────────┘  └───────────┘ └─────┘
```

- **SISA** (existente): provee el PAC (estructura del curso: unidades, temas, logros), docentes, grupos, cronograma maestro.
- **Sistema de Estudiantes** (externo, API a definir): provee matrícula y datos de estudiantes por grupo.
- **Sistema de Notas Centralizado** (externo, API a definir): recibe calificaciones desde el LMS.
- **SSO SISA**: autenticación unificada vía tokens Sanctum.

---

## 2. Arquitectura Técnica

### 2.1 Stack Tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Backend | Laravel | 12 |
| Lenguaje backend | PHP | 8.2+ |
| ORM | Eloquent | — |
| Autenticación | Laravel Sanctum (SSO contra SISA) | — |
| Frontend | Vue.js | 3.5 |
| UI Framework | Quasar | 2.16 |
| Estado (store) | Pinia | 2.x |
| Build tool | Vite | — |
| Router | Vue Router | — |
| Base de datos | MySQL | 8.0 |
| File storage | S3-compatible / MinIO | — |
| Dev Tools | CodeGraph (AST), ESLint, Prettier, Vitest, Playwright | — |

### 2.2 Backend (Laravel 12)

```
back/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php            # Login SSO, perfil
│   │   ├── CursoController.php           # CRUD cursos + generación desde SISA
│   │   ├── SeccionController.php         # Unidades/secciones del curso
│   │   ├── ActividadController.php       # CRUD bloques de actividad
│   │   ├── TareaController.php           # Entregas, correcciones
│   │   ├── ForoController.php            # Hilos, respuestas
│   │   ├── CuestionarioController.php    # Banco, intentos, calificación auto
│   │   ├── EncuestaController.php        # Creación y respuestas
│   │   ├── CalificacionController.php    # Libro de calificaciones
│   │   ├── DashboardController.php       # Dashboards por rol
│   │   └── ReporteController.php         # Reportes
│   ├── Models/
│   │   ├── Curso.php                     # creado_desde_sisa, sisa_asignatura_id
│   │   ├── Seccion.php                   # Unidad dentro del curso
│   │   ├── Actividad.php                 # Polimórfico: Tarea|Foro|Cuestionario|...
│   │   ├── Tarea.php
│   │   ├── Foro.php
│   │   ├── Cuestionario.php
│   │   ├── Encuesta.php
│   │   ├── Leccion.php
│   │   ├── Entrega.php                   # Submission de estudiante
│   │   ├── Calificacion.php              # Nota por actividad/estudiante
│   │   ├── Matricula.php                 # Estudiante en curso
│   │   └── Usuario.php                   # Cache local de datos de usuario
│   ├── Services/
│   │   ├── SisaSyncService.php           # Consumir API SISA → generar cursos
│   │   ├── StudentSyncService.php        # Consumir API estudiantes → matricular
│   │   ├── GradeSyncService.php          # Enviar notas al sistema centralizado
│   │   ├── CursoBuilderService.php       # Lógica del builder
│   │   └── CalificacionService.php       # Cálculo de promedios, rúbricas
│   ├── Interfaces/
│   │   └── TipoActividad.php             # Interfaz común para actividades extensibles
│   └── Jobs/
│       ├── SyncCursosFromSisa.php        # Sincronización periódica
│       ├── SyncEstudiantesJob.php
│       └── SyncNotasJob.php
├── database/migrations/
└── routes/
    ├── api.php
    └── web.php
```

### 2.3 Frontend (Vue 3 / Quasar 2)

```
front/
├── src/
│   ├── pages/
│   │   ├── auth/LoginPage.vue
│   │   ├── docente/
│   │   │   ├── MisCursosPage.vue
│   │   │   ├── CursoBuilderPage.vue       # Builder del curso
│   │   │   ├── CalificarPage.vue
│   │   │   └── DashboardDocentePage.vue
│   │   ├── estudiante/
│   │   │   ├── MisCursosPage.vue
│   │   │   ├── VerCursoPage.vue
│   │   │   ├── EntregarTareaPage.vue
│   │   │   ├── RendirCuestionarioPage.vue
│   │   │   └── MisNotasPage.vue
│   │   ├── director/
│   │   │   ├── DashboardDirectorPage.vue
│   │   │   ├── SeguimientoCursoPage.vue
│   │   │   └── ReportesPage.vue
│   │   └── admin/
│   │       └── AdminPage.vue
│   ├── components/
│   │   ├── course-builder/
│   │   │   ├── BuilderSimple.vue          # Modo lista con botones
│   │   │   ├── BuilderCanvas.vue          # Modo drag & drop visual
│   │   │   ├── BlockPalette.vue           # Paleta de bloques disponibles
│   │   │   ├── BlockPreview.vue           # Preview de cada bloque
│   │   │   └── BlockEditor.vue            # Editor de propiedades de bloque
│   │   ├── actividades/
│   │   │   ├── ActividadTarea.vue
│   │   │   ├── ActividadForo.vue
│   │   │   ├── ActividadCuestionario.vue
│   │   │   ├── ActividadLeccion.vue
│   │   │   └── ActividadEncuesta.vue
│   │   └── calificaciones/
│   │       ├── RubricaEditor.vue
│   │       └── LibroCalificaciones.vue
│   ├── composables/
│   │   ├── useAuth.js
│   │   ├── useCursoBuilder.js
│   │   └── useCalificaciones.js
│   ├── stores/
│   │   ├── auth.js
│   │   ├── cursos.js
│   │   └── calificaciones.js
│   ├── services/
│   │   ├── sisaService.js
│   │   ├── cursoService.js
│   │   ├── actividadService.js
│   │   └── calificacionService.js
│   └── router/
```

---

## 3. Modelo de Datos Core (MySQL)

### 3.1 Diagrama ER simplificado

```
┌──────────┐     ┌───────────┐     ┌────────────┐     ┌───────────────┐
│  CURSOS  │1───*│ SECCIONES │1───*│ ACTIVIDADES │1───1│ (polimórfico) │
└────┬─────┘     └───────────┘     └──────┬─────┘     │ TAREAS        │
     │                                    │            │ FOROS         │
     │*                           ┌───────┴──────┐     │ CUESTIONARIOS │
┌────┴─────┐                      │  ENTREGAS    │     │ LECCIONES     │
│MATRICULAS│                      └───────┬──────┘     │ ENCUESTAS     │
└──────────┘                              │            └───────────────┘
     │                              ┌─────┴──────┐
     │                              │CALIFICACIONES│
     │                              └─────────────┘
```

### 3.2 Tablas principales

```sql
-- Curso generado desde SISA o creado manualmente
cursos (
    id BIGINT PK,
    sisa_asignatura_id BIGINT,           -- FK simbólica a asignatura en SISA
    sisa_grupo_id BIGINT,                -- FK simbólica a grupo en SISA
    codigo VARCHAR(50),
    nombre VARCHAR(255),
    descripcion TEXT,
    docente_id BIGINT,                   -- FK simbólica a docente en SISA
    carrera_id BIGINT,                   -- FK simbólica a carrera en SISA
    sede_id BIGINT,
    gestion VARCHAR(20),                 -- ej: "1-2026"
    estado ENUM('borrador','publicado','archivado'),
    fecha_inicio DATE,
    fecha_fin DATE,
    imagen_portada VARCHAR(255),
    config JSON,                         -- {foro_visible, calificacion_visible, etc.}
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

-- Sección = Unidad del PAC convertida
secciones (
    id BIGINT PK,
    curso_id BIGINT FK,
    sisa_unidad_id BIGINT,               -- FK simbólica a unidad en SISA
    titulo VARCHAR(255),
    descripcion TEXT,
    orden INT,
    visible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

-- Bloque de actividad dentro de una sección (polimórfico)
actividades (
    id BIGINT PK,
    seccion_id BIGINT FK,
    tipo ENUM('tarea','foro','cuestionario','leccion','encuesta'),
    titulo VARCHAR(255),
    descripcion TEXT,
    orden INT,
    config JSON,                         -- fechas, visibilidad, tipo_calificacion, etc.
    tiene_nota BOOLEAN DEFAULT TRUE,
    nota_maxima DECIMAL(5,2),
    peso DECIMAL(5,2) DEFAULT 1.00,     -- peso relativo para cálculo de promedio
    actividadable_id BIGINT,
    actividadable_type VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

-- Cada tipo de actividad tiene su propia tabla específica
tareas (
    id BIGINT PK,
    fecha_entrega DATETIME,
    fecha_limite DATETIME,
    archivos_permitidos VARCHAR(255),     -- ej: "pdf,docx,jpg"
    tamano_max_mb INT DEFAULT 10,
    instrucciones TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

foros (
    id BIGINT PK,
    tipo_foro ENUM('normal','pregunta_respuesta','debate'),
    moderado BOOLEAN DEFAULT FALSE,
    anonimo BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

foro_hilos (
    id BIGINT PK,
    foro_id BIGINT FK,
    usuario_id BIGINT,
    titulo VARCHAR(255),
    contenido TEXT,
    fijado BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

foro_respuestas (
    id BIGINT PK,
    hilo_id BIGINT FK,
    usuario_id BIGINT,
    contenido TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

cuestionarios (
    id BIGINT PK,
    tiempo_limite_minutos INT,
    intentos_maximos INT DEFAULT 1,
    aleatorio BOOLEAN DEFAULT FALSE,       -- preguntas en orden aleatorio
    mostrar_respuestas BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

cuestionario_preguntas (
    id BIGINT PK,
    cuestionario_id BIGINT FK,
    tipo ENUM('opcion_multiple','verdadero_falso','respuesta_corta','ensayo','emparejamiento'),
    enunciado TEXT,
    opciones JSON,                         -- [{texto, es_correcta}]
    puntaje DECIMAL(5,2) DEFAULT 1.00,
    orden INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

cuestionario_intentos (
    id BIGINT PK,
    cuestionario_id BIGINT FK,
    estudiante_id BIGINT,
    intento_numero INT,
    fecha_inicio DATETIME,
    fecha_fin DATETIME,
    nota DECIMAL(5,2),
    estado ENUM('en_curso','finalizado','tiempo_agotado'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

cuestionario_respuestas (
    id BIGINT PK,
    intento_id BIGINT FK,
    pregunta_id BIGINT FK,
    respuesta JSON,                        -- {seleccionadas:[0,2], texto:"..."}
    es_correcta BOOLEAN,
    puntaje_obtenido DECIMAL(5,2),
    created_at TIMESTAMP
)

lecciones (
    id BIGINT PK,
    contenido_html LONGTEXT,
    archivos_adjuntos JSON,               -- [{nombre, url, tipo}]
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

encuestas (
    id BIGINT PK,
    anonima BOOLEAN DEFAULT FALSE,
    multiple_seleccion BOOLEAN DEFAULT FALSE,
    fecha_cierre DATETIME,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

encuesta_preguntas (
    id BIGINT PK,
    encuesta_id BIGINT FK,
    tipo ENUM('opcion_multiple','escala','texto_abierto'),
    enunciado TEXT,
    opciones JSON,
    obligatorio BOOLEAN DEFAULT TRUE,
    orden INT,
    created_at TIMESTAMP
)

encuesta_respuestas (
    id BIGINT PK,
    encuesta_id BIGINT FK,
    estudiante_id BIGINT NULL,             -- NULL si es anónima
    respuestas JSON,                       -- [{pregunta_id, respuesta}]
    created_at TIMESTAMP
)

-- Matrícula de estudiante en curso
matriculas (
    id BIGINT PK,
    curso_id BIGINT FK,
    estudiante_id BIGINT,                 -- FK simbólica al sistema de estudiantes externo
    fecha_matricula DATETIME,
    estado ENUM('activo','inactivo','finalizado'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

-- Entrega de actividad por estudiante
entregas (
    id BIGINT PK,
    actividad_id BIGINT FK,
    estudiante_id BIGINT,
    contenido JSON,                        -- {texto, archivos:[{nombre,url}]}
    fecha_entrega DATETIME,
    estado ENUM('pendiente','entregado','revisado','rechazado'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

-- Calificación
calificaciones (
    id BIGINT PK,
    entrega_id BIGINT FK NULL,            -- NULL si es nota manual sin entrega
    actividad_id BIGINT FK,
    estudiante_id BIGINT,
    nota DECIMAL(5,2),
    nota_maxima DECIMAL(5,2),
    porcentaje DECIMAL(5,2) GENERATED ALWAYS AS (nota / nota_maxima * 100) STORED,
    retroalimentacion TEXT,
    calificado_por BIGINT,                -- docente
    sincronizado_externo BOOLEAN DEFAULT FALSE,
    fecha_sincronizacion DATETIME,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

---

## 4. Integraciones (APIs necesarias)

### 4.1 Del SISA (existente)

| Endpoint | Estado | Descripción |
|---|---|---|
| `GET /api/programas-analiticos` | **Existe** | Lista de materias con su estructura por sede/carrera |
| `GET /api/export/documentacion-asignatura?codigo=X` | **Existe** | PAC completo de una materia (unidades, temas, logros, indicadores) |
| `GET /api/export/documentacion-carrera?carrera_id=X` | **Existe** | PAC de todas las materias de una carrera |

**Endpoints que necesitamos que SISA exponga:**

| Endpoint | Descripción | Controlador existente |
|---|---|---|
| `GET /api/docentes` | Lista de docentes con sus materias y grupos | `DocenteController` (verificar) |
| `GET /api/docentes/{id}/grupos?gestion=Y` | Grupos asignados a un docente | `GrupoController` |
| `GET /api/grupos/{id}/cronograma` | Cronograma maestro del grupo | `CronogramaController` |
| `GET /api/carreras/{id}` | Datos de carrera + sede | `CarreraController` (verificar) |

Autenticación: token estático tipo `PROGRAMAS_API_TOKEN` (ya implementado en SISA).

### 4.2 Del Sistema de Estudiantes (a definir)

| Endpoint | Descripción |
|---|---|
| `GET /api/estudiantes?grupo_id=X&gestion=Y` | Lista de estudiantes por grupo/materia |
| `GET /api/estudiantes/{id}` | Datos de un estudiante (nombre, CI, email, carrera) |
| `GET /api/matriculas?estudiante_id=X&gestion=Y` | Cursos en los que está matriculado un estudiante |

### 4.3 Del Sistema de Notas Centralizado (a definir)

| Endpoint | Descripción |
|---|---|
| `POST /api/notas` | Enviar lote de calificaciones |
| `PUT /api/notas/{id}` | Actualizar una calificación existente |
| `GET /api/notas?curso_id=X&gestion=Y` | Verificar notas ya enviadas (evitar duplicados) |

**Formato esperado de envío de notas** (a confirmar con el sistema externo):

```json
{
  "curso_codigo": "MAT101",
  "gestion": "1-2026",
  "notas": [
    {
      "estudiante_id": "12345",
      "actividad": "Tarea 1",
      "nota": 85.5,
      "nota_maxima": 100,
      "porcentaje": 85.5,
      "fecha_calificacion": "2026-06-15"
    }
  ]
}
```

---

## 5. Funcionalidades Core

### 5.1 Generación automática de curso desde SISA

**Flujo:**
1. Docente inicia sesión en el LMS vía SSO SISA
2. El sistema consulta a SISA: `GET /api/docentes/{id}/grupos?gestion=1-2026`
3. Docente ve lista de sus materias/grupos sin curso LMS aún
4. Selecciona **"Generar curso desde SISA"** para una materia
5. El sistema consume `GET /api/export/documentacion-asignatura?codigo=MAT101`
6. **Mapeo automático PAC → Curso:**

| PAC (SISA) | LMS |
|---|---|
| Asignatura | Curso |
| Unidad (I, II, III...) | Sección |
| Tema (1.1, 1.2...) | Tema dentro de Sección |
| Logros de aprendizaje | Objetivos de la sección |
| Cronograma maestro | Fechas sugeridas por sección |
| Bibliografía | Recursos del curso |
| Sistema de evaluación | Esquema de calificación inicial |

7. El curso se crea en estado **"borrador"**
8. El docente personaliza: añade actividades, edita fechas, ajusta contenido

### 5.2 Builder de Curso (dos modos)

#### Modo Simple (default)

- Vista de lista jerárquica: Secciones → Actividades
- Cada sección muestra sus actividades anidadas
- Botón **"+ Añadir actividad"** al final de cada sección
- Menú desplegable para elegir tipo: Tarea, Foro, Lección, Cuestionario, Encuesta
- **Drag & drop de filas** (arrastrar ícono de la izquierda) para reordenar
- Click en una actividad → panel lateral de edición de propiedades
- Toggle para mostrar/ocultar actividad a estudiantes
- Indicador visual de estado: Sin configurar / Listo / Oculto

#### Modo Canvas (avanzado, estilo Elementor)

- **Vista de lienzo visual:** Secciones como contenedores apilados verticalmente
- **Panel lateral izquierdo:** Paleta de bloques arrastrables
  - Bloques de actividad: Tarea, Foro, Cuestionario, Lección, Encuesta
  - Bloques de contenido: Texto enriquecido, Imagen, Video embebido, Archivo descargable
  - Bloques estructurales: Separador, Columna, Acordeón, Pestañas
- **Drag & drop:** Arrastrar bloques desde la paleta al lienzo, dentro de cualquier sección
- **Click en bloque → Panel lateral derecho:** Propiedades específicas del bloque
- **Preview en tiempo real** de cómo lo ve el estudiante (toggle builder/preview)
- **Deshacer / Rehacer**
- **Plantillas:** Guardar disposición como plantilla reutilizable. Cargar plantilla existente.

### 5.3 Sistema de Actividades Extensible

Cada tipo de actividad implementa una interfaz común para que el sistema sea modular:

```php
interface TipoActividad
{
    /**
     * Props que necesita el builder para renderizar el bloque.
     */
    public function renderBuilder(): array;

    /**
     * Props que necesita la vista del estudiante.
     */
    public function renderEstudiante(): array;

    /**
     * Calificar una entrega (manual o automática).
     */
    public function calificar(Entrega $entrega): Calificacion;

    /**
     * Validar la entrega del estudiante.
     */
    public function validarEntrega(array $datos): bool;

    /**
     * Ícono y etiqueta para el panel de bloques del builder.
     */
    public function metadata(): array;
}
```

Esto permite añadir nuevos tipos de actividad (ej: "Simulación interactiva", "Peer review", "Laboratorio virtual") sin modificar el core del sistema.

### 5.4 Actividades del MVP

#### Tarea (Assignment)
- **Configuración:** fecha de entrega, fecha límite con penalización, archivos permitidos, tamaño máximo, instrucciones
- **Estudiante:** subir archivos, escribir texto en línea, marcar como entregado
- **Docente:** visor de archivos, anotaciones, rúbrica de evaluación, nota numérica, retroalimentación
- **Estados:** pendiente → entregado → revisado / rechazado (solicitar re-entrega)

#### Cuestionario (Quiz)
- **Configuración:** tiempo límite, intentos máximos, preguntas aleatorias, mostrar/ocultar respuestas
- **Tipos de pregunta:** opción múltiple, verdadero/falso, respuesta corta, ensayo, emparejamiento
- **Estudiante:** interfaz con temporizador, navegación entre preguntas, confirmación de envío
- **Calificación:** automática para preguntas cerradas, manual para ensayo
- **Prevención de trampa:** navegación bloqueada (salir = intento perdido)

#### Foro (Forum)
- **Tipos:** foro general, debate, pregunta-respuesta (estudiante debe responder para ver otras respuestas)
- **Configuración:** moderado, anónimo, calificable
- **Hilos:** crear hilo, responder, citar, adjuntar archivos
- **Suscripción:** notificaciones de nuevas respuestas

#### Lección (Lesson/Content)
- **Configuración:** editor de texto enriquecido (TipTap), archivos adjuntos, video embebido (YouTube, Vimeo)
- **Soporte para:** imágenes, tablas, código fuente, ecuaciones (KaTeX/MathJax)
- **Opcional:** marcar como completada para avanzar (progreso condicional)

#### Encuesta (Survey)
- **Tipos de pregunta:** opción múltiple, escala Likert, texto abierto
- **Configuración:** anónima, fecha de cierre, mostrar resultados
- **Resultados:** gráficos de barras/torta en tiempo real

### 5.5 Sistema de Calificaciones

- **Libro de calificaciones por curso:** tabla con estudiantes (filas) vs actividades (columnas)
- **Cálculo de promedio:** ponderado por peso de cada actividad. Fórmula configurable por curso.
- **Rúbricas:** criterios con niveles (Excelente, Bueno, Regular, Insuficiente), puntaje por nivel
- **Categorías de calificación:** agrupar actividades (ej: "Tareas 30%", "Exámenes 50%", "Participación 20%")
- **Exportación:** Excel, PDF
- **Sincronización externa:** job programado o botón manual para enviar notas al sistema centralizado

---

## 6. Vistas por Rol

### Docente
| Función | Descripción |
|---|---|
| Mis Cursos | Lista de cursos activos. Crear nuevo desde SISA o manual. |
| Builder de Curso | Modo simple o canvas. Organizar secciones y actividades. |
| Calificar | Visor de entregas, rúbrica, retroalimentación, nota. |
| Libro de Calificaciones | Tabla completa, exportar, sincronizar con sistema externo. |
| Foro del Curso | Participar como moderador. |
| Reporte de Avance | Por estudiante: % completado, notas, últimas entregas. |

### Estudiante
| Función | Descripción |
|---|---|
| Mis Cursos | Cursos activos matriculados. Progreso visual (barra %). |
| Ver Curso | Secciones, temas, actividades. Navegación lateral. |
| Entregar Tarea | Subir archivos, escribir texto, ver estado y retroalimentación. |
| Rendir Cuestionario | Temporizador, navegación entre preguntas, resultado al finalizar. |
| Foros | Ver hilos, crear/respuestas, adjuntar archivos. |
| Mis Notas | Tabla de calificaciones por actividad. Promedio. |

### Director de Carrera
| Función | Descripción |
|---|---|
| Dashboard | Cursos activos de la carrera. % de avance general. |
| Seguimiento por Curso | Vista de observador. Ver contenido, actividades, entregas. |
| Reportes | Cumplimiento docente, rendimiento estudiantil, cursos sin actividad. |
| Alertas | Docentes que no califican, cursos sin actualizar, entregas masivas sin revisar. |

### Admin
| Función | Descripción |
|---|---|
| Gestión de Cursos | CRUD completo. Archivar, duplicar. |
| Gestión de Usuarios | Ver usuarios cacheados. Forzar re-sync. |
| Configuración | Conexiones a APIs externas, tokens, parámetros globales. |
| Logs de Sincronización | Estado de jobs, errores, reintentos. |

---

## 7. Plan de Desarrollo por Fases

### Fase 1 — Fundación (Semanas 1-3)

- [ ] Inicializar proyecto: `composer create-project laravel/laravel back` + `npm create quasar front`
- [ ] Configurar CodeGraph, ESLint, Prettier, Vitest, Playwright
- [ ] Docker Compose para desarrollo (nginx, php, mysql, redis, minio)
- [ ] Modelo de datos base: migraciones de `cursos`, `secciones`, `actividades`, `usuarios`
- [ ] Seeders con datos de prueba
- [ ] Autenticación SSO contra SISA (validación de token Sanctum)
- [ ] Middleware de roles: docente, estudiante, director, admin
- [ ] `SisaSyncService`: consumir API SISA, parsear PAC, mapear a modelo interno
- [ ] Página "Mis Cursos" (docente ve lista)
- [ ] Página "Generar curso desde SISA" con mapeo automático PAC → Curso
- [ ] CRUD básico de cursos

**Entregable:** Docente se loguea, ve sus materias de SISA, genera curso con estructura base.

### Fase 2 — Builder y Actividades Core (Semanas 4-7)

- [ ] Builder modo simple: lista jerárquica con botones añadir + drag reordenar
- [ ] Tipo de actividad: **Lección** (editor TipTap, archivos, video embebido)
- [ ] Tipo de actividad: **Tarea** (fecha, archivos permitidos, entregas)
- [ ] Tipo de actividad: **Foro** (hilos, respuestas, adjuntos)
- [ ] Vista de estudiante: ver curso completo (secciones + actividades)
- [ ] Estudiante: leer lecciones, entregar tareas, participar en foros
- [ ] `StudentSyncService`: consumir API de estudiantes externa
- [ ] Matrícula automática de estudiantes en cursos
- [ ] Navegación entre secciones con indicador de progreso

**Entregable:** Docente construye curso, estudiante ve contenidos, entrega tareas, participa en foros.

### Fase 3 — Calificaciones (Semanas 8-10)

- [ ] Modelo de calificaciones y rúbricas
- [ ] Calificación manual: tareas y foros
- [ ] Libro de calificaciones (tabla interactiva)
- [ ] Cálculo de promedios ponderados
- [ ] Exportación Excel/PDF
- [ ] `GradeSyncService`: envío de notas al sistema centralizado externo
- [ ] Dashboard de director de carrera (vista observador)
- [ ] Dashboard de estudiante: mis notas, progreso
- [ ] Indicador visual de sincronización (notas enviadas/ pendientes)

**Entregable:** Ciclo completo desde actividad → entrega → calificación → sincronización externa.

### Fase 4 — Avanzado (Semanas 11-14)

- [ ] Builder Canvas drag & drop (lienzo visual con paleta de bloques)
- [ ] Toggle Simple / Canvas en el builder
- [ ] Plantillas de curso (guardar y cargar)
- [ ] Tipo de actividad: **Cuestionario** (banco, tipos de pregunta, temporizador, auto-calificación)
- [ ] Tipo de actividad: **Encuesta** (creación, respuesta, gráficos de resultados)
- [ ] Sistema de notificaciones (email + in-app)
- [ ] Reportes avanzados: cumplimiento docente, rendimiento, alertas
- [ ] Plugins: arquitectura extensible para nuevos tipos de actividad
- [ ] Biblioteca de plantillas reutilizables

**Entregable final:** LMS completo con todas las actividades, builder avanzado, notificaciones, y reportes.

---

## 8. Pre-requisitos (qué necesitamos resolver antes de arrancar)

###   Del lado de SISA (sistema actual)

1. **Crear/verificar endpoints públicos con token estático** para que el LMS consuma datos sin login de usuario:
   - [ ] `GET /api/docentes` — listar docentes con materias/grupos
   - [ ] `GET /api/docentes/{id}/grupos?gestion=X` — grupos de un docente
   - [ ] `GET /api/grupos/{id}/cronograma` — cronograma maestro
   - [ ] `GET /api/carreras/{id}` — datos de carrera + sede

2. **Definir token estático** (mismo mecanismo que `PROGRAMAS_API_TOKEN` ya implementado)

3. **Verificar CORS** para permitir requests desde el dominio del nuevo LMS

### ✏️ Del lado del Sistema de Estudiantes

1. **Documentar API existente** (endpoints, formato JSON, autenticación)
2. Si no hay API, **definir contrato mínimo**:
   - Endpoint para obtener estudiantes por grupo/materia
   - Endpoint para obtener datos de un estudiante
   - Formato de respuesta esperado
3. **Credenciales de acceso**

###   Del lado del Sistema de Notas Centralizado

1. **Documentar API existente** (endpoints, formato, campos obligatorios)
2. Si no hay API, **definir contrato mínimo**:
   - Endpoint para enviar calificaciones (batch)
   - Endpoint para consultar notas ya enviadas
   - Formato esperado de datos
3. **Credenciales de acceso**

###   Decisiones técnicas previas

| Decisión | Opciones | Recomendación |
|---|---|---|
| Librería drag & drop | `vuedraggable@next` (sortablejs), `@vueuse/integrations`, `interact.js` | `vuedraggable@next` — maduro, simple, Vue 3 nativo |
| Editor de texto enriquecido | TipTap (ProseMirror), Quill, TinyMCE, CKEditor | TipTap — extensible, Vue 3 nativo, sin licencias |
| File storage | Local, S3, MinIO | MinIO (S3-compatible local) para dev, S3 para prod |
| Notificaciones | Laravel Notifications + Pusher, SSE, WebSockets | Email + SSE (Server-Sent Events) para MVP |
| Colas / Jobs | Database, Redis, SQS | Redis — rápido, ya usado en Laravel |
| Hosting | VPS + Docker, Laravel Forge, Laravel Vapor | Docker Compose para dev, Forge para staging/prod |

---

## 9. Estructura sugerida del nuevo proyecto

```
C:\PROYECTOS\LMS_UNITEPC\
│
├── back/                           # Laravel 12 API REST
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Interfaces/
│   │   └── Jobs/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   ├── seeders/
│   │   └── factories/
│   ├── routes/
│   │   ├── api.php
│   │   └── web.php
│   ├── tests/
│   ├── composer.json
│   ├── .env.example
│   └── README.md
│
├── front/                          # Quasar 2 + Vue 3 SPA
│   ├── src/
│   │   ├── boot/
│   │   ├── components/
│   │   ├── composables/
│   │   ├── layouts/
│   │   ├── pages/
│   │   ├── router/
│   │   ├── services/
│   │   └── stores/
│   ├── public/
│   ├── quasar.config.js
│   ├── vite.config.mjs
│   ├── package.json
│   ├── eslint.config.js
│   └── README.md
│
├── docker/
│   ├── docker-compose.yml
│   ├── nginx/
│   │   └── default.conf
│   ├── php/
│   │   └── Dockerfile
│   └── mysql/
│       └── init.sql
│
├── .codegraph/                     # Índice CodeGraph
├── AGENTS.md                       # Instrucciones para Codex
├── README.md                       # Guía del proyecto
├── PLAN_LMS_UNITEPC.md             # Este documento
└── CONTEXTO_PROYECTO.md            # Estado actual y contexto operativo
```

---

## 10. Riesgos y Mitigaciones

| Riesgo | Impacto | Mitigación |
|---|---|---|
| APIs externas no están listas al arrancar | Alto | Usar mocks/fixtures en desarrollo. Diseñar contratos de API primero. |
| SISA no expone endpoints necesarios | Medio | Agregarlos como tarea separada en SISA. Son controladores existentes, solo faltan rutas públicas. |
| Cambio de requisitos del sistema de notas | Medio | Capa de abstracción (`GradeSyncService`) adaptable a distintos formatos. |
| Usuarios sin acceso a SSO | Bajo | Login de respaldo local para desarrollo/pruebas con seeders. |
| Complejidad del builder canvas | Bajo | Fase 4. El modo simple cubre el MVP. |

---

## 11. Próximos Pasos

1. **Resolver integraciones externas:**
   - Coordinar con equipo SISA para exponer endpoints faltantes
   - Solicitar documentación de API del sistema de estudiantes
   - Solicitar documentación de API del sistema de notas

2. **Inicializar proyecto:**
   - Crear repositorio `LMS_UNITEPC`
   - Inicializar Laravel 12 backend
   - Inicializar Quasar 2 frontend
   - Configurar Docker Compose
   - Inicializar CodeGraph

3. **Primera iteración:**
   - Implementar SSO auth
   - Consumir API SISA
   - Generar cursos desde PAC
   - CRUD de cursos

---

> **Nota para Codex:** este documento contiene el contexto maestro para continuar el desarrollo. Usarlo junto con `CONTEXTO_PROYECTO.md`, `AGENTS.md` y el MCP global de CodeGraph.
