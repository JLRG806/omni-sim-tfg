<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\Resultado;
use App\Models\SesionSimulacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CU-25 Nav Dashboard Alumno
 *
 * Actor: alumno
 * Route: GET /api/v1/alumno/dashboard  (auth:sanctum, role:alumno)
 *
 * Devuelve estadísticas globales del alumno y sus asignaturas matriculadas
 * con progreso por asignatura (escenarios completados, nota media, badge).
 */
class navDashboardAlumnoController extends Controller
{
    /**
     * Devuelve stats globales + asignaturas con progreso para el dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $alumno = $request->user();

        // ── Stats globales ────────────────────────────────────────────────────

        $sesionesRealizadas = SesionSimulacion::where('alumno_id', $alumno->id)
            ->where('tipo', 'real')
            ->whereIn('estado', ['finalizada', 'evaluada'])
            ->count();

        $enCurso = SesionSimulacion::where('alumno_id', $alumno->id)
            ->where('tipo', 'real')
            ->whereIn('estado', ['en_curso', 'pausada'])
            ->count();

        $notaMedia = Resultado::whereHas('sesion', fn ($q) => $q->where('alumno_id', $alumno->id)->where('tipo', 'real'))
            ->where('estado', 'evaluado')
            ->avg('final_calificacion');

        // ── Asignaturas con progreso ──────────────────────────────────────────

        $matriculas = Matricula::with([
            'asignatura.profesor:id,name',
            'asignatura.escenarios' => fn ($q) => $q->where('estado', 'publicado')
                ->orderBy('titulo')
                ->select(['id', 'asignatura_id', 'titulo', 'area_conocimiento', 'descripcion_situacion']),
        ])
            ->where('alumno_id', $alumno->id)
            ->get();

        // IDs de escenarios donde el alumno tiene sesión finalizada/evaluada
        $escenariosCompletadosIds = SesionSimulacion::where('alumno_id', $alumno->id)
            ->where('tipo', 'real')
            ->whereIn('estado', ['finalizada', 'evaluada'])
            ->pluck('escenario_id')
            ->unique();

        // IDs de escenarios donde el alumno tiene sesión activa
        $escenariosEnCursoIds = SesionSimulacion::where('alumno_id', $alumno->id)
            ->where('tipo', 'real')
            ->whereIn('estado', ['en_curso', 'pausada'])
            ->pluck('escenario_id')
            ->unique();

        $asignaturas = $matriculas->filter(fn ($m) => $m->asignatura !== null)->map(function ($m) use (
            $alumno, $escenariosCompletadosIds, $escenariosEnCursoIds
        ) {
            $escenarios      = $m->asignatura->escenarios;
            $totalEscenarios = $escenarios->count();
            $completados     = $escenarios->whereIn('id', $escenariosCompletadosIds)->count();
            $activosAsig     = $escenarios->whereIn('id', $escenariosEnCursoIds)->count();
            $nuevos          = $escenarios->whereNotIn('id', $escenariosCompletadosIds)
                                          ->whereNotIn('id', $escenariosEnCursoIds)->count();

            // Nota media de esta asignatura — N+1 conocido: 1 query por asignatura.
            // Para el TFG (≤50 usuarios, ≤10 asignaturas) es aceptable.
            // Trabajo futuro: precargar todas las notas en una sola query antes del map().
            $notaAsig = Resultado::whereHas('sesion', fn ($q) => $q
                ->where('alumno_id', $alumno->id)
                ->where('tipo', 'real')
                ->whereIn('escenario_id', $escenarios->pluck('id'))
            )->where('estado', 'evaluado')->avg('final_calificacion');

            // Badge: prioridad "en curso" > "nuevos"
            $badge = null;
            if ($activosAsig > 0) {
                $badge = ['tipo' => 'en_curso', 'texto' => "{$activosAsig} en curso"];
            } elseif ($nuevos > 0) {
                $badge = ['tipo' => 'nuevo', 'texto' => "{$nuevos} " . ($nuevos === 1 ? 'escenario nuevo' : 'escenarios nuevos')];
            }

            return [
                'id'              => $m->asignatura->id,
                'codigo'          => $m->asignatura->codigo,
                'nombre'          => $m->asignatura->nombre,
                'profesor'        => ['name' => $m->asignatura->profesor?->name ?? 'Profesor eliminado'],
                'total_escenarios' => $totalEscenarios,
                'completados'     => $completados,
                'nota_media'      => $notaAsig ? round($notaAsig, 1) : null,
                'badge'           => $badge,
                'escenarios'      => $escenarios->map(fn ($e) => [
                    'id'    => $e->id,
                    'titulo' => $e->titulo,
                ])->values(),
            ];
        })->values();

        return response()->json([
            'alumno' => ['id' => $alumno->id, 'name' => $alumno->name],
            'stats'  => [
                'sesiones_realizadas' => $sesionesRealizadas,
                'en_curso'            => $enCurso,
                'nota_media'          => $notaMedia ? round($notaMedia, 1) : null,
            ],
            'asignaturas' => $asignaturas,
        ]);
    }
}
