<?php

namespace App\Http\Controllers;

use App\Http\Requests\MatricularAlumnoRequest;
use App\Models\Asignatura;
use App\Models\Matricula;
use Illuminate\Http\JsonResponse;

/**
 * CU-15 Matricular Alumno  (incluye CU-17 buscar alumno)
 *
 * Actor: profesor
 * Route: POST /api/v1/asignaturas/{id}/matriculas  (auth:sanctum, role:profesor)
 *
 * El frontend busca el alumno con CU-17 (GET /alumnos?q=) antes de llamar a este endpoint.
 * La validación de "ya matriculado" se hace en MatricularAlumnoRequest.
 */
class matricularAlumnoController extends Controller
{
    /**
     * Matricula un alumno en la asignatura indicada.
     * Establece la fecha de matrícula a la fecha actual.
     *
     * @param  \App\Http\Requests\MatricularAlumnoRequest  $request
     * @param  int  $id  ID de la asignatura
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(MatricularAlumnoRequest $request, int $id): JsonResponse
    {
        $asignatura = Asignatura::findOrFail($id);

        $matricula = Matricula::create([
            'alumno_id'       => $request->alumno_id,
            'asignatura_id'   => $asignatura->id,
            'fecha_matricula' => now()->toDateString(),
        ]);

        $matricula->load('alumno:id,name,email');

        return response()->json([
            'message'    => 'Alumno matriculado correctamente',
            'matricula'  => [
                'id'              => $matricula->id,
                'alumno_id'       => $matricula->alumno_id,
                'alumno'          => ['id' => $matricula->alumno->id, 'name' => $matricula->alumno->name, 'email' => $matricula->alumno->email],
                'fecha_matricula' => $matricula->fecha_matricula,
            ],
        ], 201);
    }
}
