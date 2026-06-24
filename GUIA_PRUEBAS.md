# Guía de Pruebas — Aula Virtual UNITEPC

> Sistema dockerizado con 4 contenedores: `db` (MySQL), `back` (Laravel 12 API), `front` (Vue 3 + Quasar), `minio` (S3 para archivos).
>
> **Fase A:** Auth + Cursos → API real
> **Fase B:** Secciones, Actividades, Foros, Encuestas, Cuestionarios, Archivos MinIO → API real
> **Fase C:** Entregas, Calificaciones, Rúbricas, Promedios, Mis Notas → API real
> **Fase D:** Integraciones SISA (cursos, estudiantes, notas) + Notificaciones → API real (stubs)
> **Pendiente (Fase E):** Calendario, Mensajería, Gestión masiva, Banco docente, Reportes avanzados → mock local

---

## 0. Levantar el sistema

```bash
# Desde la raíz del workspace
docker compose up -d --build
```

Verificar que los 4 contenedores estén corriendo:

```bash
docker compose ps
```

Deberías ver:
- `aula_db` — healthy — puerto 3307
- `aula_back` — running — puerto 8000
- `aula_front` — running — puerto 9000
- `aula_minio` — running — puertos 9090 (API) / 9091 (consola)

URLs:
- **Frontend:** http://localhost:9000
- **API:** http://localhost:8000/api
- **MySQL:** localhost:3307 (user `aula` / `aula_secret`)
- **MinIO Consola:** http://localhost:9091 (minioadmin / minioadmin)
- **MinIO API:** http://localhost:9090

---

## 1. Probar Autenticación (API real)

### 1.1 Login con email y contraseña

1. Abrir http://localhost:9000 → redirige a `/login`
2. Ingresar credenciales reales del seeder:
   - **Email:** `carlos.mendoza@unitepc.edu`
   - **Contraseña:** `clave-aula-2026`
3. Clic en **Ingresar**
4. Debe redirigir a `/docente/cursos` y mostrar "Bienvenido, Dr. Carlos Mendoza"

### 1.2 Login vía SSO (tokens SISA stub)

1. En la página de login, clic en **Ingresar via SSO UNITEPC**
2. Entra como docente (token `sisa-token-docente-1`)
3. Debe redirigir al dashboard de docente

### 1.3 Accesos de desarrollo (login rápido por rol)

1. En la página de login, clic en **Accesos de desarrollo** (o ir a http://localhost:9000/#/dev-access)
2. Seleccionar un perfil → entra directamente con ese usuario
3. Probar con cada rol: docente, estudiante, director, admin

### 1.4 Logout

1. Estando logueado, clic en el avatar (esquina superior derecha)
2. Clic en **Cerrar Sesión**
3. Confirmar → redirige a `/login`

### 1.5 Persistencia de sesión

1. Loguearse
2. Recargar la página (F5)
3. Debe mantener la sesión (no redirigir a login)

---

## 2. Usuarios del seeder para pruebas

Todos con contraseña `clave-aula-2026`:

| Rol | Email | Nombre |
|-----|-------|--------|
| Docente | carlos.mendoza@unitepc.edu | Dr. Carlos Mendoza |
| Docente | lucia.fernandez@unitepc.edu | Ing. Lucia Fernandez |
| Director | roberto.suarez@unitepc.edu | Lic. Roberto Suarez |
| Admin | admin@unitepc.edu | Administrador UNITEPC |
| Estudiante | ana.vargas@estudiante.unitepc.edu | Ana Vargas |
| Estudiante | bruno.calle@estudiante.unitepc.edu | Bruno Calle |
| Estudiante | camila.paz@estudiante.unitepc.edu | Camila Paz |
| Estudiante | diego.rojas@estudiante.unitepc.edu | Diego Rojas |
| Estudiante | eliana.quispe@estudiante.unitepc.edu | Eliana Quispe |
| Estudiante | felix.mamani@estudiante.unitepc.edu | Felix Mamani |

Tokens SSO stub (para login vía SSO):
- `sisa-token-docente-1` → Dr. Carlos Mendoza
- `sisa-token-docente-2` → Ing. Lucia Fernandez
- `sisa-token-estudiante-1` → Ana Vargas
- `sisa-token-director-1` → Lic. Roberto Suarez

---

## 3. Probar Cursos (API real)

### 3.1 Listar cursos como docente

1. Login como `carlos.mendoza@unitepc.edu`
2. Ir a **Mis Cursos** (`/docente/cursos`)
3. Deben aparecer los cursos del seeder donde es docente:
   - SIS-401 Programacion Avanzada (publicado)
   - MAT-201 Calculo I (borrador)

### 3.2 Ver detalle de un curso (con secciones y actividades)

1. Estando en Mis Cursos, clic en un curso
2. Debe cargar el curso con sus secciones y actividades (vía `GET /api/cursos/{id}`)
3. Verificar que se vean las secciones (ej. "Unidad I - Introduccion") y actividades (tareas, lecciones, cuestionarios, foros)

### 3.3 Crear un curso nuevo

1. En Mis Cursos, clic en **Crear curso** o **Nuevo curso**
2. Completar nombre (obligatorio), código, descripción
3. Guardar
4. Debe aparecer en la lista con estado `borrador`

### 3.4 Editar un curso

1. Abrir un curso borrador
2. Ir al **Builder** (`/docente/curso/{id}/builder`)
3. Editar nombre o descripción
4. Guardar → debe persistir en la API

### 3.5 Publicar un curso

1. Abrir un curso en estado `borrador`
2. En el builder, clic en **Publicar**
3. Debe cambiar a estado `publicado` (vía `PUT /api/cursos/{id}/publicar`)
4. Verificar que el estudiante ahora puede verlo

### 3.6 Archivar un curso

1. Abrir un curso publicado
2. Clic en archivar/eliminar
3. Debe cambiar a estado `archivado` (vía `DELETE /api/cursos/{id}`)

---

## 4. Probar Secciones (API real — Fase B)

### 4.1 Ver secciones de un curso

1. Login como docente
2. Abrir un curso en el **Builder**
3. Verificar que las secciones del seeder aparecen (ej. "Unidad I - Introduccion", "Unidad II - Patrones")

### 4.2 Crear una sección

1. En el builder, clic en **Nueva sección**
2. Escribir título (ej. "Unidad III - Testing") y descripción
3. Guardar
4. La sección debe aparecer inmediatamente
5. Verificar en la BD:
   ```bash
   docker compose exec db mysql -uaula -paula_secret aula_virtual -e "SELECT id, curso_id, titulo, orden FROM secciones WHERE curso_id=1;"
   ```

### 4.3 Editar una sección

1. En el builder, clic en editar sección
2. Cambiar el título
3. Guardar
4. Verificar que el cambio persiste al recargar

### 4.4 Eliminar una sección

1. En el builder, clic en eliminar sección
2. Confirmar
3. La sección y todas sus actividades deben desaparecer (cascadeOnDelete)

### 4.5 Reordenar secciones

1. En el builder, arrastrar secciones para reordenar
2. El orden debe persistir

---

## 5. Probar Actividades (API real — Fase B)

### 5.1 Ver actividades de una sección

1. Abrir un curso en el builder
2. Verificar que cada sección muestra sus actividades (tareas, lecciones, cuestionarios, foros, encuestas)

### 5.2 Crear una actividad

1. En el builder, seleccionar una sección
2. Clic en **Agregar actividad** o arrastrar desde la paleta
3. Elegir tipo (leccion, tarea, foro, cuestionario, encuesta, h5p)
4. Completar título y configuración
5. Guardar
6. La actividad debe aparecer en la sección
7. Verificar en la BD:
   ```bash
   docker compose exec db mysql -uaula -paula_secret aula_virtual -e "SELECT id, seccion_id, tipo, titulo, tiene_nota FROM actividades ORDER BY id DESC LIMIT 5;"
   ```

### 5.3 Editar una actividad

1. En el builder, clic en editar actividad
2. Cambiar título, descripción o configuración
3. Guardar → debe persistir en la API

### 5.4 Eliminar una actividad

1. En el builder, clic en eliminar actividad
2. Confirmar
3. La actividad debe desaparecer

### 5.5 Reordenar actividades

1. En el builder, arrastrar actividades dentro de una sección
2. El orden debe persistir

---

## 6. Probar Foros (API real — Fase B)

### 6.1 Ver hilos de un foro

1. Login como estudiante (`ana.vargas@estudiante.unitepc.edu`)
2. Abrir el curso SIS-401 Programacion Avanzada
3. Ir a la sección "Unidad II - Patrones"
4. Abrir el foro "Patrones creacionales"
5. Deben aparecer los hilos del seeder:
   - "Duda sobre Singleton" (con 1 respuesta)
   - "Ejemplo de Factory Method"

### 6.2 Crear un hilo nuevo

1. Estando en el foro, clic en **Nuevo hilo**
2. Escribir título y contenido
3. Enviar
4. El hilo debe aparecer en la lista inmediatamente
5. Verificar en la BD:
   ```bash
   docker compose exec db mysql -uaula -paula_secret aula_virtual -e "SELECT id, actividad_id, autor_id, titulo FROM foro_hilos ORDER BY id DESC LIMIT 3;"
   ```

### 6.3 Responder a un hilo

1. Clic en un hilo existente
2. Escribir respuesta en el campo inferior
3. Enviar
4. La respuesta debe aparecer bajo el hilo

### 6.4 Foro anónimo

1. Como docente, crear una actividad de tipo foro con `anonimo: true` en el config
2. Como estudiante, crear un hilo — debe mostrar "Anonimo" como autor

---

## 7. Probar Encuestas (API real — Fase B)

### 7.1 Responder una encuesta

1. Login como estudiante (`ana.vargas@estudiante.unitepc.edu`)
2. Abrir el curso MAT-201 Calculo I (borrador — cambiar a publicado primero como docente)
3. Ir a la sección "Unidad I"
4. Abrir la "Encuesta diagnostica"
5. Responder las preguntas
6. Enviar
7. Debe mostrar confirmación "Encuesta enviada"
8. Verificar en la BD:
   ```bash
   docker compose exec db mysql -uaula -paula_secret aula_virtual -e "SELECT id, actividad_id, estudiante_id, respuestas FROM encuesta_respuestas;"
   ```

### 7.2 Verificar que no se puede responder dos veces

1. Recargar la página de la encuesta
2. Debe mostrar que ya fue respondida (no permitir responder de nuevo)

### 7.3 Ver resultados (como docente)

1. Login como docente
2. Abrir la encuesta
3. Debe poder ver los resultados agregados (conteo por opción)

---

## 8. Probar Cuestionarios (API real — Fase B)

### 8.1 Ver intentos disponibles

1. Login como estudiante (`bruno.calle@estudiante.unitepc.edu`)
2. Abrir el curso SIS-401 Programacion Avanzada
3. Ir a "Unidad I - Introduccion"
4. Abrir el "Cuestionario inicial"
5. Debe mostrar "Intentos disponibles: 2" (config del seeder)

### 8.2 Realizar un cuestionario

1. Clic en **Iniciar cuestionario**
2. Responder las preguntas (2 preguntas de opción múltiple)
3. Clic en **Finalizar**
4. Debe mostrar la nota calculada automáticamente
5. Verificar en la BD:
   ```bash
   docker compose exec db mysql -uaula -paula_secret aula_virtual -e "SELECT id, actividad_id, estudiante_id, nota, respuestas FROM cuestionario_intentos ORDER BY id DESC LIMIT 3;"
   ```

### 8.3 Verificar calificación automática

1. Responder correctamente todas las preguntas → nota debe ser 100
2. Responder incorrectamente → nota debe ser 0 o proporcional

### 8.4 Verificar límite de intentos

1. Agotar todos los intentos (2 para el cuestionario inicial)
2. Intentar de nuevo → debe mostrar "No te quedan intentos disponibles"

### 8.5 Ver resultado previo

1. Después de realizar un intento, salir y volver a entrar al cuestionario
2. Debe mostrar el resultado previo (mejor nota)

---

## 9. Probar Archivos MinIO (Fase B)

### 9.1 Verificar que MinIO está corriendo

1. Abrir http://localhost:9091 (consola de MinIO)
2. Login con `minioadmin` / `minioadmin`
3. Debe aparecer el bucket `aula-virtual`

### 9.2 Subir un archivo vía API

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"carlos.mendoza@unitepc.edu","password":"clave-aula-2026"}' \
  | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

# Subir un archivo de prueba
echo "contenido de prueba" > /tmp/test.txt
curl -X POST http://localhost:8000/api/archivos/subir \
  -H "Authorization: Bearer $TOKEN" \
  -F "archivo=@/tmp/test.txt" \
  -F "carpeta=tareas"
```

3. Debe devolver un JSON con la ruta y URL del archivo
4. Verificar en la consola de MinIO que el archivo aparece en `tareas/`

### 9.3 Descargar un archivo vía API

```bash
# Reemplazar RUTA por la ruta devuelta en el paso anterior
curl http://localhost:8000/api/archivos/RUTA \
  -H "Authorization: Bearer $TOKEN"
```

---

## 10. Probar Entregas (API real — Fase C)

### 10.1 Entregar una tarea (como estudiante)

1. Login como `ana.vargas@estudiante.unitepc.edu`
2. Abrir el curso SIS-401 Programacion Avanzada
3. Ir a "Unidad I - Introduccion"
4. Abrir la "Tarea 1: Refactor"
5. Si ya tiene una entrega (del seeder), debe mostrar el estado (Entregado o Calificado) y la calificación si existe
6. Si no tiene entrega o quiere reenviar:
   - Clic en "Reenviar" o escribir en el campo de texto
   - Adjuntar archivos (mock — botón simular)
   - Clic en **Entregar**
7. Debe mostrar "Tarea entregada exitosamente!"
8. Verificar en la BD:
   ```bash
   docker compose exec db mysql -uaula -paula_secret aula_virtual -e "SELECT id, actividad_id, estudiante_id, estado, fecha_entrega FROM entregas ORDER BY id DESC LIMIT 5;"
   ```

### 10.2 Ver entrega existente con calificación

1. La estudiante Ana Vargas ya tiene una entrega calificada (del seeder) en "Tarea 1: Refactor" con nota 85
2. Al abrir la tarea, debe mostrar:
   - Estado: Calificado
   - Nota: 85/100
   - Retroalimentación: "Buen trabajo. El patron Factory esta bien aplicado..."

### 10.3 Reentregar una tarea

1. Estando en una tarea ya entregada, clic en "Reenviar"
2. El formulario se habilita con los datos previos precargados
3. Modificar el texto o archivos
4. Entregar de nuevo → debe actualizar la entrega en la API

### 10.4 Ver "Mis entregas" (estudiante)

```bash
ETOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"ana.vargas@estudiante.unitepc.edu","password":"clave-aula-2026"}' \
  | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

curl http://localhost:8000/api/entregas/mias -H "Authorization: Bearer $ETOKEN"
```

Debe devolver todas las entregas del estudiante con sus calificaciones anidadas.

---

## 11. Probar Calificaciones — Libro del Docente (API real — Fase C)

### 11.1 Ver libro de calificaciones

1. Login como `carlos.mendoza@unitepc.edu`
2. Ir a **Calificar** (`/docente/calificar`)
3. Seleccionar el curso SIS-401 Programacion Avanzada
4. Debe cargar la tabla de calificaciones desde la API (`GET /cursos/{id}/calificaciones`)
5. Verificar:
   - Lista de estudiantes matriculados (4 del seeder)
   - Columnas por cada actividad evaluable (Tarea 1, Cuestionario inicial, Foro, Tarea 2)
   - Notas existentes (Ana Vargas: 85 en Tarea 1, 100 en Cuestionario)
   - Promedio por estudiante
   - Pendientes de calificar (1 — entrega de Bruno Calle sin calificar)

### 11.2 Calificar una entrega

1. En el libro de calificaciones, buscar una celda con badge "Entregado" (Bruno Calle - Tarea 1)
2. Clic en **Calificar entrega**
3. Se abre el diálogo de calificación con:
   - Campo de nota (0-100)
   - Editor de rúbrica (criterios con niveles Excelente/Bueno/Regular/Insuficiente)
   - Campo de retroalimentación
4. Asignar nota (ej. 75) y escribir retroalimentación
5. Clic en **Guardar**
6. Debe mostrar "Calificacion guardada"
7. La tabla debe actualizarse mostrando la nota
8. Verificar en la BD:
   ```bash
   docker compose exec db mysql -uaula -paula_secret aula_virtual -e "SELECT id, entrega_id, nota, porcentaje, retroalimentacion, calificado_por FROM calificaciones ORDER BY id DESC LIMIT 5;"
   ```

### 11.3 Calificar sin entrega previa (calificación directa)

1. En el libro, buscar una celda vacía (sin entrega ni nota)
2. Clic en **Calificar**
3. Asignar nota
4. Guardar → el sistema crea una entrega fantasma y la califica

### 11.4 Ver promedios y KPIs

1. En el libro de calificaciones, verificar los KPIs superiores:
   - Promedio general del curso
   - Pendientes de calificar
   - Entregas realizadas
2. Verificar el gráfico de distribución (estudiantes por rango: 0-39%, 40-59%, 60-79%, 80-100%)
3. Verificar el gráfico de promedio por actividad

### 11.5 Exportar CSV

1. Clic en **Exportar CSV**
2. Debe descargar un archivo `calificaciones_SIS-401.csv` con la tabla completa

---

## 12. Probar Mis Notas (API real — Fase C)

### 12.1 Ver mis notas como estudiante

1. Login como `ana.vargas@estudiante.unitepc.edu`
2. Ir a **Mis Notas** (`/estudiante/notas`)
3. Debe cargar desde la API (`GET /mis-notas`)
4. Verificar:
   - KPIs: Cursos matriculados, Actividades calificadas, Promedio general, Materias aprobadas
   - Tabla por curso con: actividad, tipo, nota, nota máxima, porcentaje, estado (Aprobado/Reprobado)
   - Distribución por curso (aprobadas/reprobadas/total)
   - Promedio por curso

### 12.2 Verificar datos del seeder

Ana Vargas tiene:
- Tarea 1: Refactor → nota 85, porcentaje 85% (Aprobado)
- Cuestionario inicial → nota 100, porcentaje 100% (Aprobado)
- Promedio: 92.5%

### 12.3 Ver notas de otro estudiante

1. Login como `bruno.calle@estudiante.unitepc.edu`
2. Ir a Mis Notas
3. Bruno tiene una entrega en Tarea 1 pero sin calificar (pendiente)
4. Si el docente lo calificó en la sección 11.2, debe aparecer la nota aquí

---

## 13. Probar como Estudiante (vista de curso)

### 10.1 Ver cursos publicados

1. Login como `ana.vargas@estudiante.unitepc.edu`
2. Ir a **Mis Cursos** (`/estudiante/cursos`)
3. Deben aparecer los cursos publicados

### 10.2 Ver contenido de un curso

1. Clic en un curso
2. Navegar por las secciones y actividades
3. Probar abrir:
   - Una **lección** — debe mostrar el contenido HTML
   - Una **tarea** — debe mostrar instrucciones y campo de entrega
   - Un **foro** — debe mostrar hilos y permitir responder (ver sección 6)
   - Un **cuestionario** — debe permitir iniciar intento (ver sección 8)
   - Una **encuesta** — debe permitir responder (ver sección 7)

### 10.3 Centro del estudiante

1. Ir a **Pendientes** (`/estudiante/centro`)
2. Verificar que muestra actividades pendientes (datos mock para progreso/entregas — Fase C)

---

## 11. Probar Integraciones SISA (API real/stub — Fase D)

### 11.1 Ver estado de integraciones (panel admin)

1. Login como `admin@unitepc.edu`
2. Ir a **Gestión** (`/admin/gestion`)
3. Debe mostrar el panel con:
   - Estado de las 3 integraciones (SISA, Estudiantes, Notas) → todas "online"
   - Indicador de modo stub (las 3 en stub)
   - Última sincronización y mensaje
   - Política de aprobación (mínimo: 60)
4. Clic en **Forzar sync** → actualiza el estado desde la API

### 11.2 Ver asignaturas SISA disponibles

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"carlos.mendoza@unitepc.edu","password":"clave-aula-2026"}' \
  | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

curl "http://localhost:8000/api/sisa/asignaturas-disponibles" \
  -H "Authorization: Bearer $TOKEN"
```

Debe devolver 4 asignaturas del PAC (SIS-401, SIS-305, SIS-210, SIS-410).

### 11.3 Generar curso desde SISA

```bash
curl -X POST "http://localhost:8000/api/sisa/generar-curso" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"codigo":"SIS-210","gestion":"1-2026","docente_id":1}'
```

Debe crear el curso "Ingenieria de Software" con 4 secciones (PAC).
Verificar:
```bash
docker compose exec db mysql -uaula -paula_secret aula_virtual -e "SELECT id, codigo, nombre, estado FROM cursos ORDER BY id DESC LIMIT 3;"
```

### 11.4 Sincronizar estudiantes (matricular desde Sistema de Estudiantes)

```bash
curl -X POST http://localhost:8000/api/cursos/1/sincronizar-estudiantes \
  -H "Authorization: Bearer $TOKEN"
```

Debe matricular a todos los estudiantes disponibles en la BD al curso 1.
Verificar:
```bash
docker compose exec db mysql -uaula -paula_secret aula_virtual -e "SELECT COUNT(*) as total_matriculas FROM matriculas WHERE curso_id=1;"
```

### 11.5 Sincronizar notas (enviar al Sistema de Notas)

```bash
curl -X POST http://localhost:8000/api/cursos/1/sincronizar-notas \
  -H "Authorization: Bearer $TOKEN"
```

Debe marcar todas las calificaciones del curso 1 como `sincronizado_externo=true`.
Verificar:
```bash
docker compose exec db mysql -uaula -paula_secret aula_virtual -e "SELECT id, nota, sincronizado_externo, fecha_sincronizacion FROM calificaciones WHERE curso_id=1;"
```

---

## 12. Probar Notificaciones (API real — Fase D)

### 12.1 Ver notificaciones

1. Login como `ana.vargas@estudiante.unitepc.edu`
2. El badge de notificaciones (campana en el header) debe mostrar "2" (del seeder)
3. Clic en la campana → debe mostrar:
   - "Nueva calificacion disponible" (Tarea 1: 85/100)
   - "Recordatorio: fecha limite proxima" (Tarea 2 vence en 3 dias)

### 12.2 Marcar como leída

1. Clic en una notificación → debe marcarse como leída (desaparece el badge)
2. O clic en "Marcar todas como leídas"

### 12.3 Notificaciones del docente

1. Login como `carlos.mendoza@unitepc.edu`
2. Debe ver:
   - "Nueva entrega para revisar" (Bruno Calle - Tarea 1)
   - "Sincronizacion SISA completada" (ya leída)

### 12.4 Verificar vía API

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"ana.vargas@estudiante.unitepc.edu","password":"clave-aula-2026"}' \
  | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

curl http://localhost:8000/api/notificaciones -H "Authorization: Bearer $TOKEN"
```

---

## 14. Probar como Admin

1. Login como `admin@unitepc.edu`
2. Ir a **Gestión** (`/admin/gestion`)
3. Verificar:
   - KPIs de cursos (desde API real)
   - Gráfico de cursos por estado (publicados, borradores, archivados)
   - **Panel de integraciones** (API real — ver sección 11)
   - Botón "Forzar sync" que actualiza el estado de integraciones

---

## 13. Verificar la API directamente (curl)

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"carlos.mendoza@unitepc.edu","password":"clave-aula-2026"}'
```

### Listar cursos
```bash
curl http://localhost:8000/api/cursos \
  -H "Authorization: Bearer TOKEN"
```

### Ver un curso con secciones y actividades
```bash
curl http://localhost:8000/api/cursos/1 \
  -H "Authorization: Bearer TOKEN"
```

### Crear una sección
```bash
curl -X POST http://localhost:8000/api/cursos/1/secciones \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"titulo":"Unidad III - Testing","descripcion":"Pruebas unitarias"}'
```

### Crear una actividad
```bash
curl -X POST http://localhost:8000/api/secciones/1/actividades \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"tipo":"leccion","titulo":"Nueva leccion","descripcion":"Contenido"}'
```

### Ver hilos de un foro
```bash
curl http://localhost:8000/api/actividades/4/foro/hilos \
  -H "Authorization: Bearer TOKEN"
```

### Crear un hilo en un foro
```bash
curl -X POST http://localhost:8000/api/actividades/4/foro/hilos \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"titulo":"Mi duda","contenido":"No entiendo esto"}'
```

### Responder a un hilo
```bash
curl -X POST http://localhost:8000/api/foros/hilos/1/respuestas \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"contenido":"Esta es mi respuesta"}'
```

### Ver intentos de cuestionario
```bash
curl http://localhost:8000/api/actividades/3/cuestionario/intentos \
  -H "Authorization: Bearer TOKEN"
```

### Enviar intento de cuestionario
```bash
curl -X POST http://localhost:8000/api/actividades/3/cuestionario/intentar \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"respuestas":{"0":0,"1":0}}'
```

### Ver mi respuesta de encuesta
```bash
curl http://localhost:8000/api/actividades/8/encuesta/respuesta \
  -H "Authorization: Bearer TOKEN"
```

### Responder encuesta
```bash
curl -X POST http://localhost:8000/api/actividades/8/encuesta/responder \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"respuestas":{"0":2,"1":"4"}}'
```

### Subir archivo a MinIO
```bash
curl -X POST http://localhost:8000/api/archivos/subir \
  -H "Authorization: Bearer TOKEN" \
  -F "archivo=@archivo.pdf" \
  -F "carpeta=tareas"
```

---

## 14. Verificar la base de datos

```bash
# Entrar a MySQL
docker compose exec db mysql -uaula -paula_secret aula_virtual

# Ver usuarios
SELECT id, nombre, email, rol FROM usuarios;

# Ver cursos
SELECT id, codigo, nombre, estado, docente_id FROM cursos;

# Ver secciones
SELECT id, curso_id, titulo, orden FROM secciones;

# Ver actividades con su config
SELECT id, seccion_id, tipo, titulo, tiene_nota, nota_maxima FROM actividades;
SELECT id, titulo, JSON_EXTRACT(config, '$.preguntas') FROM actividades WHERE tipo = 'cuestionario';

# Ver matrículas
SELECT id, curso_id, estudiante_id, estado FROM matriculas;

# Ver hilos de foro
SELECT id, actividad_id, autor_id, titulo, created_at FROM foro_hilos;

# Ver respuestas de foro
SELECT id, hilo_id, autor_id, contenido FROM foro_respuestas;

# Ver respuestas de encuesta
SELECT id, actividad_id, estudiante_id, respuestas FROM encuesta_respuestas;

# Ver intentos de cuestionario
SELECT id, actividad_id, estudiante_id, nota, respuestas FROM cuestionario_intentos;

# Ver tokens de Sanctum (sesiones activas)
SELECT id, tokenable_id, name, last_used_at FROM personal_access_tokens;
```

---

## 15. Reiniciar el sistema (borrar datos)

Si querés empezar de cero (borra todos los datos y re-siembra):

```bash
docker compose down -v          # borra el volumen de la BD y MinIO
docker compose up -d --build    # levanta de nuevo (el entrypoint migra y siembra)
```

Para solo re-sembrar sin borrar:

```bash
docker compose exec back php artisan migrate:fresh --seed
```

---

## 16. Solución de problemas

### El front no carga (página en blanco)

```bash
docker compose logs front
```
Buscar errores de Vite o ESLint.

### La API no responde

```bash
docker compose logs back
```
Verificar que nginx + php-fpm estén corriendo (supervisor).

### Error 401 al listar cursos

El token expiró o no se está enviando. Hacer logout y login de nuevo.

### Error de CORS

El backend ya tiene CORS configurado para `localhost:9000`. Si cambiás el puerto del front, actualizar `config/cors.php` en el backend.

### Los cambios en el front no se ven

El front tiene HMR (hot reload). Si no se actualiza, forzar refresh con `Ctrl+Shift+R`. Los cambios en archivos `.env` requieren restart del contenedor:

```bash
docker compose restart front
```

### Puerto 3307 en lugar de 3306

El MySQL del contenedor se expone en el puerto **3307** del host (porque el 3306 está ocupado por un MySQL local). Conectarse con Workbench/DBeaver a `localhost:3307`.

### MinIO no responde

Verificar que el contenedor `aula_minio` esté corriendo. La consola web está en http://localhost:9091. Si el bucket no existe, el entrypoint del back lo crea automáticamente al arrancar.

### Error al subir archivos

Verificar que el archivo no exceda 64MB (límite de nginx). Verificar que el bucket `aula-virtual` exista en MinIO (consola en :9091).

---

## 17. Estado de cobertura por fase

| Feature | Estado | Origen de datos |
|---------|--------|-----------------|
| Login / Logout / Sesión | ✅ API real | Backend Laravel + Sanctum |
| Listar / ver / crear / editar cursos | ✅ API real | Backend Laravel |
| Publicar / archivar cursos | ✅ API real | Backend Laravel |
| **Secciones (CRUD + reordenar)** | ✅ **API real (Fase B)** | Backend Laravel |
| **Actividades (CRUD + reordenar)** | ✅ **API real (Fase B)** | Backend Laravel |
| **Foros (hilos + respuestas)** | ✅ API real (Fase B) | Backend Laravel |
| **Encuestas (responder + resultados)** | ✅ API real (Fase B) | Backend Laravel |
| **Cuestionarios (intentos + calificación auto)** | ✅ API real (Fase B) | Backend Laravel |
| **Archivos (upload/download MinIO)** | ✅ API real (Fase B) | MinIO + Laravel S3 |
| **Entregas de tareas** | ✅ **API real (Fase C)** | Backend Laravel |
| **Libro de calificaciones** | ✅ **API real (Fase C)** | Backend Laravel |
| **Calificación manual con rúbrica** | ✅ **API real (Fase C)** | Backend Laravel |
| **Promedios ponderados** | ✅ **API real (Fase C)** | Backend Laravel |
| **Mis notas (estudiante)** | ✅ **API real (Fase C)** | Backend Laravel |
| **Estados de entrega (pendiente→entregado→revisado→rechazado)** | ✅ **API real (Fase C)** | Backend Laravel |
| **Integraciones SISA (generar curso, asignaturas)** | ✅ **API real/stub (Fase D)** | Backend Laravel + stub |
| **Sincronizar estudiantes (matricular)** | ✅ **API real/stub (Fase D)** | Backend Laravel + stub |
| **Sincronizar notas (Sistema de Notas)** | ✅ **API real/stub (Fase D)** | Backend Laravel + stub |
| **Panel admin de integraciones** | ✅ **API real (Fase D)** | Backend Laravel |
| **Notificaciones (CRUD + marcar leída)** | ✅ **API real (Fase D)** | Backend Laravel |
| **Banco Docente (plantillas + contador de uso)** | ✅ **API real (Fase E)** | Backend Laravel (JSON casts) |
| **Reportes exportables (rendimiento/asistencia/banco)** | ✅ **API real (Fase E)** | Backend Laravel (CSV UTF-8 BOM) |
| **Gestión masiva e importación CSV de usuarios** | ✅ **API real (Fase E)** | Backend Laravel |
| Calendario académico | ⚠️ Mock | No hay endpoint (Fase E) |
| Mensajería interna | ⚠️ Mock | No hay endpoint (Fase E) |
| Herramientas docente / reglas de automatización | ⚠️ Mock | No hay endpoint (Fase E) |
| Dashboard director (KPIs, gráficos) | ⚠️ Mock | No hay endpoints agregados |
| Progreso de lecciones (marcar vista) | ⚠️ Mock local | No hay endpoint |

Cuando se implementen los componentes pendientes, los mocks se reemplazarán por servicios API reales siguiendo el mismo patrón.

