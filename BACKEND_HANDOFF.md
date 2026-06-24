# Handoff para Backend — LMS UNITEPC

> Documento de entrega para iniciar el desarrollo del backend Laravel 12 en otro chat/agente. Contiene el contexto actual del frontend, el plan completo de funcionalidades, modelo de datos y los contratos API que el backend debe exponer.
>
> Fecha: Junio 2026  
> Stack backend propuesto: Laravel 12 + PHP 8.2+ + MySQL 8.0 + Sanctum + Redis + MinIO  
> Frontend existente: Vue 3 + Quasar 2 + Pinia + Axios + Tailwind CSS v4

---

## 1. Contexto actual del sistema

### 1.1 Frontend prototipo listo

El frontend (`Aula-virtual/`) es una SPA en Vue 3/Quasar 2 que actualmente funciona **100% con datos mock**. Las 4 fases del plan original están completadas y se hizo una auditoría UX/UI exhaustiva.

**Módulos funcionales en el frontend:**

| Módulo | Estado | Notas |
|---|---|---|
| Autenticación mock | ✅ | 4 roles: estudiante, docente, director, admin. Login local + DevAccessPage. |
| MainLayout multi-rol | ✅ | Header, sidebar dinámico, notificaciones, perfil, dark/light mode. |
| Dashboard Docente | ✅ | KPIs, gráficos, entregas pendientes, cursos activos. |
| Dashboard Estudiante | ✅ | Progreso, pendientes, cursos, docentes. |
| Dashboard Director | ✅ | KPIs, observatorio académico, reportes. |
| Gestión de cursos | ✅ | CRUD visual, builder drag & drop, vista previa. |
| Wizard SISA (mock) | ✅ | 3 pasos para generar curso desde PAC. |
| 6 tipos de actividad | ✅ | Lección, Tarea, Foro, Cuestionario, Encuesta, H5P. |
| Libro de calificaciones | ✅ | Tabla interactiva, rúbricas, export CSV/PDF mock. |
| Panel Admin | ✅ | KPIs, configuraciones, logs de sync. |
| Centro inteligente docente | ✅ | Alertas, agenda, automatizaciones, banco docente, asistente de creación, analítica. |
| Notificaciones | ✅ | Drawer con notificaciones mock. |

**Datos mock disponibles:**
- `mock/data/usuarios.js` — 2 docentes, 1 director, 1 admin, 20 estudiantes.
- `mock/data/cursos.js` — 3 cursos con secciones.
- `mock/data/actividades.js` — 30 actividades de 6 tipos.
- `mock/data/matriculas.js` — matrículas de estudiantes en cursos.
- `mock/data/calificaciones.js` — notas por estudiante/actividad.
- `mock/data/entregas_estudiante.js` — entregas de prueba.

**Servicios del frontend con fallback mock:**
- `src/services/api.js` — instancia Axios configurada.
- `src/services/authService.js`
- `src/services/cursoService.js`
- `src/services/actividadService.js`
- `src/services/calificacionService.js`

Cuando el backend responda, los servicios deben dejar de usar el fallback mock.

---

## 2. ¿Para qué se necesita el backend?

El frontend ya se ve y navega bien, pero **carece de persistencia real, sincronización entre vistas y flujos completos**. Para presentar el sistema como reemplazo de Moodle se requiere backend que soporte:

1. Autenticación SSO real contra SISA.
2. Persistencia de cursos, secciones, actividades, entregas y calificaciones.
3. Flujo completo estudiante → entrega → docente califica → estudiante ve nota.
4. Integración con SISA (PAC, docentes, grupos).
5. Integración con Sistema de Estudiantes (matrículas).
6. Integración con Sistema de Notas Centralizado (envío de calificaciones).
7. Módulos faltantes: calendario académico, mensajería, gestión masiva de usuarios, banco docente real, reportes exportables.

---

## 3. Plan de desarrollo backend propuesto

### Fase A — Fundación y autenticación (semanas 1-2)

Objetivo: que el frontend pueda autenticarse y consumir datos reales del backend.

- Inicializar Laravel 12 en carpeta `back/`.
- Docker Compose: nginx, php-fpm, mysql, redis, minio.
- Migraciones base: `usuarios`, `cursos`, `secciones`, `actividades`, `matriculas`, `entregas`, `calificaciones`.
- Seeders con datos mínimos de prueba.
- Autenticación SSO contra SISA vía Sanctum:
  - `POST /auth/login` (recibe token SISA, valida, devuelve JWT LMS).
  - `POST /auth/logout`, `GET /auth/me`.
- Middleware de roles.
- Endpoints CRUD de cursos.
- Configurar CORS para el frontend en `localhost:9000`.

**Entregable:** Frontend se loguea con usuario real, lista cursos desde el backend.

---

### Fase B — Cursos, actividades y contenido (semanas 3-5)

Objetivo: que el docente pueda crear/editar cursos y el estudiante verlos.

- CRUD de secciones y actividades (incluyendo reordenamiento).
- Actividad `leccion`: contenido HTML, archivos adjuntos.
- Actividad `tarea`: fechas, archivos permitidos, tamaño máximo.
- Actividad `foro`: hilos y respuestas.
- Actividad `encuesta`: preguntas y respuestas.
- Actividad `cuestionario`: preguntas, intentos, calificación automática.
- Soporte de archivos a MinIO/S3.
- Vista de estudiante: `GET /cursos/{id}` con secciones y actividades.

**Entregable:** Docente arma un curso; estudiante lo ve y participa en foros, lecciones y encuestas.

---

### Fase C — Entregas y calificaciones (semanas 6-8)

Objetivo: cerrar el flujo de evaluación.

- Entregas de tareas con archivos y texto.
- Libro de calificaciones por curso.
- Calificación manual con rúbrica.
- Calificación automática de cuestionarios.
- Cálculo de promedios ponderados.
- Estados de entrega: pendiente → entregado → revisado/rechazado.
- Vista "Mis notas" del estudiante.

**Entregable:** Ciclo completo entrega → calificación → nota publicada.

---

### Fase D — Integraciones externas (semanas 9-11)

Objetivo: conectar con los sistemas legados de UNITEPC.

- `SisaSyncService`: consumir API SISA para generar cursos desde PAC.
- `StudentSyncService`: consumir API de Sistema de Estudiantes para matricular.
- `GradeSyncService`: enviar calificaciones al Sistema de Notas Centralizado.
- Jobs en cola (Redis) para sincronización periódica.
- Panel admin: estado de integraciones, logs, re-sync manual.

**Entregable:** Curso se genera desde SISA; estudiantes se matriculan automáticamente; notas se sincronizan.

---

### Fase E — Módulos de alto valor para reemplazar Moodle (semanas 12-16)

Estos módulos son los que más harían ver al sistema como alternativa real a Moodle:

1. **Calendario académico central**
   - Eventos por curso: entregas, evaluaciones, fechas límite.
   - Vista mensual/semanal por rol.
   - Sincronización con actividades existentes.

2. **Mensajería interna**
   - Conversaciones docente-estudiante y por curso.
   - Notificaciones de nuevo mensaje.

3. **Gestión masiva de usuarios y matrículas (admin)**
   - Importación CSV de estudiantes/docentes.
   - Asignación masiva a cursos.
   - Roles y permisos granulares.

4. **Banco docente real**
   - Guardar plantillas de actividades/rúbricas/preguntas.
   - Compartir entre docentes.

5. **Reportes exportables**
   - Notas por curso (Excel/PDF).
   - Progreso de estudiantes.
   - Cumplimiento docente.
   - Rendimiento por carrera.

6. **Notificaciones push e in-app**
   - Eventos: entrega calificada, nueva tarea, fecha límite próxima, respuesta en foro.

7. **Asistente de creación con IA/local**
   - Generar estructura de curso desde tema/objetivo (ya existe en frontend).

8. **Cuestionario avanzado**
   - Banco de preguntas, tipos adicionales, aleatorización.

9. **Plugins / tipos de actividad extensibles**
   - Arquitectura que permita registrar nuevos tipos de actividad sin tocar el core.

---

## 4. Modelo de datos sugerido

### Relación principal

```
cursos
  └── secciones
        └── actividades (polimórfica: tarea, foro, cuestionario, leccion, encuesta, h5p)
              └── entregas
                    └── calificaciones

usuarios (cache local de SISA + admin creados localmente)
  └── matriculas (estudiante en curso)
  └── roles
```

### Tablas principales

```sql
-- Usuarios cacheados del sistema externo + admin local
usuarios (
  id BIGINT PK,
  sisa_id BIGINT UNIQUE NULL,
  nombre VARCHAR(255),
  email VARCHAR(255) UNIQUE,
  avatar VARCHAR(255),
  rol ENUM('estudiante','docente','director','admin'),
  carrera_id BIGINT NULL,
  sede_id BIGINT NULL,
  activo BOOLEAN DEFAULT TRUE,
  ultimo_sync TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Cursos generados desde SISA o creados manualmente
cursos (
  id BIGINT PK,
  sisa_asignatura_id BIGINT NULL,
  sisa_grupo_id BIGINT NULL,
  codigo VARCHAR(50),
  nombre VARCHAR(255),
  descripcion TEXT,
  docente_id BIGINT FK,
  carrera_id BIGINT NULL,
  sede_id BIGINT NULL,
  gestion VARCHAR(20),
  estado ENUM('borrador','publicado','archivado') DEFAULT 'borrador',
  imagen_portada VARCHAR(255) NULL,
  config JSON,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Secciones/unidades del curso
secciones (
  id BIGINT PK,
  curso_id BIGINT FK,
  sisa_unidad_id BIGINT NULL,
  titulo VARCHAR(255),
  descripcion TEXT,
  orden INT,
  visible BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Actividad genérica (polimórfica)
actividades (
  id BIGINT PK,
  seccion_id BIGINT FK,
  tipo ENUM('tarea','foro','cuestionario','leccion','encuesta','h5p'),
  titulo VARCHAR(255),
  descripcion TEXT,
  orden INT,
  tiene_nota BOOLEAN DEFAULT TRUE,
  nota_maxima DECIMAL(5,2) DEFAULT 100,
  peso DECIMAL(5,2) DEFAULT 1.00,
  config JSON,
  actividadable_id BIGINT,
  actividadable_type VARCHAR(100),
  visible BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Detalle Tarea
tareas (
  id BIGINT PK,
  instrucciones TEXT,
  fecha_entrega DATETIME NULL,
  fecha_limite DATETIME NULL,
  archivos_permitidos VARCHAR(255) DEFAULT 'pdf,docx',
  tamano_max_mb INT DEFAULT 10,
  reintentos BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Detalle Foro
foros (
  id BIGINT PK,
  tipo_foro ENUM('normal','pregunta_respuesta','debate') DEFAULT 'normal',
  moderado BOOLEAN DEFAULT FALSE,
  anonimo BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

foro_hilos (
  id BIGINT PK,
  foro_id BIGINT FK,
  usuario_id BIGINT FK,
  titulo VARCHAR(255),
  contenido TEXT,
  fijado BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

foro_respuestas (
  id BIGINT PK,
  hilo_id BIGINT FK,
  usuario_id BIGINT FK,
  contenido TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Detalle Cuestionario
cuestionarios (
  id BIGINT PK,
  tiempo_limite_minutos INT DEFAULT 20,
  intentos_maximos INT DEFAULT 1,
  aleatorio BOOLEAN DEFAULT FALSE,
  mostrar_respuestas BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

cuestionario_preguntas (
  id BIGINT PK,
  cuestionario_id BIGINT FK,
  tipo ENUM('opcion_multiple','verdadero_falso','respuesta_corta','ensayo','emparejamiento'),
  enunciado TEXT,
  opciones JSON,
  puntaje DECIMAL(5,2) DEFAULT 1,
  orden INT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

cuestionario_intentos (
  id BIGINT PK,
  cuestionario_id BIGINT FK,
  estudiante_id BIGINT FK,
  intento_numero INT,
  fecha_inicio DATETIME,
  fecha_fin DATETIME NULL,
  nota DECIMAL(5,2) NULL,
  estado ENUM('en_curso','finalizado','tiempo_agotado'),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

cuestionario_respuestas (
  id BIGINT PK,
  intento_id BIGINT FK,
  pregunta_id BIGINT FK,
  respuesta JSON,
  es_correcta BOOLEAN NULL,
  puntaje_obtenido DECIMAL(5,2) NULL,
  created_at TIMESTAMP
)

-- Detalle Lección
lecciones (
  id BIGINT PK,
  contenido_html LONGTEXT,
  archivos_adjuntos JSON,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Detalle Encuesta
encuestas (
  id BIGINT PK,
  anonima BOOLEAN DEFAULT FALSE,
  multiple_seleccion BOOLEAN DEFAULT FALSE,
  fecha_cierre DATETIME NULL,
  mostrar_resultados BOOLEAN DEFAULT FALSE,
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
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

encuesta_respuestas (
  id BIGINT PK,
  encuesta_id BIGINT FK,
  estudiante_id BIGINT NULL,
  respuestas JSON,
  created_at TIMESTAMP
)

-- Matrícula
matriculas (
  id BIGINT PK,
  curso_id BIGINT FK,
  estudiante_id BIGINT FK,
  estado ENUM('activo','inactivo','finalizado') DEFAULT 'activo',
  fecha_matricula DATE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Entrega de estudiante a una actividad
entregas (
  id BIGINT PK,
  actividad_id BIGINT FK,
  estudiante_id BIGINT FK,
  contenido JSON, -- {texto, archivos:[{nombre,url}]}
  fecha_entrega DATETIME,
  estado ENUM('pendiente','entregado','revisado','rechazado') DEFAULT 'pendiente',
  intento_cuestionario_id BIGINT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Calificación
calificaciones (
  id BIGINT PK,
  entrega_id BIGINT NULL,
  actividad_id BIGINT FK,
  estudiante_id BIGINT FK,
  nota DECIMAL(5,2),
  nota_maxima DECIMAL(5,2),
  porcentaje DECIMAL(5,2),
  retroalimentacion TEXT,
  rubrica JSON NULL,
  calificado_por BIGINT FK,
  sincronizado_externo BOOLEAN DEFAULT FALSE,
  fecha_sincronizacion TIMESTAMP NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Categorías de calificación por curso (ponderación)
calificacion_categorias (
  id BIGINT PK,
  curso_id BIGINT FK,
  nombre VARCHAR(100),
  peso DECIMAL(5,2),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Notificaciones
notificaciones (
  id BIGINT PK,
  usuario_id BIGINT FK,
  tipo VARCHAR(50),
  titulo VARCHAR(255),
  descripcion TEXT,
  icono VARCHAR(50),
  color VARCHAR(20),
  data JSON,
  leida BOOLEAN DEFAULT FALSE,
  ruta VARCHAR(255) NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Calendario académico
eventos_calendario (
  id BIGINT PK,
  curso_id BIGINT NULL,
  actividad_id BIGINT NULL,
  titulo VARCHAR(255),
  descripcion TEXT,
  tipo ENUM('entrega','evaluacion','clase','evento_institucional'),
  fecha_inicio DATETIME,
  fecha_fin DATETIME NULL,
  todo_el_dia BOOLEAN DEFAULT FALSE,
  creado_por BIGINT FK,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Mensajería interna
conversaciones (
  id BIGINT PK,
  curso_id BIGINT NULL,
  asunto VARCHAR(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

conversacion_participantes (
  id BIGINT PK,
  conversacion_id BIGINT FK,
  usuario_id BIGINT FK,
  created_at TIMESTAMP
)

mensajes (
  id BIGINT PK,
  conversacion_id BIGINT FK,
  remitente_id BIGINT FK,
  contenido TEXT,
  adjuntos JSON NULL,
  leido BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Banco docente (plantillas)
plantillas (
  id BIGINT PK,
  docente_id BIGINT FK,
  categoria ENUM('actividad','rubrica','preguntas'),
  tipo VARCHAR(50),
  nombre VARCHAR(255),
  descripcion TEXT,
  datos JSON,
  uso_count INT DEFAULT 0,
  publica BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- Logs de auditoría / sincronización
auditoria (
  id BIGINT PK,
  usuario_id BIGINT NULL,
  accion VARCHAR(255),
  tipo ENUM('success','warning','error','info'),
  detalle TEXT,
  servicio VARCHAR(50) NULL,
  created_at TIMESTAMP
)

-- Configuración global del sistema
configuraciones (
  id VARCHAR(100) PK,
  valor JSON,
  estado ENUM('online','degradado','pausado') DEFAULT 'online',
  updated_at TIMESTAMP
)
```

---

## 5. Contratos API que el backend debe exponer

Ya existe un documento base en `Aula-virtual/API_CONTRACTS.md`. A continuación se resumen los endpoints críticos y se añaden los que faltan para los nuevos módulos.

### 5.1 Autenticación

```http
POST /auth/login
POST /auth/logout
GET  /auth/me
GET  /auth/roles
```

### 5.2 Cursos y secciones

```http
GET    /cursos
GET    /cursos/{id}
POST   /cursos
PUT    /cursos/{id}
DELETE /cursos/{id}
PUT    /cursos/{id}/publicar
PUT    /cursos/{id}/archivar

POST   /cursos/{id}/secciones
PUT    /secciones/{id}
DELETE /secciones/{id}
PUT    /cursos/{id}/secciones/reordenar
```

### 5.3 Actividades

```http
GET    /secciones/{id}/actividades
POST   /secciones/{id}/actividades
GET    /actividades/{id}
PUT    /actividades/{id}
DELETE /actividades/{id}
PUT    /secciones/{id}/actividades/reordenar
```

### 5.4 Entregas

```http
GET    /actividades/{id}/entregas
POST   /actividades/{id}/entregas
GET    /entregas/{id}
PUT    /entregas/{id}/rechazar
```

### 5.5 Calificaciones

```http
GET    /cursos/{id}/calificaciones
POST   /entregas/{id}/calificar
PUT    /calificaciones/{id}
POST   /cursos/{id}/calificaciones/sincronizar
```

### 5.6 Foros

```http
GET    /actividades/{id}/hilos
POST   /actividades/{id}/hilos
GET    /hilos/{id}/respuestas
POST   /hilos/{id}/respuestas
```

### 5.7 Cuestionarios

```http
GET    /actividades/{id}/cuestionario
POST   /actividades/{id}/intentos
POST   /intentos/{id}/responder
GET    /intentos/{id}/resultado
```

### 5.8 Encuestas

```http
POST   /actividades/{id}/respuestas
GET    /actividades/{id}/resultados
```

### 5.9 Centros inteligentes por rol

```http
GET    /centros/estudiante
GET    /centros/docente
POST   /centros/docente/asistente-generar
POST   /centros/docente/actividades-guiadas
GET    /centros/director
GET    /centros/admin/configuracion
PUT    /centros/admin/configuracion/{id}
```

### 5.10 Dashboards y reportes

```http
GET    /dashboard/docente
GET    /dashboard/estudiante
GET    /dashboard/director
GET    /dashboard/admin
GET    /reportes/{tipo}?formato=excel|pdf&curso_id=X
```

### 5.11 Notificaciones

```http
GET    /notificaciones
PUT    /notificaciones/{id}/leida
PUT    /notificaciones/leer-todas
```

### 5.12 Calendario académico (nuevo)

```http
GET    /calendario?desde=YYYY-MM-DD&hasta=YYYY-MM-DD&curso_id=X
POST   /calendario/eventos
PUT    /calendario/eventos/{id}
DELETE /calendario/eventos/{id}
```

### 5.13 Mensajería interna (nuevo)

```http
GET    /mensajes/conversaciones
POST   /mensajes/conversaciones
GET    /mensajes/conversaciones/{id}
POST   /mensajes/conversaciones/{id}/mensajes
PUT    /mensajes/{id}/leido
```

### 5.14 Gestión de usuarios y matrículas (admin)

```http
GET    /admin/usuarios
POST   /admin/usuarios
POST   /admin/usuarios/importar-csv
PUT    /admin/usuarios/{id}
DELETE /admin/usuarios/{id}

POST   /cursos/{id}/matricular
POST   /cursos/{id}/desmatricular
POST   /cursos/{id}/matricular-masivo
GET    /cursos/{id}/estudiantes
```

### 5.15 Banco docente (nuevo)

```http
GET    /banco-docente/plantillas
POST   /banco-docente/plantillas
PUT    /banco-docente/plantillas/{id}
DELETE /banco-docente/plantillas/{id}
POST   /banco-docente/plantillas/{id}/usar
```

### 5.16 Integración SISA

```http
GET    /sisa/asignaturas-disponibles
POST   /sisa/generar-curso
POST   /sisa/sync-docentes
POST   /sisa/sync-estudiantes
GET    /sisa/estado-sync
```

### 5.17 Sistema de notas externo

```http
POST   /sync/enviar-notas
GET    /sync/estado
```

---

## 6. Integraciones externas

### 6.1 SISA (ya existe)

Se requieren endpoints públicos con token estático tipo `PROGRAMAS_API_TOKEN`:

- `GET /api/programas-analiticos`
- `GET /api/export/documentacion-asignatura?codigo=X`
- `GET /api/export/documentacion-carrera?carrera_id=X`
- `GET /api/docentes`
- `GET /api/docentes/{id}/grupos?gestion=Y`
- `GET /api/grupos/{id}/cronograma`
- `GET /api/carreras/{id}`

### 6.2 Sistema de Estudiantes (a definir)

Contrato mínimo sugerido:

- `GET /api/estudiantes?grupo_id=X&gestion=Y`
- `GET /api/estudiantes/{id}`
- `GET /api/matriculas?estudiante_id=X&gestion=Y`

### 6.3 Sistema de Notas Centralizado (a definir)

Contrato mínimo sugerido:

- `POST /api/notas` — enviar lote de calificaciones.
- `GET /api/notas?curso_id=X&gestion=Y` — verificar notas enviadas.

Formato de envío:

```json
{
  "curso_codigo": "SIS-401",
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

### 6.4 SSO SISA

- Validar token Sanctum contra SISA.
- Si el usuario no existe en LMS, crearlo cacheando datos básicos.
- Generar token propio (JWT/Sanctum) para el frontend.

---

## 7. Consideraciones técnicas

### 7.1 Arquitectura sugerida (Laravel 12)

```
back/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Controladores por dominio
│   │   ├── Middleware/         # RoleMiddleware
│   │   └── Requests/           # Form requests
│   ├── Models/                 # Eloquent + relaciones
│   ├── Services/               # Lógica de negocio e integraciones
│   ├── Repositories/           # Opcional: abstracción de datos
│   ├── Interfaces/             # Contratos para actividades extensibles
│   ├── Jobs/                   # Syncs en cola
│   └── Policies/               # Autorización
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
└── tests/
```

### 7.2 Polimorfismo de actividades

Cada actividad implementa una interfaz común para que el frontend las trate de forma uniforme:

```php
interface TipoActividad
{
    public function metadata(): array;          // icono, label, color
    public function renderBuilder(): array;     // config para vista docente
    public function renderEstudiante(): array;  // config para vista estudiante
    public function calificar(Entrega $entrega): ?Calificacion;
    public function validarEntrega(array $datos): bool;
}
```

### 7.3 Files / Storage

- MinIO para desarrollo.
- S3-compatible para producción.
- URLs firmadas de corta duración para descargas privadas.

### 7.4 Colas y jobs

- Redis como driver de colas.
- Jobs:
  - `SyncCursosFromSisa`
  - `SyncEstudiantesJob`
  - `SyncNotasJob`
  - `EnviarNotificacionJob`

### 7.5 Autorización

- Roles en middleware.
- Policies para acciones sobre cursos, actividades, entregas.
- Docente solo ve/edita sus cursos.
- Estudiante solo ve cursos donde está matriculado.
- Director ve cursos de su carrera.
- Admin accede a todo.

### 7.6 Respuestas JSON estándar

```json
{
  "data": { ... },
  "meta": { ... }
}
```

Errores:

```json
{
  "error": true,
  "message": "Descripcion del error",
  "errors": { "campo": ["Error"] }
}
```

---

## 8. Comunicación con el frontend

### 8.1 Base URL

```env
VITE_API_URL=http://localhost:8000/api
```

### 8.2 Cliente Axios

Ya existe en `Aula-virtual/src/services/api.js`:

```js
const api = axios.create({ baseURL: process.env.VITE_API_URL })
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})
```

### 8.3 Stores a adaptar

Cada store de Pinia debe reemplazar el fallback mock por llamadas Axios:

- `src/stores/auth.js`
- `src/stores/cursos.js`
- `src/stores/actividades.js`
- `src/stores/notificaciones.js`
- `src/stores/suiteRoles.js`
- `src/stores/herramientasDocente.js`

### 8.4 Estrategia de migración

1. Implementar backend endpoint por endpoint.
2. En el frontend, ir cambiando los servicios para usar Axios en lugar de mocks.
3. Mantener un flag de desarrollo `VITE_USE_MOCK=true` para poder trabajar sin backend cuando sea necesario.

---

## 9. Próximos pasos inmediatos

1. **Crear estructura Laravel 12** en `back/`.
2. **Configurar Docker Compose** con nginx, php-fpm, mysql, redis, minio.
3. **Implementar migraciones base**: usuarios, cursos, secciones, actividades, matrículas.
4. **Implementar autenticación SSO** contra SISA.
5. **Exponer CRUD de cursos** y que el frontend `MisCursosPage` lo consuma.
6. **Definir/confirmar contratos** con Sistema de Estudiantes y Sistema de Notas.

---

## 10. Documentos relacionados

- `PLAN_LMS_UNITEPC.md` — Plan maestro con diagramas, stack y fases originales.
- `Aula-virtual/API_CONTRACTS.md` — Contratos API REST detallados.
- `Aula-virtual/CONTEXTO_PROYECTO.md` — Estado actual del frontend.
- `Aula-virtual/CAMBIOS_VISUALES.md` — Cambios visuales y de UX/UI aplicados.
- `Aula-virtual/DESIGN_SYSTEM.md` — Tokens de diseño UNITEPC.
- `AGENTS.md` — Instrucciones para agentes en este workspace.
