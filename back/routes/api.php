<?php

use App\Http\Controllers\Api\ActividadController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalificacionController;
use App\Http\Controllers\Api\CursoController;
use App\Http\Controllers\Api\CuestionarioController;
use App\Http\Controllers\Api\EncuestaController;
use App\Http\Controllers\Api\EntregaController;
use App\Http\Controllers\Api\ForoController;
use App\Http\Controllers\Api\IntegracionController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\SeccionController;
use App\Http\Controllers\Api\ArchivoController;
use App\Http\Controllers\Api\CalendarioController;
use App\Http\Controllers\Api\MensajeController;
use App\Http\Controllers\Api\AdminUsuarioController;
use App\Http\Controllers\Api\PlantillaController;
use App\Http\Controllers\Api\ReporteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticacion (publicas salvo /auth/me y /auth/logout)
|--------------------------------------------------------------------------
*/
Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
});

/*
|--------------------------------------------------------------------------
| Cursos (Fase A) + Secciones + Actividades (Fase B)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cursos', [CursoController::class, 'index'])->name('cursos.index');
    Route::get('/cursos/{curso}', [CursoController::class, 'show'])->name('cursos.show');

    Route::middleware('role:docente,director,admin')->group(function () {
        Route::post('/cursos', [CursoController::class, 'store'])->name('cursos.store');
        Route::put('/cursos/{curso}', [CursoController::class, 'update'])->name('cursos.update');
        Route::put('/cursos/{curso}/publicar', [CursoController::class, 'publicar'])->name('cursos.publicar');
        Route::put('/cursos/{curso}/archivar', [CursoController::class, 'archivar'])->name('cursos.archivar');
        Route::delete('/cursos/{curso}', [CursoController::class, 'destroy'])->name('cursos.destroy');

        // Secciones (anidadas bajo curso)
        Route::get('/cursos/{curso}/secciones', [SeccionController::class, 'index'])->name('secciones.index');
        Route::post('/cursos/{curso}/secciones', [SeccionController::class, 'store'])->name('secciones.store');
        Route::put('/cursos/{curso}/secciones/{seccion}', [SeccionController::class, 'update'])->name('secciones.update');
        Route::delete('/cursos/{curso}/secciones/{seccion}', [SeccionController::class, 'destroy'])->name('secciones.destroy');
        Route::put('/cursos/{curso}/secciones/reordenar', [SeccionController::class, 'reordenar'])->name('secciones.reordenar');

        // Actividades (anidadas bajo seccion)
        Route::get('/secciones/{seccion}/actividades', [ActividadController::class, 'index'])->name('actividades.index');
        Route::post('/secciones/{seccion}/actividades', [ActividadController::class, 'store'])->name('actividades.store');
        Route::put('/secciones/{seccion}/actividades/reordenar', [ActividadController::class, 'reordenar'])->name('actividades.reordenar');
        Route::get('/actividades/{actividad}', [ActividadController::class, 'show'])->name('actividades.show');
        Route::put('/actividades/{actividad}', [ActividadController::class, 'update'])->name('actividades.update');
        Route::delete('/actividades/{actividad}', [ActividadController::class, 'destroy'])->name('actividades.destroy');

        // Foros (Fase B)
        Route::get('/actividades/{actividad}/foro/hilos', [ForoController::class, 'hilos'])->name('foro.hilos');
        Route::post('/actividades/{actividad}/foro/hilos', [ForoController::class, 'crearHilo'])->name('foro.crearHilo');
        Route::post('/foros/hilos/{hilo}/respuestas', [ForoController::class, 'crearRespuesta'])->name('foro.crearRespuesta');
        Route::delete('/foros/hilos/{hilo}', [ForoController::class, 'eliminarHilo'])->name('foro.eliminarHilo');

        // Encuestas (Fase B)
        Route::get('/actividades/{actividad}/encuesta/respuesta', [EncuestaController::class, 'miRespuesta'])->name('encuesta.miRespuesta');
        Route::post('/actividades/{actividad}/encuesta/responder', [EncuestaController::class, 'responder'])->name('encuesta.responder');
        Route::get('/actividades/{actividad}/encuesta/resultados', [EncuestaController::class, 'resultados'])->name('encuesta.resultados');

        // Cuestionarios (Fase B)
        Route::get('/actividades/{actividad}/cuestionario/intentos', [CuestionarioController::class, 'intentos'])->name('cuestionario.intentos');
        Route::post('/actividades/{actividad}/cuestionario/intentar', [CuestionarioController::class, 'intentar'])->name('cuestionario.intentar');

        // Archivos (MinIO/S3)
        Route::post('/archivos/subir', [ArchivoController::class, 'subir'])->name('archivos.subir');
        Route::get('/archivos/{path?}', [ArchivoController::class, 'descargar'])->name('archivos.descargar')->where('path', '.+');

        // Calificaciones — libro de curso (docente/director/admin)
        Route::get('/cursos/{curso}/calificaciones', [CalificacionController::class, 'libroCurso'])->name('calificaciones.libro');
        Route::post('/entregas/{entrega}/calificar', [CalificacionController::class, 'calificar'])->name('calificaciones.calificar');
        Route::put('/calificaciones/{calificacion}', [CalificacionController::class, 'actualizar'])->name('calificaciones.actualizar');
    });

    // Entregas — estudiante crea, docente ve todas
    Route::get('/entregas', [EntregaController::class, 'index'])->name('entregas.index');
    Route::get('/entregas/mias', [EntregaController::class, 'misEntregas'])->name('entregas.mias');
    Route::get('/entregas/{entrega}', [EntregaController::class, 'show'])->name('entregas.show');
    Route::post('/entregas', [EntregaController::class, 'store'])->name('entregas.store');
    Route::put('/entregas/{entrega}', [EntregaController::class, 'update'])->name('entregas.update');

    // Mis notas (estudiante)
    Route::get('/mis-notas', [CalificacionController::class, 'misNotas'])->name('calificaciones.misNotas');

    // Notificaciones (Fase D)
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('/notificaciones', [NotificacionController::class, 'store'])->name('notificaciones.store');
    Route::put('/notificaciones/{notificacion}/leer', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.marcarLeida');
    Route::put('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas'])->name('notificaciones.marcarTodasLeidas');
    Route::delete('/notificaciones/{notificacion}', [NotificacionController::class, 'destroy'])->name('notificaciones.destroy');

    // Integraciones (Fase D) — admin/director
    Route::middleware('role:admin,director')->group(function () {
        Route::get('/integraciones/estado', [IntegracionController::class, 'estado'])->name('integraciones.estado');
    });

    Route::middleware('role:docente,director,admin')->group(function () {
        Route::get('/sisa/asignaturas-disponibles', [IntegracionController::class, 'asignaturasSisa'])->name('sisa.asignaturas');
        Route::post('/sisa/generar-curso', [IntegracionController::class, 'generarCursoSisa'])->name('sisa.generarCurso');
        Route::get('/sisa/banco-preguntas', [IntegracionController::class, 'bancoPreguntas'])->name('sisa.bancoPreguntas');
        Route::post('/cursos/{curso}/sincronizar-estudiantes', [IntegracionController::class, 'sincronizarEstudiantes'])->name('sisa.syncEstudiantes');
        Route::post('/cursos/{curso}/sincronizar-notas', [IntegracionController::class, 'sincronizarNotas'])->name('sisa.syncNotas');
    });

    // Calendario Academico (Fase E)
    Route::get('/calendario', [CalendarioController::class, 'index'])->name('calendario.index');
    Route::post('/calendario/eventos', [CalendarioController::class, 'store'])->name('calendario.store');
    Route::put('/calendario/eventos/{evento}', [CalendarioController::class, 'update'])->name('calendario.update');
    Route::delete('/calendario/eventos/{evento}', [CalendarioController::class, 'destroy'])->name('calendario.destroy');

    // Mensajeria Interna (Fase E)
    Route::get('/mensajes/contactos', [MensajeController::class, 'contactos'])->name('mensajes.contactos');
    Route::get('/mensajes/conversaciones', [MensajeController::class, 'conversaciones'])->name('mensajes.conversaciones');
    Route::post('/mensajes/conversaciones', [MensajeController::class, 'iniciarConversacion'])->name('mensajes.iniciar');
    Route::get('/mensajes/conversaciones/{conversacion}', [MensajeController::class, 'mensajes'])->name('mensajes.conversacion');
    Route::post('/mensajes/conversaciones/{conversacion}/mensajes', [MensajeController::class, 'enviarMensaje'])->name('mensajes.enviar');
    Route::put('/mensajes/conversaciones/{conversacion}/leer', [MensajeController::class, 'marcarLeida'])->name('mensajes.marcarLeida');

    // Gestión de Usuarios (Admin)
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/usuarios', [AdminUsuarioController::class, 'index'])->name('admin.usuarios.index');
        Route::post('/admin/usuarios', [AdminUsuarioController::class, 'store'])->name('admin.usuarios.store');
        Route::put('/admin/usuarios/{usuario}', [AdminUsuarioController::class, 'update'])->name('admin.usuarios.update');
        Route::delete('/admin/usuarios/{usuario}', [AdminUsuarioController::class, 'destroy'])->name('admin.usuarios.destroy');
        Route::post('/admin/usuarios/importar-csv', [AdminUsuarioController::class, 'importarCsv'])->name('admin.usuarios.importarCsv');
        Route::post('/cursos/{curso}/matricular-masivo', [AdminUsuarioController::class, 'matricularMasivo'])->name('cursos.matricularMasivo');
    });

    // Banco Docente
    Route::middleware('role:docente,director,admin')->group(function () {
        Route::get('/banco-docente/plantillas', [PlantillaController::class, 'index'])->name('plantillas.index');
        Route::post('/banco-docente/plantillas', [PlantillaController::class, 'store'])->name('plantillas.store');
        Route::put('/banco-docente/plantillas/{plantilla}', [PlantillaController::class, 'update'])->name('plantillas.update');
        Route::delete('/banco-docente/plantillas/{plantilla}', [PlantillaController::class, 'destroy'])->name('plantillas.destroy');
        Route::post('/banco-docente/plantillas/{plantilla}/usar', [PlantillaController::class, 'usar'])->name('plantillas.usar');

        // Reporte de calificaciones de curso
        Route::get('/reportes/curso/{curso}/calificaciones', [ReporteController::class, 'exportarCursoCalificaciones'])->name('reportes.curso.calificaciones');
    });

    // Reportes Dirección / Admin
    Route::middleware('role:director,admin')->group(function () {
        Route::get('/reportes/director/cumplimiento', [ReporteController::class, 'exportarCumplimientoDocente'])->name('reportes.director.cumplimiento');
        Route::get('/reportes/director/rendimiento', [ReporteController::class, 'exportarRendimientoEstudiantes'])->name('reportes.director.rendimiento');
    });
});