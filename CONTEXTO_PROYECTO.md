# Contexto del Proyecto — Aula Virtual LMS UNITEPC

> Documento generado el: Junio 2026
> Proyecto: Plataforma LMS para reemplazar Moodle en la Universidad Técnica Privada Cosmos (UNITEPC)
> Autor: Harold Rojas

---

## 1. Visión General

**Aula Virtual** es un Learning Management System (LMS) independiente, liviano y extensible, construido como SPA (Single Page Application) con Vue 3 + Quasar. Se conecta vía API REST a 3 sistemas externos de UNITEPC:

- **SISA** — Sistema existente que provee el PAC (estructura del curso: unidades, temas, logros), docentes, grupos y cronograma maestro.
- **Sistema de Estudiantes** (externo, API a definir) — Provee matrícula y datos de estudiantes por grupo.
- **Sistema de Notas Centralizado** (externo, API a definir) — Recibe calificaciones desde el LMS.
- **SSO SISA** — Autenticación unificada vía tokens Sanctum.

Actualmente el proyecto se encuentra en **fase de prototipo frontend** con datos mock. Las 4 fases del plan de desarrollo frontend están completadas. El backend Laravel 12 está planificado pero no implementado.

---

## 2. Herramientas de Desarrollo

### 2.1 Codex Desktop (Agente IA)

Codex Desktop es el agente de IA utilizado actualmente para el desarrollo. El proyecto migró desde OpenCode manteniendo CodeGraph como MCP global.

Configuración activa en `C:\Users\harol\.codex\config.toml`:

```toml
[mcp_servers.codegraph]
command = "codegraph"
args = ["serve", "--mcp", "--no-watch"]
```

Como se usa `--no-watch`, el índice se actualiza manualmente antes de sesiones grandes con:

```powershell
cd "C:\PROYECTOS\PROYECTO AULA VIRTUAL\Aula-virtual"
codegraph sync .
```

### 2.2 CodeGraph (MCP Server)

CodeGraph es el servidor MCP (Model Context Protocol) que indexa el código fuente en una base SQLite para permitir búsquedas semánticas y análisis de dependencias.

- **Binario:** `codegraph`
- **Modo:** `serve --mcp --no-watch`
- **Índice:** `Aula-virtual/.codegraph/codegraph.db`
- **Logs:** `Aula-virtual/.codegraph/errors.log`
- **Funciones:** Búsqueda de símbolos, análisis de llamadas (callers/callees), exploración de archivos, trazado de rutas de ejecución.

### 2.3 Skills de Codex

Skills migradas y limpiadas en `C:\Users\harol\.codex\skills\`:

| Skill | Propósito |
|---|---|
| `aula-virtual-frontend-design-system` | Guía para UI premium con Vue 3, Quasar 2, Tailwind, TailAdmin y branding UNITEPC |
| `aula-virtual-laravel-patterns` | Patrones de arquitectura Laravel 12 para el backend futuro |
| `aula-virtual-tailwind-design-system` | Sistema de diseño con Tailwind CSS: tokens, responsive, dark mode y accesibilidad |
| `quasar-solana-no-usar` | Skill heredado de Solana; no usar para Aula Virtual ni Quasar Vue |

### 2.4 Plugins y tooling

- **Browser (Codex):** validación de UI local en `http://localhost:9000`
- **Google Drive (Codex):** solo para documentos externos, contratos o planillas conectadas
- **Documents / Spreadsheets / Presentations (Codex):** entregables formales; no editar código con estos plugins

- **ESLint:** v9 con configuración plana (`eslint.config.js`), plugin vue y prettier
- **Prettier:** v3.3.3 con configuración en `.prettierrc.json`
- **Vite Plugin Checker:** Verificación ESLint en tiempo de compilación
- **@intlify/unplugin-vue-i18n:** Compilación de mensajes i18n

---

## 3. Stack Tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Frontend | Vue.js | 3.5.20 |
| UI Framework | Quasar | 2.16.x |
| Estado Global | Pinia | 3.x |
| Router | Vue Router | 4.x |
| HTTP Client | Axios | 1.x |
| i18n | vue-i18n | 11.x |
| Build | Vite (via Quasar CLI) | — |
| CSS Framework | Tailwind CSS | 4.3.x |
| Pre-procesador CSS | SCSS (dart-sass) | — |
| Animaciones | anime.js | 3.2.2 |
| Editor Rich Text | Tiptap (ProseMirror) | 3.24.x |
| Charts | Chart.js + vue-chartjs | 4.5 / 5.3 |
| Drag & Drop | vuedraggable | 4.1 |
| Date Utils | date-fns | 4.4 |
| H5P | h5p-standalone | 3.8 |
| ZIP | jszip | 3.10 |
| Utilidades Vue | @vueuse/core | 14.3 |

---

## 4. Estructura del Repositorio

```
C:\PROYECTOS\PROYECTO AULA VIRTUAL\
├── .git/                              # Repositorio Git
├── AGENTS.md                          # Instrucciones para Codex en la raíz
├── .opencode/
│   ├── opencode.json                  # Configuración histórica de OpenCode
│   └── .gitignore                     # Ignorados de OpenCode histórico
│
├── Aula-virtual/                      # ★ Aplicación principal (Quasar SPA)
│   ├── .codegraph/
│   │   ├── codegraph.db               # Índice de código (SQLite)
│   │   └── errors.log                 # Logs de CodeGraph
│   ├── .opencode/skills/              # Skills históricos de OpenCode
│   │   ├── frontend-design-system/
│   │   ├── laravel-patterns/
│   │   ├── quasar-solana-no-usar/
│   │   └── tailwind-design-system/
│   ├── src/
│   │   ├── App.vue                    # Componente raíz
│   │   ├── assets/                    # Recursos estáticos
│   │   ├── boot/                      # Archivos de inicialización
│   │   │   ├── auth.js                # Restaurar sesión desde localStorage
│   │   │   ├── axios.js               # Cliente HTTP global
│   │   │   └── i18n.js                # Configuración de internacionalización
│   │   ├── components/                # Componentes reutilizables
│   │   │   ├── actividades/           # 6 componentes de actividad
│   │   │   ├── calificaciones/        # RubricaEditor.vue
│   │   │   ├── curso-builder/         # BlockPalette, BuilderCanvas
│   │   │   ├── tailadmin/             # TaCard, TaButton, TaInput, TaKpiCard, TaLoadingScreen, TaPageHeader
│   │   │   └── ui/                    # AppSkeleton
│   │   ├── composables/
│   │   │   └── useAnimations.js       # 9 composables de animación (anime.js)
│   │   ├── css/
│   │   │   ├── app.scss               # Estilos globales (sidebar, notificaciones, scrollbar)
│   │   │   ├── tailwind.css           # Tailwind v4 @theme (+ tokens personalizados)
│   │   │   ├── quasar.variables.scss  # Variables Quasar (brand colors)
│   │   │   ├── design-system.scss     # Tokens de diseño (radii, shadows, glass)
│   │   │   └── tailadmin-theme.scss   # Tema light/dark completo
│   │   ├── i18n/                      # Traducciones (en-US)
│   │   ├── layouts/
│   │   │   └── MainLayout.vue         # Layout multi-rol (header + sidebar dinámico)
│   │   ├── mock/                      # Datos mock
│   │   │   ├── index.js               # Barrel export
│   │   │   └── data/                  # 6 archivos JSON/JS mock
│   │   ├── pages/                     # 18 páginas
│   │   │   ├── auth/                  # LoginPage, DevAccessPage
│   │   │   ├── docente/               # 5 páginas (cursos, builder, preview, dashboard, calificar)
│   │   │   ├── estudiante/            # 4 páginas (dashboard, cursos, ver-curso, notas)
│   │   │   ├── director/              # 3 páginas (dashboard, seguimiento, reportes)
│   │   │   ├── admin/                 # AdminPage (gestión, KPIs, sync)
│   │   │   └── ErrorNotFound.vue, IndexPage.vue, TestLoadingPage.vue
│   │   ├── router/
│   │   │   ├── index.js               # Instancia Vue Router + guard global
│   │   │   └── routes.js              # Definición de rutas por rol
│   │   ├── services/                  # Servicios API con fallback mock
│   │   │   ├── api.js                 # Axios instance (baseURL, interceptors)
│   │   │   ├── authService.js         # Login/Logout/Me
│   │   │   ├── cursoService.js        # CRUD cursos + secciones
│   │   │   ├── actividadService.js    # CRUD actividades
│   │   │   └── calificacionService.js # Libro calificaciones + sync
│   │   └── stores/                    # Estado global Pinia
│   │       ├── index.js               # Instancia Pinia
│   │       ├── auth.js                # Autenticación (usuario, token, roles)
│   │       ├── cursos.js              # CRUD cursos
│   │       ├── actividades.js         # CRUD actividades, entregas, foros, quizzes
│   │       └── notificaciones.js      # Notificaciones con localStorage
│   ├── public/                        # Archivos públicos
│   ├── dist/                          # Build de producción
│   ├── AGENTS.md                      # Instrucciones para Codex
│   ├── API_CONTRACTS.md               # Especificación de endpoints REST
│   ├── DESIGN_SYSTEM.md               # Documentación del sistema de diseño
│   ├── CONTEXTO_PROYECTO.md           # ★ Este documento
│   ├── README.md                      # Readme del proyecto
│   ├── package.json                   # Dependencias y scripts
│   ├── quasar.config.js               # Configuración de Quasar (brand, plugins, vite)
│   ├── postcss.config.js              # PostCSS con autoprefixer
│   ├── eslint.config.js               # ESLint flat config
│   └── index.html                     # Entry point HTML
│
├── plantilla front/                   # TailAdmin Vue Pro v2.3.0 (plantilla de referencia)
│   └── vue-tailwind-admin-dashboard-main/
│
├── skills/                            # Skills heredadas/locales (+ archivos .zip)
│   ├── frontend-design-system/
│   ├── laravel-patterns/
│   ├── quasar-solana-no-usar/
│   └── tailwind-design-system/
│
└── PLAN_LMS_UNITEPC.md                # Plan maestro del proyecto (backlog, BD, integraciones)
```

---

## 5. Arquitectura del Sistema

### 5.1 Diagrama de Integración

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
   │(existe) │   │  (externo)  │  │ (externo) │ │SISA │
   └─────────┘   └──────────────┘  └───────────┘ └─────┘
```

### 5.2 Flujo de Datos (Actual — Mock)

```
Páginas Vue → Stores Pinia → Servicios (mock fallback) → Datos mock JSON
                 ↓
         Componentes UI ← MainLayout (header/sidebar)
```

### 5.3 Flujo de Datos (Futuro — con Backend)

```
Páginas Vue → Stores Pinia → Servicios (Axios) → API Laravel 12 → MySQL
                                  ↓                          ↓
                           Interceptor 401           SISA / Sist. Estudiantes
                                  ↓                          ↓
                           Auth Store                 Servicios de Sync
```

---

## 6. Módulos del Sistema

### 6.1 Autenticación y Roles

- 4 roles: `docente`, `estudiante`, `director`, `admin`
- Login mock con selección de rol + DevAccessPage para desarrollo
- Store `auth.js`: persistencia en localStorage, guards de ruta por rol
- Redirect automático al dashboard del rol en `/`

### 6.2 Dashboard Docente

- KPIs: cursos activos, total estudiantes, promedio general, pendientes de calificar
- Gráficos Chart.js: distribución de notas, promedio por actividad
- Entregas recientes, próximos eventos

### 6.3 Dashboard Estudiante

- Layout 3 columnas con Quasar + Tailwind + anime.js
- Cards con progreso, entregas pendientes, actividades próximas
- Animaciones stagger y reflection hover

### 6.4 Dashboard Director

- KPIs y métricas agregadas por carrera
- Alertas de cursos sin actividad
- Gráficos de rendimiento por curso

### 6.5 Gestión de Cursos (Docente)

- `MisCursosPage`: Cards de cursos + diálogo crear curso
- Wizard "Generar desde SISA" (3 pasos: seleccionar materia, mapeo PAC, confirmar)
- `CursoBuilderPage`: CRUD visual de secciones y actividades con drag & drop (vuedraggable)
- Modal de actividad por tipo con formularios específicos
- `CursoPreviewPage`: Vista previa del lado del estudiante

### 6.6 Experiencia Estudiante

- `VerCursoPage`: Navegación lateral de secciones, progreso, pendientes
- 6 componentes de actividad:
  - **Lección** (Tiptap render, archivos adjuntos)
  - **Tarea** (subir archivos, texto, estados)
  - **Foro** (hilos, respuestas, adjuntos)
  - **Cuestionario** (temporizador, auto-envío, resultados)
  - **Encuesta** (preguntas con opciones)
  - **H5P** (contenido interactivo embebido)

### 6.7 Calificaciones y Reportes

- `CalificarPage`: Tabla interactiva (estudiantes × actividades) con promedios
- `RubricaEditor`: Editor visual de rúbricas con criterios y niveles
- Exportación CSV real y PDF mock
- `MisNotasPage`: Vista estudiante con retroalimentación y distribución
- `ReportesPage`: Reportes exportables del director

### 6.8 Panel Admin

- KPIs del sistema: cursos, usuarios, actividades, entregas
- Gestión de cursos (archivar, duplicar)
- Gestión de usuarios (listar, forzar re-sync)
- Configuración de conexiones API externas
- Logs de sincronización con estado

---

## 7. Datos Mock

| Archivo | Contenido |
|---|---|
| `mock/data/usuarios.js` | 2 docentes, 1 director, 1 admin, 20 estudiantes |
| `mock/data/cursos.js` | 3 cursos con secciones y temas |
| `mock/data/actividades.js` | 30 actividades de 6 tipos |
| `mock/data/matriculas.js` | 3 cursos × estudiantes |
| `mock/data/calificaciones.js` | Notas aleatorias por estudiante/actividad |
| `mock/data/entregas_estudiante.js` | 264 registros de entregas |

---

## 8. Sistema de Diseño — Paleta UNITEPC

### 8.1 Colores Institucionales

| Token | Color | HEX | RGB |
|---|---|---|---|
| UNI (Primary) | Púrpura | `#6B3FA0` | `107, 63, 160` |
| TEPC (Accent) | Teal | `#0D9488` | `13, 148, 136` |
| Highlight | Ámbar | `#F59E0B` | `245, 158, 11` |

### 8.2 Escala Primary (Tailwind)

| 50 | 100 | 200 | 300 | 400 | **500** | 600 | 700 | 800 | 900 |
|---|---|---|---|---|---|---|---|---|---|
| #F3E8FF | #E9D5FF | #D8B4FE | #C084FC | #A855F7 | **#6B3FA0** | #5A2E8C | #4A1F7A | #3D1A66 | #2D1B4E |

### 8.3 Header y Sidebar

| Elemento | Light Mode | Dark Mode |
|---|---|---|
| **Header** | `#5A2E8C` (primary-600) | `#2D1B4E` (primary-900) |
| **Sidebar** | `#6B3FA0` (primary) | `#2D1B4E` (primary-900) |
| **Header text/icons** | `#FFFFFF` | `#FFFFFF` |
| **Sidebar items** | White text, white hover/active overlays | White text, white hover/active overlays |

### 8.4 Tokens de Diseño

| Token | Valor |
|---|---|
| Card radius | 20px |
| Button radius | 10px |
| Input radius | 12px |
| Shadow card | `0 1px 3px rgba(0,0,0,0.08)` |
| Shadow card hover | `0 10px 40px rgba(0,0,0,0.12)` |

---

## 9. Fases de Desarrollo Completadas

### Fase 0 (E1) — Auth + Dashboards
- Login mock SSO con 4 roles
- Guards de router por rol
- MainLayout multi-rol con menús dinámicos
- Librerías clave instaladas (vuedraggable, tiptap, chart.js, vueuse)
- Mock data base (usuarios, cursos, actividades, matrículas)
- CodeGraph MCP configurado
- Dashboards por rol con KPIs, entregas, distribución, alertas
- MisNotasPage con retroalimentación

### Fase 1 (E2) — Experiencia Docente
- MisCursosPage con cards y diálogo crear curso
- Wizard "Generar desde SISA" (3 pasos con stepper)
- CursoBuilderPage con drag & drop real (vuedraggable)
- CRUD visual de secciones (agregar, editar, eliminar, reordenar)
- CRUD de actividades con formularios específicos por tipo
- Flujo publicar/despublicar curso

### Fase 2 (E3) — Experiencia Estudiante
- Dashboard Estudiante moderno (3 columnas, Quasar + Tailwind + anime.js)
- 6 componentes de actividad interactivos (Lección, Tarea, Foro, Cuestionario, Encuesta, H5P)
- VerCursoPage con navegación lateral, progreso y pendientes
- Mock de entregas, foros, cuestionarios con intentos
- Temporizador de cuestionario con auto-envío
- Paleta de colores institucional UNITEPC

### Fase 3 (E4) — Calificaciones + Reportes
- Libro de calificaciones (tabla interactiva, promedios, exportar CSV/PDF)
- Rúbrica visual de evaluación con criterios y niveles
- Dashboards con gráficos Chart.js
- Reportes exportables (CSV real, PDF mock)
- Panel admin con KPIs, gestión de cursos/usuarios, conexiones API, logs de sync

---

## 10. Estado Actual del Proyecto

| Componente | Estado |
|---|---|
| Frontend (Vue 3 + Quasar) | ✅ 4 fases completadas |
| Mock Data | ✅ 6 archivos, datos completos |
| Autenticación mock | ✅ 4 roles funcionales |
| Dashboard Docente | ✅ KPIs, gráficos, entregas |
| Dashboard Estudiante | ✅ Layout moderno con animaciones |
| Dashboard Director | ✅ KPIs, alertas, rendimiento |
| Gestión de Cursos | ✅ CRUD completo + builder drag & drop |
| Wizard SISA | ✅ 3 pasos (mock) |
| Actividades (6 tipos) | ✅ Componentes funcionales |
| Cuestionario con timer | ✅ Auto-envío al agotarse |
| Foro con hilos | ✅ Crear, responder, adjuntar |
| Libro de Calificaciones | ✅ Tabla interactiva + exportación |
| Rúbrica de Evaluación | ✅ Editor visual |
| Panel Admin | ✅ KPIs, gestión, sync |
| CodeGraph Index | ✅ Base de datos SQLite generada |
| Contratos API | ✅ Documentados (API_CONTRACTS.md) |
| Sistema de Diseño | ✅ Documentado (DESIGN_SYSTEM.md) |
| Branding UNITEPC | ✅ Colores consistentes en todo el sistema |
| **Backend Laravel 12** | ❌ No iniciado |
| **Integración SISA real** | ❌ Pendiente |
| **Integración Sist. Estudiantes** | ❌ Pendiente |
| **Integración Sist. Notas** | ❌ Pendiente |
| **Autenticación SSO real** | ❌ Pendiente |

---

## 11. Próximos Pasos Planificados

1. **Backend Laravel 12:** Migraciones, modelos, controladores, servicios de integración
2. **Integración SISA:** Endpoints reales para consumir PAC, docentes, grupos
3. **Integración Sistema de Estudiantes:** Endpoints reales para matrícula
4. **Integración Sistema de Notas:** Envío de calificaciones al sistema centralizado
5. **Autenticación SSO real:** Tokens Sanctum contra SISA
6. **Builder Canvas (avanzado):** Lienzo visual con paleta de bloques arrastrables
7. **Notificaciones:** Sistema de notificaciones in-app + email
8. **Cuestionario avanzado:** Más tipos de pregunta, banco de preguntas
9. **Plugins:** Arquitectura extensible para nuevos tipos de actividad
10. **Pruebas:** Vitest + Playwright

---

## 12. Comandos Útiles

```bash
npm run dev        # Iniciar servidor de desarrollo (localhost:9000)
npm run build      # Compilar para producción
npm run lint       # Ejecutar ESLint
npm run format     # Ejecutar Prettier
```

> **Nota:** El servidor de desarrollo debe reiniciarse (Ctrl+C → npm run dev) después de editar `quasar.variables.scss`, `design-system.scss` o `tailadmin-theme.scss` — HMR no recompila estos archivos.
