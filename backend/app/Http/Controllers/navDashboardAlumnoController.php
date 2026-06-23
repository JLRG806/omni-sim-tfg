<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-25 Nav Dashboard Alumno
 *
 * Actor: alumno
 * Route: GET /api/v1/alumno/dashboard  (auth:sanctum, role:alumno)
 *
 * Devuelve las asignaturas en las que el alumno está matriculado,
 * con los escenarios publicados disponibles para simulación.
 */
class navDashboardAlumnoController extends Controller
{
    /**
     * Devuelve asignaturas del alumno con sus escenarios publicados.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $alumno = $request->user();

        $matriculas = Matricula::with([
            'asignatura.profesor:id,name',
            'asignatura.escenarios' => fn ($q) => $q->where('estado', 'publicado')
                ->orderBy('titulo')
                ->select(['id', 'asignatura_id', 'titulo', 'area_conocimiento', 'descripcion_situacion']),
        ])
            ->where('alumno_id', $alumno->id)
            ->get();

        return response()->json([
            'alumno'      => ['id' => $alumno->id, 'name' => $alumno->name],
            'asignaturas' => $matriculas->map(fn ($m) => [
                'id'              => $m->asignatura->id,
                'codigo'          => $m->asignatura->codigo,
                'nombre'          => $m->asignatura->nombre,
                'profesor'        => ['id' => $m->asignatura->profesor->id, 'name' => $m->asignatura->profesor->name],
                'fecha_matricula' => $m->fecha_matricula,
                'escenarios'      => $m->asignatura->escenarios->map(fn ($e) => [
                    'id'                    => $e->id,
                    'titulo'                => $e->titulo,
                    'area_conocimiento'     => $e->area_conocimiento,
                    'descripcion_situacion' => $e->descripcion_situacion,
                ])->values(),
            ]),
        ]);
    }
}
