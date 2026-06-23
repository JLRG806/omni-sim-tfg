<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Transversal — sin autenticación ──────────────────────────────────────
    // CU-01 Iniciar Sesión
    Route::post('/auth/login', \App\Http\Controllers\loginController::class);

    // CU-03 Recuperar Cuenta
    Route::post('/auth/forgot-password', [\App\Http\Controllers\recuperarCuentaController::class, 'forgot']);
    Route::post('/auth/reset-password',  [\App\Http\Controllers\recuperarCuentaController::class, 'reset']);

    // ── Transversal — requiere autenticación ─────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Restauración de sesión: devuelve el usuario autenticado por su token.
        Route::get('/auth/me', function (Request $request) {
            $user = $request->user();
            return response()->json([
                'user' => [
                    'id'   => $user->id,
                    'name' => $user->name,
                    'rol'  => $user->rol,
                ],
            ]);
        });

        // CU-02 Cerrar Sesión
        Route::post('/auth/logout', \App\Http\Controllers\logoutController::class);
    });

    // ── Admin ─────────────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

        // CU-04 Listar Usuarios + CU-08 Buscar Usuario
        Route::get('/usuarios', \App\Http\Controllers\buscarUsuarioController::class);

        // CU-05 Crear Usuario
        Route::post('/usuarios', \App\Http\Controllers\crearUsuarioController::class);

        // CU-06 Modificar Usuario
        Route::get('/usuarios/{id}', \App\Http\Controllers\obtenerUsuarioController::class);
        Route::put('/usuarios/{id}', \App\Http\Controllers\modificarUsuarioController::class);

        // CU-07 Eliminar Usuario
        Route::delete('/usuarios/{id}', \App\Http\Controllers\eliminarUsuarioController::class);

        // ── Asignaturas ───────────────────────────────────────────────────────
        // CU-09 Listar Asignaturas + CU-13 Buscar Asignatura
        Route::get('/asignaturas', \App\Http\Controllers\buscarAsignaturaController::class);

        // CU-10 Crear Asignatura
        Route::post('/asignaturas', \App\Http\Controllers\crearAsignaturaController::class);

        // CU-11 Modificar Asignatura
        Route::get('/asignaturas/{id}', \App\Http\Controllers\obtenerAsignaturaController::class);
        Route::put('/asignaturas/{id}', \App\Http\Controllers\modificarAsignaturaController::class);

        // CU-12 Eliminar Asignatura
        Route::delete('/asignaturas/{id}', \App\Http\Controllers\eliminarAsignaturaController::class);
    });

    // ── Profesor ──────────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'role:profesor'])->group(function () {

        // CU-14 Nav Dashboard Profesor
        Route::get('/profesor/dashboard', \App\Http\Controllers\navDashboardProfesorController::class);

        // CU-15 Matricular Alumno + CU-17 Buscar Alumno (incluido)
        Route::post('/asignaturas/{id}/matriculas', \App\Http\Controllers\matricularAlumnoController::class);
        Route::get('/asignaturas/{id}/alumnos',     \App\Http\Controllers\buscarAlumnoController::class);

        // CU-16 Desmatricular Alumno
        Route::delete('/matriculas/{id}', \App\Http\Controllers\desmatricularAlumnoController::class);

        // Competencias disponibles para criterios de evaluación
        Route::get('/competencias', function () {
            return response()->json([
                'data' => \App\Models\Competencia::orderBy('nombre')->get(['id', 'nombre', 'descripcion', 'tipo']),
            ]);
        });

        // CU-18 Crear Escenario (dos fases)
        Route::post('/escenarios',              [\App\Http\Controllers\crearEscenarioController::class, 'fase1']);
        Route::post('/escenarios/{id}/perfil',  [\App\Http\Controllers\crearEscenarioController::class, 'fase2']);

        // CU-19 Editar Escenario (dos fases, solo borrador)
        Route::get('/escenarios/{id}',          \App\Http\Controllers\obtenerEscenarioController::class);
        Route::put('/escenarios/{id}',          [\App\Http\Controllers\editarEscenarioController::class, 'fase1']);
        Route::put('/escenarios/{id}/perfil',   [\App\Http\Controllers\editarEscenarioController::class, 'fase2']);

        // CU-20 Publicar + CU-21 Despublicar
        Route::patch('/escenarios/{id}/publicar',     \App\Http\Controllers\publicarEscenarioController::class);
        Route::patch('/escenarios/{id}/despublicar',  \App\Http\Controllers\despublicarEscenarioController::class);

        // CU-22 Buscar Escenario (por asignatura)
        Route::get('/asignaturas/{id}/escenarios',    \App\Http\Controllers\buscarEscenarioController::class);
    });
});
