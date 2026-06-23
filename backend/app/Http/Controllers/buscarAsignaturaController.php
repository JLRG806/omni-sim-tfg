<?php

namespace App\Http\Controllers;

use App\Models\Asignatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-13 Buscar Asignatura (superset de CU-09 Listar Asignaturas)
 *
 * Sin ?q=  → devuelve todas las asignaturas (comportamiento CU-09)
 * Con ?q=  → filtra por nombre, código o nombre de profesor (CU-13)
 *
 * Actor: admin
 * Route: GET /api/v1/asignaturas[?q=término]  (auth:sanctum, role:admin)
 */
class buscarAsignaturaController extends Controller
{
    /**
     * Lista asignaturas con filtro opcional por nombre, código o profesor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->query('q');

        $asignaturas = Asignatura::with(['profesor' => fn ($query) => $query->withTrashed()->select(['id', 'name', 'deleted_at'])])
            ->when($q, function ($query, $termino) {
                $query->where(function ($q) use ($termino) {
                    $q->where('nombre', 'ilike', "%{$termino}%")
                      ->orWhere('codigo', 'ilike', "%{$termino}%")
                      ->orWhereHas('profesor', fn ($r) => $r->withTrashed()->where('name', 'ilike', "%{$termino}%"));
                });
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo', 'descripcion', 'profesor_id']);

        return response()->json([
            'data' => $asignaturas->map(fn ($a) => [
                'id'          => $a->id,
                'nombre'      => $a->nombre,
                'codigo'      => $a->codigo,
                'descripcion' => $a->descripcion,
                'profesor'    => $a->profesor_id
                    ? ['id' => $a->profesor->id, 'name' => $a->profesor->name, 'eliminado' => $a->profesor->deleted_at !== null]
                    : null,
            ]),
        ]);
    }
}
