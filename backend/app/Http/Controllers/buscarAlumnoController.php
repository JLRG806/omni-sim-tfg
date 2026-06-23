<?php

namespace App\Http\Controllers;

use App\Models\Asignatura;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-17 Buscar Alumno (incluido por CU-15)
 *
 * Actor: profesor
 * Route: GET /api/v1/asignaturas/{id}/alumnos?q=  (auth:sanctum, role:profesor)
 *
 * Devuelve alumnos del sistema filtrados por nombre/email,
 * indicando si ya están matriculados en la asignatura.
 */
class buscarAlumnoController extends Controller
{
    /**
     * Busca alumnos por nombre o email e indica si ya están matriculados
     * en la asignatura indicada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  ID de la asignatura
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $asignatura = Asignatura::findOrFail($id);
        $q          = $request->query('q', '');

        $alumnosMatriculadosIds = $asignatura->matriculas()->pluck('alumno_id');

        $alumnos = User::where('rol', 'alumno')
            ->whereNull('deleted_at')
            ->when($q, fn ($query) => $query->where(function ($q2) use ($q) {
                $q2->where('name',  'ilike', "%{$q}%")
                   ->orWhere('email', 'ilike', "%{$q}%");
            }))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'estado'])
            ->map(fn ($a) => [
                'id'          => $a->id,
                'name'        => $a->name,
                'email'       => $a->email,
                'estado'      => $a->estado,
                'matriculado' => $alumnosMatriculadosIds->contains($a->id),
            ]);

        return response()->json(['data' => $alumnos]);
    }
}
