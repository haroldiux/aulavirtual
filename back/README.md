# Backend Aula Virtual UNITEPC (Laravel 12)

API REST del LMS UNITEPC. Fase A: fundación, migraciones base, autenticación SSO (stub + fallback local) y CRUD de cursos.

> Stack: Laravel 12 + PHP 8.2 + MySQL 8 + Sanctum + Redis + MinIO (vía Docker Compose).

## Requisitos

- Docker Desktop (recomendado) o PHP 8.2 + Composer + MySQL local.
- El frontend prevé consumir `http://localhost:8000/api` (ver `Aula-virtual/src/services/api.js`).

## Levantar con Docker (recomendado)

> El proyecto completo (back + front + bd) se levanta desde la **raíz del workspace** con un único compose de 3 contenedores. Ver `../docker-compose.yml`.

### Modo desarrollo (front con HMR, código montado)

```bash
# desde la raíz del workspace (C:\PROYECTOS\PROYECTO AULA VIRTUAL)
docker compose up -d --build
```

- Front (Vite HMR): http://localhost:9000
- API Laravel: http://localhost:8000/api
- MySQL: localhost:3306 (db `aula_virtual`, user `aula` / `aula_secret`, root `secret`)
- El `entrypoint` del back instala composer, genera APP_KEY, migra y siembra automáticamente.

### Modo producción (front compilado + nginx, sin volúmenes)

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

- Front (nginx + proxy `/api`): http://localhost:8080
- API Laravel: http://localhost:8000/api

### Comandos útiles

```bash
docker compose logs -f back          # logs del backend (nginx + php-fpm)
docker compose logs -f front         # logs del frontend
docker compose exec back bash        # shell dentro del back
docker compose exec back php artisan migrate
docker compose exec db mysql -uaula -paula_secret aula_virtual
docker compose down                  # detener (mantiene el volumen de la BD)
docker compose down -v               # detener y borrar la BD
```

> Redis y MinIO se retiraron del compose de 3 contenedores. Laravel usa `database`/`sync`/`local` como fallback (ver `.env`). Cuando llegue la Fase B (archivos) o se necesiten colas, sumarlos al compose y volver a `redis`/`s3` en `.env`.

## Alternativa local (sin Docker)

```bash
cd back
composer install
cp .env.example .env
# editar DB_HOST=127.0.0.1 y crear la BD aula_virtual en MySQL
php artisan key:generate
php artisan migrate --seed
php artisan serve   # http://localhost:8000
```

## Autenticación

El login acepta dos modos (definidos en `app/Services/SisaAuthService.php` y `app/Http/Controllers/Api/AuthController.php`):

1. **SSO SISA (stub)** — `POST /api/auth/login` con `{"sisa_token": "..."}`. Tokens de demostración en `SisaAuthService::STUB_USUARIOS` (p. ej. `sisa-token-docente-1`). Cuando se obtengan las credenciales reales, setear `SISA_STUB_ENABLED=false`, `SISA_API_URL` y `SISA_API_TOKEN`.
2. **Login local (fallback)** — `{"email":"...","password":"..."}` útil para desarrollo/admin. Contraseña de los usuarios sembrados: `clave-aula-2026`.

Respuesta exitosa:
```json
{ "data": { "usuario": {...}, "token": "...", "rol": "docente" } }
```

Los endpoints protegidos usan `Authorization: Bearer {token}` (Sanctum).

## Endpoints (Fase A)

```
POST   api/auth/login
POST   api/auth/logout        (auth)
GET    api/auth/me            (auth)

GET    api/cursos             (auth)            ?estado=&gestion=&per_page=
GET    api/cursos/{curso}     (auth)
POST   api/cursos             (auth, role:docente|director|admin)
PUT    api/cursos/{curso}     (auth, role:docente|director|admin)
PUT    api/cursos/{curso}/publicar
PUT    api/cursos/{curso}/archivar
DELETE api/cursos/{curso}     (archiva)
GET    sanctum/csrf-cookie
```

## Usuarios sembrados

| Rol | Email | Password |
|---|---|---|
| Docente | carlos.mendoza@unitepc.edu | clave-aula-2026 |
| Docente | lucia.fernandez@unitepc.edu | clave-aula-2026 |
| Director | roberto.suarez@unitepc.edu | clave-aula-2026 |
| Admin | admin@unitepc.edu | clave-aula-2026 |
| Estudiante | ana.vargas@estudiante.unitepc.edu | clave-aula-2026 |

## Modelo de datos

Migraciones en `database/migrations/`: usuarios, cursos, secciones, actividades, matriculas, entregas, calificaciones, notificaciones, configuraciones y personal_access_tokens (Sanctum). El modelo relacional completo está en `../BACKEND_HANDOFF.md` (sección 4).

## Estructura

```
app/
  Enums/        Rol, EstadoCurso, EstadoEntrega, EstadoMatricula, TipoActividad
  Http/
    Controllers/Api/   AuthController, CursoController
    Middleware/        RoleMiddleware
    Requests/          LoginRequest, StoreCursoRequest, UpdateCursoRequest
    Resources/         UsuarioResource, CursoResource, SeccionResource, ActividadResource
  Models/       Usuario, Curso, Seccion, Actividad, Matricula, Entrega, Calificacion, Notificacion, Configuracion
  Services/     SisaAuthService (stub SSO)
config/  sisa.php, cors.php, auth.php
database/seeders/  DatabaseSeeder
docker/  nginx/, php/
docker-compose.yml, Dockerfile
```

## Próximas fases (ver BACKEND_HANDOFF.md)

- **Fase B** — CRUD secciones/actividades + tipos polimórficos + MinIO.
- **Fase C** — Entregas, calificaciones, rúbricas, cálculo de promedios.
- **Fase D** — Integraciones reales (SISA, Sistema de Estudiantes, Sistema de Notas).
- **Fase E** — Calendario, mensajería, gestión masiva, banco docente, reportes.